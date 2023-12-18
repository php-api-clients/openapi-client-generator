<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Pets implements \EventSauce\ObjectHydrator\PropertyCaster
{
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator) : mixed
    {
        if (\is_array($value)) {
            $signatureChunks = \array_unique(\array_keys($value));
            \sort($signatureChunks);
            $signature = \implode('|', $signatureChunks);
            if ($signature === 'eyes|features|id|indoor|name') {
                try {
                    return $hydrator->hydrateObject(Schema\Cat::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|good-boy|id|name') {
                try {
                    return $hydrator->hydrateObject(Schema\Dog::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'bad-boy|eyes|id|name') {
                try {
                    return $hydrator->hydrateObject(Schema\HellHound::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|features|id|indoor|name') {
                try {
                    return $hydrator->hydrateObject(Schema\Cat::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|good-boy|id|name') {
                try {
                    return $hydrator->hydrateObject(Schema\Dog::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'bad-boy|eyes|id|name') {
                try {
                    return $hydrator->hydrateObject(Schema\HellHound::class, $value);
                } catch (\Throwable) {
                }
            }
        }
        return $value;
    }
}
