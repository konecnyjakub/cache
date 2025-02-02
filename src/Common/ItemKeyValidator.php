<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use Traversable;

final readonly class ItemKeyValidator
{
    public function isKeyValid(mixed $key): bool
    {
        return is_string($key) && $key !== "" && strlen($key) <= 64 && strpbrk($key, "{}()/\@:") === false;
    }

    /**
     * @param mixed[] $keys
     */
    public function isKeysValid(iterable $keys): bool
    {
        $keys = $keys instanceof Traversable ? iterator_to_array($keys) : $keys;
        foreach ($keys as $key) {
            if (!$this->isKeyValid($key)) {
                return false;
            }
        }
        return true;
    }
}
