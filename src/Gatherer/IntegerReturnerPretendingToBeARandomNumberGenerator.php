<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ReverseRegex\Random\GeneratorInterface;

// phpcs:disable
final readonly class IntegerReturnerPretendingToBeARandomNumberGenerator implements GeneratorInterface
{
    public function __construct(private int $randomNumber)
    {

    }

    /**
     * @phpstan-ignore-next-line
     */
    public function generate($min = 0, $max = null)
    {
        return $this->randomNumber > $max ? $max : $this->randomNumber;
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function seed($seed = null)
    {
        return $this->randomNumber;
    }

    public function max()
    {
        return $this->randomNumber;
    }
}
