<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Generator\Helper;

use OpenAPITools\Representation;
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
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Rx\Observable;

use function array_map;
use function count;
use function implode;
use function is_string;
use function str_replace;

use const PHP_EOL;

final class Operation
{
    public static function methodSignature(Builder\Method $method, Representation\Namespaced\Operation $operation): Builder\Method
    {
        return self::methodReturnType(self::methodParams($method, $operation), $operation);
    }

    public static function methodParams(Builder\Method $method, Representation\Namespaced\Operation $operation): Builder\Method
    {
        return $method->addParams([
            ...(static function (array $params): iterable {
                foreach ($params as $param) {
                    yield new Builder\Param($param->name)->setType($param->type === '' ? 'mixed' : $param->type);
                }
            })($operation->parameters),
            ...(count($operation->requestBody) > 0 ? [
                new Builder\Param('params')->setType('array'),
            ] : []),
        ]);
    }

    public static function methodReturnType(Builder\Method $method, Representation\Namespaced\Operation $operation): Builder\Method
    {
        /** @var class-string $operatorClassName */
        $operatorClassName = $operation->operatorClassName->fullyQualified->source;
        $docComment        = ReflectionTypes::copyDocBlock($operatorClassName, 'call');

        if ($docComment instanceof Doc) {
            $method = $method->setDocComment($docComment);
        }

        return $method->setReturnType(
            ReflectionTypes::copyReturnType($operatorClassName, 'call'),
        );
    }

    public static function methodCallOperation(Representation\Namespaced\Operation $operation): Node\Stmt\Return_
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

    public static function getResultTypeFromOperation(Representation\Namespaced\Operation $operation): string
    {
        /** @var class-string $className */
        $className  = $operation->className->fullyQualified->source;
        $returnType = new ReflectionClass($className)->getMethod('createResponse')->getReturnType();
        if ($returnType === null) {
            return 'void';
        }

        if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'void') {
            return 'void';
        }

        $types = $returnType instanceof ReflectionUnionType
            ? array_map(
                ReflectionTypes::name(...),
                $returnType->getTypes(),
            )
            : [ReflectionTypes::name($returnType)];

        return self::convertObservableIntoIterable(implode('|', $types));
    }

    public static function getDocBlockFromOperation(Representation\Namespaced\Operation $operation): Doc|null
    {
        $resultType = self::getDocBlockResultTypeFromOperation($operation);

        /**
         * An operation only carries an @return on createResponse() when the native
         * return type cannot express the full type, such as the item type behind an
         * Observable. Reflecting on the rest yields no type, and writing the tag
         * anyway leaves an empty `@return` behind.
         */
        if ($resultType === '') {
            return null;
        }

        return new Doc(
            implode(
                PHP_EOL,
                [
                    '/**',
                    ' * @return ' . $resultType,
                    ' */',
                ],
            ),
        );
    }

    public static function getDocBlockResultTypeFromOperation(Representation\Namespaced\Operation $operation): string
    {
        /** @var class-string $className */
        $className  = $operation->className->fullyQualified->source;
        $docComment = new ReflectionClass($className)->getMethod('createResponse')->getDocComment();
        if (! is_string($docComment)) {
            return '';
        }

        // basic setup

        $config          = new ParserConfig([]);
        $lexer           = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $typeParser      = new TypeParser($config, $constExprParser);
        $phpDocParser    = new PhpDocParser($config, $typeParser, $constExprParser);

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
