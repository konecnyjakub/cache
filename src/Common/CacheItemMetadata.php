<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class CacheItemMetadata
{
    /**
     * @internal
     * @param string[] $tags
     */
    public function __construct(public ?int $expiresAt = null, public array $tags = [])
    {
    }
}
