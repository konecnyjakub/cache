<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

final readonly class IniFileJournal implements IJournal
{
    public function __construct(private string $directory)
    {
    }

    public function get(string $key): CacheItemMetadata
    {
        $ini = $this->getParsedIni();
        if (!array_key_exists($key, $ini)) {
            return new CacheItemMetadata();
        }

        /** @var array<string, array<string, mixed>> $ini */
        $expiresAt = $ini[$key]["expiresAt"];
        /** @var string[] $tags */
        $tags = $ini[$key]["tags"] ?? [];
        return new CacheItemMetadata(is_int($expiresAt) ? $expiresAt : null, $tags);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $contents = $this->getParsedIni();

        /** @var array<string, array<string, mixed>> $contents */
        $contents[$key] = [
            "expiresAt" => $metadata->expiresAt,
            "tags" => $metadata->tags,
        ];

        $iniString = "";
        foreach ($contents as $section => $values) {
            $iniString .= $this->toIni($section, $values);
        }

        if ($iniString === "") {
            return $this->clear();
        }

        return (bool) file_put_contents($this->getFilename(), $iniString, LOCK_EX);
    }

    public function clear(?string $key = null): bool
    {
        if ($key === null) {
            return !file_exists($this->getFilename()) ||
                @unlink($this->getFilename()); // phpcs:ignore Generic.PHP.NoSilencedErrors
        }

        return $this->set($key, new CacheItemMetadata());
    }

    public function getKeysByTags(array $tags): array
    {
        $keys = [];
        $ini = $this->getParsedIni();

        foreach ($ini as $key => $values) {
            /** @var string[] $keyTags */
            $keyTags = $values[self::KEY_TAGS] ?? [];
            if (count(array_intersect($tags, $keyTags)) > 0) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @internal
     */
    public function getFilename(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . "journal.ini";
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getParsedIni(): array
    {
        if (!file_exists($this->getFilename())) {
            return [];
        }

        return parse_ini_file($this->getFilename(), true, INI_SCANNER_TYPED); // @phpstan-ignore return.type
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function toIni(string $key, array $metadata): string
    {
        $content = "";

        if (isset($metadata["expiresAt"]) && is_int($metadata["expiresAt"])) {
            $content .= "expiresAt = {$metadata["expiresAt"]}\n";
        }
        if (isset($metadata["tags"]) && is_array($metadata["tags"])) {
            foreach ($metadata["tags"] as $tag) {
                if (is_string($tag)) {
                    $content .= "tags[] = $tag\n";
                }
            }
        }

        if ($content !== "") {
            $content = "[$key]\n" . $content;
        }
        return $content;
    }
}
