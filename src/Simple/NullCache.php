<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

final readonly class NullCache implements CacheInterface
{
    use CacheUtils;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKey($key);
        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->validateKeys($keys);
        /** @var string[] $keys */
        $keys = $this->iterableToArray($keys);
        return array_fill_keys($keys, $default);
    }

    /**
     * @param mixed[] $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        $this->validateKeys(array_keys($this->iterableToArray($values)));
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $this->validateKeys($keys);
        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return false;
    }
}
