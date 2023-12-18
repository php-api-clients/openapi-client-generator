<?php

declare (strict_types=1);
namespace ApiClients\Tests\Client\PetStore\Internal\Operation\Pets\Kinds;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
/**
 * @covers \ApiClients\Client\PetStore\Internal\Operation\Pets\Kinds\WalkingListing
 */
final class WalkingListingTest extends \WyriHaximus\AsyncTestUtilities\AsyncTestCase
{
    /**
     * @test
     */
    public function call_httpCode_default_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(999, array('Content-Type' => 'application/json'), json_encode(json_decode(Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/kinds/walking?page=1&per_page=8', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->call(Internal\Operation\Pets\Kinds\WalkingListing::OPERATION_MATCH, (static function (array $data) : array {
            $data['per_page'] = 8;
            $data['page'] = 1;
            return $data;
        })(array()));
        foreach ($result as $item) {
        }
    }
    /**
     * @test
     */
    public function operations_httpCode_default_responseContentType_application_json_zero()
    {
        $response = new \React\Http\Message\Response(999, array('Content-Type' => 'application/json'), json_encode(json_decode(Schema\Error::SCHEMA_EXAMPLE_DATA, true)));
        $auth = $this->prophesize(\ApiClients\Contracts\HTTP\Headers\AuthenticationInterface::class);
        $auth->authHeader(\Prophecy\Argument::any())->willReturn('Bearer beer')->shouldBeCalled();
        $browser = $this->prophesize(\React\Http\Browser::class);
        $browser->withBase(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->withFollowRedirects(\Prophecy\Argument::any())->willReturn($browser->reveal());
        $browser->request('GET', '/pets/kinds/walking?page=1&per_page=8', \Prophecy\Argument::type('array'), \Prophecy\Argument::any())->willReturn(\React\Promise\resolve($response))->shouldBeCalled();
        $client = new \ApiClients\Client\PetStore\Client($auth->reveal(), $browser->reveal());
        $result = $client->operations()->petsKinds()->walkingListing(8, 1);
        foreach ($result as $item) {
        }
    }
}
