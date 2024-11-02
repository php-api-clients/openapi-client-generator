<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error\Cat;

final class Features extends \Error
{
    public function __construct(public int $status, public \ApiClients\Client\PetStore\Schema\Cat\Features $error)
    {
    }
}
