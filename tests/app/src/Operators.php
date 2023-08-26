<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore;

use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Hydrator;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Operator;
use ApiClients\Client\PetStore\Schema;
use ApiClients\Client\PetStore\WebHook;
use ApiClients\Client\PetStore\Router;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final class Operators
{
    private ?Operator\Pets\List_ $pets👷List_ = null;
    private ?Operator\Pets\ListListing $pets👷ListListing = null;
    private ?Operator\Pets\Create $pets👷Create = null;
    private ?Operator\Pets\List_\Gatos $pets👷List_👷Gatos = null;
    private ?Operator\Pets\List_\GatosListing $pets👷List_👷GatosListing = null;
    private ?Operator\Pets\Names $pets👷Names = null;
    private ?Operator\Pets\NamesListing $pets👷NamesListing = null;
    private ?Operator\ShowPetById $showPetById = null;
    public function __construct(private \ApiClients\Contracts\HTTP\Headers\AuthenticationInterface $authentication, private \React\Http\Browser $browser, private \League\OpenAPIValidation\Schema\SchemaValidator $requestSchemaValidator, private \League\OpenAPIValidation\Schema\SchemaValidator $responseSchemaValidator, private Hydrators $hydrators)
    {
    }
    public function pets👷List_() : Operator\Pets\List_
    {
        if ($this->pets👷List_ instanceof Operator\Pets\List_ === false) {
            $this->pets👷List_ = new Operator\Pets\List_($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷List_;
    }
    public function pets👷ListListing() : Operator\Pets\ListListing
    {
        if ($this->pets👷ListListing instanceof Operator\Pets\ListListing === false) {
            $this->pets👷ListListing = new Operator\Pets\ListListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷ListListing;
    }
    public function pets👷Create() : Operator\Pets\Create
    {
        if ($this->pets👷Create instanceof Operator\Pets\Create === false) {
            $this->pets👷Create = new Operator\Pets\Create($this->browser, $this->authentication, $this->requestSchemaValidator, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets());
        }
        return $this->pets👷Create;
    }
    public function pets👷List_👷Gatos() : Operator\Pets\List_\Gatos
    {
        if ($this->pets👷List_👷Gatos instanceof Operator\Pets\List_\Gatos === false) {
            $this->pets👷List_👷Gatos = new Operator\Pets\List_\Gatos($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->pets👷List_👷Gatos;
    }
    public function pets👷List_👷GatosListing() : Operator\Pets\List_\GatosListing
    {
        if ($this->pets👷List_👷GatosListing instanceof Operator\Pets\List_\GatosListing === false) {
            $this->pets👷List_👷GatosListing = new Operator\Pets\List_\GatosListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Gatos());
        }
        return $this->pets👷List_👷GatosListing;
    }
    public function pets👷Names() : Operator\Pets\Names
    {
        if ($this->pets👷Names instanceof Operator\Pets\Names === false) {
            $this->pets👷Names = new Operator\Pets\Names($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        }
        return $this->pets👷Names;
    }
    public function pets👷NamesListing() : Operator\Pets\NamesListing
    {
        if ($this->pets👷NamesListing instanceof Operator\Pets\NamesListing === false) {
            $this->pets👷NamesListing = new Operator\Pets\NamesListing($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀Names());
        }
        return $this->pets👷NamesListing;
    }
    public function showPetById() : Operator\ShowPetById
    {
        if ($this->showPetById instanceof Operator\ShowPetById === false) {
            $this->showPetById = new Operator\ShowPetById($this->browser, $this->authentication, $this->responseSchemaValidator, $this->hydrators->getObjectMapperOperation🌀Pets🌀PetId());
        }
        return $this->showPetById;
    }
}
