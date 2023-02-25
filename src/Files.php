<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Client;
use ApiClients\Tools\OpenApiClientGenerator\Generator\ClientInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class Files
{
    /**
     * @param string $path
     * @return iterable<string, string>
     */
    public static function listExistingFiles(string $path): iterable
    {
        if (!file_exists($path)) {
            yield from [];
            return;
        }

        foreach (scandir($path) as $node) {
            if ($node === '.' || $node === '..') {
                continue;
            }

            if (is_file($path . $node)) {
                yield $path . $node => md5_file($path . $node);
            }

            if (is_dir($path . $node)) {
                yield from self::listExistingFiles($path . $node . DIRECTORY_SEPARATOR);
            }
        }
    }
}
