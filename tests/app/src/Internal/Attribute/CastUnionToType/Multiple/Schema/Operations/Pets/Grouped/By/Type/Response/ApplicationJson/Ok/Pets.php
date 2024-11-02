<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Pets implements \EventSauce\ObjectHydrator\PropertyCaster
{
    private \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets $wrappedCaster;
    public function __construct()
    {
        $this->wrappedCaster = new \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets();
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
