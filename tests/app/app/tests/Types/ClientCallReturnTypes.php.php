<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Types;

$client = new \ApiClients\Client\PetStore\Client(new class implements \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface
{
    public function authHeader(): string
    {
        return 'Saturn V';
    }
}, new \React\Http\Browser());
\PHPStan\Testing\assertType('iterable<int, \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider>|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets'));
\PHPStan\Testing\assertType('\ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody', $client->call('POST /pets'));
\PHPStan\Testing\assertType('iterable<int, \ApiClients\Client\PetStore\Schema\Cat>|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets/gatos'));
\PHPStan\Testing\assertType('iterable<int, \ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets/kinds/walking'));
\PHPStan\Testing\assertType('\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets/groupedByType'));
\PHPStan\Testing\assertType('iterable<int, string>|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets/names'));
\PHPStan\Testing\assertType('\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error', $client->call('GET /pets/{petId}'));

