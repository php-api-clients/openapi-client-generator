<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Voter\StreamOperation;

use ApiClients\Tools\OpenApiClientGenerator\Contract\Voter\StreamOperation;
use OpenAPITools\Representation\Namespaced\Operation;

use function str_contains;

final class DownloadInPath implements StreamOperation
{
    public static function stream(Operation $operation): bool
    {
        return str_contains($operation->path, 'download');
    }
}
