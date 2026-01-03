<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use InvalidArgumentException;
use PDO;

final class SqliteJournal implements Journal
{
    private string $tableTtl = "cache_meta_ttl";
    private string $columnTtlKey = "key";
    private string $columnTtl = "ttl";

    private string $tableTags = "cache_meta_tags";
    private string $columnTagKey = "key";
    private string $columnTag = "tag";

    public function __construct(private readonly PDO $connection)
    {
        /** @var string $pdoDriverName */
        $pdoDriverName = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($pdoDriverName !== "sqlite") {
            throw new InvalidArgumentException(sprintf(
                "%s requires connection to a sqlite database, %s given",
                self::class,
                $pdoDriverName
            ));
        }
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS $this->tableTtl ($this->columnTtlKey TEXT NOT NULL, $this->columnTtl INT NULL)"
        );
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS $this->tableTags ($this->columnTagKey TEXT NOT NULL, $this->columnTag TEXT NOT NULL)"
        );
        $this->connection->exec(
            "CREATE UNIQUE INDEX IF NOT EXISTS idx_ttl_key ON $this->tableTtl ($this->columnTtlKey)"
        );
        $this->connection->exec(
            "CREATE UNIQUE INDEX IF NOT EXISTS idx_tags_key_tag ON $this->tableTags ($this->columnTagKey, $this->columnTag)"
        );
    }

    public function get(string $key): CacheItemMetadata
    {
        $ttl = null;
        $ttlStm = $this->connection->prepare(
            "SELECT $this->columnTtl FROM $this->tableTtl WHERE $this->columnTtlKey = ?"
        );
        if ($ttlStm !== false) {
            $ttlStm->execute([$key,]);
            while ($row = $ttlStm->fetch()) {
                /** @var array<string, int|null> $row */
                $ttl = $row[$this->columnTtl];
            }
        }

        $tags = [];
        $tagsStm = $this->connection->prepare(
            "SELECT $this->columnTag FROM $this->tableTags WHERE $this->columnTagKey = ?"
        );
        if ($tagsStm !== false) {
            $tagsStm->execute([$key,]);
            while ($row = $tagsStm->fetch()) {
                /** @var array<string, string> $row */
                $tags[] = $row[$this->columnTag];
            }
        }

        return new CacheItemMetadata($ttl, $tags);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $ttlStm = $this->connection->prepare(
            "REPLACE INTO $this->tableTtl($this->columnTtlKey, $this->columnTtl) VALUES(?, ?)"
        );
        $result = ($ttlStm !== false);
        if ($ttlStm !== false) {
            $result = $result && $ttlStm->execute([$key, $metadata->expiresAt]);
        }

        $tagsDeleteStm = $this->connection->prepare("DELETE FROM $this->tableTags WHERE $this->columnTagKey = ?");
        $result = $result && ($tagsDeleteStm !== false);
        if ($tagsDeleteStm !== false) {
            $result = $result && $tagsDeleteStm->execute([$key,]);
        }
        $tagInsertStm = $this->connection->prepare(
            "INSERT INTO $this->tableTags($this->columnTagKey, $this->columnTag) VALUES(?, ?)"
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

        $query = "DELETE FROM $this->tableTtl";
        if ($key !== null) {
            $query .= " WHERE $this->columnTtlKey = ?";
        }
        $ttlStm = $this->connection->prepare($query);
        $result = ($ttlStm !== false);
        if ($ttlStm !== false) {
            $result = $result && $ttlStm->execute($params);
        }

        $query = "DELETE FROM $this->tableTags";
        if ($key !== null) {
            $query .= " WHERE $this->columnTagKey = ?";
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
            "SELECT $this->columnTagKey FROM $this->tableTags WHERE $this->columnTag IN (?" .
            str_repeat(", ?", count($tags) - 1) .
            ")"
        );
        if ($stm !== false) {
            $stm->execute($tags);
            while ($row = $stm->fetch()) {
                /** @var array<string, string> $row */
                yield $row[$this->columnTagKey];
            }
        }
    }
}
