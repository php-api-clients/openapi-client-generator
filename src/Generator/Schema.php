<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema\CastUnionToType;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use EventSauce\ObjectHydrator\MapFrom;
use EventSauce\ObjectHydrator\PropertyCasters\CastListToType;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

use function array_filter;
use function array_unique;
use function count;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_string;
use function md5;
use function Safe\json_encode;
use function str_replace;
use function strlen;
use function trim;

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
                $className->baseNamespaces,
                'Schema\\AliasAbstract\\Tiet' . implode('\\Tiet', str_split(strtoupper(md5(json_encode($schema->schema->getSerializableData()))), 8)),
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
            if ($property->type->type === 'union' && is_array($property->type->payload)) {
                $types[] = self::buildUnionType($property->type);
                $schemaClasses = [...self::getUnionTypeSchemas($property->type)];

                if (count($schemaClasses) > 0) {
                    $castToUnionToType = ClassString::factory($schema->className->baseNamespaces, Utils::className('Attribute\\CastUnionToType\\' . $schema->className->relative . '\\' . $property->name));

                    yield from CastUnionToType::generate($pathPrefix, $castToUnionToType, ...$schemaClasses);

                    $constructorParam->addAttribute(
                        new Node\Attribute(
                            new Node\Name($castToUnionToType->fullyQualified->source),
                        ),
                    );
                }
            }

            if ($property->type->type === 'array' && ! is_string($property->type->payload)) {
                if ($property->type->payload instanceof Representation\PropertyType) {
                    if (! $property->type->payload->payload instanceof Representation\PropertyType) {
                        $iterableType = $property->type->payload;
                        if ($iterableType->payload instanceof Representation\Schema) {
                            $iterableType = $iterableType->payload->className->fullyQualified->source;
                        }

                        if ($iterableType instanceof Representation\PropertyType && (($iterableType->payload instanceof Representation\PropertyType && $iterableType->payload->type === 'union') || is_array($iterableType->payload))) {
                            $schemaClasses = [...self::getUnionTypeSchemas($iterableType)];
                            $iterableType  = self::buildUnionType($iterableType);

                            if (count($schemaClasses) > 0) {
                                $castToUnionToType = ClassString::factory($schema->className->baseNamespaces, Utils::className('Attribute\\CastUnionToType\\' . $schema->className->relative . '\\' . $property->name));

                                yield from CastUnionToType::generate($pathPrefix, $castToUnionToType, ...$schemaClasses);

                                $constructorParam->addAttribute(
                                    new Node\Attribute(
                                        new Node\Name($castToUnionToType->fullyQualified->source),
                                    ),
                                );
                            }
                        }

                        if ($iterableType instanceof Representation\PropertyType) {
                            $iterableType = $iterableType->payload;
                        }

                        $constructDocBlock[] = '@param ' . ($property->nullable ? '?' : '') . 'array<' . $iterableType . '> $' . $property->name;
                    }

                    if ($property->type->payload->payload instanceof Representation\Schema) {
                        $constructorParam->addAttribute(
                            new Node\Attribute(
                                new Node\Name('\\' . CastListToType::class),
                                [
                                    new Node\Arg(new Node\Expr\ClassConstFetch(
                                        new Node\Name($property->type->payload->payload->className->relative),
                                        'class',
                                    )),
                                ],
                            ),
                        );
                    }
                }

                $types[] = 'array';
            } elseif ($property->type->payload instanceof Representation\Schema) {
                $types[] = $property->type->payload->className->relative;
            } elseif (is_string($property->type->payload)) {
                $types[] = $property->type->payload;
            }

            $types = array_unique($types);

            $nullable = '';
            if ($property->nullable) {
                $nullable = count($types) > 1 || count(explode('|', implode('|', $types))) > 1 ? 'null|' : '?';
            }

            if (count($types) > 0) {
                $constructorParam->setType($nullable . implode('|', $types));
            }

            $constructor->addParam($constructorParam);
        }

        if (count($constructDocBlock) > 0) {
            $constructor->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', str_replace(['/**', '*/'], '', $constructDocBlock)) . PHP_EOL . ' */');
        }

        $class->addStmt($constructor);

        yield new File($pathPrefix, $className->relative, $stmt->addStmt($class)->getNode());

        foreach ($aliases as $alias) {
            $aliasTms   = $factory->namespace($alias->namespace->source);
            $aliasClass = $factory->class($alias->className)->makeFinal()->makeReadonly()->extend($className->relative);

            yield new File($pathPrefix, $alias->relative, $aliasTms->addStmt($aliasClass)->getNode());
        }
    }

    private static function buildUnionType(Representation\PropertyType $type): string
    {
        $typeList = [];
        if (is_array($type->payload)) {
            foreach ($type->payload as $typeInUnion) {
                $typeList[] = match (gettype($typeInUnion->payload)) {
                    'string' => $typeInUnion->payload,
                    'array' => 'array',
                    'object' => match ($typeInUnion->payload::class) {
                        Representation\Schema::class => $typeInUnion->payload->className->relative,
                        Representation\PropertyType::class => self::buildUnionType($typeInUnion->payload),
                    },
                };
            }
        } else {
            $typeList[] = $type->payload;
        }

        return implode(
            '|',
            array_unique(
                array_filter(
                    $typeList,
                    static fn (string $item): bool => strlen(trim($item)) > 0,
                ),
            ),
        );
    }

    /**
     * @return iterable<Representation\Schema>
     */
    private static function getUnionTypeSchemas(Representation\PropertyType $type): iterable
    {
        if (! is_array($type->payload)) {
            return;
        }

        foreach ($type->payload as $typeInUnion) {
            if ($typeInUnion->payload instanceof Representation\Schema) {
                yield $typeInUnion->payload;
            }

            if (! ($typeInUnion->payload instanceof Representation\PropertyType)) {
                continue;
            }

            yield from self::getUnionTypeSchemas($typeInUnion->payload);
        }
    }
}
