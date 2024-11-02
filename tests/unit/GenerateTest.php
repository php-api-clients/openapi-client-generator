<?php

declare(strict_types=1);

namespace ApiClients\Tests\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\ConfigurationFactory;
use IteratorIterator;
use OpenAPITools\Generator\Generator;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\TestUtilities\TestCase;

use function array_keys;
use function array_unique;
use function dirname;
use function file_get_contents;
use function ksort;
use function md5_file;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final class GenerateTest extends TestCase
{
    #[Test]
    public function generateAndCompare(): void
    {
        $configurationFile      = dirname(__DIR__) . '/openapi-client-petstore.yaml';
        $configurationDirectory = dirname($configurationFile) . DIRECTORY_SEPARATOR;

        /** @var array<string, mixed> $yaml */
        $yaml = Yaml::parseFile($configurationFile);

        $destination = $yaml['destination'] ?? null;
        self::assertIsArray($destination);
        $destination['root'] = 'test-app';
        $yaml['destination'] = $destination;

        Generator::generate(
            ConfigurationFactory::fromYaml($yaml, $configurationDirectory),
            $configurationDirectory,
        );

        $appRootPath     = dirname(__DIR__) . '/app/';
        $testAppRootPath = dirname(__DIR__) . '/test-app/';

        $appMap     = [];
        $testAppMap = [];

        foreach (new IteratorIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appRootPath))) as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $fileName = substr($file->getPathname(), strlen($appRootPath));
            if ($fileName === 'etc/openapi-client-generator.state') {
                continue;
            }

            $appMap[$fileName] = md5_file($appRootPath . $fileName);
        }

        foreach (new IteratorIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testAppRootPath))) as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $fileName = substr($file->getPathname(), strlen($testAppRootPath));
            if ($fileName === 'etc/openapi-client-generator.state') {
                continue;
            }

            $testAppMap[$fileName] = md5_file($testAppRootPath . $fileName);
        }

        ksort($appMap);
        ksort($testAppMap);

        foreach (array_unique([...array_keys($appMap), ...array_keys($testAppMap)]) as $generatedFileName) {
            self::assertFileExists($appRootPath . $generatedFileName);
            self::assertFileExists($testAppRootPath . $generatedFileName);

            self::assertSame(
                file_get_contents($appRootPath . $generatedFileName),
                file_get_contents($testAppRootPath . $generatedFileName),
                $generatedFileName,
            );
        }
    }
}
