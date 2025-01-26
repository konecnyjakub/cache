<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use APCUIterator;
use DateInterval;
use DateTime;

/**
 * Simple apcu cache
 *
 * Stores values in memory using apcu
 */
final class ApcuCache extends BaseCache
{
    /**
     * @param string $namespace Optional namespace for this instance. Is added as prefix to keys
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     */
    public function __construct(private readonly string $namespace = "", private readonly ?int $defaultTtl = null)
    {
    }

    protected function doGet(string $key): mixed
    {
        $value = apcu_fetch($this->getKey($key), $success);
        return $success ? $value : null;
    }

    protected function doSet(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime())->add($ttl)->getTimestamp() - time();
        } elseif ($ttl === null) {
            $ttl = $this->defaultTtl;
        }
        return apcu_store($this->getKey($key), $value, (int) $ttl);
    }

    protected function doDelete(string $key): bool
    {
        return apcu_delete($this->getKey($key));
    }

    protected function doHas(string $key): bool
    {
        return apcu_exists($this->getKey($key));
    }

    protected function doClear(): bool
    {
        if ($this->namespace === "") {
            return apcu_clear_cache();
        }
        $result = true;
        /** @var array{key: string, value: mixed} $counter */
        foreach (new APCUIterator("/^test:(.+)/") as $counter) {
            $result = $result && $this->doDelete(str_replace($this->namespace . ":", "", $counter["key"]));
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
}
