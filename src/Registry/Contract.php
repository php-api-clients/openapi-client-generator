<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as openAPISchema;
use RuntimeException;
use Safe\Exceptions\JsonException;

use function array_key_exists;
use function count;
use function spl_object_hash;
use function strtoupper;

final class Contract
{
    /** @var array<string, string> */
    private array $splHash = [];

    /** @var array<string, UnknownSchema> */
    private array $unknownSchemas = [];

    /** @throws JsonException */
    public function get(openAPISchema $schema, string $fallbackName): string
    {
        if ($schema->type === 'array') {
            $schema = $schema->items;
        }

        if (! $schema instanceof openAPISchema) {
            throw new RuntimeException('Schemas has to be instance of: ' . openAPISchema::class);
        }

        $hash = spl_object_hash($schema);
        if (array_key_exists($hash, $this->splHash)) {
            return $this->splHash[$hash];
        }

        $className = Utils::fixKeyword($fallbackName);

        $suffix = 'a';
        while (array_key_exists($className, $this->unknownSchemas)) {
            $className = Utils::fixKeyword($fallbackName . strtoupper($suffix++));
        }

        $this->splHash[spl_object_hash($schema)] = $className;
        $this->unknownSchemas[$className]        = new UnknownSchema($fallbackName, $className, $schema);

        return $className;
    }

    public function hasContracts(): bool
    {
        return count($this->unknownSchemas) > 0;
    }

    /** @return iterable<UnknownSchema> */
    public function contracts(): iterable
    {
        $unknownSchemas       = $this->unknownSchemas;
        $this->unknownSchemas = [];

        yield from $unknownSchemas;
    }
}
