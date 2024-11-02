<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Schema\Operations\Pets\Names\Response\ApplicationJson;

final readonly class Ok implements \ApiClients\Client\PetStore\Contract\Operations\Pets\Names\Response\ApplicationJson\Ok
{
    const string SCHEMA_JSON = '{
    "type": "string"
}';
    public const string SCHEMA_TITLE = '';
    public const string SCHEMA_DESCRIPTION = '';
    const string SCHEMA_EXAMPLE_DATA = '[]';
    public function __construct()
    {
    }
}

