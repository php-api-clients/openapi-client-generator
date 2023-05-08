<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator;

use ApiClients\Tools\OpenApiClientGenerator\File;
use ApiClients\Tools\OpenApiClientGenerator\PromotedPropertyAsParam;
use ApiClients\Tools\OpenApiClientGenerator\Representation\Schema;
use PhpParser\BuilderFactory;

final class Error
{
    /**
     * @return iterable<File>
     */
    public static function generate(string $pathPrefix, Schema $schema): iterable
    {
        $factory = new BuilderFactory();
        $stmt    = $factory->namespace($schema->errorClassName->namespace->source);

        $class = $factory->class($schema->errorClassName->className)->extend('\\' . \Error::class)->makeFinal();

        $class->addStmt((new BuilderFactory())->method('__construct')->makePublic()->addParam(
            (new PromotedPropertyAsParam('status'))->setType('int')
        )->addParam(
            (new PromotedPropertyAsParam('error'))->setType($schema->className->relative)
        ));

        yield new File($pathPrefix, $schema->errorClassName->relative, $stmt->addStmt($class)->getNode());
    }
}
