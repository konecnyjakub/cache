<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;

/**
 * Represents a single cached item
 *
 * @internal
 */
final readonly class CacheItem
{
    public mixed $value;

    public ?int $expiresAt;

    /**
     * @param string[] $tags
     */
    public function __construct(mixed $value, DateInterval|int|null $ttl = null, public array $tags = [])
    {
        $this->value = is_object($value) ? clone $value : $value;
        $this->expiresAt = match (true) {
            $ttl === null => $ttl,
            is_int($ttl) => time() + $ttl,
            $ttl instanceof DateInterval => (new DateTime())->add($ttl)->getTimestamp(),
        };
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= time();
    }
}
