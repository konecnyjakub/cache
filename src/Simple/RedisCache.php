<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;

final class RedisCache extends BaseCache
{
    private readonly Redis $client;

    private bool $connected = false;

    /**
     * @param int $namespace Database to use
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(
        private readonly string $host,
        ?Redis $client = null,
        private readonly int $namespace = 0,
        private readonly ?int $defaultTtl = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->client = $client ?? new Redis();
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function doGet(string $key): mixed
    {
        $this->connect();
        return $this->client->get($key);
    }

    protected function doSet(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        if ((is_int($ttl) && $ttl < 0)) {
            return true;
        }
        $this->connect();
        $options = [];
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        } elseif ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        if (is_int($ttl)) {
            $options['EX'] = $ttl;
        }
        return $this->client->set($key, $value, $options);
    }

    protected function doDelete(string $key): bool
    {
        $this->connect();
        $this->client->del($key);
        return true;
    }

    protected function doHas(string $key): bool
    {
        $this->connect();
        return (bool) $this->client->exists($key);
    }

    protected function doClear(): bool
    {
        $this->connect();
        return $this->client->flushDB();
    }

    private function connect(): void
    {
        if ($this->connected) {
            return;
        }
        $this->client->connect($this->host);
        $this->client->select($this->namespace);
        $this->connected = true;
    }
}
