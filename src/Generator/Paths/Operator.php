<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;

use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ConfigurationPackage;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Operation as OperationHelper;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ReflectionTypes;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\ResultConverter;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use League\OpenAPIValidation\Schema\SchemaValidator;
use OpenAPITools\Contract\Package;
use OpenAPITools\Representation\Namespaced;
use OpenAPITools\Utils\File;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function array_filter;
use function array_map;
use function count;
use function explode;
use function str_contains;
use function str_starts_with;

final readonly class Operator
{
    public function __construct(
        private BuilderFactory $builderFactory,
    ) {
    }

    /** @return iterable<File> */
    public function generate(Package $package, Namespaced\Operation $operation, Namespaced\Hydrator $hydrator): iterable
    {
        $package = ConfigurationPackage::unwrap($package);

        /** @var class-string $className */
        $className                         = $operation->className->fullyQualified->source;
        $operationConstructor              = new ReflectionClass($className)->getConstructor();
        $bringHydratorAndResponseValidator = $operationConstructor !== null && count(
            array_filter(
                $operationConstructor->getParameters(),
                static fn (ReflectionParameter $parameter): bool => $parameter->name === 'responseSchemaValidator' || $parameter->name === 'hydrator',
            ),
        ) > 0;
        $factory                           = new BuilderFactory();
        $stmt                              = $factory->namespace($operation->operatorClassName->namespace->source);

        $class = $factory->class($operation->operatorClassName->className)->makeFinal()->makeReadonly()->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_ID',
                        new Node\Scalar\String_(
                            $operation->operationId,
                        ),
                    ),
                ],
                1,
            ),
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'OPERATION_MATCH',
                        new Node\Scalar\String_(
                            $operation->matchMethod . ' ' . $operation->path, // Deal with the query
                        ),
                    ),
                ],
                1,
            ),
        );

        $constructor = $factory->method('__construct')->makePublic();
        $constructor->addParam(
            $this->builderFactory->param('browser')->makePrivate()->setType('\\' . Browser::class),
        );
        $constructor->addParam(
            $this->builderFactory->param('authentication')->makePrivate()->setType('\\' . AuthenticationInterface::class),
        );

        if (count($operation->requestBody) > 0) {
            $constructor->addParam(
                $this->builderFactory->param('requestSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
            );
        }

        if ($bringHydratorAndResponseValidator) {
            $constructor->addParam(
                $this->builderFactory->param('responseSchemaValidator')->makePrivate()->setType('\\' . SchemaValidator::class),
            )->addParam(
                $this->builderFactory->param('hydrator')->makePrivate()->setType($hydrator->className->fullyQualified->source),
            );
        }

        $class->addStmt($constructor);

        $callParams = [];
        foreach ($operation->parameters as $parameter) {
            $param = new Param($parameter->name);

            if ($parameter->type !== '') {
                $param->setType($parameter->type);
            }

            if ($parameter->default !== null) {
                $param->setDefault($parameter->default);
            }

            $callParams[] = $param;
        }

        if (count($operation->requestBody) > 0) {
            $callParams[] = $factory->param('params')->setType('array');
        }

        $returnType = OperationHelper::getResultTypeFromOperation($operation);

        $callMethod = $factory->method('call')->makePublic()->setReturnType(
            new Node\UnionType(
                array_map(
                    static fn (string $object): Node\Name => new Node\Name(
                        str_contains($object, '\\')
                            ? (str_starts_with($object, '\\') ? $object : '\\' . $object)
                            : $object,
                    ),
                    [...Types::filterDuplicatesAndIncompatibleRawTypes(...explode('|', $returnType))],
                ),
            ),
        );

        $docBlock = OperationHelper::getDocBlockFromOperation($operation);
        if ($docBlock instanceof Doc) {
            $callMethod->setDocComment($docBlock);
        }

        $class->addStmt(
            $callMethod->addParams($callParams)->addStmts([
                new Node\Stmt\Expression(new Node\Expr\Assign(
                    new Node\Expr\Variable('operation'),
                    new Node\Expr\New_(
                        new Node\Name($operation->className->fullyQualified->source),
                        [
                            ...(count($operation->requestBody) > 0 ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'requestSchemaValidator',
                                )),
                            ] : []),
                            ...($bringHydratorAndResponseValidator ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'responseSchemaValidator',
                                )),
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'hydrator',
                                )),
                            ] : []),
                            ...($operation->matchMethod === 'STREAM' ? [
                                new Arg(new Node\Expr\PropertyFetch(
                                    new Node\Expr\Variable('this'),
                                    'browser',
                                )),
                            ] : []),
                            ...(static function (array $params): iterable {
                                foreach ($params as $param) {
                                    yield new Arg(new Node\Expr\Variable($param->name));
                                }
                            })($operation->parameters),
                        ],
                    ),
                )),
                new Node\Stmt\Expression(new Node\Expr\Assign(new Node\Expr\Variable('request'), new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createRequest', count($operation->requestBody) > 0 ? [new Arg(new Node\Expr\Variable('params'))] : []))),
                ...($returnType === 'void' ? [new Node\Stmt\Expression($this->callOperation($returnType, $operation))] : ResultConverter::convert(
                    $this->callOperation($returnType, $operation),
                )),
            ]),
        );

        yield new File($package->destination->source, $operation->operatorClassName->relative, $stmt->addStmt($class)->getNode(), File::DO_LOAD_ON_WRITE);
    }

    private function callOperation(string $returnType, Namespaced\Operation $operation): Node\Expr\MethodCall
    {
        return new Node\Expr\MethodCall(
            new Node\Expr\MethodCall(
                new Node\Expr\PropertyFetch(
                    new Node\Expr\Variable('this'),
                    'browser',
                ),
                'request',
                [
                    new Node\Arg(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getMethod')),
                    new Node\Arg(new Expr\Cast\String_(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getUri'))),
                    new Node\Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('request'),
                                'withHeader',
                                [
                                    new Node\Arg(new Node\Scalar\String_('Authorization')),
                                    new Node\Arg(
                                        new Node\Expr\MethodCall(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                'authentication',
                                            ),
                                            'authHeader',
                                        ),
                                    ),
                                ],
                            ),
                            'getHeaders',
                        ),
                    ),
                    new Node\Arg(new Expr\Cast\String_(new Node\Expr\MethodCall(new Node\Expr\Variable('request'), 'getBody'))),
                ],
            ),
            'then',
            [
                new Arg(new Node\Expr\Closure([
                    'stmts' => [
                        $returnType === 'void'
                            ? new Node\Stmt\Expression(new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createResponse', [
                                new Arg(new Node\Expr\Variable('response')),
                            ]))
                            : new Node\Stmt\Return_(new Node\Expr\MethodCall(new Node\Expr\Variable('operation'), 'createResponse', [
                                new Arg(new Node\Expr\Variable('response')),
                            ])),
                    ],
                    'params' => [new Node\Param(new Node\Expr\Variable('response'), null, new Node\Name('\\' . ResponseInterface::class))],
                    'uses' => [
                        new Node\ClosureUse(new Node\Expr\Variable('operation')),
                    ],
                    'returnType' => (static function (Namespaced\Operation $operation): Node\UnionType|Node\Name {
                        /** @var class-string $className */
                        $className  = $operation->className->fullyQualified->source;
                        $returnType = new ReflectionClass($className)->getMethod('createResponse')->getReturnType();
                        if ($returnType === null || ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'void')) {
                            return new Node\Name('void');
                        }

                        $types = $returnType instanceof ReflectionUnionType
                            ? array_map(
                                ReflectionTypes::name(...),
                                $returnType->getTypes(),
                            )
                            : [ReflectionTypes::name($returnType)];

                        return new Node\UnionType(
                            array_map(
                                static fn (string $object): Node\Name => new Node\Name(
                                    str_contains($object, '\\')
                                        ? (str_starts_with($object, '\\') ? $object : '\\' . $object)
                                        : $object,
                                ),
                                [...Types::filterDuplicatesAndIncompatibleRawTypes(...$types)],
                            ),
                        );
                    })($operation),
                ])),
            ],
        );
    }
}
