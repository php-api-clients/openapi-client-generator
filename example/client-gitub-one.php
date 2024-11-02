<?php

declare(strict_types=1);

use ApiClients\Tools\OpenApiClientGenerator\Generator;
use OpenAPITools\Configuration\Configuration;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Configuration\Package;
use OpenAPITools\Configuration\State;
use OpenAPITools\Utils\Namespace_;
use PhpParser\BuilderFactory;

$builderFactory = new BuilderFactory();

return new Configuration(
    new State(
        'etc/state.json',
    ),
    new Gathering(
//        'https://raw.githubusercontent.com/github/rest-api-description/main/descriptions-next/api.github.com/api.github.com.yaml',
        'api.github.com.yaml',
        null,
        new Gathering\Schemas(
            true,
            true,
        )
    ),
    [
        new Package(
            new Package\Metadata(
                'GitHub',
                'Fully type safe generated GitHub REST API client',
                [],
            ),
            'api-clients',
            'github',
            'git@github.com:php-api-clients/gitHub.git',
            'v0.2.x',
            null,
            new Package\Templates(
                __DIR__ . '/templates',
                [],
            ),
            new Package\Destination(
                'generated/gitHub',
                'src',
                'tests',
            ),
            new Namespace_(
                'ApiClients\Client\GitHub',
                'ApiClients\Tests\Client\GitHub',
            ),
            new Package\QA(
                phpcs: new Package\QA\Tool(true, null),
                phpstan: new Package\QA\Tool(
                    true,
                    'etc/phpstan-extension.neon',
                ),
                psalm: new Package\QA\Tool(false, null),
            ),
            new Package\State(
                [
                    'composer.json',
                    'composer.lock',
                ],
            ),
            [
                new OpenAPITools\Generator\Schema\Schema($builderFactory),
                new OpenAPITools\Generator\Hydrator\Hydrator($builderFactory),
                new OpenAPITools\Generator\Templates\Templates(),
                new Generator\Paths($builderFactory, true, true),
                new Generator\ClientInterface($builderFactory, true, true),
                new Generator\Client($builderFactory, true, true),
            ]
        ),
    ],
);
