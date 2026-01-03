<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Konecnyjakub\Cache\Common\ItemKeyValidator;
use Konecnyjakub\Cache\Events;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * @internal
 */
abstract class BaseCache implements CacheInterface
{
    protected readonly ItemKeyValidator $itemKeyValidator;

    /**
     * @param string $namespace Optional namespace for one instance
     * @param int|null $defaultTtl Default lifetime in seconds for items if not provided for a specific item
     */
    public function __construct(
        protected readonly string $namespace = "",
        protected readonly ?int $defaultTtl = null,
        protected readonly ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->itemKeyValidator = new ItemKeyValidator();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            $this->eventDispatcher?->dispatch(new Events\CacheMiss($key));
            return $default;
        }

        $value = $this->doGet($key);
        $this->eventDispatcher?->dispatch(new Events\CacheHit($key, $value));
        return $value;
    }

    /**
     * @param string[] $tags
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null, array $tags = []): bool
    {
        $this->validateKey($key);
        $this->eventDispatcher?->dispatch(new Events\CacheSave($key, $value));
        return $this->doSet($key, $value, $ttl ?? $this->defaultTtl, $tags);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $this->eventDispatcher?->dispatch(new Events\CacheDelete($key));
        return $this->doDelete($key);
    }

    public function clear(): bool
    {
        $this->eventDispatcher?->dispatch(new Events\CacheClear());
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
        $result = true;
        foreach ($keys as $key) {
            $result = $result && $this->delete($key);
        }
        return $result;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return $this->doHas($key);
    }

    /**
     * @internal
     */
    public function getKey(string $key): string
    {
        return ($this->namespace !== "" ? $this->namespace . ":" : "") . $key;
    }

    abstract protected function doGet(string $key): mixed;

    /**
     * @param string[] $tags
     */
    abstract protected function doSet(string $key, mixed $value, \DateInterval|int|null $ttl, array $tags = []): bool;

    abstract protected function doDelete(string $key): bool;

    abstract protected function doClear(): bool;

    abstract protected function doHas(string $key): bool;

    protected function validateKey(mixed $key): void
    {
        if (!$this->itemKeyValidator->isKeyValid($key)) {
            throw new InvalidKeyException();
        }
    }

    /**
     * @param mixed[] $keys
     */
    protected function validateKeys(iterable $keys): void
    {
        if (!$this->itemKeyValidator->isKeysValid($keys)) {
            throw new InvalidKeyException();
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
