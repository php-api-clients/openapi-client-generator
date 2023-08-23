<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output;

use ApiClients\Tools\OpenApiClientGenerator\Output\Status\ANSI;
use ApiClients\Tools\OpenApiClientGenerator\Output\Status\Simple;
use ApiClients\Tools\OpenApiClientGenerator\Output\Status\Step;

final readonly class Status
{
    private ANSI|Simple $output;

    public function __construct(bool $ansi, Step ...$steps)
    {
        if ($ansi) {
            $this->output = new ANSI(...$steps);
        } else {
            $this->output = new Simple(...$steps);
        }
    }

    public function markStepBusy(string $key): void
    {
        $this->output->markStepBusy($key);
    }

    public function markStepDone(string $key): void
    {
        $this->output->markStepDone($key);
    }

    public function markStepWontDo(string ...$keys): void
    {
        $this->output->markStepWontDo(...$keys);
    }

    public function itemForStep(string $key, int $count): void
    {
        $this->output->itemForStep($key, $count);
    }

    public function advanceStep(string $key): void
    {
        $this->output->advanceStep($key);
    }
}
