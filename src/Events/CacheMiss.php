<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Events;

/**
 * Fires when an item could not be retrieved from cache or was expired
 */
final readonly class CacheMiss
{
    public function __construct(public string $key)
    {
    }
}
