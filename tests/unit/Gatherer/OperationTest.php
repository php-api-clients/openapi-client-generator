<?php

declare(strict_types=1);

namespace ApiClients\Tests\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Generator;
use PhpParser\Node;
use WyriHaximus\TestUtilities\TestCase;

use function array_map;

final class OperationTest extends TestCase
{
    private const SCALARS = [
        'string',
        'int',
        'float',
        'bool',
    ];

    /** @return iterable<array<string>, array{docBlock: array<string>, raw: array<string>}> */
    public function types(): iterable
    {
        yield [
            ['array{code: int}'],
            [
                'docBlock' => ['array{code: int}'],
                'raw' => ['array'],
            ],
        ];

        foreach (self::SCALARS as $scalar) {
            yield [
                [$scalar],
                [
                    'docBlock' => [$scalar],
                    'raw' => [$scalar],
                ],
            ];
        }

        yield [
            ['SimpleUser'],
            [
                'docBlock' => ['Schema\SimpleUser'],
                'raw' => ['Schema\SimpleUser'],
            ],
        ];

        yield [
            ['\SimpleUser'],
            [
                'docBlock' => ['\SimpleUser'],
                'raw' => ['\SimpleUser'],
            ],
        ];
    }

    /**
     * @param array<string>                  $input
     * @param array{docBlock: array<string>} $output
     *
     * @test
     * @dataProvider types
     */
    public function normalizeDocBlock(array $input, array $output): void
    {
        self::assertEquals(
            Generator\Helper\Types::normalizeDocBlock(...$input),
            $output['docBlock'],
        );
    }

    /**
     * @param array<string>             $input
     * @param array{raw: array<string>} $output
     *
     * @test
     * @dataProvider types
     */
    public function normalizeRaw(array $input, array $output): void
    {
        self::assertEquals(
            Generator\Helper\Types::normalizeRaw(...$input),
            $output['raw'],
        );
    }

    /**
     * @param array<string>             $input
     * @param array{raw: array<string>} $output
     *
     * @test
     * @dataProvider types
     */
    public function normalizeNodeName(array $input, array $output): void
    {
        self::assertEquals(
            array_map(
                static fn (Node\Name $type): string => $type->toString(),
                Generator\Helper\Types::normalizeNodeName(...$input),
            ),
            $output['raw'],
        );
    }
}
