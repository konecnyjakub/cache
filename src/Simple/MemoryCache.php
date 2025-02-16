<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Simple in memory cache
 *
 * Stores values during the current request
 */
final class MemoryCache extends BaseCache implements ITaggableCache
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
            if (count(array_intersect($tags, $item->tags)) > 0) {
                $result = $result && $this->delete($key);
            }
        }
        return $result;
    }

    protected function doGet(string $key): mixed
    {
        return $this->items[$key]->value;
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl, array $tags = []): bool
    {
        $this->items[$key] = new CacheItem($value, $ttl, $tags);
        return true;
    }

    protected function doDelete(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    protected function doClear(): bool
    {
        $this->items = [];
        return true;
    }

    protected function doHas(string $key): bool
    {
        return array_key_exists($key, $this->items) && !$this->items[$key]->isExpired();
    }
}
