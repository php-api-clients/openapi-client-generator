<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\AliasAbstract\TietD65BC5E9\Tiet6F251EDA\Tiet699DECD9;

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
abstract readonly class TietD8B901F6
{
    public const SCHEMA_JSON = '{"oneOf":[{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"indoor":{"type":"bool"}}},{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"good-boy":{"type":"bool"}}},{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string4"},"flies":{"type":"bool"}}},{"required":["id","name"],"type":"object","properties":{"id":{"type":"integer","format":"int64"},"name":{"type":"string"},"flat":{"type":"bool"}}}]}';
    public const SCHEMA_TITLE = '';
    public const SCHEMA_DESCRIPTION = '';
    public const SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
