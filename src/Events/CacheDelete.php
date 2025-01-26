<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Events;

/**
 * Fires when an item is deleted from cache
 */
final readonly class CacheDelete
{
    public function __construct(public string $key)
    {
    }
}
