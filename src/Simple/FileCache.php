<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DirectoryIterator;
use Konecnyjakub\Cache\Common\IItemValueSerializer;
use Konecnyjakub\Cache\Common\PhpSerializer;
use SplFileInfo;

/**
 * Simple file cache
 *
 * Stores values in a specified directory in the file system
 */
final class FileCache extends BaseCache
{
    private const string CACHE_FILE_EXTENSION = ".cache";

    private const string META_FILE_EXTENSION = ".meta";

    private const string META_EXPIRES_AT_TEXT = "expiresAt=";

    private readonly string $directory;

    /**
     * @param string $directory Base directory for cache
     * @param string $namespace Optional namespace for this instance. Creates a sub-directory in base directory
     * @param int|null $defaultTtl Default life time in seconds for items if not provided for a specific item
     * @param IItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        string $directory,
        private readonly string $namespace = "",
        private readonly ?int $defaultTtl = null,
        private readonly IItemValueSerializer $serializer = new PhpSerializer()
    ) {
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new InvalidDirectoryException(sprintf(
                "Directory %s does not exist or is not writable",
                realpath($directory)
            ));
        }
        $this->directory = (string) realpath($directory);
        if ($this->namespace !== "" && !is_dir($this->getFullPath())) {
            mkdir($this->getFullPath(), 0755);
        }
    }

    protected function doGet(string $key): mixed
    {
        return $this->serializer->unserialize((string) file_get_contents($this->getFilePath($key)));
    }

    protected function doSet(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $item = new CacheItem($value, $ttl ?? $this->defaultTtl);
        $result = (bool) file_put_contents(
            $this->getFilePath($key),
            $this->serializer->serialize($item->value),
            LOCK_EX
        );
        $meta = self::META_EXPIRES_AT_TEXT . $item->expiresAt;
        $result = $result && (bool) file_put_contents($this->getMetaFilePath($key), $meta, LOCK_EX);
        return $result;
    }

    protected function doDelete(string $key): bool
    {
        $result = @unlink($this->getFilePath($key)); // phpcs:ignore Generic.PHP.NoSilencedErrors
        $result = $result && @unlink($this->getMetaFilePath($key)); // phpcs:ignore Generic.PHP.NoSilencedErrors
        return $result;
    }

    public function clear(): bool
    {
        $result = true;
        /** @var SplFileInfo $fileInfo */ // @phpstan-ignore varTag.nativeType
        foreach (new DirectoryIterator($this->getFullPath()) as $fileInfo) {
            if (
                str_ends_with($fileInfo->getFilename(), self::CACHE_FILE_EXTENSION) ||
                str_ends_with($fileInfo->getFilename(), self::META_FILE_EXTENSION)
            ) {
                $result = $result && @unlink($fileInfo->getPathname()); // phpcs:ignore Generic.PHP.NoSilencedErrors
            }
        }
        return $result;
    }

    protected function doHas(string $key): bool
    {
        if (!file_exists($this->getFilePath($key)) || !file_exists($this->getMetaFilePath($key))) {
            return false;
        }
        /** @var list<string> $metaLines */
        $metaLines = file($this->getMetaFilePath($key));
        $expiresAt = PHP_INT_MAX;
        foreach ($metaLines as $line) {
            if (str_starts_with($line, self::META_EXPIRES_AT_TEXT)) {
                $expiresAt = (int) str_replace(self::META_EXPIRES_AT_TEXT, "", $line);
            }
        }
        return $expiresAt > time();
    }

    private function getFullPath(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR .
            ($this->namespace !== "" ? $this->namespace . DIRECTORY_SEPARATOR : "");
    }

    private function getFilePath(string $key): string
    {
        return $this->getFullPath() . $key . self::CACHE_FILE_EXTENSION;
    }

    private function getMetaFilePath(string $key): string
    {
        return $this->getFullPath() . $key . self::META_FILE_EXTENSION;
    }
}
