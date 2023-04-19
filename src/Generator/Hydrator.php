<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use PhpParser\Node;
use ReflectionMethod;

use function array_filter;
use function array_map;
use function array_unique;
use function count;
use function str_replace;
use function trim;

final class Hydrator
{
    /**
     * @param array<string, string> $operations
     *
     * @return iterable<Node>
     */
    public static function generate(string $pathPrefix, string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator $hydrator): iterable
    {
        $schemaClasses = [];

        foreach ($hydrator->schemas as $schema) {
            $schemaClasses[] = trim($namespace, '\\') . '\\Schema\\' . $schema->className;
        }

        if (count($schemaClasses) <= 0) {
            return;
        }

        yield new File(
            $pathPrefix,
            '\\Hydrator\\' . $hydrator->className,
            (new ObjectMapperCodeGenerator())->dump(
                array_unique(
                    array_filter(
                        array_map(
                            static fn (string $className): string => str_replace('/', '\\', $className),
                            $schemaClasses,
                        ),
                        static fn (string $className): bool => count((new ReflectionMethod($className, '__construct'))->getParameters()) > 0,
                    )
                ),
                trim($namespace, '\\') . '\\Hydrator\\' . $hydrator->className
            )
        );
    }
}
