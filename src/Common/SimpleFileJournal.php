<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use DirectoryIterator;
use SplFileInfo;

final readonly class SimpleFileJournal implements IJournal
{
    private const string FILE_EXTENSION = ".meta";

    private const string TEXT_EXPIRES_AT = "expiresAt=";
    private const string TEXT_TAGS = "tags=";

    public function __construct(private string $directory)
    {
    }

    public function get(string $key): CacheItemMetadata
    {
        if (!file_exists($this->getFilename($key))) {
            return new CacheItemMetadata();
        }

        /** @var list<string> $lines */
        $lines = file($this->getFilename($key), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $expiresAt = null;
        $tags = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, self::TEXT_EXPIRES_AT)) {
                $expiresAt = (int) str_replace(self::TEXT_EXPIRES_AT, "", $line);
            }
            if (str_starts_with($line, self::TEXT_TAGS)) {
                $tags = explode(",", str_replace(self::TEXT_TAGS, "", $line));
            }
        }

        return new CacheItemMetadata($expiresAt, $tags);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $content = "";
        if ($metadata->expiresAt !== null) {
            $content .= self::TEXT_EXPIRES_AT . $metadata->expiresAt . "\n";
        }
        if (count($metadata->tags) > 0) {
            $content .= self::TEXT_TAGS . join(",", $metadata->tags) . "\n";
        }
        return $content === "" ?
            $this->clear($key) : (bool) file_put_contents($this->getFilename($key), $content, LOCK_EX);
    }

    public function clear(?string $key = null): bool
    {
        if ($key !== null) {
            if (!file_exists($this->getFilename($key))) {
                return true;
            }
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

    public function getKeysByTags(array $tags): array
    {
        $keys = [];

        /** @var SplFileInfo $fileInfo */ // @phpstan-ignore varTag.nativeType
        foreach (new DirectoryIterator($this->directory) as $fileInfo) {
            if (
                str_ends_with($fileInfo->getFilename(), self::FILE_EXTENSION)
            ) {
                $key = $fileInfo->getBasename(self::FILE_EXTENSION);
                $metadata = $this->get($key);
                if (count(array_intersect($tags, $metadata->tags)) > 0) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    /**
     * @internal
     */
    public function getFilename(string $key): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $key . self::FILE_EXTENSION;
    }
}
