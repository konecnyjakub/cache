<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

interface IItemValueSerializer
{
    public function serialize(mixed $value): string;
    public function unserialize(string $value): mixed;
}
