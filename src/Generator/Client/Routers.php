<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Client;

use ApiClients\Client\Github\Schema\WebhookLabelEdited\Changes\Name;
use ApiClients\Contracts\HTTP\Headers\AuthenticationInterface;
use ApiClients\Contracts\OpenAPI\WebHooksInterface;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\Router;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Client\Routers\RouterClass;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Path;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Operation as OpenAPiOperation;
use cebe\openapi\spec\PathItem;
use EventSauce\ObjectHydrator\ObjectMapper;
use Jawira\CaseConverter\Convert;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Request;
use Rx\Observable;
use Rx\Subject\Subject;
use Twig\Node\Expression\Binary\AndBinary;
use Twig\Node\Expression\Binary\OrBinary;
use function React\Promise\resolve;

final class Routers
{
    /**
     * @var array<string, array<string, array<string, array<>>>
     */
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
