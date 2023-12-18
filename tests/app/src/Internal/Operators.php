<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Operators
{
    private ?Internal\Operator\Pets\List_ $pets👷List_ = null;
    private ?Internal\Operator\Pets\ListListing $pets👷ListListing = null;
    private ?Internal\Operator\Pets\Create $pets👷Create = null;
    private ?Internal\Operator\Pets\List_\Gatos $pets👷List_👷Gatos = null;
    private ?Internal\Operator\Pets\List_\GatosListing $pets👷List_👷GatosListing = null;
    private ?Internal\Operator\Pets\Kinds\Walking $pets👷Kinds👷Walking = null;
    private ?Internal\Operator\Pets\Kinds\WalkingListing $pets👷Kinds👷WalkingListing = null;
    private ?Internal\Operator\Pets\Grouped\By\Type $pets👷Grouped👷By👷Type = null;
    private ?Internal\Operator\Pets\Names $pets👷Names = null;
    private ?Internal\Operator\Pets\NamesListing $pets👷NamesListing = null;
    private ?Internal\Operator\ShowPetById $showPetById = null;
    public function __construct(private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \React\Http\Browser $browser, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Internal\Hydrators $hydrators)
    {
    }
    public function pets👷List_() : Internal\Operator\Pets\List_
    {
        if ($this->pets👷List_ instanceof Internal\Operator\Pets\List_ === false) {
            $this->pets👷List_ = new Internal\Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷List_;
    }
    public function pets👷ListListing() : Internal\Operator\Pets\ListListing
    {
        if ($this->pets👷ListListing instanceof Internal\Operator\Pets\ListListing === false) {
            $this->pets👷ListListing = new Internal\Operator\Pets\ListListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷ListListing;
    }
    public function pets👷Create() : Internal\Operator\Pets\Create
    {
        if ($this->pets👷Create instanceof Internal\Operator\Pets\Create === false) {
            $this->pets👷Create = new Internal\Operator\Pets\Create($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷Create;
    }
    public function pets👷List_👷Gatos() : Internal\Operator\Pets\List_\Gatos
    {
        if ($this->pets👷List_👷Gatos instanceof Internal\Operator\Pets\List_\Gatos === false) {
            $this->pets👷List_👷Gatos = new Internal\Operator\Pets\List_\Gatos($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->pets👷List_👷Gatos;
    }
    public function pets👷List_👷GatosListing() : Internal\Operator\Pets\List_\GatosListing
    {
        if ($this->pets👷List_👷GatosListing instanceof Internal\Operator\Pets\List_\GatosListing === false) {
            $this->pets👷List_👷GatosListing = new Internal\Operator\Pets\List_\GatosListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->pets👷List_👷GatosListing;
    }
    public function pets👷Kinds👷Walking() : Internal\Operator\Pets\Kinds\Walking
    {
        if ($this->pets👷Kinds👷Walking instanceof Internal\Operator\Pets\Kinds\Walking === false) {
            $this->pets👷Kinds👷Walking = new Internal\Operator\Pets\Kinds\Walking($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Kinds🌀Walking());
        }
        return $this->pets👷Kinds👷Walking;
    }
    public function pets👷Kinds👷WalkingListing() : Internal\Operator\Pets\Kinds\WalkingListing
    {
        if ($this->pets👷Kinds👷WalkingListing instanceof Internal\Operator\Pets\Kinds\WalkingListing === false) {
            $this->pets👷Kinds👷WalkingListing = new Internal\Operator\Pets\Kinds\WalkingListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Kinds🌀Walking());
        }
        return $this->pets👷Kinds👷WalkingListing;
    }
    public function pets👷Grouped👷By👷Type() : Internal\Operator\Pets\Grouped\By\Type
    {
        if ($this->pets👷Grouped👷By👷Type instanceof Internal\Operator\Pets\Grouped\By\Type === false) {
            $this->pets👷Grouped👷By👷Type = new Internal\Operator\Pets\Grouped\By\Type($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀GroupedByType());
        }
        return $this->pets👷Grouped👷By👷Type;
    }
    public function pets👷Names() : Internal\Operator\Pets\Names
    {
        if ($this->pets👷Names instanceof Internal\Operator\Pets\Names === false) {
            $this->pets👷Names = new Internal\Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        }
        return $this->pets👷Names;
    }
    public function pets👷NamesListing() : Internal\Operator\Pets\NamesListing
    {
        if ($this->pets👷NamesListing instanceof Internal\Operator\Pets\NamesListing === false) {
            $this->pets👷NamesListing = new Internal\Operator\Pets\NamesListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        }
        return $this->pets👷NamesListing;
    }
    public function showPetById() : Internal\Operator\ShowPetById
    {
        if ($this->showPetById instanceof Internal\Operator\ShowPetById === false) {
            $this->showPetById = new Internal\Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId());
        }
        return $this->showPetById;
    }
}
