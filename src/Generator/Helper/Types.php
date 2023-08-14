<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use PhpParser\Node;

use function array_key_exists;
use function array_map;
use function in_array;
use function substr;

final class Types
{
    private const SCALARS = [
        'string',
        'int',
        'float',
        'bool',
    ];

    /** @return array<string> */
    public static function normalizeDocBlock(string ...$types): array
    {
        return array_map(
            static fn (string $type): string => in_array($type, self::SCALARS) || substr($type, 0, 1) === '\\' || substr($type, 0, 5) === 'array' ? $type : 'Schema\\' . $type,
            $types,
        );
    }

    /** @return array<string> */
    public static function normalizeRaw(string ...$types): array
    {
        return array_map(
            static function (string $type): string {
                if (substr($type, 0, 6) === 'array{') {
                    return 'array';
                }

                if (in_array($type, self::SCALARS) || substr($type, 0, 1) === '\\') {
                    return $type;
                }

                return 'Schema\\' . $type;
            },
            $types,
        );
    }

    /** @return array<Node\Name> */
    public static function normalizeNodeName(string ...$types): array
    {
        return array_map(
            static fn (string $type): Node\Name => new Node\Name($type),
            self::normalizeRaw(...$types),
        );
    }

    /** @return iterable<string> */
    public static function filterDuplicatesAndIncompatibleRawTypes(string ...$types): iterable
    {
        $keepVoid   = true;
        $keepArray  = true;
        $duplicates = [];

        foreach ($types as $type) {
            if ($type !== 'void') {
                $keepVoid = false;
            }

            if ($type === 'iterable') {
                $keepArray = false;
            }

            if ($type === 'void' || $type === 'array') {
                continue;
            }

            if (array_key_exists($type, $duplicates)) {
                continue;
            }

            yield $type;

            $duplicates[$type] = true;
        }

        if ($keepArray) {
            yield 'array';

            return;
        }

        if (! $keepVoid) {
            return;
        }

        yield 'void';
    }
}
