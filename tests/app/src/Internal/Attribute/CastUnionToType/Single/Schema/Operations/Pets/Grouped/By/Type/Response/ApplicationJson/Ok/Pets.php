<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Pets implements \EventSauce\ObjectHydrator\PropertyCaster
{
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator): mixed
    {
        if (\is_array($value)) {
            $signatureChunks = \array_unique(\array_keys($value));
            \sort($signatureChunks);
            $signature = \implode('|', $signatureChunks);
            if ($signature === 'eyes|features|id|indoor|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|good-boy|id|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'bad-boy|eyes|id|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|features|id|indoor|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'eyes|good-boy|id|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'bad-boy|eyes|id|name') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets::class, $value);
                } catch (\Throwable) {
                }
            }
        }
        return $value;
    }
}
