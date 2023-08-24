<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Output;

use ApiClients\Tools\OpenApiClientGenerator\Output\Status\ANSI;
use ApiClients\Tools\OpenApiClientGenerator\Output\Status\Simple;
use ApiClients\Tools\OpenApiClientGenerator\Output\Status\Step;
use OndraM\CiDetector\CiDetector;
use function Termwind\render;

final readonly class Error
{
    public static function display(\Throwable $throwable)
    {
        render('<div>
            <div class="px-1 bg-red-600">ERROR</div>
            <em class="ml-1">
              ' . $throwable->getMessage() . '
            </em>
        </div>');

        if ((new CiDetector())->detect()->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
            file_put_contents(getenv('GITHUB_STEP_SUMMARY'), "### ⚠️ Error ⚠️\n```" . $throwable->getMessage() . "```\n", FILE_APPEND);
        }
    }
}
