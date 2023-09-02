<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use ApiClients\Tools\OpenApiClientGenerator\Representation;
use PhpParser\Builder;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionClass;
use Rx\Observable;

use function array_map;
use function count;
use function explode;
use function implode;
use function is_string;
use function str_replace;
use function strpos;

use const PHP_EOL;

final class Operation
{
    public static function methodSignature(Builder\Method $method, Representation\Operation $operation): Builder\Method
    {
        return self::methodReturnType(self::methodParams($method, $operation), $operation);
    }

    public static function methodParams(Builder\Method $method, Representation\Operation $operation): Builder\Method
    {
        return $method->addParams([
            ...(static function (array $params): iterable {
                foreach ($params as $param) {
                    yield (new Builder\Param($param->name))->setType($param->type === '' ? 'mixed' : $param->type);
                }
            })($operation->parameters),
            ...(count($operation->requestBody) > 0 ? [
                (new Builder\Param('params'))->setType('array'),
            ] : []),
        ]);
    }

    public static function methodReturnType(Builder\Method $method, Representation\Operation $operation): Builder\Method
    {
        $docComment = ReflectionTypes::copyDocBlock($operation->operatorClassName->fullyQualified->source, 'call');

        if ($docComment !== null) {
            $method = $method->setDocComment($docComment);
        }

        return $method->setReturnType(
            ReflectionTypes::copyReturnType($operation->operatorClassName->fullyQualified->source, 'call'),
        );
    }

    public static function methodCallOperation(Representation\Operation $operation): Node\Stmt\Return_
    {
        return new Node\Stmt\Return_(
            new Expr\MethodCall(
                new Expr\MethodCall(
                    new Node\Expr\PropertyFetch(
                        new Node\Expr\Variable('this'),
                        'operators',
                    ),
                    $operation->operatorLookUpMethod,
                ),
                'call',
                [
                    ...(static function (array $params): iterable {
                        foreach ($params as $param) {
                            yield new Arg(new Node\Expr\Variable($param->name));
                        }
                    })($operation->parameters),
                    ...(count($operation->requestBody) > 0 ? [new Arg(new Node\Expr\Variable('params'))] : []),
                ],
            ),
        );
    }

    public static function getResultTypeFromOperation(Representation\Operation $operation): string
    {
        /** @phpstan-ignore-next-line */
        $returnType = (new ReflectionClass($operation->className->fullyQualified->source))->getMethod('createResponse')->getReturnType();
        if ($returnType === null) {
            return 'void';
        }

        if ((string) $returnType === 'void') {
            return (string) $returnType;
        }

        return self::convertObservableIntoIterable(
            implode(
                '|',
                array_map(
                    static fn (string $object): Node\Name => new Node\Name((strpos($object, '\\') > 0 ? '\\' : '') . $object),
                    explode('|', (string) $returnType),
                ),
            ),
        );
    }

    public static function getDocBlockFromOperation(Representation\Operation $operation): Doc
    {
        return new Doc(
            implode(
                PHP_EOL,
                [
                    '/**',
                    ' * @return ' . self::getDocBlockResultTypeFromOperation($operation),
                    ' */',
                ],
            ),
        );
    }

    public static function getDocBlockResultTypeFromOperation(Representation\Operation $operation): string
    {
        /** @phpstan-ignore-next-line */
        $docComment = (new ReflectionClass($operation->className->fullyQualified->source))->getMethod('createResponse')->getDocComment();
        if (! is_string($docComment)) {
            return '';
        }

        // basic setup

        $lexer           = new Lexer();
        $constExprParser = new ConstExprParser();
        $typeParser      = new TypeParser($constExprParser);
        $phpDocParser    = new PhpDocParser($typeParser, $constExprParser);

        // parsing and reading a PHPDoc string
        $tokens     = new TokenIterator($lexer->tokenize($docComment));
        $phpDocNode = $phpDocParser->parse($tokens); // PhpDocNode

        return self::convertObservableIntoIterable(
            implode(
                '|',
                array_map(
                    static fn (ReturnTagValueNode $returnTagValueNode): string => (string) $returnTagValueNode->type,
                    $phpDocNode->getReturnTagValues(),
                ),
            ),
        );
    }

    private static function convertObservableIntoIterable(string $string): string
    {
        return str_replace(
            [
                '\\' . Observable::class . '<',
                '\\' . Observable::class,
                '(',
                ' ',
                ')',
            ],
            [
                'iterable<int ,',
                'iterable',
                '',
                '',
                '',
            ],
            $string,
        );
    }
}
