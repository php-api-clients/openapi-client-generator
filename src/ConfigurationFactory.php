<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Generator\Client;
use ApiClients\Tools\OpenApiClientGenerator\Generator\ClientInterface;
use ApiClients\Tools\OpenApiClientGenerator\Generator\Paths;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHook;
use ApiClients\Tools\OpenApiClientGenerator\Generator\WebHooks;
use InvalidArgumentException;
use OpenAPITools\Configuration\Configuration;
use OpenAPITools\Configuration\Gathering;
use OpenAPITools\Configuration\Gathering\Schemas;
use OpenAPITools\Configuration\Gathering\Voter;
use OpenAPITools\Configuration\Package;
use OpenAPITools\Configuration\Package\Destination;
use OpenAPITools\Configuration\Package\Metadata;
use OpenAPITools\Configuration\Package\QA;
use OpenAPITools\Configuration\Package\QA\Tool;
use OpenAPITools\Configuration\Package\State as PackageState;
use OpenAPITools\Configuration\Package\Templates;
use OpenAPITools\Configuration\State;
use OpenAPITools\Contract\Voter\ListOperation as ListOperationVoterContract;
use OpenAPITools\Generator\Hydrator\Hydrator;
use OpenAPITools\Generator\PSR15\WebHook\WebHookMiddleware as WebHookMiddlewareGenerator;
use OpenAPITools\Generator\Schema\Schema;
use OpenAPITools\Generator\Templates\Templates as GeneratorTemplates;
use OpenAPITools\Utils\Namespace_;
use PhpParser\BuilderFactory;
use Symfony\Component\Yaml\Yaml;

use function array_key_exists;
use function array_values;
use function dirname;
use function is_array;
use function is_string;

use const DIRECTORY_SEPARATOR;

final class ConfigurationFactory
{
    /** @param array<string, mixed> $yaml */
    public static function fromYaml(array $yaml, string $configurationDirectory): Configuration
    {
        $builderFactory = new BuilderFactory();

        /** @var array{file: string, additionalFiles?: list<string>} $stateConfig */
        $stateConfig = $yaml['state'];

        /** @var array{source: string, test: string} $namespaceConfig */
        $namespaceConfig = $yaml['namespace'];

        /** @var array{root: string, source: string, test: string} $destinationConfig */
        $destinationConfig = $yaml['destination'];

        /** @var array{call?: bool, operations?: bool, webHooks?: bool, webHookMiddleware?: bool|array{paths?: list<string>}} $entryPoints */
        $entryPoints = $yaml['entryPoints'] ?? [];

        $call       = $entryPoints['call'] ?? false;
        $operations = $entryPoints['operations'] ?? false;
        $webHooks   = $entryPoints['webHooks'] ?? false;

        $webHookMiddlewarePaths = self::webHookMiddlewarePaths($entryPoints['webHookMiddleware'] ?? false);
        if ($webHookMiddlewarePaths !== null) {
            $webHooks = true;
        }

        /** @var array{dir?: string, variables?: array<string, mixed>}|null $templatesConfig */
        $templatesConfig = $yaml['templates'] ?? null;

        /** @var array{allowDuplication?: bool, useAliasesForDuplication?: bool} $schemasConfig */
        $schemasConfig = $yaml['schemas'] ?? [];

        /** @var array<string, mixed> $qaConfig */
        $qaConfig = $yaml['qa'] ?? [];

        $variables = is_array($templatesConfig) ? ($templatesConfig['variables'] ?? null) : null;
        if (! is_array($variables)) {
            $variables = null;
        }

        $packageName = 'client';
        $fullName    = 'Client';
        if ($variables !== null) {
            if (is_string($variables['packageName'] ?? null)) {
                $packageName = $variables['packageName'];
            }

            if (is_string($variables['fullName'] ?? null)) {
                $fullName = $variables['fullName'];
            } elseif (is_string($variables['fullname'] ?? null)) {
                $fullName = $variables['fullname'];
            }
        }

        if ($webHookMiddlewarePaths !== null) {
            $variables ??= [];
            /** @var list<array{name: string, version: string}> $existingRequires */
            $existingRequires = is_array($variables['requires'] ?? null) ? $variables['requires'] : [];
            $requiredPackages = [
                'openapi-tools/contract'      => ['name' => 'openapi-tools/contract', 'version' => 'dev-main'],
                'psr/http-server-handler'     => ['name' => 'psr/http-server-handler', 'version' => '^2 || ^1'],
                'psr/http-server-middleware'  => ['name' => 'psr/http-server-middleware', 'version' => '^2 || ^1'],
            ];

            foreach ($existingRequires as $require) {
                unset($requiredPackages[$require['name']]);
            }

            $variables['requires'] = [...$existingRequires, ...array_values($requiredPackages)];
        }

        $templates = null;
        if (is_array($templatesConfig) && array_key_exists('dir', $templatesConfig)) {
            $templates = new Templates(
                $configurationDirectory . $templatesConfig['dir'],
                $variables,
            );
        }

        $generators = [
            new Schema($builderFactory),
            new Hydrator($builderFactory, $webHookMiddlewarePaths === null),
            new GeneratorTemplates(),
            new Paths($builderFactory, $call, $operations),
            new ClientInterface($builderFactory, $call, $operations),
            new Client($builderFactory, $call, $operations),
        ];

        if ($webHooks) {
            if ($webHookMiddlewarePaths === null) {
                $generators[] = new WebHooks($builderFactory);
                $generators[] = new WebHook($builderFactory);
            }
        }

        if ($webHookMiddlewarePaths !== null) {
            $generators[] = new WebHookMiddlewareGenerator($builderFactory, $webHookMiddlewarePaths);
        }

        /** @var array{listOperation?: list<class-string<ListOperationVoterContract>>, streamOperation?: list<string>}|null $voterConfig */
        $voterConfig = $yaml['voter'] ?? null;

        if (! is_string($yaml['spec'])) {
            throw new InvalidArgumentException('Configuration must contain a string spec path.');
        }

        $gathering = new Gathering(
            $yaml['spec'],
            is_array($voterConfig)
                ? new Voter(
                    $voterConfig['listOperation'] ?? null,
                    $voterConfig['streamOperation'] ?? null,
                )
                : null,
            new Schemas(
                $schemasConfig['allowDuplication'] ?? false,
                $schemasConfig['useAliasesForDuplication'] ?? false,
            ),
        );

        $package = new Package(
            new Metadata(
                $fullName,
                $fullName . ' API client',
                [],
            ),
            'api-clients',
            $packageName,
            null,
            null,
            null,
            $templates,
            new Destination(
                $destinationConfig['root'],
                $destinationConfig['source'],
                $destinationConfig['test'],
            ),
            new Namespace_(
                $namespaceConfig['source'],
                $namespaceConfig['test'],
            ),
            self::qaFromYaml($qaConfig),
            new PackageState($stateConfig['additionalFiles'] ?? []),
            $generators,
        );

        return new Configuration(
            new State($stateConfig['file']),
            $gathering,
            [$package],
        );
    }

