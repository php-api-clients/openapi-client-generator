<?php

declare(strict_types=1);

namespace ApiClients\Tests\Tools\OpenApiClientGenerator\Generator\Helper;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Helper\Types;
use PhpParser\Node;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use WyriHaximus\TestUtilities\TestCase;

use function array_map;

final class TypesTest extends TestCase
{
    private const array SCALARS = [
        'string',
        'int',
        'float',
        'bool',
    ];

    /** @return iterable<string, array{array<string>, array{docBlock: list<string>, raw: list<string>}}> */
    public static function types(): iterable
    {
        foreach (self::SCALARS as $scalar) {
            yield $scalar => [
                [$scalar],
                [
                    'docBlock' => [$scalar],
                    'raw' => [$scalar],
                ],
            ];
        }

        yield 'simple-user' => [
            ['SimpleUser'],
            [
                'docBlock' => ['Schema\SimpleUser'],
                'raw' => ['Schema\SimpleUser'],
            ],
        ];

        yield 'leading-slash' => [
            ['\SimpleUser'],
            [
                'docBlock' => ['\SimpleUser'],
                'raw' => ['\SimpleUser'],
            ],
        ];
    }

    /**
     * @param array<string>                                    $input
     * @param array{docBlock: list<string>, raw: list<string>} $output
     */
    #[Test]
    #[DataProvider('types')]
    public function normalizeDocBlock(array $input, array $output): void
    {
        self::assertEquals(
            Types::normalizeDocBlock(...$input),
            $output['docBlock'],
        );
    }

    /**
     * @param array<string>                                    $input
     * @param array{docBlock: list<string>, raw: list<string>} $output
     */
    #[Test]
    #[DataProvider('types')]
    public function normalizeRaw(array $input, array $output): void
    {
        self::assertEquals(
            Types::normalizeRaw(...$input),
            $output['raw'],
        );
    }

    /**
     * @param array<string>                                    $input
     * @param array{docBlock: list<string>, raw: list<string>} $output
     */
    #[Test]
    #[DataProvider('types')]
    public function normalizeNodeName(array $input, array $output): void
    {
        self::assertEquals(
            array_map(
                static fn (Node\Name $type): string => $type->toString(),
                Types::normalizeNodeName(...$input),
            ),
            $output['raw'],
        );
    }
}
