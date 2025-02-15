<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use DirectoryIterator;
use SplFileInfo;

final readonly class SimpleFileJournal implements IJournal
{
    private const string FILE_EXTENSION = ".meta";

    private const string EXPIRES_AT_TEXT = "expiresAt=";

    public function __construct(private string $directory)
    {
    }

    public function get(string $key): CacheItemMetadata
    {
        if (!file_exists($this->getFilename($key))) {
            return new CacheItemMetadata();
        }

        /** @var list<string> $lines */
        $lines = file($this->getFilename($key));
        $expiresAt = null;
        foreach ($lines as $line) {
            if (str_starts_with($line, self::EXPIRES_AT_TEXT)) {
                $expiresAt = (int) str_replace(self::EXPIRES_AT_TEXT, "", $line);
            }
        }

        return new CacheItemMetadata($expiresAt);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $content = "";
        if ($metadata->expiresAt !== null) {
            $content = self::EXPIRES_AT_TEXT . $metadata->expiresAt;
        }
        return (bool) file_put_contents($this->getFilename($key), $content, LOCK_EX);
    }

    public function clear(?string $key = null): bool
    {
        if ($key !== null) {
            return @unlink($this->getFilename($key)); // phpcs:ignore Generic.PHP.NoSilencedErrors
        }

        $result = true;
        /** @var SplFileInfo $fileInfo */ // @phpstan-ignore varTag.nativeType
        foreach (new DirectoryIterator($this->directory) as $fileInfo) {
            if (
                str_ends_with($fileInfo->getFilename(), self::FILE_EXTENSION)
            ) {
                $result = $result && @unlink($fileInfo->getPathname()); // phpcs:ignore Generic.PHP.NoSilencedErrors
            }
        }
        return $result;
    }

    /**
     * @internal
     */
    public function getFilename(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $key . self::FILE_EXTENSION;
    }
}
