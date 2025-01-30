<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Simple chain cache
 *
 * Tries all reading operations on all available engines until one returns data
 * Performs all writing operations on all available engines
 */
final class ChainCache implements CacheInterface
{
    /** @var CacheInterface[] */
    private array $engines = [];

    public function addEngine(CacheInterface $engine): void
    {
        $this->engines[] = $engine;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->engines as $engine) {
            if ($engine->has($key)) {
                return $engine->get($key);
            }
        }
        return $default;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->set($key, $value, $ttl);
        }
        return $result;
    }

    public function delete(string $key): bool
    {
        $result = true;
        foreach ($this->engines as $engine) {
            $result = $result && $engine->delete($key);
        }
        return $result;
    }

    public function has(string $key): bool
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

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param mixed[] $values
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        /**
         * @var string $key
         */
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $result && $this->delete($key);
        }
        return $result;
    }
}
