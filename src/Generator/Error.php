<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Utils;
use PhpParser\BuilderFactory;
use PhpParser\Node;

use function trim;

final class Error
{
    /**
     * @return iterable<Node>
     */
    public static function generate(string $pathPrefix, string $namespace, Schema $schema): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace(trim(Utils::dirname($namespace . '\\Error\\' . $schema->className), '\\'));

        $class = $factory->class(trim(Utils::basename($schema->className), '\\'))->extend('\\' . \Error::class)->makeFinal();

        $class->addStmt((new BuilderFactory())->method('__construct')->makePublic()->addParam(
            (new PromotedPropertyAsParam('status'))->setType('int')
        )->addParam(
            (new PromotedPropertyAsParam('error'))->setType('Schema\\' . $schema->className)
        ));

        yield new File($pathPrefix, 'Error\\' . $schema->className, $stmt->addStmt($class)->getNode());
    }
}
