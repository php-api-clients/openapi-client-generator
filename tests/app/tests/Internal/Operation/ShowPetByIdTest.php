<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Internal\Operation;

/**
 * @covers \ApiClients\Client\PetStore\Internal\Operation\ShowPetById
 */
final class ShowPetByIdTest extends \WyriHaximus\AsyncTestUtilities\AsyncTestCase
{
    /**
     * @test
     */
    public function call_httpCode_200_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_200_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    /**
     * @test
     */
    public function call_httpCode_200_responseContentType_application_json_one()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_200_responseContentType_application_json_one()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    /**
     * @test
     */
    public function call_httpCode_200_responseContentType_application_json_two()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_200_responseContentType_application_json_two()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    /**
     * @test
     */
    public function call_httpCode_200_responseContentType_application_json_three()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_200_responseContentType_application_json_three()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    /**
     * @test
     */
    public function call_httpCode_200_responseContentType_application_json_four()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_200_responseContentType_application_json_four()
    {
        $response = new \React\Http\Message\Response(200, ['Content-Type' => 'application/json'], \json_decode('[]', false));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
    /**
     * @test
     */
    public function call_httpCode_default_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\ShowPetById::OPERATION_MATCH, (static function (array $data): array {
            return $data;
        })([]));
    }
    /**
     * @test
     */
    public function operations_httpCode_default_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/{petId}', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->showPetById();
    }
}
