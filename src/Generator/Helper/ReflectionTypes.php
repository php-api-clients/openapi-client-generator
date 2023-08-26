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

use function strpos;

final class ReflectionTypes
{
    public static function copyReturnType(string $class, string $method): Node\ComplexType|Name|string
    {
        $reflection = (new ReflectionClass($class))->getMethod($method)->getReturnType();
        switch ($reflection::class) {
            //ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType
            case ReflectionNamedType::class:
                return new Name(
                    (strpos((string) $reflection, '\\') !== false ? '\\' : '') . $reflection,
                );

                break;
            case ReflectionUnionType::class:
                return new Node\UnionType(
                    [
                        ...(static function (ReflectionType ...$types): iterable {
                            foreach ($types as $type) {
                                yield new Name(
                                    (strpos((string) $type, '\\') !== false ? '\\' : '') . $type,
                                );
                            }
                        })(...$reflection->getTypes()),
                    ],
                );

                break;
            default:
                return '';
        }
    }

    public static function copyDocBlock(string $class, string $method): Doc|null
    {
        $comment = (new ReflectionClass($class))->getMethod($method)->getDocComment();
        if ($comment !== null) {
            return new Doc($comment);
        }

        return null;
    }
}
