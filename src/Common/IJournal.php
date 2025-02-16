<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

/**
 * Handles meta data for cache items
 */
interface IJournal
{
    /**
     * Get metadata for a cache item
     */
    public function get(string $key): CacheItemMetadata;

    /**
     * Set/replace metadata for a cache item
     */
    public function set(string $key, CacheItemMetadata $metadata): bool;

    /**
     * Clear metadata for a cache item or all cache items
     *
     * @param string|null $key Name of cache item/null for all cache items
     */
    public function clear(?string $key = null): bool;

    /**
     * Get list of keys that has at least one of the listed tags
     *
     * @param string[] $tags
     * @return string[]
     */
    public function getKeysByTags(array $tags): iterable;
}
