<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use InvalidArgumentException;
use OpenAPITools\Configuration;
use OpenAPITools\Contract\Package;

use function sprintf;

final class ConfigurationPackage
{
    public static function unwrap(Package $package): Configuration\Package
    {
        if (! $package instanceof Configuration\Package) {
            throw new InvalidArgumentException(sprintf(
                'Expected %s, got %s.',
                Configuration\Package::class,
                $package::class,
            ));
        }

        return $package;
    }
}
