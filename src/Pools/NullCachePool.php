<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Null cache pool
 *
 * Does not store any values. Can be used to disable caching
 */
final class NullCachePool extends BaseCachePool
{
    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        parent::__construct("", null, $eventDispatcher);
    }

    protected function doGet(string $key): CacheItem
    {
        return new CacheItem($key);
    }

    protected function doHas(string $key): bool
    {
        return false;
    }

    protected function doClear(): bool
    {
        return true;
    }

    protected function doDelete(string $key): bool
    {
        return true;
    }

    protected function doSave(CacheItem $item): bool
    {
        return true;
    }
}
