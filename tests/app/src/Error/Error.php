<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Error extends \Error
{
    public function __construct(public int $status, public Schema\Error $error)
    {
    }
}
