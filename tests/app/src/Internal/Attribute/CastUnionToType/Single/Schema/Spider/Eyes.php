<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Eyes implements \EventSauce\ObjectHydrator\PropertyCaster
{
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator): mixed
    {
        if (\is_array($value)) {
            $signatureChunks = \array_unique(\array_keys($value));
            \sort($signatureChunks);
            $signature = \implode('|', $signatureChunks);
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['blood', 'wine', 'stale'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && ($value['type'] === 'sky' || $value['type'] === 'boobies')) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && (in_array($value['type'], ['hulk', 'forest', 'feral'], true))) {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'snake') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
            if ($signature === 'count|type' && $value['type'] === 'rage') {
                try {
                    return $hydrator->hydrateObject(\ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes::class, $value);
                } catch (\Throwable) {
                }
            }
        }
        return $value;
    }
}
