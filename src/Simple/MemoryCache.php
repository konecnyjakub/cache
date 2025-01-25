<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;

/**
 * In memory cache
 *
 * Stores values during the current request
 */
final class MemoryCache extends BaseCache
{
    /** @var array<string, CacheItem> */
    private array $items = [];

    /**
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(private readonly ?int $defaultTtl = null)
    {
    }

    protected function doGet(string $key): mixed
    {
        return $this->items[$key]->value;
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->items[$key] = new CacheItem($value, $ttl ?? $this->defaultTtl);
        return true;
    }

    protected function doDelete(string $key): bool
    {
        unset($this->items[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->items = [];
        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return array_key_exists($key, $this->items) && !$this->items[$key]->isExpired();
    }
}
