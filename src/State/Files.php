<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\State;

use function array_key_exists;
use function array_values;

final class Files
{
    /** @var array<File> $files */
    private array $files = [];

    /**
     * @param array<File> $files
     */
    public function __construct(
        array $files,
    ) {
        foreach ($files as $file) {
            $this->files[$file->name] = $file;
        }
    }

    public function upsert(string $fileName, string $hash): void
    {
        $this->files[$fileName] = new File($fileName, $hash);
    }

    public function has(string $fileName): bool
    {
        return array_key_exists($fileName, $this->files);
    }

    public function get(string $fileName): File
    {
        return $this->files[$fileName];
    }

    public function remove(string $fileName): void
    {
        unset($this->files[$fileName]);
    }

    /**
     * @return array<File>
     */
    public function files(): array
    {
        return array_values($this->files);
    }
}
