<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Client\Github\Schema\WebhookLabelEdited\Changes\Name;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as OpenAPiSchema;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Schema
{
    /**
     * @param string $name
     * @param string $namespace
     * @param string $schema->className
     * @param OpenAPiSchema $schema
     * @return iterable<Node>
     */
    public static function generate(string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema $schema): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(trim(Utils::dirname($namespace . '\\Schema\\' . $schema->className), '\\'));

        $schemaJson = new Node\Stmt\ClassConst(
            [
                new Node\Const_(
                    'SCHEMA_JSON',
                    new Node\Scalar\String_(
                        json_encode($schema->schema->getSerializableData())
                    )
                ),
            ],
            Class_::MODIFIER_PUBLIC
        );

        $class = $factory->class(trim(Utils::basename($schema->className), '\\'))->makeFinal()->makeReadonly()->addStmt(
            $schemaJson
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_TITLE',
                        new Node\Scalar\String_(
                            $schema->title
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_DESCRIPTION',
                        new Node\Scalar\String_(
                            $schema->description
                        )
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        )->addStmt(
            new Node\Stmt\ClassConst(
                [
                    new Node\Const_(
                        'SCHEMA_EXAMPLE_DATA',
                        $factory->val(json_encode($schema->example)),
                    ),
                ],
                Class_::MODIFIER_PUBLIC
            )
        );

        $constructor = (new BuilderFactory())->method('__construct')->makePublic();
        $constructDocBlock = [];
        foreach ($schema->properties as $property) {
            if (is_string($property->description) && strlen($property->description) > 0) {
                $constructDocBlock[] = $property->name . ': ' . $property->description;
            }

            $constructorParam = new PromotedPropertyAsParam($property->name);

            $types = [];
            foreach ($property->type as $type) {
                if ($type->type === 'array') {
                    $constructDocBlock[] = '@param ' . ($property->nullable ? '?' : '') . 'array<' . ($type->payload->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema ? ($namespace . 'Schema\\' . $type->payload->payload->className) : $type->payload->payload) . '> $' . $property->name;
                    if ($type->payload->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                        $constructorParam->addAttribute(
                            new Node\Attribute(
                                new Node\Name('\\' . \EventSauce\ObjectHydrator\PropertyCasters\CastListToType::class),
                                [
                                    new Node\Arg(new Node\Expr\ClassConstFetch(
                                        new Node\Name('Schema\\' . $type->payload->payload->className),
                                        new Node\Name('class'),
                                    )),
                                ],
                            ),
                        );
                    }
                    $types[] = 'array';
                    continue;
                }

                if ($type->payload instanceof \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema) {
                    $types[] = 'Schema\\' . $type->payload->className;
                    continue;
                }

                $types[] = $type->payload;
            }

            $types = array_unique($types);

            $nullable = '';
            if ($property->nullable) {
                $nullable = count($types) > 1 ? 'null|' : '?';
            }

            $constructor->addParam($constructorParam->setType($nullable . implode('|', $types)));
        }

        if (count($constructDocBlock) > 0) {
            $constructor->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', str_replace(['/**', '*/'], '', $constructDocBlock)) . PHP_EOL .' */');
        }

        $class->addStmt($constructor);


        yield new File($namespace . 'Schema\\' . $schema->className, $stmt->addStmt($class)->getNode());
    }
}
