<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class CacheItemMetadata
{
    /**
     * @internal
     */
    public function __construct(public ?int $expiresAt = null)
    {
    }
}
