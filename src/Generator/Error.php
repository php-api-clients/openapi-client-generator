<?php

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Client\Github\Schema\WebhookLabelEdited\Changes\Name;
use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Registry\ThrowableSchema;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\spec\Schema as OpenAPiSchema;
use Jawira\CaseConverter\Convert;
use PhpParser\Builder\Param;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Psr\Http\Message\RequestInterface;
use RingCentral\Psr7\Request;

final class Error
{
    /**
     * @return iterable<Node>
     */
    public static function generate(string $namespace, \ApiClients\Tools\OpenApiClientGenerator\Representation\Schema $schema): iterable
    {
        $factory = new BuilderFactory();
        $stmt = $factory->namespace(trim(Utils::dirname($namespace . '\\Error\\' . $schema->className), '\\'));

        $class = $factory->class(trim(Utils::basename($schema->className), '\\'))->extend('\\' . \Error::class)->makeFinal();

        $class->addStmt((new BuilderFactory())->method('__construct')->makePublic()->addParam(
            (new PromotedPropertyAsParam('status'))->setType('int')
        )->addParam(
            (new PromotedPropertyAsParam('error'))->setType('Schema\\' . $schema->className)
        ));


        yield new File($namespace . 'Error\\' . $schema->className, $stmt->addStmt($class)->getNode());
    }
}
