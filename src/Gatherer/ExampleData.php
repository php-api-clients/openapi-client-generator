<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Representation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use Ckr\Util\ArrayMerger;
use DateTimeInterface;
use PhpParser\Node;
use ReverseRegex\Generator\Scope;
use ReverseRegex\Lexer;
use ReverseRegex\Parser;

use function gettype;
use function is_array;
use function is_string;
use function Safe\date;
use function Safe\json_encode;
use function strlen;

final class ExampleData
{
    public static function gather(mixed $exampleData, PropertyType $type, string $propertyName): Representation\ExampleData
    {
        if ($type->type === 'array') {
            if ($type->payload instanceof Schema) {
                $exampleData = ArrayMerger::doMerge(
                    $type->payload->example,
                    is_array($exampleData) ? $exampleData : [],
                    ArrayMerger::FLAG_OVERWRITE_NUMERIC_KEY | ArrayMerger::FLAG_ALLOW_SCALAR_TO_ARRAY_CONVERSION,
                );
            } elseif ($type->payload instanceof PropertyType) {
                return self::gather($exampleData, $type->payload, $propertyName);
            }

            return new Representation\ExampleData($exampleData, $exampleData instanceof Node\Expr ? $exampleData : self::turnArrayIntoNode((array) $exampleData));
        }

        if ($type->payload instanceof Schema) {
            $exampleData = ArrayMerger::doMerge($type->payload->example, is_array($exampleData) ? $exampleData : [], ArrayMerger::FLAG_OVERWRITE_NUMERIC_KEY | ArrayMerger::FLAG_ALLOW_SCALAR_TO_ARRAY_CONVERSION);

            return new Representation\ExampleData($exampleData, self::turnArrayIntoNode($exampleData));
        }

        if ($exampleData === null && $type->type === 'scalar' && is_string($type->payload)) {
            return self::scalarData(strlen($propertyName), $type->payload, $type->format, $type->pattern);
        }

        return self::determiteType($exampleData);
    }

    public static function determiteType(mixed $exampleData): Representation\ExampleData
    {
        return match (gettype($exampleData)) {
            'boolean' => new Representation\ExampleData(
                $exampleData,
                new Node\Expr\ConstFetch(
                    new Node\Name(
                        $exampleData ? 'true' : 'false',
                    ),
                ),
            ),
            'integer' => new Representation\ExampleData(
                $exampleData,
                new Node\Scalar\LNumber($exampleData),
            ),
            'double' => new Representation\ExampleData(
                $exampleData,
                new Node\Scalar\DNumber($exampleData),
            ),
            'string' => new Representation\ExampleData(
                $exampleData,
                new Node\Scalar\String_($exampleData),
            ),
            'array' => new Representation\ExampleData($exampleData, self::turnArrayIntoNode($exampleData)),
            default => new Representation\ExampleData(
                null,
                new Node\Expr\ConstFetch(
                    new Node\Name(
                        'null',
                    ),
                ),
            ),
        };
    }

    /** @phpstan-ignore-next-line */
    public static function scalarData(int $seed, string $type, string|null $format, string|null $pattern = null): Representation\ExampleData
    {
        if ($type === 'int' || $type === '?int') {
            return new Representation\ExampleData($seed, new Node\Scalar\LNumber($seed));
        }

        if ($type === 'float' || $type === '?float' || $type === 'int|float' || $type === 'null|int|float') {
            return new Representation\ExampleData($seed / 10, new Node\Scalar\DNumber($seed / 10));
        }

        if ($type === 'bool' || $type === '?bool') {
            return new Representation\ExampleData(
                false,
                new Node\Expr\ConstFetch(
                    new Node\Name(
                        'false',
                    ),
                ),
            );
        }

        if ($type === 'string' || $type === '?string') {
            if ($pattern !== null) {
                $result = '';

                /** @phpstan-ignore-next-line */
                @(new Parser(new Lexer($pattern), new Scope(), new Scope()))->parse()->getResult()->generate(
                    $result,
                    new IntegerReturnerPretendingToBeARandomNumberGenerator(strlen($pattern)),
                );

                return new Representation\ExampleData($result, new Node\Scalar\String_($result));
            }

            if ($format === 'uri') {
                return new Representation\ExampleData('https://example.com/', new Node\Scalar\String_('https://example.com/'));
            }

            if ($format === 'email') {
                return new Representation\ExampleData('hi@example.com', new Node\Scalar\String_('hi@example.com'));
            }

            if ($format === 'date-time') {
                return new Representation\ExampleData(date(DateTimeInterface::RFC3339, 0), new Node\Scalar\String_(date(DateTimeInterface::RFC3339, 0)));
            }

            if ($format === 'uuid') {
                return new Representation\ExampleData('4ccda740-74c3-4cfa-8571-ebf83c8f300a', new Node\Scalar\String_('4ccda740-74c3-4cfa-8571-ebf83c8f300a'));
            }

            if ($format === 'ipv4') {
                return new Representation\ExampleData('127.0.0.1', new Node\Scalar\String_('127.0.0.1'));
            }

            if ($format === 'ipv6') {
                return new Representation\ExampleData('::1', new Node\Scalar\String_('::1'));
            }

            return new Representation\ExampleData('generated', new Node\Scalar\String_('generated'));
        }

        return new Representation\ExampleData(
            null,
            new Node\Expr\ConstFetch(
                new Node\Name(
                    'null',
                ),
            ),
        );
    }

    /** @param array<mixed> $array */
    private static function turnArrayIntoNode(array $array): Node\Expr
    {
        return new Node\Expr\FuncCall(
            new Node\Name('\json_decode'),
            [
                new Node\Arg(
                    new Node\Scalar\String_(
                        json_encode([...self::arrayToRaw($array)]),
                    ),
                ),
                new Node\Arg(
                    new Node\Expr\ConstFetch(
                        new Node\Name(
                            'false',
                        ),
                    ),
                ),
            ],
        );
    }

    /**
     * @param array<Representation\ExampleData|mixed> $exampleData
     *
     * @return iterable<string, mixed>
     */
    private static function arrayToRaw(array $exampleData): iterable
    {
        foreach ($exampleData as $key => $value) {
            if ($value instanceof Representation\ExampleData) {
                $value = $value->raw;
            }

            if (is_array($value)) {
                $value = [...self::arrayToRaw($value)];
            }

            yield $key => $value;
        }
    }
}
