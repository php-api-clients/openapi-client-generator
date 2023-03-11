<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Voter\StreamOperation;

use ApiClients\Tools\OpenApiClientGenerator\Contract\Voter\StreamOperation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

final class DownloadInPath implements StreamOperation
{
    public static function stream(Operation $operation): bool
    {
        return strpos($operation->path, 'download') !== false;
    }
}
