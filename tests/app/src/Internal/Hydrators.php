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
final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper
{
    private ?Internal\Hydrator\Operation\Pets $operation🌀Pets = null;
    private ?Internal\Hydrator\Operation\Pets\Gatos $operation🌀Pets🌀Gatos = null;
    private ?Internal\Hydrator\Operation\Pets\Kinds\Walking $operation🌀Pets🌀Kinds🌀Walking = null;
    private ?Internal\Hydrator\Operation\Pets\GroupedByType $operation🌀Pets🌀GroupedByType = null;
    private ?Internal\Hydrator\Operation\Pets\Names $operation🌀Pets🌀Names = null;
    private ?Internal\Hydrator\Operation\Pets\PetId $operation🌀Pets🌀PetId = null;
    public function hydrateObject(string $className, array $payload) : object
    {
        return match ($className) {
            '\\ApiClients\\Client\\PetStore\\Schema\\Error' => $this->getObjectMapperOperation🌀Pets()->hydrateObject($className, $payload),
            '\\ApiClients\\Client\\PetStore\\Schema\\Operations\\Pets\\Grouped\\By\\Type\\Response\\ApplicationJson\\Ok' => $this->getObjectMapperOperation🌀Pets🌀GroupedByType()->hydrateObject($className, $payload),
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
            '\\ApiClients\\Client\\PetStore\\Schema\\Operations\\Pets\\Grouped\\By\\Type\\Response\\ApplicationJson\\Ok' => $this->getObjectMapperOperation🌀Pets🌀GroupedByType()->serializeObject($object),
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
    public function getObjectMapperOperation🌀Pets() : Internal\Hydrator\Operation\Pets
    {
        if ($this->operation🌀Pets instanceof Internal\Hydrator\Operation\Pets === false) {
            $this->operation🌀Pets = new Internal\Hydrator\Operation\Pets();
        }
        return $this->operation🌀Pets;
    }
    public function getObjectMapperOperation🌀Pets🌀Gatos() : Internal\Hydrator\Operation\Pets\Gatos
    {
        if ($this->operation🌀Pets🌀Gatos instanceof Internal\Hydrator\Operation\Pets\Gatos === false) {
            $this->operation🌀Pets🌀Gatos = new Internal\Hydrator\Operation\Pets\Gatos();
        }
        return $this->operation🌀Pets🌀Gatos;
    }
    public function getObjectMapperOperation🌀Pets🌀Kinds🌀Walking() : Internal\Hydrator\Operation\Pets\Kinds\Walking
    {
        if ($this->operation🌀Pets🌀Kinds🌀Walking instanceof Internal\Hydrator\Operation\Pets\Kinds\Walking === false) {
            $this->operation🌀Pets🌀Kinds🌀Walking = new Internal\Hydrator\Operation\Pets\Kinds\Walking();
        }
        return $this->operation🌀Pets🌀Kinds🌀Walking;
    }
    public function getObjectMapperOperation🌀Pets🌀GroupedByType() : Internal\Hydrator\Operation\Pets\GroupedByType
    {
        if ($this->operation🌀Pets🌀GroupedByType instanceof Internal\Hydrator\Operation\Pets\GroupedByType === false) {
            $this->operation🌀Pets🌀GroupedByType = new Internal\Hydrator\Operation\Pets\GroupedByType();
        }
        return $this->operation🌀Pets🌀GroupedByType;
    }
    public function getObjectMapperOperation🌀Pets🌀Names() : Internal\Hydrator\Operation\Pets\Names
    {
        if ($this->operation🌀Pets🌀Names instanceof Internal\Hydrator\Operation\Pets\Names === false) {
            $this->operation🌀Pets🌀Names = new Internal\Hydrator\Operation\Pets\Names();
        }
        return $this->operation🌀Pets🌀Names;
    }
    public function getObjectMapperOperation🌀Pets🌀PetId() : Internal\Hydrator\Operation\Pets\PetId
    {
        if ($this->operation🌀Pets🌀PetId instanceof Internal\Hydrator\Operation\Pets\PetId === false) {
            $this->operation🌀Pets🌀PetId = new Internal\Hydrator\Operation\Pets\PetId();
        }
        return $this->operation🌀Pets🌀PetId;
    }
}
