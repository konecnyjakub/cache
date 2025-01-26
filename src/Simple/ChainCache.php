<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Psr\SimpleCache\CacheInterface;

/**
 * Simple chain cache
 *
 * Tries all reading operations on all available engines until one returns data
 * Performs all writing operations on all available engines
 */
final class ChainCache extends BaseCache
{
    /** @var CacheInterface[] */
    private array $engines = [];

    public function addEngine(CacheInterface $engine): void
    {
        $this->engines[] = $engine;
    }

    protected function doGet(string $key): mixed
    {
        foreach ($this->engines as $engine) {
            if ($engine->has($key)) {
                return $engine->get($key);
            }
        }
        return null;
    }

    protected function doSet(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->set($key, $value, $ttl);
        }
        return $result;
    }

    protected function doDelete(string $key): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->delete($key);
        }
        return $result;
    }

    protected function doHas(string $key): bool
    {
        foreach ($this->engines as $engine) {
            if ($engine->has($key)) {
                return true;
            }
        }
        return false;
    }

    public function clear(): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->clear();
        }
        return $result;
    }
}
