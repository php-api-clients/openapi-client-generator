<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\Router;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClass;
use Jawira\CaseConverter\Convert;

final class Routers
{
    /** @var array<string, array<string, array<string, array<>>> */
    private array $operations = [];

    public function add(
        string $method,
        string $group,
        string $name,
        array $nodes,
    ): Router {
        $this->operations[$method][$group][$name] = $nodes;

        return $this->createClassName($method, $group, $name);
    }

    /**
     * @return iterable<RouterClass>
     */
    public function get(): iterable
    {
        foreach ($this->operations as $method => $groups) {
            foreach ($groups as $group => $methods) {
                yield new RouterClass(
                    $method,
                    $group,
                    $methods,
                );
            }
        }
    }

    public function createClassName(
        string $method,
        string $group,
        string $name,
    ): Router {
        return new Router(
            'Router\\' . (new Convert($method))->toPascal() . '\\' . (new Convert($group))->toPascal(),
            (new Convert($name))->toCamel(),
        );
    }
}
