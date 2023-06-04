<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use ReflectionMethod;

use function array_filter;
use function array_unique;
use function count;
use function trim;

final class Hydrator
{
    /** @return iterable<File> */
    public static function generate(string $pathPrefix, \ApiClients\Tools\OpenApiClientGenerator\Representation\Hydrator $hydrator): iterable
    {
        $schemaClasses = [];

        foreach ($hydrator->schemas as $schema) {
            $schemaClasses[] = trim($schema->className->fullyQualified->source, '\\');
        }

        if (count($schemaClasses) <= 0) {
            return;
        }

        yield new File(
            $pathPrefix,
            $hydrator->className->relative,
            (new ObjectMapperCodeGenerator())->dump(
                array_unique(
                    array_filter(
                        $schemaClasses,
                        static fn (string $className): bool => count((new ReflectionMethod($className, '__construct'))->getParameters()) > 0,
                    ),
                ),
                trim($hydrator->className->fullyQualified->source, '\\'),
            ),
        );
    }
}
