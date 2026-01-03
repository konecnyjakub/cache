<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Common\ItemValueSerializer;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Memcached;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Memcached cache pool
 *
 * Stores values on a memcached server
 */
final class MemcachedCachePool extends BaseCachePool
{
    /**
     * @param int|null $defaultTtl Default lifetime in seconds for items if not provided for a specific item
     * @param ItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        private readonly Memcached $client,
        ?int $defaultTtl = null,
        private readonly ItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct("", $defaultTtl, $eventDispatcher);
    }


    protected function doGet(string $key): CacheItem
    {
        /** @var string $value */
        $value = $this->client->get($this->getKey($key));
        return new CacheItem(
            $this->getKey($key),
            $this->serializer->unserialize($value),
            $this->client->getResultCode() === Memcached::RES_SUCCESS,
            $this->defaultTtl
        );
    }

    protected function doHas(string $key): bool
    {
        $this->client->get($this->getKey($key));
        return $this->client->getResultCode() === Memcached::RES_SUCCESS;
    }

    protected function doClear(): bool
    {
        return $this->client->flush();
    }

    protected function doDelete(string $key): bool
    {
        $this->client->delete($this->getKey($key));
        return in_array($this->client->getResultCode(), [Memcached::RES_SUCCESS, Memcached::RES_NOTFOUND], true);
    }

    protected function doSave(CacheItem $item): bool
    {
        return $this->client->set(
            $this->getKey($item->getKey()),
            $this->serializer->serialize($item->getValue()),
            $item->getTtl()
        );
    }
}
