<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Psr\SimpleCache\CacheInterface;

/**
 * Cache that supports tags
 */
interface TaggableCache extends CacheInterface
{
    /**
     * @param string[] $tags
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null, array $tags = []): bool;

    /**
     * Remove items from pool that have at least one of the listed tags
     *
     * @param string[] $tags
     */
    public function invalidateTags(array $tags): bool;
}
