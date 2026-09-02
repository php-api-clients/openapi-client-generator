<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\Internal;

final class Hydrators implements \EventSauce\ObjectHydrator\ObjectMapper
{
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets $operation🌀Pets = null;
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Gatos $operation🌀Pets🌀Gatos = null;
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking $operation🌀Pets🌀Kinds🌀Walking = null;
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType $operation🌀Pets🌀GroupedByType = null;
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Names $operation🌀Pets🌀Names = null;
    private ?\ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Petid $operation🌀Pets🌀Petid = null;
    public function hydrateObject(string $className, array $payload): object
    {
        return match ($className) {
            \ApiClients\Client\PetStore\Schema\Error::class => $this->getObjectMapperOperation🌀Pets()->hydrateObject($className, $payload),
            \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::class => $this->getObjectMapperOperation🌀Pets🌀GroupedByType()->hydrateObject($className, $payload),
        };
    }
    public function hydrateObjects(string $className, iterable $payloads): \EventSauce\ObjectHydrator\IterableList
    {
        return new \EventSauce\ObjectHydrator\IterableList($this->doHydrateObjects($className, $payloads));
    }
    private function doHydrateObjects(string $className, iterable $payloads): \Generator
    {
        foreach ($payloads as $index => $payload) {
            yield $index => $this->hydrateObject($className, $payload);
        }
    }
    public function serializeObject(object $object): mixed
    {
        return $this->serializeObjectOfType($object, $object::class);
    }
    public function serializeObjectOfType(object $object, string $className): mixed
    {
        return match ($className) {
            \ApiClients\Client\PetStore\Schema\Error::class => $this->getObjectMapperOperation🌀Pets()->serializeObject($object),
            \ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok::class => $this->getObjectMapperOperation🌀Pets🌀GroupedByType()->serializeObject($object),
        };
    }
    public function serializeObjects(iterable $payloads): \EventSauce\ObjectHydrator\IterableList
    {
        return new \EventSauce\ObjectHydrator\IterableList($this->doSerializeObjects($payloads));
    }
    private function doSerializeObjects(iterable $objects): \Generator
    {
        foreach ($objects as $index => $object) {
            yield $index => $this->serializeObject($object);
        }
    }
    public function getObjectMapperOperation🌀Pets(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets
    {
        if ($this->operation🌀Pets instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets === false) {
            $this->operation🌀Pets = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets();
        }
        return $this->operation🌀Pets;
    }
    public function getObjectMapperOperation🌀Pets🌀Gatos(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Gatos
    {
        if ($this->operation🌀Pets🌀Gatos instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Gatos === false) {
            $this->operation🌀Pets🌀Gatos = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Gatos();
        }
        return $this->operation🌀Pets🌀Gatos;
    }
    public function getObjectMapperOperation🌀Pets🌀Kinds🌀Walking(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking
    {
        if ($this->operation🌀Pets🌀Kinds🌀Walking instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking === false) {
            $this->operation🌀Pets🌀Kinds🌀Walking = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Kinds\Walking();
        }
        return $this->operation🌀Pets🌀Kinds🌀Walking;
    }
    public function getObjectMapperOperation🌀Pets🌀GroupedByType(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType
    {
        if ($this->operation🌀Pets🌀GroupedByType instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType === false) {
            $this->operation🌀Pets🌀GroupedByType = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\GroupedByType();
        }
        return $this->operation🌀Pets🌀GroupedByType;
    }
    public function getObjectMapperOperation🌀Pets🌀Names(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Names
    {
        if ($this->operation🌀Pets🌀Names instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Names === false) {
            $this->operation🌀Pets🌀Names = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Names();
        }
        return $this->operation🌀Pets🌀Names;
    }
    public function getObjectMapperOperation🌀Pets🌀Petid(): \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Petid
    {
        if ($this->operation🌀Pets🌀Petid instanceof \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Petid === false) {
            $this->operation🌀Pets🌀Petid = new \ApiClients\Client\PetStore\Internal\Hydrator\Operation\Pets\Petid();
        }
        return $this->operation🌀Pets🌀Petid;
    }
}
