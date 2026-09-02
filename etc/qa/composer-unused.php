<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static fn (Configuration $config): Configuration => $config
    ->addNamedFilter(NamedFilter::fromString('api-clients/github'))
    ->addNamedFilter(NamedFilter::fromString('delight-im/random'))
    ->addNamedFilter(NamedFilter::fromString('react/async'))
    ->addNamedFilter(NamedFilter::fromString('ringcentral/psr7'))
    ->addNamedFilter(NamedFilter::fromString('twig/twig'))
    ->addNamedFilter(NamedFilter::fromString('wyrihaximus/react-awaitable-observable'));
