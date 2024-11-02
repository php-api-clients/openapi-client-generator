<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Internal\Operation\Pets;

#[\PHPUnit\Framework\Attributes\CoversClass(\ApiClients\Client\PetStore\Internal\Operation\Pets\Names::class)]
final class NamesTest extends \WyriHaximus\AsyncTestUtilities\AsyncTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_200_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\Pets\Names::OPERATION_MATCH, (static function (array $data): array {
            $data['per_page'] = 8;
            $data['page'] = 1;
            return $data;
        })([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_200_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->pets()->names(8, 1);
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_200_responseContentType_application_json_one(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\Pets\Names::OPERATION_MATCH, (static function (array $data): array {
            $data['per_page'] = 8;
            $data['page'] = 1;
            return $data;
        })([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_200_responseContentType_application_json_one(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->pets()->names(8, 1);
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_default_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\Pets\Names::OPERATION_MATCH, (static function (array $data): array {
            $data['per_page'] = 8;
            $data['page'] = 1;
            return $data;
        })([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_default_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/names?page=1&per_page=8', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->pets()->names(8, 1);
    }
}

