<?php

declare (strict_types=1);
namespace ApiClients\Client\PetStore\PHPStan;

use ApiClients\Client\PetStore\Contract;
use ApiClients\Client\PetStore\Error as ErrorSchemas;
use ApiClients\Client\PetStore\Internal;
use ApiClients\Client\PetStore\Operation;
use ApiClients\Client\PetStore\Schema;
use League\OpenAPIValidation;
use React\Http;
use ApiClients\Contracts;
final readonly class ClientCallReturnTypes implements \PHPStan\Type\DynamicMethodReturnTypeExtension
{
    private \PhpParser\PrettyPrinter\Standard $printer;
    public function __construct(private \PHPStan\PhpDoc\TypeStringResolver $typeResolver)
    {
        $this->printer = new \PhpParser\PrettyPrinter\Standard();
    }
    public function getClass() : string
    {
        return \ApiClients\Client\PetStore\Client::class;
    }
    public function isMethodSupported(\PHPStan\Reflection\MethodReflection $methodReflection) : bool
    {
        return $methodReflection->getName() === 'call';
    }
    public function getTypeFromMethodCall(\PHPStan\Reflection\MethodReflection $methodReflection, \PhpParser\Node\Expr\MethodCall $methodCall, \PHPStan\Analyser\Scope $scope) : null|\PHPStan\Type\Type
    {
        $args = $methodCall->getArgs();
        if (count($args) === 0) {
            return null;
        }
        $call = substr($this->printer->prettyPrintExpr($args[0]->value), 1, -1);
        if ($call === 'GET /pets') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\HellHound|Schema\\Bird|Schema\\Fish|Schema\\Spider>');
        }
        if ($call === 'LIST /pets') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\HellHound|Schema\\Bird|Schema\\Fish|Schema\\Spider>');
        }
        if ($call === 'POST /pets') {
            return $this->typeResolver->resolve('\\ApiClients\\Tools\\OpenApiClient\\Utils\\Response\\WithoutBody');
        }
        if ($call === 'GET /pets/gatos') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat>');
        }
        if ($call === 'LIST /pets/gatos') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat>');
        }
        if ($call === 'GET /pets/kinds/walking') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\HellHound>');
        }
        if ($call === 'LIST /pets/kinds/walking') {
            return $this->typeResolver->resolve('iterable<int,Schema\\Cat|Schema\\Dog|Schema\\HellHound>');
        }
        if ($call === 'GET /pets/groupedByType') {
            return $this->typeResolver->resolve('Schema\\Operations\\Pets\\Grouped\\By\\Type\\Response\\ApplicationJson\\Ok');
        }
        if ($call === 'GET /pets/names') {
            return $this->typeResolver->resolve('iterable<int,string>');
        }
        if ($call === 'LIST /pets/names') {
            return $this->typeResolver->resolve('iterable<int,string>');
        }
        if ($call === 'GET /pets/{petId}') {
            return $this->typeResolver->resolve('Schema\\Cat|Schema\\Dog|Schema\\Bird|Schema\\Fish|Schema\\Spider');
        }
        return null;
    }
}
