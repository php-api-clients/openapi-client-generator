<?php

declare(strict_types=1);

namespace ApiClients\Tests\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\ConfigurationFactory;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use InvalidArgumentException;
use OpenAPITools\Configuration\Package\Templates;
use OpenAPITools\Generator\Hydrator\Hydrator;
use OpenAPITools\Generator\PSR15\WebHook\WebHookMiddleware as WebHookMiddlewareGenerator;
use OpenAPITools\Generator\Schema\Schema;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use WyriHaximus\TestUtilities\TestCase;

use function array_is_list;
use function array_map;
use function dirname;

final class ConfigurationFactoryTest extends TestCase
{
    #[Test]
    public function webHookMiddlewareEnabledWithDefaultPaths(): void
    {
        $configuration = ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'entryPoints' => ['webHookMiddleware' => true],
        ], '/tmp/');

        $generators = $this->generatorClassNames($this->packageGenerators($configuration->packages[0]));

        self::assertContains(WebHookMiddlewareGenerator::class, $generators);
        self::assertNotContains(WebHooks::class, $generators);
        self::assertNotContains(WebHook::class, $generators);
        self::assertContains(Hydrator::class, $generators);

        $generator = $this->findGenerator($this->packageGenerators($configuration->packages[0]), WebHookMiddlewareGenerator::class);
        self::assertInstanceOf(WebHookMiddlewareGenerator::class, $generator);

        $pathsProperty = new ReflectionProperty(WebHookMiddlewareGenerator::class, 'defaultPaths');
        self::assertSame(['/webhook'], $pathsProperty->getValue($generator));
    }

    #[Test]
    public function webHookMiddlewareUsesConfiguredPaths(): void
    {
        $configuration = ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'entryPoints' => [
                'webHookMiddleware' => [
                    'paths' => ['/hooks/github', '/webhook'],
                ],
            ],
        ], '/tmp/');

        $generator = $this->findGenerator($this->packageGenerators($configuration->packages[0]), WebHookMiddlewareGenerator::class);
        self::assertInstanceOf(WebHookMiddlewareGenerator::class, $generator);

        $pathsProperty = new ReflectionProperty(WebHookMiddlewareGenerator::class, 'defaultPaths');
        self::assertSame(['/hooks/github', '/webhook'], $pathsProperty->getValue($generator));
    }

    #[Test]
    public function webHookMiddlewareAllowsExplicitEmptyPaths(): void
    {
        $configuration = ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'entryPoints' => [
                'webHookMiddleware' => [
                    'paths' => [],
                ],
            ],
        ], '/tmp/');

        $generator = $this->findGenerator($this->packageGenerators($configuration->packages[0]), WebHookMiddlewareGenerator::class);
        self::assertInstanceOf(WebHookMiddlewareGenerator::class, $generator);

        $pathsProperty = new ReflectionProperty(WebHookMiddlewareGenerator::class, 'defaultPaths');
        self::assertSame([], $pathsProperty->getValue($generator));
    }

    #[Test]
    public function webHookMiddlewareDisabledByDefault(): void
    {
        $configuration = ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'entryPoints' => ['webHooks' => false],
        ], '/tmp/');

        $generators = $this->generatorClassNames($this->packageGenerators($configuration->packages[0]));

        self::assertNotContains(WebHookMiddlewareGenerator::class, $generators);
        self::assertContains(Schema::class, $generators);
    }

    #[Test]
    public function webHookMiddlewareRejectsInvalidConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'entryPoints' => [
                'webHookMiddleware' => ['paths' => '/webhook'],
            ],
        ], '/tmp/');
    }

    #[Test]
    public function webHookMiddlewareAddsPsrDependenciesToTemplateVariables(): void
    {
        $configuration = ConfigurationFactory::fromYaml([
            'state' => ['file' => 'state.json'],
            'spec' => 'spec.yaml',
            'namespace' => [
                'source' => 'ApiClients\\Client\\Example',
                'test' => 'ApiClients\\Tests\\Client\\Example',
            ],
            'destination' => [
                'root' => 'generated',
                'source' => 'src',
                'test' => 'tests',
            ],
            'templates' => [
                'dir' => 'templates/',
                'variables' => [
                    'requires' => [
                        ['name' => 'example/package', 'version' => '^1.0'],
                    ],
                ],
            ],
            'entryPoints' => ['webHookMiddleware' => true],
        ], '/tmp/');

        $templates = $this->packageTemplates($configuration->packages[0]);
        self::assertNotNull($templates);
        self::assertIsArray($templates->variables);

        /** @var list<array{name: string, version: string}> $requires */
        $requires = $templates->variables['requires'];
        $names    = array_map(static fn (array $require): string => $require['name'], $requires);

        self::assertContains('example/package', $names);
        self::assertContains('openapi-tools/contract', $names);
        self::assertContains('psr/http-server-handler', $names);
        self::assertContains('psr/http-server-middleware', $names);
        self::assertCount(4, $requires);

        /** @var mixed $requiresMixed */
        $requiresMixed = $templates->variables['requires'];
        self::assertIsArray($requiresMixed);
        self::assertTrue(array_is_list($requiresMixed));
    }

    #[Test]
    public function fromYamlFileLoadsConfiguration(): void
    {
        $configuration = ConfigurationFactory::fromYamlFile(dirname(__DIR__) . '/openapi-client-petstore.yaml');

        self::assertSame('petstore.yaml', $configuration->gathering->spec);
        self::assertSame('ApiClients\\Client\\PetStore', $configuration->packages[0]->namespace->source);
    }

    /**
     * @param array<object> $generators
     *
     * @return array<class-string>
     */
    private function generatorClassNames(array $generators): array
    {
        return array_map(static fn (object $generator): string => $generator::class, $generators);
    }

    /** @return list<object> */
    private function packageGenerators(object $package): array
    {
        $generatorsProperty = new ReflectionProperty($package, 'generators');

        /** @var list<object> $generators */
        $generators = $generatorsProperty->getValue($package);

        return $generators;
    }

    private function packageTemplates(object $package): Templates|null
    {
        $templatesProperty = new ReflectionProperty($package, 'templates');

        $templates = $templatesProperty->getValue($package);

        return $templates instanceof Templates ? $templates : null;
    }

    /** @param array<object> $generators */
    private function findGenerator(array $generators, string $className): object
    {
        foreach ($generators as $generator) {
            if ($generator::class === $className) {
                return $generator;
            }
        }

        self::fail('Generator not found: ' . $className);
    }
}
