<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Representation;
use PhpParser\BuilderFactory;

use function array_filter;
use function array_key_exists;
use function array_unique;
use function count;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_string;
use function strlen;
use function trim;

use const PHP_EOL;

final class Contract
{
    /**
     * @param array<ClassString> $aliases
     *
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, Representation\Contract $contract): iterable
    {
        $factory = new BuilderFactory();

        $interface          = $factory->interface($contract->className->className);
        $contractProperties = [];
        foreach ($contract->properties as $property) {
            $types = [];
            if ($property->type->type === 'union' && is_array($property->type->payload)) {
                $types[] = self::buildUnionType($property->type);
            }

            if ($property->type->type === 'array' && ! is_string($property->type->payload)) {
                if ($property->type->payload instanceof Representation\PropertyType) {
                    if (! $property->type->payload->payload instanceof Representation\PropertyType) {
                        $iterableType = $property->type->payload;
                        if ($iterableType->payload instanceof Representation\Schema) {
                            $iterableType = $iterableType->payload->className->fullyQualified->source;
                        }

                        if ($iterableType instanceof Representation\PropertyType && (($iterableType->payload instanceof Representation\PropertyType && $iterableType->payload->type === 'union') || is_array($iterableType->payload))) {
                            $iterableType = self::buildUnionType($iterableType);
                        }

                        if ($iterableType instanceof Representation\PropertyType) {
                            $iterableType = $iterableType->payload;
                        }

                        $compiledTYpe                        = ($property->nullable ? '?' : '') . 'array<' . $iterableType . '>';
                        $contractProperties[$property->name] = '@property ' . $compiledTYpe . ' $' . $property->name;
                    }
                } elseif (is_array($property->type->payload)) {
                    $schemaClasses = [];
                    foreach ($property->type->payload as $payloadType) {
                        $schemaClasses = [...$schemaClasses, ...self::getUnionTypeSchemas($payloadType)];
                    }

                    if (count($schemaClasses) > 0) {
                        $compiledTYpe                        = ($property->nullable ? '?' : '') . 'array<' . implode('|', array_unique([
                            ...(static function (Representation\Schema ...$schemas): iterable {
                                foreach ($schemas as $schema) {
                                    yield $schema->className->fullyQualified->source;
                                }
                            })(...$schemaClasses),
                        ])) . '>';
                        $contractProperties[$property->name] = '@property ' . $compiledTYpe . ' $' . $property->name;
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
                if (! array_key_exists($property->name, $contractProperties)) {
                    $contractProperties[$property->name] = '@property ' . $nullable . implode('|', $types) . ' $' . $property->name;
                }
            } else {
                if (! array_key_exists($property->name, $contractProperties)) {
                    $contractProperties[$property->name] = '@property $' . $property->name;
                }
            }
        }

        if (count($contractProperties) > 0) {
            $interface->setDocComment('/**' . PHP_EOL . ' * ' . implode(PHP_EOL . ' * ', $contractProperties) . PHP_EOL . ' */');
        }

        yield new File($pathPrefix, $contract->className->relative, $factory->namespace($contract->className->namespace->source)->addStmt($interface)->getNode());
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

    /** @return iterable<Representation\Schema> */
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
