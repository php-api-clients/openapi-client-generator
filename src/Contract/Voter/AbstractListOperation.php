<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Contract\Voter;

use ApiClients\Tools\OpenApiClientGenerator\Representation\Operation;

abstract class AbstractListOperation implements ListOperation
{
    final public static function list(Operation $operation): bool
    {
        $match = [];
        foreach (static::keys() as $key) {
            $match[$key] = false;
        }
        foreach ($operation->parameters as $parameter) {
            if (!array_key_exists($parameter->name, $match)) {
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
