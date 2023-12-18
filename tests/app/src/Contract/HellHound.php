<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Contract;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
/**
 * @property int $id
 * @property string $name
 * @property bool $badMinBoy
 * @property ?Schema\HellHound\Eyes $eyes
 */
interface HellHound
{
}
