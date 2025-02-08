<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

final class CacheItem implements CacheItemInterface
{
    private mixed $value;

    private ?DateTimeInterface $expiresAt = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly string $key,
        mixed $value = null,
        private readonly bool $hit = false,
        private readonly ?int $defaultTtl = null
    ) {
        $this->value = is_object($value) ? clone $value : $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        if (!$this->isHit()) {
            return null;
        }
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiresAt = $expiration;
        return $this;
    }

    public function expiresAfter(DateInterval|int|null $time): static
    {
        $expiresAt = match (true) {
            $time === null => null,
            is_int($time) => (new DateTime())->setTimestamp(time() + $time),
            $time instanceof DateInterval => (new DateTime())->add($time),
        };
        $this->expiresAt($expiresAt);
        return $this;
    }

    /**
     * @internal
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getTtl(): int
    {
        if ($this->expiresAt === null) {
            return (int) $this->defaultTtl;
        }
        return $this->expiresAt->getTimestamp() - time();
    }
}
