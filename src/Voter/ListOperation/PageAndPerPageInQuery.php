<?php

declare(strict_types=1);

namespace ApiClients\Tools\OpenApiClientGenerator\Voter\ListOperation;

use ApiClients\Tools\OpenApiClientGenerator\Contract\Voter\AbstractListOperation;
use ApiClients\Tools\OpenApiClientGenerator\Contract\Voter\ListOperation;

final class PageAndPerPageInQuery extends AbstractListOperation implements ListOperation
{
    final public static function incrementorKey(): string
    {
        return 'page';
    }

    final public static function incrementorInitialValue(): int
    {
        return 1;
    }

    final public static function keys(): array
    {
        return ['per_page', 'page'];
    }
}
