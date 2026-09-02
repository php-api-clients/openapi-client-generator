<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Cat;

final readonly class Features implements \ApiClients\Client\PetStore\Contract\Cat\Features
{
    const string SCHEMA_JSON = '{
    "type": "object"
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}
