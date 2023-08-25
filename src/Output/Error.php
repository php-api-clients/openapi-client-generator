<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output;

use OndraM\CiDetector\CiDetector;
use Throwable;

use function file_put_contents;
use function getenv;
use function Termwind\render;

use const FILE_APPEND;

final readonly class Error
{
    public static function display(Throwable $throwable): void
    {
        render('<div>
            <div class="px-1 bg-red-600">ERROR</div>
            <em class="ml-1">
              ' . $throwable->getMessage() . '
            </em>
        </div>');

        if ((new CiDetector())->detect()->getCiName() !== CiDetector::CI_GITHUB_ACTIONS) {
            return;
        }

        file_put_contents(getenv('GITHUB_STEP_SUMMARY'), "### ⚠️ Error ⚠️\n```" . $throwable->getMessage() . "```\n", FILE_APPEND);
    }
}
