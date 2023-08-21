<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;

use function array_key_exists;

abstract class AbstractListOperation implements ListOperation
{
    final public static function list(Operation $operation): bool
    {
        foreach ($operation->response as $response) {
            if ($response->code === 200 && $response->content instanceof Schema) {
                return false;
            }

            if ($response->code === 200 && $response->content instanceof PropertyType && $response->content->type !== 'array') {
                return false;
            }
        }

        $match = [];
        foreach (static::keys() as $key) {
            $match[$key] = false;
        }

        foreach ($operation->parameters as $parameter) {
            if (! array_key_exists($parameter->name, $match)) {
                continue;
            }

            if ($parameter->location !== 'query') {
                continue;
            }

            $match[$parameter->name] = true;
        }

        foreach ($match as $matched) {
            if ($matched === false) {
                return false;
            }
        }

        return true;
    }
}
