<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RingCentral\Psr7\Request;
use WyriHaximus\Hydrator\Hydrator;

final class Operation
{
    /**
     * @param string $path
     * @param string $method
     * @param string $namespace
     * @param string $className
     * @param OpenAPiOperation $operation
     * @return iterable<Node>
     */
    public static function generate(string $path, string $method, string $namespace, string $rootNamespace, string $className, OpenAPiOperation $operation, array $schemaClassNameMap): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $class = $factory->class($className)->makeFinal()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId
                        )
                    ),
                ],
                Class_::MODIFIER_PRIVATE
            )
        )->addStmt(
            $factory->method('operationId')->makePublic()->setReturnType('string')->addStmt(
                new Node\Stmt\Return_(
                    new Node\Expr\ClassConstFetch(
                        new Node\Name('self'),
                        'OPERATION_ID'
                    )
                )
            )
        )->addStmt(
          $factory->property('requestSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()  
        )->addStmt(
          $factory->property('responseSchemaValidator')->setType('\League\OpenAPIValidation\Schema\SchemaValidator')->makeReadonly()->makePrivate()  
        );

        $constructor = $factory->method('__construct')->makePublic()->addParam(
            (new Param('requestSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'requestSchemaValidator'
                ),
                new Node\Expr\Variable('requestSchemaValidator'),
            )
        )->addParam(
            (new Param('responseSchemaValidator'))->setType('\League\OpenAPIValidation\Schema\SchemaValidator')
        )->addStmt(
            new Node\Expr\Assign(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'responseSchemaValidator'
                ),
                new Node\Expr\Variable('responseSchemaValidator'),
            )
        );
        $requestReplaces = [];
        $query = [];
        foreach ($operation->parameters as $parameter) {
            $paramterStmt = $factory->property($parameter->name);
            if (strlen((string)$parameter->description) > 0) {
                $paramterStmt->setDocComment('/**' . (string)$parameter->description . '**/');
            }
            if ($parameter->schema->type !== null) {
                $paramterStmt->setType(str_replace([
                    'integer',
                    'any',
                    'boolean',
                ], [
                    'int',
                    '',
                    'bool',
                ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type])));
            }
            $class->addStmt($paramterStmt->makeReadonly()->makePrivate());

            $param = new Param($parameter->name);
            if ($parameter->schema->type !== null) {
                $param->setType(
                    str_replace([
                        'integer',
                        'any',
                        'boolean',
                    ], [
                        'int',
                        '',
                        'bool',
                    ], implode('|', is_array($parameter->schema->type) ? $parameter->schema->type : [$parameter->schema->type]))
                );
            }
            if ($parameter->schema->default !== null) {
                $param->setDefault($parameter->schema->default);
            }
            $constructor->addParam(
                $param
            )->addStmt(
                new Node\Expr\Assign(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        $parameter->name
                    ),
                    new Node\Expr\Variable($parameter->name),
                )
            );
            if ($parameter->in === 'path' || $parameter->in === 'query') {
                $requestReplaces['{' . $parameter->name . '}'] = new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    $parameter->name
                );
            }
            if ($parameter->in === 'query') {
                $query[] = $parameter->name . '={' . $parameter->name . '}';
            }
        }
        $class->addStmt($constructor);
        $requestParameters = [
            new Node\Arg(new Node\Scalar\String_($method)),
            new Node\Arg(new Node\Expr\FuncCall(
                new Node\Name('\str_replace'),
                [
                    new Node\Expr\Array_(array_map(static fn (string $key): Node\Expr\ArrayItem => new Node\Expr\ArrayItem(new Node\Scalar\String_($key)), array_keys($requestReplaces))),
                    new Node\Expr\Array_(array_values($requestReplaces)),
                    new Node\Scalar\String_(rtrim($path . '?' . implode('&', $query), '?')),
                ]
            )),
        ];

        $createRequestMethod = $factory->method('createRequest')->setReturnType('\\' . RequestInterface::class)->addParam(
            $factory->param('data')->setType('array')->setDefault([])
        );

        if ($operation->requestBody !== null) {
            foreach ($operation->requestBody->content as $requestBodyContentType => $requestBodyContent) {
                $requestParameters[] = new Node\Expr\Array_([
                    new Node\Expr\ArrayItem(new Node\Scalar\String_($requestBodyContentType), new Node\Scalar\String_('Content-Type'))
                ]);
                $requestParameters[] = new Node\Expr\FuncCall(new Node\Name('json_encode'), [new Arg(new Node\Expr\Variable('data'))]);
                $createRequestMethod->addStmt(
                    new Node\Stmt\Expression(new Node\Expr\MethodCall(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            'requestSchemaValidator'
                        ),
                        new Node\Name('validate'),
                        [
                            new Node\Arg(new Node\Expr\Variable('data')),
                            new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [new Node\Scalar\String_(json_encode($requestBodyContent->schema->getSerializableData())), new Node\Scalar\String_('\cebe\openapi\spec\Schema')])),
                        ]
                    ))
                );
                break;
            }
        }

        $createRequestMethod->addStmt(
            new Node\Stmt\Return_(
                new Node\Expr\New_(
                    new Node\Name(
                        '\\' . Request::class
                    ),
                    $requestParameters
                )
            )
        );

        $class->addStmt(
            $createRequestMethod
        );
        $cases = [];
        $returnType = [];
        foreach ($operation->responses as $code => $spec) {
            $contentTypeCases = [];
            foreach ($spec->content as $contentType => $contentTypeSchema) {
                $returnType[] = $object = '\\' . $rootNamespace . 'Schema\\' . $schemaClassNameMap[spl_object_hash($contentTypeSchema->schema)];
                $ctc = new Node\Stmt\Case_(
                    new Node\Scalar\String_($contentType),
                    [
                        new Node\Stmt\Expression(new Node\Expr\MethodCall(
                            new Node\Expr\PropertyFetch(
                                new Node\Expr\Variable('this'),
                                'responseSchemaValidator'
                            ),
                            new Node\Name('validate'),
                            [
                                new Node\Arg(new Node\Expr\Variable('body')),
                                new Node\Arg(new Node\Expr\StaticCall(new Node\Name('\cebe\openapi\Reader'), new Node\Name('readFromJson'), [new Node\Scalar\String_(json_encode($contentTypeSchema->schema->getSerializableData())), new Node\Scalar\String_('\cebe\openapi\spec\Schema')])),
                            ]
                        )),
                        new Node\Stmt\Return_(new Node\Expr\MethodCall(
                            new Node\Expr\Variable('hydrator'),
                            new Node\Name('hydrate'),
                            [
                                new Node\Arg(new Node\Scalar\String_($object)),
                                new Node\Arg(new Node\Expr\Variable('body')),
                            ]
                        )),
//                        new Node\Stmt\Break_()
                    ]
                );
                $contentTypeCases[] = $ctc;
//                $ctc->setDocComment(new Doc('/**' . @var_export($contentTypeSchema->getSerializableData(), true) . '**/'));
            }
            $case = new Node\Stmt\Case_(
                new Node\Scalar\LNumber($code),
                [
                    new Node\Stmt\Switch_(
                        new Node\Expr\Variable('contentType'),
                        $contentTypeCases
                    ),
                    new Node\Stmt\Break_()
                ]
            );
            $cases[] = $case;
            $case->setDocComment(new Doc('/**' . $spec->description . '**/'));
//            $case->setDocComment(new Doc('/**' . @var_export($spec->getSerializableData(), true) . '**/'));
        }
        $class->addStmt(
            $factory->method('createResponse')->addParam(
                $factory->param('response')->setType('\\' . ResponseInterface::class)
            )->setReturnType(
                new Node\UnionType(array_map(static fn (string $object): Node\Name => new Node\Name($object), array_unique($returnType)))
            )->addStmt(
                new Node\Expr\Assign(new Node\Expr\Variable('contentType'), new Node\Expr\MethodCall(new Node\Expr\Variable('response'), 'getHeaderLine', [new Arg(new Node\Scalar\String_('Content-Type'))]))
            )->addStmt(
                new Node\Expr\Assign(new Node\Expr\Variable('body'), new Node\Expr\FuncCall(new Node\Name('json_decode'), [new Node\Expr\MethodCall(new Node\Expr\MethodCall(new Node\Expr\Variable('response'), 'getBody'), 'getContents'), new Node\Expr\ConstFetch(new Node\Name('true'))]))
            )->addStmt(
                new Node\Expr\Assign(new Node\Expr\Variable('hydrator'), new Node\Expr\New_(new Node\Name('\\' . Hydrator::class)))
            )->addStmt(
                new Node\Stmt\Switch_(
                    new Node\Expr\MethodCall(new Node\Expr\Variable('response'), 'getStatusCode'),
                    $cases
                )
            )->addStmt(
                new Node\Stmt\Throw_(
                    new Node\Expr\New_(
                        new Node\Name('\\' . \RuntimeException::class),
                        [
                            new Arg(new Node\Scalar\String_('Unable to find matching reponse code and content type'))
                        ]
                    )
                )
            )
        );

        yield new File($namespace . '\\' . $className, $stmt->addStmt($class)->getNode());
    }
}
