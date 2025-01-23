<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final class MemoryCache implements CacheInterface
{
    use CacheUtils;

    /** @var array<string, MemoryCacheItem> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->items[$key]->value;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKey($key);
        $this->items[$key] = new MemoryCacheItem(is_object($value) ? clone $value : $value, $ttl);
        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        unset($this->items[$key]);
        return true;
    }

    public function clear(): bool
    {
        $this->items = [];
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->validateKeys($keys);
        /** @var string[] $keys */
        $keys = $this->iterableToArray($keys);
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param mixed[] $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $values = $this->iterableToArray($values);
        $this->validateKeys(array_keys($values));
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $this->validateKeys($keys);
        /** @var string[] $keys */
        $keys = $this->iterableToArray($keys);
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return array_key_exists($key, $this->items) && !$this->items[$key]->isExpired();
    }
}
