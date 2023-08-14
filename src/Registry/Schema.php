<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use ApiClients\Tools\OpenApiClientGenerator\ClassString;
use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use cebe\openapi\spec\Schema as openAPISchema;
use RuntimeException;
use Safe\Exceptions\JsonException;

use function array_key_exists;
use function count;
use function Safe\json_encode;
use function spl_object_hash;
use function strtoupper;

final class Schema
{
    /** @var array<string, string> */
    private array $splHash = [];
    /** @var array<string, string> */
    private array $json = [];

    /** @var array<string, UnknownSchema> */
    private array $unknownSchemas = [];

    /** @var array<string, string> */
    private array $unknownSchemasJson = [];
    /** @var array<string, array<ClassString>> */
    private array $aliasses = [];

    public function __construct(
        private readonly Namespace_ $baseNamespaces,
        private readonly bool $allowDuplicatedSchemas,
        private readonly bool $useAliasesForDuplication,
    ) {
    }

    public function addClassName(string $className, openAPISchema $schema): void
    {
        if ($schema->type === 'array') {
            $schema = $schema->items;
        }

        if (! $schema instanceof openAPISchema) {
            throw new RuntimeException('Schemas has to be instance of: ' . openAPISchema::class);
        }

        $className                                               = Utils::className($className);
        $this->splHash[spl_object_hash($schema)]                 = $className;
        $this->json[json_encode($schema->getSerializableData())] = $className;
    }

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

        $json = json_encode($schema->getSerializableData());
        if (! $this->allowDuplicatedSchemas && array_key_exists($json, $this->json)) {
            return $this->json[$json];
        }

        if (! $this->allowDuplicatedSchemas && array_key_exists($json, $this->unknownSchemasJson)) {
            return $this->unknownSchemasJson[$json];
        }

        $className = Utils::fixKeyword($fallbackName);

        if ($this->allowDuplicatedSchemas && $this->useAliasesForDuplication && array_key_exists($json, $this->json)) {
            $this->aliasses['Schema\\' . $this->json[$json]][] = ClassString::factory($this->baseNamespaces, 'Schema\\' . $className);

            return $className;
        }

        if ($this->allowDuplicatedSchemas && $this->useAliasesForDuplication && array_key_exists($json, $this->unknownSchemasJson)) {
            $this->aliasses['Schema\\' . $this->unknownSchemasJson[$json]][] = ClassString::factory($this->baseNamespaces, 'Schema\\' . $className);

            return $className;
        }

        $suffix = 'a';
        while (array_key_exists($className, $this->unknownSchemas)) {
            $className = Utils::fixKeyword($fallbackName . strtoupper($suffix++));
        }

        $this->splHash[spl_object_hash($schema)] = $className;
        $this->unknownSchemasJson[$json]         = $className;
        $this->unknownSchemas[$className]        = new UnknownSchema($fallbackName, $className, $schema);

        return $className;
    }

    public function hasUnknownSchemas(): bool
    {
        return count($this->unknownSchemas) > 0;
    }

    /** @return iterable<UnknownSchema> */
    public function unknownSchemas(): iterable
    {
        $unknownSchemas       = $this->unknownSchemas;
        $this->unknownSchemas = [];

        yield from $unknownSchemas;
    }

    /** @return iterable<ClassString> */
    public function aliasesForClassName(string $classname): iterable
    {
        if (! array_key_exists($classname, $this->aliasses)) {
            return;
        }

        yield from $this->aliasses[$classname];
    }
}
