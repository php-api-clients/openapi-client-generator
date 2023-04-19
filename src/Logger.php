<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use Monolog\Handler\StdoutHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WyriHaximus\Monolog\Factory;

final class Logger
{
    public static function create(): LoggerInterface
    {
        $logger = Factory::create('openapi-client-generator', new NullLogger(), []);
        $logger->pushHandler(new StdoutHandler());

        return $logger;
    }
}
