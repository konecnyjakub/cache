<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use DirectoryIterator;
use Konecnyjakub\Cache\Common\CacheItemMetadata;
use Konecnyjakub\Cache\Common\IItemValueSerializer;
use Konecnyjakub\Cache\Common\IJournal;
use Konecnyjakub\Cache\Common\IniFileJournal;
use Konecnyjakub\Cache\Common\PhpSerializer;
use Psr\EventDispatcher\EventDispatcherInterface;
use SplFileInfo;

/**
 * File cache pool
 *
 * Stores values in a specified directory in the file system
 */
final class FileCachePool extends BaseCachePool
{
    private const string CACHE_FILE_EXTENSION = ".cache";

    private readonly string $directory;

    private readonly IJournal $journal;

    /**
     * @param string $directory Base directory for cache
     * @param string $namespace Optional namespace for this instance. Creates a sub-directory in base directory
     * @param int $defaultTtl Default life time in seconds for items if not provided for a specific item
     * @param IItemValueSerializer $serializer Used when saving into/reading from cache files
     */
    public function __construct(
        string $directory,
        string $namespace = "",
        int $defaultTtl = 1000000000,
        private readonly IItemValueSerializer $serializer = new PhpSerializer(),
        ?EventDispatcherInterface $eventDispatcher = null,
        ?IJournal $journal = null
    ) {
        parent::__construct($namespace, $defaultTtl, $eventDispatcher);
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
        $this->journal = $journal ?? new IniFileJournal($this->getFullPath());
    }

    protected function doGet(string $key): CacheItem
    {
        return new CacheItem(
            $key,
            $this->serializer->unserialize((string) file_get_contents($this->getFilePath($key))),
            true
        );
    }

    protected function doHas(string $key): bool
    {
        if (!file_exists($this->getFilePath($key))) {
            return false;
        }

        $meta = $this->journal->get($key);
        return $meta->expiresAt === null || $meta->expiresAt > time();
    }

    protected function doClear(): bool
    {
        $result = true;
        /** @var SplFileInfo $fileInfo */ // @phpstan-ignore varTag.nativeType
        foreach (new DirectoryIterator($this->getFullPath()) as $fileInfo) {
            if (
                str_ends_with($fileInfo->getFilename(), self::CACHE_FILE_EXTENSION)
            ) {
                $result = $result && @unlink($fileInfo->getPathname()); // phpcs:ignore Generic.PHP.NoSilencedErrors
            }
        }
        return $result && $this->journal->clear();
    }

    protected function doDelete(string $key): bool
    {
        $result = @unlink($this->getFilePath($key)); // phpcs:ignore Generic.PHP.NoSilencedErrors
        return $result && $this->journal->clear($key);
    }

    protected function doSave(CacheItem $item): bool
    {
        $result = (bool) file_put_contents(
            $this->getFilePath($item->getKey()),
            $this->serializer->serialize($item->getValue()),
            LOCK_EX
        );
        return $result && $this->journal->set($item->getKey(), new CacheItemMetadata($item->getTtl() + time()));
    }

    /**
     * @internal
     */
    public function getKey(string $key): string
    {
        return ($this->namespace !== "" ? $this->namespace . DIRECTORY_SEPARATOR : "") . $key;
    }

    private function getFullPath(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $this->getKey("");
    }

    /**
     * @internal
     */
    public function getFilePath(string $key): string
    {
        return $this->getFullPath() . $key . self::CACHE_FILE_EXTENSION;
    }
}
