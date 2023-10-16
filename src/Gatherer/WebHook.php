<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Header;
use cebe\openapi\spec\PathItem;
use RuntimeException;

use function explode;
use function iterator_to_array;
use function property_exists;
use function Safe\preg_replace;
use function time;
use function ucfirst;

final class WebHook
{
    public static function gather(
        Namespace_ $baseNamespace,
        PathItem $webhook,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook {
        if ($webhook->post?->requestBody === null && ! property_exists($webhook->post->requestBody, 'content')) {
            throw new RuntimeException('Missing request body content to deal with');
        }

        [$event] = explode('/', $webhook->post->operationId);

        $headers = [];
        foreach ($webhook->post->parameters ?? [] as $header) {
            if ($header->in !== 'header') {
                continue;
            }

            $headers[] = new Header($header->name, Schema::gather(
                $baseNamespace,
                $schemaRegistry->get(
                    $header->schema,
                    'WebHookHeader\\' . ucfirst(preg_replace('/\PL/u', '', $header->name)),
                ),
                $header->schema,
                $schemaRegistry,
            ), ExampleData::determiteType($header->example));
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook(
            $event,
            $webhook->post->summary ?? '',
            $webhook->post->description ?? '',
            $webhook->post->operationId,
            $webhook->post->externalDocs->url ?? '',
            $headers,
            iterator_to_array((static function (array $content, SchemaRegistry $schemaRegistry, Namespace_ $baseNamespace): iterable {
                foreach ($content as $type => $schema) {
                    yield $type => Schema::gather(
                        $baseNamespace,
                        $schemaRegistry->get($schema->schema, 'T' . time()),
                        $schema->schema,
                        $schemaRegistry,
                    );
                }
            })($webhook->post->requestBody->content, $schemaRegistry, $baseNamespace)),
        );
    }
}
