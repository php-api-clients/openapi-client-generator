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
final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper
{
    private ?Hydrator\Operation\Pets $operation🌀Pets = null;
    private ?Hydrator\Operation\Pets\Gatos $operation🌀Pets🌀Gatos = null;
    private ?Hydrator\Operation\Pets\Names $operation🌀Pets🌀Names = null;
    private ?Hydrator\Operation\Pets\PetId $operation🌀Pets🌀PetId = null;
    public function hydrateObject(string $className, array $payload) : object
    {
        return match ($className) {
            '\\ApiClients\\Client\\PetStore\\Schema\\Error' => $this->getObjectMapperOperation🌀Pets()->hydrateObject($className, $payload),
        };
    }
    public function hydrateObjects(string $className, iterable $payloads) : \EventSauce\ObjectHydrator\IterableList
    {
        return new \EventSauce\ObjectHydrator\IterableList($this->doHydrateObjects($className, $payloads));
    }
    private function doHydrateObjects(string $className, iterable $payloads) : \Generator
    {
        foreach ($payloads as $index => $payload) {
            (yield $index => $this->hydrateObject($className, $payload));
        }
    }
    public function serializeObject(object $object) : mixed
    {
        return $this->serializeObjectOfType($object, $object::class);
    }
    public function serializeObjectOfType(object $object, string $className) : mixed
    {
        return match ($className) {
            '\\ApiClients\\Client\\PetStore\\Schema\\Error' => $this->getObjectMapperOperation🌀Pets()->serializeObject($object),
        };
    }
    public function serializeObjects(iterable $payloads) : \EventSauce\ObjectHydrator\IterableList
    {
        return new \EventSauce\ObjectHydrator\IterableList($this->doSerializeObjects($payloads));
    }
    private function doSerializeObjects(iterable $objects) : \Generator
    {
        foreach ($objects as $index => $object) {
            (yield $index => $this->serializeObject($object));
        }
    }
    public function getObjectMapperOperation🌀Pets() : Hydrator\Operation\Pets
    {
        if ($this->operation🌀Pets instanceof Hydrator\Operation\Pets === false) {
            $this->operation🌀Pets = new Hydrator\Operation\Pets();
        }
        return $this->operation🌀Pets;
    }
    public function getObjectMapperOperation🌀Pets🌀Gatos() : Hydrator\Operation\Pets\Gatos
    {
        if ($this->operation🌀Pets🌀Gatos instanceof Hydrator\Operation\Pets\Gatos === false) {
            $this->operation🌀Pets🌀Gatos = new Hydrator\Operation\Pets\Gatos();
        }
        return $this->operation🌀Pets🌀Gatos;
    }
    public function getObjectMapperOperation🌀Pets🌀Names() : Hydrator\Operation\Pets\Names
    {
        if ($this->operation🌀Pets🌀Names instanceof Hydrator\Operation\Pets\Names === false) {
            $this->operation🌀Pets🌀Names = new Hydrator\Operation\Pets\Names();
        }
        return $this->operation🌀Pets🌀Names;
    }
    public function getObjectMapperOperation🌀Pets🌀PetId() : Hydrator\Operation\Pets\PetId
    {
        if ($this->operation🌀Pets🌀PetId instanceof Hydrator\Operation\Pets\PetId === false) {
            $this->operation🌀Pets🌀PetId = new Hydrator\Operation\Pets\PetId();
        }
        return $this->operation🌀Pets🌀PetId;
    }
}
