<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use \cebe\openapi\spec\Schema;

final class SchemaRegistry
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

    public function addClassName(string $className, Schema $schema): void
    {
        $this->splHash[spl_object_hash($schema)] = $className;
        $this->json[json_encode($schema->getSerializableData())] = $className;
    }

    public function get(\cebe\openapi\spec\Schema $schema, string $fallbackName = ''): string
    {
        $hash = spl_object_hash($schema);
        if (array_key_exists($hash, $this->splHash)) {
            return $this->splHash[$hash];
        }

        $json = json_encode($schema->getSerializableData());
        if (array_key_exists($json, $this->json)) {
            return $this->json[$json];
        }

        $name = $fallbackName === '' ? 'c_' . md5($json) : $fallbackName;
        $className = $fallbackName === '' ? Generator::className('Unknown\C_' . md5($json)) : $fallbackName;
        $this->unknownSchemas[$hash] = [
            'name' => $name,
            'className' => $className,
            'schema' => $schema,
        ];

        return $className;
    }

    public function unknownSchemas(): iterable
    {
        yield from $this->unknownSchemas;
    }
}
