<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Operation;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class PetsList
{
    public function __construct(private \ApiClients\Client\PetStore\Operators $operators)
    {
    }
    /**
     * @return iterable<Schema\Cat>
     */
    public function gatos(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷List_👷Gatos()->call($perPage, $page);
    }
    /**
     * @return iterable<Schema\Cat>
     */
    public function gatosListing(int $perPage, int $page) : iterable
    {
        return $this->operators->pets👷List_👷GatosListing()->call($perPage, $page);
    }
}
