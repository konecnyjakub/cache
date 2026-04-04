<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Konecnyjakub\Cache\Common\CacheItemMetadata;
use Konecnyjakub\Cache\Common\ItemValueSerializer;
use Konecnyjakub\Cache\Common\Journal;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Konecnyjakub\Cache\Common\SqliteCacheTableStructure;
use Konecnyjakub\Cache\Common\SqliteJournal;
use Pdo\Sqlite;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Sqlite cache
 *
 * Stores values in a sqlite database
 */
final class SqliteCache extends BaseCache implements TaggableCache
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
            $result = $result && $this->delete($key);
        }
        return $result;
    }

    protected function doGet(string $key): mixed
    {
        $stm = $this->connection->prepare(
            sprintf(
                "SELECT %s FROM %s WHERE %s = ?",
                $this->structure->columnValue,
                $this->structure->table,
                $this->structure->columnKey
            )
        );
        if ($stm === false) {
            return null;
        }
        $stm->execute([$this->getKey($key)]);
        $row = $stm->fetch();
        return is_array($row) ?
            // @phpstan-ignore cast.string
            $this->serializer->unserialize((string) $row[$this->structure->columnValue]) :
            null;
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl, array $tags = []): bool
    {
        $item = new CacheItem($value, $ttl);
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
        if (!$stm->execute([$this->getKey($key), $this->serializer->serialize($value)])) {
            return false;
        }
        return $this->journal->set($key, new CacheItemMetadata($item->expiresAt, $tags));
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
        return $stm->execute([$this->getKey($key),]) && $this->journal->clear($this->getKey($key));
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
}
