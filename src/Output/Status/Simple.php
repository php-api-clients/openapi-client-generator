<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output\Status;

use function array_combine;
use function array_map;

use const PHP_EOL;

final class Simple
{
    /** @var array<string, Step> */
    private readonly array $steps;

    public function __construct(Step ...$steps)
    {
        $this->steps = array_combine(
            array_map(
                static fn (Step $step): string => $step->key,
                $steps,
            ),
            $steps,
        );
    }

    public function markStepBusy(string $key): void
    {
    }

    public function markStepDone(string $key): void
    {
        echo $this->steps[$key]->name, PHP_EOL;
    }

    public function markStepWontDo(string ...$keys): void
    {
    }

    public function itemForStep(string $key, int $count): void
    {
    }

    public function advanceStep(string $key): void
    {
    }
}
