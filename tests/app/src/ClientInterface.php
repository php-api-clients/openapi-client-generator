<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

interface ClientInterface
{
    // phpcs:disable
    /**
     */
    // phpcs:enabled
    public function call(string $call, array $params = []): Rx\Observable|ApiClients\Client\PetStore\Schema\Error|ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody|ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|ApiClients\Client\PetStore\Schema\Cat|ApiClients\Client\PetStore\Schema\Dog|ApiClients\Client\PetStore\Schema\Bird|ApiClients\Client\PetStore\Schema\Fish|ApiClients\Client\PetStore\Schema\Spider;
    public function operations(): OperationsInterface;
}
