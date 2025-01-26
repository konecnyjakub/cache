<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Events;

/**
 * Fires when an item is successfully retrieved from cache
 */
final readonly class CacheHit
{
    public function __construct(public string $key, public mixed $value)
    {
    }
}
