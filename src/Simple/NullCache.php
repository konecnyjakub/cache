<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Simple null cache
 *
 * Does not store any values. Can be used to disable caching
 */
final class NullCache extends BaseCache
{
    public function __construct(?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function doGet(string $key): mixed
    {
        return null;
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return true;
    }

    protected function doDelete(string $key): bool
    {
        return true;
    }

    protected function doClear(): bool
    {
        return true;
    }

    protected function doHas(string $key): bool
    {
        return false;
    }
}
