<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use Devium\Toml\Toml;
use Devium\Toml\TomlError;

final readonly class TomlFileJournal implements Journal
{
    private const string KEY_EXPIRES_AT = "expiresAt";
    private const string KEY_TAGS = "tags";

    public function __construct(private string $directory)
    {
    }

    public function get(string $key): CacheItemMetadata
    {
        $toml = $this->getParsedToml();
        if (!array_key_exists($key, $toml)) {
            return new CacheItemMetadata();
        }

        $expiresAt = $toml[$key][self::KEY_EXPIRES_AT] ?? null;
        /** @var string[] $tags */
        $tags = $toml[$key][self::KEY_TAGS] ?? [];
        return new CacheItemMetadata(is_int($expiresAt) ? $expiresAt : null, $tags);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $contents = $this->getParsedToml();
        $contents[$key] = [
            self::KEY_EXPIRES_AT => $metadata->expiresAt,
            self::KEY_TAGS => $metadata->tags,
        ];

        foreach ($contents as $itemKey => &$values) {
            if ($values[self::KEY_EXPIRES_AT] === null) {
                unset($values[self::KEY_EXPIRES_AT]);
            }
            if (isset($values[self::KEY_TAGS]) && $values[self::KEY_TAGS] === []) {
                unset($values[self::KEY_TAGS]);
            }
            if (!isset($values[self::KEY_EXPIRES_AT]) && !isset($values[self::KEY_TAGS])) {
                unset($contents[$itemKey]);
            }
        }

        if (count($contents) === 0) {
            return $this->clear();
        }

        return (bool) file_put_contents($this->getFilename(), Toml::encode($contents));
    }

    public function clear(?string $key = null): bool
    {
        if ($key === null) {
            // phpcs:ignore Generic.PHP.NoSilencedErrors
            return !file_exists($this->getFilename()) || @unlink($this->getFilename());
        }

        return $this->set($key, new CacheItemMetadata());
    }

    public function getKeysByTags(array $tags): iterable
    {
        $toml = $this->getParsedToml();

        foreach ($toml as $key => $values) {
            /** @var string[] $keyTags */
            $keyTags = $values[self::KEY_TAGS] ?? [];
            if (count(array_intersect($tags, $keyTags)) > 0) {
                yield $key;
            }
        }
    }

    /**
     * @internal
     */
    public function getFilename(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . "journal.toml";
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws TomlError
     */
    private function getParsedToml(): array
    {
        if (!file_exists($this->getFilename())) {
            return [];
        }

        return Toml::decode((string) file_get_contents($this->getFilename()), true); // @phpstan-ignore return.type
    }
}
