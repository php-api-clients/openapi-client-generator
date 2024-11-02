<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Internal\Operation\Pets;

#[\PHPUnit\Framework\Attributes\CoversClass(\ApiClients\Client\PetStore\Internal\Operation\Pets\Create::class)]
final class CreateTest extends \WyriHaximus\AsyncTestUtilities\AsyncTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_default_requestContentType_application_json_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('POST', '/pets', Prophecy\Argument::type('array'), json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)))->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\Pets\Create::OPERATION_MATCH, (static fn(array $data): array => $data)(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_default_requestContentType_application_json_responseContentType_application_json_zero(): void
    {
        $response = new \React\Http\Message\Response(999, ['Content-Type' => 'application/json'], json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('POST', '/pets', Prophecy\Argument::type('array'), json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)))->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->pets()->create(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function call_httpCode_201_requestContentType_application_json_empty(): void
    {
        $response = new \React\Http\Message\Response(201, []);
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('POST', '/pets', Prophecy\Argument::type('array'), json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)))->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(\ApiClients\Client\PetStore\Internal\Operation\Pets\Create::OPERATION_MATCH, (static fn(array $data): array => $data)(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)));
    }
    #[\PHPUnit\Framework\Attributes\Test]
    public function operations_httpCode_201_requestContentType_application_json_empty(): void
    {
        $response = new \React\Http\Message\Response(201, []);
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('POST', '/pets', Prophecy\Argument::type('array'), json_encode(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true)))->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->pets()->create(json_decode(\ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson::SCHEMA_EXAMPLE_DATA, true));
        self::assertArrayHasKey('code', $result);
        self::assertSame(201, $result['code']);
    }
}

