<?php

declare(strict_types=1);

use ApiClients\Tools\OpenApiClientGenerator\ConfigurationFactory;

return ConfigurationFactory::fromYamlFile(__DIR__ . '/openapi-client-petstore.yaml');
