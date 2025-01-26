<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Events;

/**
 * Fires when an item is saved in cache
 */
final readonly class CacheSave
{
    public function __construct(public string $key, public mixed $value)
    {
    }
}
