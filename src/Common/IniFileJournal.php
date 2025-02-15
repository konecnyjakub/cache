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
        $ini = file_exists($this->getFilename()) ?
            parse_ini_file($this->getFilename(), true, INI_SCANNER_TYPED) : false;
        if ($ini === false || !array_key_exists($key, $ini)) {
            return new CacheItemMetadata();
        }

        /** @var array<string, array<string, mixed>> $ini */
        $expiresAt = $ini[$key]["expiresAt"];
        return new CacheItemMetadata(is_int($expiresAt) ? $expiresAt : null);
    }

    public function set(string $key, CacheItemMetadata $metadata): bool
    {
        $contents = file_exists($this->getFilename()) ?
            parse_ini_file($this->getFilename(), true, INI_SCANNER_TYPED) : false;
        if ($contents === false) {
            $contents = [];
        }

        /** @var array<string, array<string, mixed>> $contents */
        $contents[$key] = [
            "expiresAt" => $metadata->expiresAt,
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

    /**
     * @internal
     */
    public function getFilename(): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . "journal.ini";
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

        if ($content !== "") {
            $content = "[$key]\n" . $content;
        }
        return $content;
    }
}
