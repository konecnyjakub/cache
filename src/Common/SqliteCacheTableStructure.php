<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class SqliteCacheTableStructure
{
    public function __construct(
        public string $table = "cache_items",
        public string $columnKey = "key",
        public string $columnValue = "value"
    ) {
    }
}
