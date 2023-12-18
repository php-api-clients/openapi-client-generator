<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Registry;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;
use ApiClients\Tools\OpenApiClientGenerator\Representation\PropertyType;

final class CompositSchema
{
    /** @var array<string, string> */
    private array $splHash = [];

    public function __construct(
        private readonly Namespace_ $baseNamespaces,
    ) {
    }

    public function get(PropertyType $propertyType): void
    {
    }

    /** @return iterable<UnknownSchema> */
    public function list(): iterable
    {
        $unknownSchemas       = $this->unknownSchemas;
        $this->unknownSchemas = [];

        yield from $unknownSchemas;
    }
}
