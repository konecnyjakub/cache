<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use Pdo\Sqlite;

final class SqliteJournal implements Journal
{
    public function __construct(
        private readonly Sqlite $connection,
        private readonly SqliteJournalTablesStructure $structure = new SqliteJournalTablesStructure()
    ) {
        $this->connection->exec(
            sprintf(
                "CREATE TABLE IF NOT EXISTS %s (%s TEXT NOT NULL, %s INT NULL)",
                $this->structure->tableTtl,
                $this->structure->columnTtlKey,
                $this->structure->columnTtl
            )
        );
        $this->connection->exec(
            sprintf(
                "CREATE TABLE IF NOT EXISTS %s (%s TEXT NOT NULL, %s TEXT NOT NULL)",
                $this->structure->tableTags,
                $this->structure->columnTagKey,
                $this->structure->columnTag
            )
        );
        $this->connection->exec(
            sprintf(
                "CREATE UNIQUE INDEX IF NOT EXISTS idx_ttl_key ON %s (%s)",
                $this->structure->tableTtl,
                $this->structure->columnTtlKey
            )
        );
        $this->connection->exec(
            sprintf(
                "CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_key_tag ON %s (%s, %s)",
                $this->structure->tableTags,
                $this->structure->columnTagKey,
                $this->structure->columnTag
            )
        );
    }

    public function get(string $key): CacheItemMetadata
    {
        $ttl = null;
        $ttlStm = $this->connection->prepare(
            sprintf(
                "SELECT %s FROM %s WHERE %s = ?",
                $this->structure->columnTtl,
                $this->structure->tableTtl,
                $this->structure->columnTtlKey
            )
        );
        if ($ttlStm !== false) {
            $ttlStm->execute([$key,]);
            while ($row = $ttlStm->fetch()) {
                /** @var array<string, int|null> $row */
                $ttl = $row[$this->structure->columnTtl];
            }
        }

        $tags = [];
        $tagsStm = $this->connection->prepare(
            sprintf(
                "SELECT %s FROM %s WHERE %s = ?",
                $this->structure->columnTag,
                $this->structure->tableTags,
                $this->structure->columnTagKey
            )
        );
        if ($tagsStm !== false) {
            $tagsStm->execute([$key,]);
            while ($row = $tagsStm->fetch()) {
                /** @var array<string, string> $row */
                $tags[] = $row[$this->structure->columnTag];
            }
        }

        return new CacheItemMetadata($ttl, $tags);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $ttlStm = $this->connection->prepare(
            sprintf(
                "REPLACE INTO %s(%s, %s) VALUES(?, ?)",
                $this->structure->tableTtl,
                $this->structure->columnTtlKey,
                $this->structure->columnTtl
            )
        );
        $result = ($ttlStm !== false);
        if ($ttlStm !== false) {
            $result = $result && $ttlStm->execute([$key, $metadata->expiresAt]);
        }

        $tagsDeleteStm = $this->connection->prepare(
            sprintf(
                "DELETE FROM %s WHERE %s = ?",
                $this->structure->tableTags,
                $this->structure->columnTagKey
            )
        );
        $result = $result && ($tagsDeleteStm !== false);
        if ($tagsDeleteStm !== false) {
            $result = $result && $tagsDeleteStm->execute([$key,]);
        }
        $tagInsertStm = $this->connection->prepare(
            sprintf(
                "INSERT INTO %s(%s, %s) VALUES(?, ?)",
                $this->structure->tableTags,
                $this->structure->columnTagKey,
                $this->structure->columnTag
            )
        );
        $result = $result && ($tagInsertStm !== false);
        if ($tagInsertStm !== false) {
            foreach ($metadata->tags as $tag) {
                $result = $result && $tagInsertStm->execute([$key, $tag]);
            }
        }

        return $result;
    }

    public function clear(?string $key = null): bool
    {
        $params = [];
        if ($key !== null) {
            $params[] = $key;
        }

        $query = "DELETE FROM {$this->structure->tableTtl}";
        if ($key !== null) {
            $query .= " WHERE {$this->structure->columnTtlKey} = ?";
        }
        $ttlStm = $this->connection->prepare($query);
        $result = ($ttlStm !== false);
        if ($ttlStm !== false) {
            $result = $result && $ttlStm->execute($params);
        }

        $query = "DELETE FROM {$this->structure->tableTags}";
        if ($key !== null) {
            $query .= " WHERE {$this->structure->columnTagKey} = ?";
        }
        $tagsStm = $this->connection->prepare($query);
        $result = $result && ($tagsStm !== false);
        if ($tagsStm !== false) {
            $result = $result && $tagsStm->execute($params);
        }

        return $result;
    }

    public function getKeysByTags(array $tags): iterable
    {
        if (count($tags) === 0) {
            return;
        }

        $stm = $this->connection->prepare(
            sprintf(
                "SELECT %s FROM %s WHERE %s IN (?%s)",
                $this->structure->columnTagKey,
                $this->structure->tableTags,
                $this->structure->columnTag,
                str_repeat(", ?", count($tags) - 1)
            )
        );
        if ($stm !== false) {
            $stm->execute($tags);
            while ($row = $stm->fetch()) {
                /** @var array<string, string> $row */
                yield $row[$this->structure->columnTagKey];
            }
        }
    }
}
