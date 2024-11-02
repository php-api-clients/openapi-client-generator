<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output;

use OndraM\CiDetector\CiDetector;
use Throwable;

use function file_put_contents;
use function getenv;
use function is_string;
use function Termwind\render;

use const FILE_APPEND;

final readonly class Error
{
    public static function display(Throwable $throwable): void
    {
        render('<div>
            <div class="px-1 bg-red-600">ERROR</div>
            <em class="ml-1">
              ' . $throwable . '
            </em>
        </div>');

        if (new CiDetector()->detect()->getCiName() !== CiDetector::CI_GITHUB_ACTIONS) {
            return;
        }

        $summaryPath = getenv('GITHUB_STEP_SUMMARY');
        if (! is_string($summaryPath) || $summaryPath === '') {
            return;
        }

        file_put_contents($summaryPath, "### ⚠️ Error ⚠️\n```" . $throwable->getMessage() . "```\n", FILE_APPEND);
    }
}
