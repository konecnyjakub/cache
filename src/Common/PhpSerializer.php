<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class PhpSerializer implements IItemValueSerializer
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
