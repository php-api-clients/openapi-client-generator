<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Authentication
{
    public const INTERFACE_NAME = 'AuthenticationInterface';

    /**
     * @return iterable<Node>
     */
    public static function generate(string $namespace): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace($namespace);

        $interface = $factory->interface(self::INTERFACE_NAME)->addStmt(
            $factory->method('authHeader')->makePublic()->setReturnType('string')
        );

        yield new File($namespace . '\\' . self::INTERFACE_NAME, $stmt->addStmt($interface)->getNode());
    }
}
