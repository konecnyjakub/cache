<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;
use Konecnyjakub\Cache\Common\IItemValueSerializer;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;

/**
 * Simple redis cache
 *
 * Stores values on a redis server
 */
final class RedisCache extends BaseCache
{
    /**
     * @param string $namespace Optional namespace for this instance. Is added as prefix to keys
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     * @param IItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        private readonly Redis $client,
        string $namespace = "",
        ?int $defaultTtl = null,
        private readonly IItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        parent::__construct($namespace, $defaultTtl, $eventDispatcher);
    }

    protected function doGet(string $key): mixed
    {
        return $this->serializer->unserialize($this->client->get($this->getKey($key))); // @phpstan-ignore argument.type
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl, array $tags = []): bool
    {
        if ((is_int($ttl) && $ttl < 0)) {
            return true;
        }
        $options = [];
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        }
        if (is_int($ttl)) {
            $options['EX'] = $ttl;
        }
        return $this->client->set($this->getKey($key), $this->serializer->serialize($value), $options);
    }

    protected function doDelete(string $key): bool
    {
        $this->client->del($this->getKey($key));
        return true;
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

    public function __destruct()
    {
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }
}
