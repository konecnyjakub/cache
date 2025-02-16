<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache pool that supports tags
 */
interface ITaggableCachePool extends CacheItemPoolInterface
{
    /**
     * Remove items from pool that have at least one of the listed tags
     *
     * @param string[] $tags
     */
    public function invalidateTags(array $tags): bool;
}
