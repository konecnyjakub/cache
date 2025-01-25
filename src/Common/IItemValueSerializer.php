<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

/**
 * Takes care of serializing and unserializing items when they are saved into/read from cache
 */
interface IItemValueSerializer
{
    public function serialize(mixed $value): string;
    public function unserialize(string $value): mixed;
}
