<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use DateTime;

/**
 * @internal
 */
final readonly class MemoryCacheItem
{
    public ?int $expiresAt;

    public function __construct(public mixed $value, DateInterval|int|null $ttl = null)
    {
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
