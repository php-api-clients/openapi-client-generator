<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

use function array_unique;
use function count;
use function explode;
use function implode;
use function is_string;
use function md5;
use function Safe\json_encode;
use function str_replace;
use function strlen;

use const PHP_EOL;

final class Schema
{
    /**
     * @param array<ClassString> $aliases
     *
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, Representation\Schema $schema, array $aliases): iterable
    {
        $className = $schema->className;
        if (count($aliases) > 0) {
            $className = ClassString::factory(
                $className->namespace,
                'Schema\\AliasAbstract\\Abstract' . md5(json_encode($schema->schema->getSerializableData())),
            );
            $aliases[] = $schema->className;
        }

        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($className->namespace->source);

        $schemaJson = new Node\Stmt\ClassConst(
            [
                new Node\Const_(
                    'SCHEMA_JSON',
                    new Node\Scalar\String_(
                        json_encode($schema->schema->getSerializableData()),
                    ),
                ),
            ],
            Class_::MODIFIER_PUBLIC
        );

        $class = $factory->class($className->className)->makeReadonly();

        if (count($aliases) === 0) {
            $class = $class->makeFinal();
        } else {
            $class = $class->makeAbstract();
        }

        $class->addStmt(
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

        $constructor       = (new BuilderFactory())->method('__construct')->makePublic();
        $constructDocBlock = [];
        foreach ($schema->properties as $property) {
            if (strlen($property->description) > 0) {
                $constructDocBlock[] = $property->name . ': ' . $property->description;
            }

            $constructorParam = new PromotedPropertyAsParam($property->name);
            if ($property->name !== $property->sourceName) {
                $constructorParam->addAttribute(
                    new Node\Attribute(
                        new Node\Name('\\' . MapFrom::class),
                        [
                            new Node\Arg(new Node\Scalar\String_($property->sourceName)),
                        ],
                    ),
                );
            }

            $types = [];
            foreach ($property->type as $type) {
                if ($type->type === 'array' && ! is_string($type->payload)) {
                    if ($type->payload instanceof Representation\PropertyType) {
                        if (! $type->payload->payload instanceof Representation\PropertyType) {
                            $constructDocBlock[] = '@param ' . ($property->nullable ? '?' : '') . 'array<' . ($type->payload->payload instanceof Representation\Schema ? $type->payload->payload->className->fullyQualified->source : $type->payload->payload) . '> $' . $property->name;
                        }

                        if ($type->payload->payload instanceof Representation\Schema) {
                            $constructorParam->addAttribute(
                                new Node\Attribute(
                                    new Node\Name('\\' . CastListToType::class),
                                    [
                                        new Node\Arg(new Node\Expr\ClassConstFetch(
                                            new Node\Name($type->payload->payload->className->relative),
                                            'class',
                                        )),
                                    ],
                                ),
                            );
                        }
                    }

                    $types[] = 'array';
                    continue;
                }

                if ($type->payload instanceof Representation\Schema) {
                    $types[] = $type->payload->className->relative;
                    continue;
                }

                if (! is_string($type->payload)) {
                    continue;
                }

                $types[] = $type->payload;
            }

            $types = array_unique($types);

            $nullable = '';
            if ($property->nullable) {
                $nullable = count($types) > 1 || count(explode('|', implode('|', $types))) > 1 ? 'null|' : '?';
            }

            $constructor->addParam($constructorParam->setType($nullable . implode('|', $types)));
        }

        if (count($constructDocBlock) > 0) {
            $constructor->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', str_replace(['/**', '*/'], '', $constructDocBlock)) . PHP_EOL . ' */');
        }

        $class->addStmt($constructor);

        yield new File($pathPrefix, $className->relative, $stmt->addStmt($class)->getNode());

        foreach ($aliases as $alias) {
            $aliasTms   = $factory->namespace($alias->namespace->source);
            $aliasClass = $factory->class($alias->className)->makeFinal()->makeReadonly()->extend($className->relative);

            yield new File($pathPrefix, $alias->className, $aliasTms->addStmt($aliasClass)->getNode());
        }
    }
}
