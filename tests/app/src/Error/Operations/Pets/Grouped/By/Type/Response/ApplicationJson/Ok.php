<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error\Operations\Pets\Grouped\By\Type\Response\ApplicationJson;

final class Ok extends \Error
{
    public function __construct(public int $status, public \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok $error)
    {
    }
}
