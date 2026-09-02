<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Internal\Operation;

#[\PHPUnit\Framework\Attributes\CoversClass(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::class)]
final class ShowPetByIdTest extends \WyriHaximus\AsyncTestUtilities\AsyncTestCase
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_200_responseContentType_application_json_two(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_200_responseContentType_application_json_two(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_200_responseContentType_application_json_three(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_200_responseContentType_application_json_three(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_200_responseContentType_application_json_four(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_200_responseContentType_application_json_four(): void
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static fn(array $data): array => $data)([]));
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
        $browser->request('GET', '/pets/{petId}', Prophecy\Argument::type('array'), Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
}

