<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Spider;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Eyes implements \EventSauce\ObjectHydrator\PropertyCaster
{
    private \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes $wrappedCaster;
    public function __construct()
    {
        $this->wrappedCaster = new \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Spider\Eyes();
    }
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator): mixed
    {
        $data = [];
        $values = $value;
        unset($value);
        foreach ($values as $value) {
            $values[] = $this->wrappedCaster->cast($value, $hydrator);
        }
        return $data;
    }
}
