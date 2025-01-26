<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * @internal
 */
abstract class BaseCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->doGet($key);
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->validateKey($key);
        return $this->doSet($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        return $this->doDelete($key);
    }

    public function clear(): bool
    {
        return $this->doClear();
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
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
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
        return $this->doHas($key);
    }

    abstract protected function doGet(string $key): mixed;

    abstract protected function doSet(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool;

    abstract protected function doDelete(string $key): bool;

    abstract protected function doClear(): bool;

    abstract protected function doHas(string $key): bool;

    protected function validateKey(mixed $key): void
    {
        if (!is_string($key) || $key === '' || strlen($key) > 64 || strpbrk($key, "{}()/\@:") !== false) {
            throw new InvalidKeyException();
        }
    }

    /**
     * @param mixed[] $keys
     */
    protected function validateKeys(iterable $keys): void
    {
        $keys = $this->iterableToArray($keys);
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    protected function iterableToArray(iterable $array): array
    {
        return $array instanceof Traversable ? iterator_to_array($array) : $array;
    }
}
