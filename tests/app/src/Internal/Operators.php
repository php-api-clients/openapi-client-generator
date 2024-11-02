<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal;

final class Operators
{
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\List_ $pets👷List_ = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\Create $pets👷Create = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\List_\Gatos $pets👷List👷Gatos = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds\Walking $pets👷Kinds👷Walking = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\Grouped\By\Type $pets👷Grouped👷By👷Type = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\Pets\Names $pets👷Names = null;
    private ?\ApiClients\Client\PetStore\Internal\Operator\ShowPetById $showPetById = null;
    public function __construct(private readonly \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private readonly \React\Http\Browser $browser, private readonly \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private readonly \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private readonly Hydrators $hydrators)
    {
    }
    public function pets👷List_(): \ApiClients\Client\PetStore\Internal\Operator\Pets\List_
    {
        if ($this->pets👷List_ instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\List_ === false) {
            $this->pets👷List_ = new \ApiClients\Client\PetStore\Internal\Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷List_;
    }
    public function pets👷Create(): \ApiClients\Client\PetStore\Internal\Operator\Pets\Create
    {
        if ($this->pets👷Create instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\Create === false) {
            $this->pets👷Create = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Create($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷Create;
    }
    public function pets👷List👷Gatos(): \ApiClients\Client\PetStore\Internal\Operator\Pets\List_\Gatos
    {
        if ($this->pets👷List👷Gatos instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\List_\Gatos === false) {
            $this->pets👷List👷Gatos = new \ApiClients\Client\PetStore\Internal\Operator\Pets\List_\Gatos($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->pets👷List👷Gatos;
    }
    public function pets👷Kinds👷Walking(): \ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds\Walking
    {
        if ($this->pets👷Kinds👷Walking instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds\Walking === false) {
            $this->pets👷Kinds👷Walking = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Kinds\Walking($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Kinds🌀Walking());
        }
        return $this->pets👷Kinds👷Walking;
    }
    public function pets👷Grouped👷By👷Type(): \ApiClients\Client\PetStore\Internal\Operator\Pets\Grouped\By\Type
    {
        if ($this->pets👷Grouped👷By👷Type instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\Grouped\By\Type === false) {
            $this->pets👷Grouped👷By👷Type = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Grouped\By\Type($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀GroupedByType());
        }
        return $this->pets👷Grouped👷By👷Type;
    }
    public function pets👷Names(): \ApiClients\Client\PetStore\Internal\Operator\Pets\Names
    {
        if ($this->pets👷Names instanceof \ApiClients\Client\PetStore\Internal\Operator\Pets\Names === false) {
            $this->pets👷Names = new \ApiClients\Client\PetStore\Internal\Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        }
        return $this->pets👷Names;
    }
    public function showPetById(): \ApiClients\Client\PetStore\Internal\Operator\ShowPetById
    {
        if ($this->showPetById instanceof \ApiClients\Client\PetStore\Internal\Operator\ShowPetById === false) {
            $this->showPetById = new \ApiClients\Client\PetStore\Internal\Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Petid());
        }
        return $this->showPetById;
    }
}
