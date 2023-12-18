<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Multiple\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Pets implements \EventSauce\ObjectHydrator\PropertyCaster
{
    private \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets $wrappedCaster;
    public function __construct()
    {
        $this->wrappedCaster = new \ApiClients\Client\PetStore\Internal\Attribute\CastUnionToType\Single\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok\Pets();
    }
    public function cast(mixed $value, \EventSauce\ObjectHydrator\ObjectMapper $hydrator) : mixed
    {
        $data = array();
        $values = $value;
        unset($value);
        foreach ($values as $value) {
            $values[] = $this->wrappedCaster->cast($value, $hydrator);
        }
        return $data;
    }
}
