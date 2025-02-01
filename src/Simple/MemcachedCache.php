<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;
use Konecnyjakub\Cache\Common\IItemValueSerializer;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Memcached;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Simple memcached cache
 *
 * Stores values on a memcached server
 */
final class MemcachedCache extends BaseCache
{
    /**
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     * @param IItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        private readonly Memcached $client,
        ?int $defaultTtl = null,
        private readonly IItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct("", $defaultTtl, $eventDispatcher);
    }

    protected function doGet(string $key): mixed
    {
        return $this->serializer->unserialize($this->client->get($this->getKey($key))); // @phpstan-ignore argument.type
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        }
        return $this->client->set($this->getKey($key), $this->serializer->serialize($value), (int) $ttl);
    }

    protected function doDelete(string $key): bool
    {
        $this->client->delete($this->getKey($key));
        return in_array($this->client->getResultCode(), [Memcached::RES_SUCCESS, Memcached::RES_NOTFOUND], true);
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

    /**
     * @internal
     */
    public function getKey(string $key): string
    {
        return $key;
    }
}
