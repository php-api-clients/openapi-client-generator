<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use PhpParser\Builder\Param;
use PhpParser\Node;

final class PromotedPropertyAsParam extends Param
{
    /**
     * Returns the built parameter node.
     *
     * @return Node\Param The built parameter node
     */
    public function getNode() : Node {
        return new Node\Param(
            new Node\Expr\Variable($this->name),
            $this->default, $this->type, $this->byRef, $this->variadic, [], Node\Stmt\Class_::MODIFIER_PUBLIC, $this->attributeGroups
        );
    }
}
