<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use OpenAPITools\Representation\Namespaced\Operation;
use OpenAPITools\Representation\Namespaced\Schema;

use function array_key_exists;
use function in_array;

abstract class AbstractListOperation implements ListOperation
{
    final public static function list(Operation $operation): bool
    {
        foreach ($operation->response as $response) {
            if ($response->code !== 200) {
                continue;
            }

            if ($response->content instanceof Schema) {
                return false;
            }

            if ($response->content->type !== 'array') {
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

        return ! in_array(false, $match, true);
    }
}
