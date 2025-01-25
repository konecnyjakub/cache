<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;
use Memcached;

/**
 * Simple memcached cache
 *
 * Stores values on a memcached server
 */
final class MemcachedCache extends BaseCache
{
    /**
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(
        private readonly Memcached $client,
        private readonly ?int $defaultTtl = null
    ) {
    }

    protected function doGet(string $key): mixed
    {
        return $this->client->get($this->getKey($key));
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        } elseif ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        return $this->client->set($this->getKey($key), $value, (int) $ttl);
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

    public function clear(): bool
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
