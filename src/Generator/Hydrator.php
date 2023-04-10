<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Hydrator
{
    /**
     * @param array<string, string> $operations
     * @return iterable<Node>
     */
    public static function generate(string $pathPrefix, string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator $hydrator): iterable
    {
        $schemaClasses = [];

        foreach ($hydrator->schemas as $schema) {
            $schemaClasses[] = trim($namespace, '\\') . '\\Schema\\' . $schema->className;
        }

        if (count($schemaClasses) > 0) {
            yield new File(
                $pathPrefix,
                '\\Hydrator\\' . $hydrator->className,
                (new ObjectMapperCodeGenerator())->dump(
                    array_unique(
                        array_filter(
                            array_map(
                                static fn(string $className): string => str_replace('/', '\\', $className),
                                $schemaClasses,
                            ),
                            static fn(string $className): bool => count((new \ReflectionMethod($className, '__construct'))->getParameters()) > 0,
                        )
                    ),
                    trim($namespace, '\\') . '\\Hydrator\\' . $hydrator->className
                )
            );
        }
    }
}
