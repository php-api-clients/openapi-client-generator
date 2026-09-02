<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error;

final class Legs extends \Error
{
    public function __construct(public int $status, public \ApiClients\Client\PetStore\Schema\Legs $error)
    {
    }
}
