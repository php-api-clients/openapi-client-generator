<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Types;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
$client = new \ApiClients\Client\PetStore\Client(new class implements \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface
{
    function authHeader() : string
    {
        return 'Saturn V';
    }
}, new \React\Http\Browser());
\PHPStan\Testing\assertType('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\Bird|Schema\\Fish>', $client->call('GET /pets'));
\PHPStan\Testing\assertType('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\Bird|Schema\\Fish>', $client->call('LIST /pets'));
\PHPStan\Testing\assertType('\\ApiClients\\Tools\\OpenApiClient\\Utils\\Response\\WithoutBody', $client->call('POST /pets'));
\PHPStan\Testing\assertType('iterable<int,Schema\\Cat>', $client->call('GET /pets/gatos'));
\PHPStan\Testing\assertType('iterable<int,Schema\\Cat>', $client->call('LIST /pets/gatos'));
\PHPStan\Testing\assertType('iterable<int,string>', $client->call('GET /pets/names'));
\PHPStan\Testing\assertType('iterable<int,string>', $client->call('LIST /pets/names'));
\PHPStan\Testing\assertType('Schema\\Cat|Schema\\Dog|Schema\\Bird|Schema\\Fish', $client->call('GET /pets/{petId}'));
