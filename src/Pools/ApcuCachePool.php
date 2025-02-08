<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use APCUIterator;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Apcu cache pool
 *
 * Stores values in memory using apcu
 */
final class ApcuCachePool extends BaseCachePool
{
    /**
     * @param string $namespace Optional namespace for this instance. Is added as prefix to keys
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(
        string $namespace = "",
        ?int $defaultTtl = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($namespace, $defaultTtl, $eventDispatcher);
    }

    protected function doGet(string $key): CacheItem
    {
        $value = apcu_fetch($this->getKey($key), $success);
        return new CacheItem($this->getKey($key), $value, (bool) $success, $this->defaultTtl);
    }

    protected function doHas(string $key): bool
    {
        return apcu_exists($this->getKey($key));
    }

    protected function doClear(): bool
    {
        if ($this->namespace === "") {
            return apcu_clear_cache();
        }
        $result = true;
        /** @var array{key: string, value: mixed} $counter */
        foreach (new APCUIterator("/^$this->namespace:(.+)/") as $counter) {
            $result = $result && $this->doDelete(str_replace($this->getKey(""), "", $counter["key"]));
        }
        return $result;
    }

    protected function doDelete(string $key): bool
    {
        return apcu_delete($this->getKey($key));
    }

    protected function doSave(CacheItem $item): bool
    {
        return apcu_store($this->getKey($item->getKey()), $item->getValue(), $item->getTtl());
    }
}
