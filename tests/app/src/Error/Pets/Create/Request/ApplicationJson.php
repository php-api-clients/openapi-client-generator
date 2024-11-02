<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Error\Pets\Create\Request;

final class ApplicationJson extends \Error
{
    public function __construct(public int $status, public \ApiClients\Client\PetStore\Schema\Pets\Create\Request\ApplicationJson $error)
    {
    }
}
