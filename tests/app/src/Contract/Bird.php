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
 * @property string $id
 * @property string4 $name
 * @property bool $flies
 * @property Schema\RedEyes|Schema\BlueEyes|Schema\GreenEyes|Schema\YellowEyes|Schema\BlackEyes $eyes
 */
interface Bird
{
}
