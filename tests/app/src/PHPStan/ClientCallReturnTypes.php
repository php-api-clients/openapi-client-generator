<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\PHPStan;

final readonly class ClientCallReturnTypes implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{
    private \PhpParser\PrettyPrinter\Standard $printer;
    public function __construct(private \PHPStan\PhpDoc\TypeStringResolver $typeResolver)
    {
        $this->printer = new \PhpParser\PrettyPrinter\Standard();
    }
    public function getClass(): string
    {
        return \ApiClients\Client\PetStore\Client::class;
    }
    public function isMethodSupported(\PHPStan\Reflection\MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'call';
    }
    public function getTypeFromMethodCall(\PHPStan\Reflection\MethodReflection $methodReflection, \PhpParser\Node\Expr\MethodCall $methodCall, \PHPStan\Analyser\Scope $scope): null|\PHPStan\Type\Type
    {
        $args = $methodCall->getArgs();
        if (count($args) === 0) {
            return null;
        }
        $call = substr($this->printer->prettyPrintExpr($args[0]->value), 1, -1);
        if ($call === 'GET /pets') {
            return $this->typeResolver->resolve('iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider>|\ApiClients\Client\PetStore\Schema\Error');
        }
        if ($call === 'POST /pets') {
            return $this->typeResolver->resolve('\ApiClients\Client\PetStore\Schema\Error|\ApiClients\Tools\OpenApiClient\Utils\Response\WithoutBody');
        }
        if ($call === 'GET /pets/gatos') {
            return $this->typeResolver->resolve('iterable<int,\ApiClients\Client\PetStore\Schema\Cat>|\ApiClients\Client\PetStore\Schema\Error');
        }
        if ($call === 'GET /pets/kinds/walking') {
            return $this->typeResolver->resolve('iterable<int,\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\HellHound>|\ApiClients\Client\PetStore\Schema\Error');
        }
        if ($call === 'GET /pets/groupedByType') {
            return $this->typeResolver->resolve('\ApiClients\Client\PetStore\Schema\Operations\Pets\Grouped\By\Type\Response\ApplicationJson\Ok|\ApiClients\Client\PetStore\Schema\Error');
        }
        if ($call === 'GET /pets/names') {
            return $this->typeResolver->resolve('iterable<int,string>|\ApiClients\Client\PetStore\Schema\Error');
        }
        if ($call === 'GET /pets/{petId}') {
            return $this->typeResolver->resolve('\ApiClients\Client\PetStore\Schema\Cat|\ApiClients\Client\PetStore\Schema\Dog|\ApiClients\Client\PetStore\Schema\Bird|\ApiClients\Client\PetStore\Schema\Fish|\ApiClients\Client\PetStore\Schema\Spider|\ApiClients\Client\PetStore\Schema\Error');
        }
        return null;
    }
}
