<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Common\ItemKeyValidator;
use Konecnyjakub\Cache\Events;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
abstract class BaseCachePool implements CacheItemPoolInterface
{
    protected readonly ItemKeyValidator $itemKeyValidator;

    /** @var CacheItemInterface[] */
    private array $deferred = [];

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

    /**
     * @return CacheItem
     */
    public function getItem(string $key): CacheItemInterface
    {
        if (!$this->hasItem($key)) {
            $this->eventDispatcher?->dispatch(new Events\CacheMiss($key));
            return new CacheItem($key, defaultTtl: $this->defaultTtl);
        }
        $item = $this->doGet($key);
        $this->eventDispatcher?->dispatch(new Events\CacheHit($key, $item->get()));
        return $item;
    }

    /**
     * @return iterable<string, CacheItem>
     */
    public function getItems(array $keys = []): iterable
    {
        $this->validateKeys($keys);
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getItem($key);
        }
        return $values;
    }

    public function hasItem(string $key): bool
    {
        $this->validateKey($key);
        return $this->doHas($key);
    }

    public function clear(): bool
    {
        $this->eventDispatcher?->dispatch(new Events\CacheClear());
        return $this->doClear();
    }

    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);
        $this->eventDispatcher?->dispatch(new Events\CacheDelete($key));
        return $this->doDelete($key);
    }

    public function deleteItems(array $keys): bool
    {
        $this->validateKeys($keys);
        $result = true;
        foreach ($keys as $key) {
            $result = $result && $this->deleteItem($key);
        }
        return $result;
    }

    public function save(CacheItemInterface $item): bool
    {
        if (!$item instanceof CacheItem) {
            return false;
        }
        $this->eventDispatcher?->dispatch(new Events\CacheSave($item->getKey(), $item->getValue()));
        return $this->doSave($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    public function commit(): bool
    {
        $result = true;
        foreach ($this->deferred as $item) {
            $result = $result && $this->save($item);
        }
        return $result;
    }

    /**
     * @internal
     */
    public function getKey(string $key): string
    {
        return ($this->namespace !== "" ? $this->namespace . ":" : "") . $key;
    }

    abstract protected function doGet(string $key): CacheItem;

    abstract protected function doHas(string $key): bool;

    abstract protected function doClear(): bool;

    abstract protected function doDelete(string $key): bool;

    abstract protected function doSave(CacheItem $item): bool;

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
}
