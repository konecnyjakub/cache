<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Common\CacheItemMetadata;
use Konecnyjakub\Cache\Common\ItemValueSerializer;
use Konecnyjakub\Cache\Common\Journal;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Konecnyjakub\Cache\Common\SqliteCacheTableStructure;
use Konecnyjakub\Cache\Common\SqliteJournal;
use Pdo\Sqlite;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Sqlite cache pool
 *
 * Stores values in a sqlite database
 */
final class SqliteCachePool extends BaseCachePool implements TaggableCachePool
{
    private readonly Journal $journal;

    public function __construct(
        private readonly Sqlite $connection,
        string $namespace = "",
        ?int $defaultTtl = null,
        private readonly ItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null,
        ?Journal $journal = null,
        private readonly SqliteCacheTableStructure $structure = new SqliteCacheTableStructure()
    ) {
        $this->connection->exec(
            sprintf(
                "CREATE TABLE IF NOT EXISTS %s (%s TEXT NOT NULL, %s BLOB NULL)",
                $this->structure->table,
                $this->structure->columnKey,
                $this->structure->columnValue
            )
        );
        $this->connection->exec(
            sprintf(
                "CREATE UNIQUE INDEX IF NOT EXISTS idx_item_key ON %s (%s)",
                $this->structure->table,
                $this->structure->columnKey
            )
        );
        parent::__construct($namespace, $defaultTtl, $eventDispatcher);
        $this->journal = $journal ?? new SqliteJournal($this->connection);
    }

    public function invalidateTags(array $tags): bool
    {
        $result = true;
        $keys = $this->journal->getKeysByTags($tags);
        foreach ($keys as $key) {
            $result = $result && $this->deleteItem($key);
        }
        return $result;
    }

    protected function doGet(string $key): CacheItem
    {
        $metadata = $this->journal->get($this->getKey($key));
        $value = null;
        $stm = $this->connection->prepare(
            sprintf(
                "SELECT %s FROM %s WHERE %s = ?",
                $this->structure->columnValue,
                $this->structure->table,
                $this->structure->columnKey
            )
        );
        if ($stm !== false) {
            $stm->execute([$this->getKey($key)]);
            /** @var array<string, mixed> $row */
            $row = $stm->fetch();
            // @phpstan-ignore cast.string
            $value = $this->serializer->unserialize((string) $row[$this->structure->columnValue]);
        }
        return new CacheItem(
            $key,
            $value,
            true,
            tags: $metadata->tags
        );
    }

    protected function doHas(string $key): bool
    {
        $stm = $this->connection->prepare(
            sprintf(
                "SELECT COUNT(*) FROM %s WHERE %s = ?",
                $this->structure->table,
                $this->structure->columnKey
            )
        );
        if ($stm === false) {
            return false;
        }
        $stm->execute([$this->getKey($key)]);
        /** @var array{0: numeric-string} $result */
        $result = $stm->fetch();
        if ((int) $result[0] < 1) {
            return false;
        }
        $meta = $this->journal->get($this->getKey($key));
        return $meta->expiresAt === null || $meta->expiresAt > time();
    }

    protected function doClear(): bool
    {
        if ($this->namespace === "") {
            return $this->connection->exec(
                sprintf(
                    "DELETE FROM %s WHERE %s NOT LIKE '%%:%%'",
                    $this->structure->table,
                    $this->structure->columnKey
                )
            ) !== false;
        }
        return $this->connection->exec(
            sprintf(
                "DELETE FROM %s WHERE %s LIKE '%%:%%'",
                $this->structure->table,
                $this->structure->columnKey
            )
        ) !== false;
    }

    protected function doDelete(string $key): bool
    {
        $stm = $this->connection->prepare(
            sprintf(
                "DELETE FROM %s WHERE %s = ?",
                $this->structure->table,
                $this->structure->columnKey
            )
        );
        if ($stm === false) {
            return false;
        }
        return $stm->execute([$this->getKey($key),]) && $this->journal->clear($this->getKey($key)) !== false;
    }

    protected function doSave(CacheItem $item): bool
    {
        $stm = $this->connection->prepare(
            sprintf(
                "REPLACE INTO %s(%s, %s) VALUES(?, ?)",
                $this->structure->table,
                $this->structure->columnKey,
                $this->structure->columnValue
            )
        );
        if ($stm === false) {
            return false;
        }
        if (!$stm->execute([$this->getKey($item->getKey()), $this->serializer->serialize($item->getValue())])) {
            return false;
        }
        return $this->journal->set(
            $this->getKey($item->getKey()),
            new CacheItemMetadata(
                $item->getTtl() === 0 && $this->defaultTtl === null ? null : $item->getTtl() + time(),
                $item->getTags()
            )
        );
    }
}
