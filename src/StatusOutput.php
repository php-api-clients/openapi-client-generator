<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\StatusOutput\OverWritingOutPut;
use ApiClients\Tools\OpenApiClientGenerator\StatusOutput\Step;
use Symfony\Component\Console\Output\ConsoleOutput;

use function Termwind\render;
use function Termwind\renderUsing;
use function time;

final class StatusOutput
{
    /** @var array<Step> */
    private readonly array $steps;

    /** @var array<string> */
    private array $stepsStatus = [];

    /** @var array<int> */
    private array $itemsCountForStep = [];

    /** @var array<int> */
    private array $stepProgress = [];

    private int $lastPaint = 0;

    public function __construct(Step ...$steps)
    {
        $this->steps = $steps;
        foreach ($this->steps as $step) {
            $this->stepsStatus[$step->key] = '🌀';
            if (! $step->progressBer) {
                continue;
            }

            $this->itemsCountForStep[$step->key] = 0;
            $this->stepProgress[$step->key]      = 0;
        }

        renderUsing(new OverWritingOutPut(new ConsoleOutput()));
    }

    public function render(): void
    {
        $html  = '<table>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Status</th>';
        $html .= '<th>Step</th>';
        $html .= '<th>Progress</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        foreach ($this->steps as $step) {
            $progress = '';
            if ($step->progressBer && $this->itemsCountForStep[$step->key] > 0) {
                $progress = $this->stepProgress[$step->key] . '/' . $this->itemsCountForStep[$step->key];
            }

            $html .= '<tr>';
            $html .= '<td>' . $this->stepsStatus[$step->key] . '</td>';
            $html .= '<td>' . $step->name . '</td>';
            $html .= '<td>' . $progress . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        render($html);
        $this->lastPaint = time();
    }

    public function maybeRender(): void
    {
        if ($this->lastPaint === time()) {
            return;
        }

        $this->render();
    }

    public function markStepBusy(string $key): void
    {
        $this->stepsStatus[$key] = '🌻';
        $this->render();
    }

    public function markStepDone(string $key): void
    {
        $this->stepsStatus[$key] = '✅';
        $this->render();
    }

    public function markStepWontDo(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->stepsStatus[$key] = '🚫';
        }

        $this->render();
    }

    public function itemForStep(string $key, int $count): void
    {
        $this->itemsCountForStep[$key] = $count;
        if ($this->stepProgress[$key] === 0) {
            $this->stepsStatus[$key] = '🌑';
        }

        $this->render();
    }

    public function advanceStep(string $key): void
    {
        $this->stepProgress[$key]++;
        $percentage = 100 / $this->itemsCountForStep[$key] * $this->stepProgress[$key];
        /** @phpstan-ignore-next-line */
        switch (true) {
            case $percentage <= 12.5:
                $this->stepsStatus[$key] = '🌑';
                break;
            case $percentage > 12.5 && $percentage <= 25:
                $this->stepsStatus[$key] = '🌒';
                break;
            case $percentage > 25 && $percentage <= 37.5:
                $this->stepsStatus[$key] = '🌓';
                break;
            case $percentage > 37.5 && $percentage <= 50:
                $this->stepsStatus[$key] = '🌔';
                break;
            case $percentage > 50 && $percentage <= 62.5:
                $this->stepsStatus[$key] = '🌕';
                break;
            case $percentage > 62.5 && $percentage <= 75:
                $this->stepsStatus[$key] = '🌖';
                break;
            case $percentage > 75 && $percentage <= 87.5:
                $this->stepsStatus[$key] = '🌗';
                break;
            case $percentage > 87.5:
                $this->stepsStatus[$key] = '🌘';
                break;
        }

        $this->maybeRender();
    }
}
