<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error\HellHound;

final class Eyes extends \Error
{
    public function __construct(public int $status, public \ApiClients\Client\PetStore\Schema\HellHound\Eyes $error)
    {
    }
}
