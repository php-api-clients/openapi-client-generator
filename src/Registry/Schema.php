<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use ApiClients\Tools\OpenApiClientGenerator\Utils;
use \cebe\openapi\spec\Schema as openAPISchema;

final class Schema
{
    /**
     * @var array<string, class-string>
     */
    private array $splHash = [];
    /**
     * @var array<string, class-string>
     */
    private array $json = [];

    /**
     * @var array<string,array<{name: string, className: string, schema: Schema}>>
     */
    private array $unknownSchemas = [];

    /**
     * @var array<string, class-string>
     */
    private array $unknownSchemasJson = [];

    private bool $allowDuplicatedSchemas = false;

    public function setAllowDuplicatedSchemas(bool $allow): void
    {
        $this->allowDuplicatedSchemas = $allow;
    }

    public function addClassName(string $className, openAPISchema $schema): void
    {
        if ($schema->type === 'array') {
            $schema = $schema->items;
        }
        $className = Utils::className($className);
        $this->splHash[spl_object_hash($schema)] = $className;
        $this->json[json_encode($schema->getSerializableData())] = $className;
    }

    public function get(openAPISchema $schema, string $fallbackName): string
    {
        if ($schema->type === 'array') {
            $schema = $schema->items;
        }
        $hash = spl_object_hash($schema);
        if (array_key_exists($hash, $this->splHash)) {
            return $this->splHash[$hash];
        }

        $json = json_encode($schema->getSerializableData());
        if (!$this->allowDuplicatedSchemas && array_key_exists($json, $this->json)) {
            return $this->json[$json];
        }
        if (!$this->allowDuplicatedSchemas && array_key_exists($json, $this->unknownSchemasJson)) {
            return $this->unknownSchemasJson[$json];
        }

        $className = Utils::fixKeyword($fallbackName);
        $suffix = 'a';
        while (array_key_exists($className, $this->unknownSchemas)) {
            $className = Utils::fixKeyword($fallbackName . strtoupper($suffix++));
        }
        $this->splHash[spl_object_hash($schema)] = $className;
        $this->unknownSchemasJson[$json] = $className;
        $this->unknownSchemas[$className] = [
            'name' => $fallbackName,
            'className' => $className,
            'schema' => $schema,
        ];

        return $className;
    }

    public function hasUnknownSchemas(): bool
    {
        return count($this->unknownSchemas) > 0;
    }

    public function unknownSchemas(): iterable
    {
        $unknownSchemas = $this->unknownSchemas;
        $this->unknownSchemas = [];
        yield from $unknownSchemas;
    }
}
