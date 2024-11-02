<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Contract;

/**
 * @property string $id
 * @property string $name
 * @property bool $indoor
 * @property \ApiClients\Client\PetStore\Schema\Cat\Features $features
 * @property array<\ApiClients\Client\PetStore\Schema\RedEyes|\ApiClients\Client\PetStore\Schema\BlueEyes|\ApiClients\Client\PetStore\Schema\GreenEyes|\ApiClients\Client\PetStore\Schema\YellowEyes|\ApiClients\Client\PetStore\Schema\BlackEyes> $eyes
 */
interface Cat
{
}
