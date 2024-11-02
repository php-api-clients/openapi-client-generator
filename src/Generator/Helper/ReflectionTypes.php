<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

use function array_map;
use function implode;
use function str_contains;
use function str_replace;

final class ReflectionTypes
{
    public static function name(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                self::name(...),
                $type->getTypes(),
            ));
        }

        return 'mixed';
    }

    /** @param class-string $class */
    public static function copyReturnType(string $class, string $method): Node\ComplexType|Name|string
    {
        $reflection = new ReflectionClass($class)->getMethod($method)->getReturnType();
        if ($reflection === null) {
            return '';
        }

        if ($reflection instanceof ReflectionNamedType) {
            return new Name(str_replace(
                'Traversable',
                'iterable',
                (str_contains($reflection->getName(), '\\') ? '\\' : '') . $reflection->getName(),
            ));
        }

        if ($reflection instanceof ReflectionUnionType) {
            return new Node\UnionType(
                [
                    ...(static function (string ...$types): iterable {
                        foreach ($types as $type) {
                            if ($type === 'array') {
                                continue;
                            }

                            yield new Name(str_replace(
                                'Traversable',
                                'iterable',
                                (str_contains($type, '\\') ? '\\' : '') . $type,
                            ));
                        }
                    })(...[
                        ...Types::filterDuplicatesAndIncompatibleRawTypes(...array_map(
                            self::name(...),
                            $reflection->getTypes(),
                        )),
                    ]),
                ],
            );
        }

        return '';
    }

    /** @param class-string $class */
    public static function copyDocBlock(string $class, string $method): Doc|null
    {
        $comment = new ReflectionClass($class)->getMethod($method)->getDocComment();
        if ($comment === false) {
            return null;
        }

        return new Doc($comment);
    }
}
