<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Chain cache pool
 *
 * Tries all reading operations on all available engines until one returns data
 * Performs all writing operations on all available engines
 */
final class ChainCachePool implements CacheItemPoolInterface
{
    /** @var CacheItemPoolInterface[] */
    private array $engines = [];

    public function addEngine(CacheItemPoolInterface $engine): void
    {
        $this->engines[] = $engine;
    }

    public function getItem(string $key): CacheItemInterface
    {
        foreach ($this->engines as $engine) {
            if ($engine->hasItem($key)) {
                return $engine->getItem($key);
            }
        }
        return new CacheItem($key);
    }

    /**
     * @return iterable<string, CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->getItem($key);
        }

        return $values;
    }

    public function hasItem(string $key): bool
    {
        foreach ($this->engines as $engine) {
            if ($engine->hasItem($key)) {
                return true;
            }
        }
        return false;
    }

    public function clear(): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->clear();
        }
        return $result;
    }

    public function deleteItem(string $key): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->deleteItem($key);
        }
        return $result;
    }

    public function deleteItems(array $keys): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->deleteItems($keys);
        }
        return $result;
    }

    public function save(CacheItemInterface $item): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->save($item);
        }
        return $result;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->saveDeferred($item);
        }
        return $result;
    }

    public function commit(): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->commit();
        }
        return $result;
    }
}
