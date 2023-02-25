<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Gatherer;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Header;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationRequestBody;
use ApiClients\Tools\OpenApiClientGenerator\Representation\OperationResponse;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Parameter;
use cebe\openapi\spec\Operation as openAPIOperation;
use cebe\openapi\spec\PathItem;
use Jawira\CaseConverter\Convert;
use Psr\Http\Message\ResponseInterface;

final class WebHook
{
    public static function gather(
        PathItem $webhook,
        SchemaRegistry $schemaRegistry,
    ): \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook {
        [$event] = explode('/', $webhook->post->operationId);

        $headers = [];
        foreach ($webhook->post?->parameters ?? [] as $header) {
            if ($header->in !== 'header') {
                continue;
            }

            $headers[] = new Header($header->name, Schema::gather(
                $schemaRegistry->get(
                    $header->schema,
                    'WebHookHeader\\' . ucfirst(preg_replace('/\PL/u', '', $header->name)),
                ),
                $header->schema,
                $schemaRegistry
            ));
        }

        return new \ApiClients\Tools\OpenApiClientGenerator\Representation\WebHook(
            $event,
            $webhook->post->summary ?? '',
            $webhook->post->description ?? '',
            $webhook->post->operationId,
            $webhook->post->externalDocs?->url ?? '',
            $headers,
            iterator_to_array((static function (array $content, SchemaRegistry $schemaRegistry): iterable {
                foreach ($content as $type => $schema) {
                    yield $type => Schema::gather(
                        $schemaRegistry->get($schema->schema, 'T' . time()),
                        $schema->schema,
                        $schemaRegistry,
                    );
                }
            })($webhook->post->requestBody->content, $schemaRegistry)),
        );
    }
}
