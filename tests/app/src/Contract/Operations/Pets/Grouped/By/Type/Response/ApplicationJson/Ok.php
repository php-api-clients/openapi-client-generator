<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Contract\Operations\Pets\Grouped\By\Type\Response\ApplicationJson;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
/**
 * @property array<\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound> $pets
 */
interface Ok
{
}
