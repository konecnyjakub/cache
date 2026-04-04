<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class SqliteJournalTablesStructure
{
    public function __construct(
        public string $tableTtl = "cache_meta_ttl",
        public string $columnTtlKey = "key",
        public string $columnTtl = "ttl",
        public string $tableTags = "cache_meta_tags",
        public string $columnTagKey = "key",
        public string $columnTag = "tag"
    ) {
    }
}
