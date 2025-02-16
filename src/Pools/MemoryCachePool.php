<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * In memory cache pool
 *
 * Stores values during the current request
 */
final class MemoryCachePool extends BaseCachePool implements ITaggableCachePool
{
    /** @var array<string, CacheItem> */
    private array $items = [];

    /**
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(
        ?int $defaultTtl = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct("", $defaultTtl, $eventDispatcher);
    }

    public function invalidateTags(array $tags): bool
    {
        $result = true;
        foreach ($this->items as $key => $item) {
            if (count(array_intersect($tags, $item->getTags())) > 0) {
                $result = $result && $this->deleteItem($key);
            }
        }
        return $result;
    }

    protected function doGet(string $key): CacheItem
    {
        return $this->items[$key];
    }

    protected function doHas(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    protected function doClear(): bool
    {
        $this->items = [];
        return true;
    }

    protected function doDelete(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    protected function doSave(CacheItem $item): bool
    {
        if ($item->getTtl() >= 0) {
            $newItem = new CacheItem($item->getKey(), $item->getValue(), true, $this->defaultTtl, $item->getTags());
            $newItem->expiresAfter($item->getTtl());
            $this->items[$item->getKey()] = $newItem;
        }
        return true;
    }
}
