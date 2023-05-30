<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Attribute\CastUnionToType\Schema\Fish;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Eyes implements \EventSauce\ObjectHydrator\PropertyCaster
{
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator) : mixed
    {
        if (\is_array($value)) {
            $signatureChunks = \array_unique(\array_keys($value));
            \sort($signatureChunks);
            $signature = \implode('|', $signatureChunks);
            if ($signature === 'count|type' && ($value['type'] === 'blood' || $value['type'] === 'wine' || $value['type'] === 'stale')) {
                try {
                    return $hydrator->hydrateObject(Schema\RedEyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(Schema\BlueEyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'hulk' || $value['type'] === 'forest' || $value['type'] === 'feral')) {
                try {
                    return $hydrator->hydrateObject(Schema\GreenEyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(Schema\YellowEyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(Schema\BlackEyes::class, $value);
                } catch (\Throwable) {
                }
            }
        }
        return $value;
    }
}
