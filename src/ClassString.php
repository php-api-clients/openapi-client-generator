<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator;

use ApiClients\Tools\OpenApiClientGenerator\Configuration\Namespace_;

use function trim;

final readonly class ClassString
{
    public static function factory(Namespace_ $namespace, string $relative): self
    {
        $relative       = trim(Utils::className($relative), '\\');
        $fullyQualified = new Namespace_(
            Utils::cleanUpNamespace(trim($namespace->source, '\\') . '\\' . $relative),
            Utils::cleanUpNamespace(trim($namespace->test, '\\') . '\\' . $relative),
        );

        return new self(
            $namespace,
            new Namespace_(
                Utils::dirname($fullyQualified->source),
                Utils::dirname($fullyQualified->test),
            ),
            $fullyQualified,
            $relative,
            Utils::basename($relative),
        );
    }

    private function __construct(
        public Namespace_ $baseNamespaces,
        public Namespace_ $namespace,
        public Namespace_ $fullyQualified,
        public string $relative,
        public string $className,
    ) {
    }
}
