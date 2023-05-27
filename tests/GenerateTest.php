<?php

declare(strict_types=1);

namespace ApiClients\Tests\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration;
use ApiClients\Tools\OpenApiClientGenerator\Generator;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\TestUtilities\TestCase;

use function array_keys;
use function array_unique;
use function assert;
use function dirname;
use function is_string;
use function ksort;
use function Safe\file_get_contents;
use function Safe\md5_file;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

final class GenerateTest extends TestCase
{
    /**
     * @test
     */
    public function generateAndCompare(): void
    {
        $yaml                        = Yaml::parseFile(__DIR__ . '/openapi-client-petstore.yaml');
        $yaml['destination']['root'] = 'test-app';
        $configuration               = (new ObjectMapperUsingReflection())->hydrateObject(Configuration::class, $yaml);
        (new Generator(
            $configuration,
            dirname(__DIR__ . '/openapi-client-petstore.yaml') . DIRECTORY_SEPARATOR,
        ))->generate(
            $configuration->namespace->source . '\\',
            $configuration->namespace->test . '\\',
            dirname(__DIR__ . '/openapi-client-petstore.yaml') . DIRECTORY_SEPARATOR,
        );

        $appRootPath     = __DIR__ . '/app/';
        $testAppRootPath = __DIR__ . '/test-app/';

        $appMap     = [];
        $testAppMap = [];

        foreach (new IteratorIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appRootPath))) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $fileName = substr($file->getPathname(), strlen($appRootPath));
            if ($fileName === 'etc/openapi-client-generator.state') {
                continue;
            }

            $appMap[$fileName] = md5_file($appRootPath . $fileName);
        }

        foreach (new IteratorIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testAppRootPath))) as $file) {
            if (! $file->isFile()) {
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
            self::assertIsString($generatedFileName);
            assert(is_string($generatedFileName));
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