    public static function fromYamlFile(string $configurationFile): Configuration
    {
        /** @var array<string, mixed> $yaml */
        $yaml = Yaml::parseFile($configurationFile);

        return self::fromYaml($yaml, dirname($configurationFile) . DIRECTORY_SEPARATOR);
    }

    /** @return list<string>|null */
    private static function webHookMiddlewarePaths(mixed $config): array|null
    {
        if ($config === false || $config === null) {
            return null;
        }

        if ($config === true) {
            return ['/webhook'];
        }

        if (! is_array($config)) {
            throw new InvalidArgumentException('entryPoints.webHookMiddleware must be a boolean or an object with an optional paths list.');
        }

        if (! array_key_exists('paths', $config)) {
            return ['/webhook'];
        }

        $paths = $config['paths'];
        if (! is_array($paths)) {
            throw new InvalidArgumentException('entryPoints.webHookMiddleware.paths must be a list of strings.');
        }

        /** @var list<string> $normalizedPaths */
        $normalizedPaths = [];
        foreach ($paths as $path) {
            if (! is_string($path)) {
                throw new InvalidArgumentException('entryPoints.webHookMiddleware.paths must be a list of strings.');
            }

            $normalizedPaths[] = $path;
        }

        return $normalizedPaths;
    }

    /** @param array<string, mixed> $qaConfig */
    private static function qaFromYaml(array $qaConfig): QA
    {
        return new QA(
            self::toolFromConfigOrNull($qaConfig['phpcs'] ?? null),
            self::toolFromConfigOrNull($qaConfig['phpstan'] ?? null),
            self::toolFromConfigOrNull($qaConfig['psalm'] ?? null),
        );
    }

    private static function toolFromConfigOrNull(mixed $config): Tool|null
    {
        if (! is_array($config)) {
            return null;
        }

        /** @var array<string, mixed> $typedConfig */
        $typedConfig = $config;

        return self::toolFromConfig($typedConfig);
    }

    /** @param array<string, mixed> $config */
    private static function toolFromConfig(array $config): Tool
    {
        $configFilePath = null;
        if (array_key_exists('configFilePath', $config) && is_string($config['configFilePath'])) {
            $configFilePath = $config['configFilePath'];
        }

        return new Tool(
            ($config['enabled'] ?? false) === true,
            $configFilePath,
        );
    }
}
