<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Common\ItemValueSerializer;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;

/**
 * Redis cache pool
 *
 * Stores values on a redis server
 */
final class RedisCachePool extends BaseCachePool
{
    /**
     * @param string $namespace Optional namespace for this instance. Is added as prefix to keys
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     * @param ItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        private readonly Redis $client,
        string $namespace = "",
        ?int $defaultTtl = null,
        private readonly ItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($namespace, $defaultTtl, $eventDispatcher);
    }


    protected function doGet(string $key): CacheItem
    {
        return new CacheItem(
            $this->getKey($key),
            $this->serializer->unserialize($this->client->get($this->getKey($key))), // @phpstan-ignore argument.type
            true
        );
    }

    protected function doHas(string $key): bool
    {
        return (bool) $this->client->exists($this->getKey($key));
    }

    protected function doClear(): bool
    {
        if ($this->namespace === "") {
            return $this->client->flushDB();
        }

        $result = true;
        /** @var string[] $keys */
        $keys = $this->client->keys($this->getKey("*"));
        foreach ($keys as $key) {
            $result = $result && $this->doDelete(str_replace($this->namespace . ":", "", $key));
        }
        return $result;
    }

    protected function doDelete(string $key): bool
    {
        $this->client->del($this->getKey($key));
        return true;
    }

    protected function doSave(CacheItem $item): bool
    {
        if ($item->getTtl() < 0) {
            return true;
        }
        $options = [];
        if ($item->getTtl() > 0) {
            $options['EX'] = $item->getTtl();
        }
        return $this->client->set(
            $this->getKey($item->getKey()),
            $this->serializer->serialize($item->getValue()),
            $options
        );
    }

    public function __destruct()
    {
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }
}
