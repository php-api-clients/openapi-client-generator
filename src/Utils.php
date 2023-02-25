<?php

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Client;
use ApiClients\Tools\OpenApiClientGenerator\Generator\ClientInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Hydrator;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Operation;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Path;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Schema;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use ApiClients\Tools\OpenApiClientGenerator\Registry\Schema as SchemaRegistry;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use EventSauce\ObjectHydrator\ObjectMapperCodeGenerator;
use Jawira\CaseConverter\Convert;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

final class Utils
{
    public static function className(string $className): string
    {
        return self::fixKeyword(str_replace(['{', '}', '-', '$', '_', '+', '*', '.'], ['Cb', 'Rcb', 'Dash', '_', '\\', 'Plus', 'Obelix', 'Dot'], (new Convert($className))->toPascal()));
    }

    public static function cleanUpNamespace(string $namespace): string
    {
        $namespace = str_replace('/', '\\', $namespace);
        $namespace = str_replace('\\\\', '\\', $namespace);

        return '\\' . $namespace;
    }

    public static function fqcn(string $fqcn): string
    {
        return str_replace('/', '\\', $fqcn);
    }

    public static function dirname(string $fqcn): string
    {
        $fqcn = str_replace('\\', '/', $fqcn);

        return self::cleanUpNamespace(dirname($fqcn));
    }

    public static function basename(string $fqcn): string
    {
        $fqcn = str_replace('\\', '/', $fqcn);

        return self::cleanUpNamespace(basename($fqcn));
    }

    public static function fixKeyword(string $name): string
    {
        $name = self::fqcn($name);
        $nameBoom = explode('\\', $name);
        return $name . (in_array(
            strtolower($nameBoom[count($nameBoom) - 1]), array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'self', 'parent', 'object'),
            false
        ) ? '_' : '');
    }
}
