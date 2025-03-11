<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

/**
 * Serializer that uses just PHP function {@see serialize} and {@see unserialize()}
 */
final readonly class PhpSerializer implements ItemValueSerializer
{
    public function serialize(mixed $value): string
    {
        return serialize($value);
    }

    public function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
