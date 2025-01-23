<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Traversable;

trait CacheUtils
{
    protected function validateKey(mixed $key): void
    {
        if (!is_string($key) || $key === '' || strlen($key) > 64 || strpbrk($key, "{}()/\@:") !== false) {
            throw new InvalidKeyException();
        }
    }

    /**
     * @param mixed[] $keys
     */
    protected function validateKeys(iterable $keys): void
    {
        $keys = $this->iterableToArray($keys);
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }

    /**
     * @param mixed[] $array
     * @return mixed[]
     */
    protected function iterableToArray(iterable $array): array
    {
        return $array instanceof Traversable ? iterator_to_array($array) : $array;
    }
}
