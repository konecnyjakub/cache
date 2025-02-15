<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("IniFileJournal")]
final class IniFileJournalTest extends TestCase
{
    public function startUp(): void
    {
        mkdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function shutDown(): void
    {
        (new IniFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache"))->clear();
        rmdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function testProcess(): void
    {
        $journal = new IniFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
        $key1 = "abc";
        $key2 = "def";

        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $journal->set($key1, new CacheItemMetadata());
        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $journal->set($key1, new CacheItemMetadata(30));
        $this->assertTrue(file_exists($journal->getFilename()));
        $this->assertSame("[abc]\nexpiresAt = 30\n", file_get_contents($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(30, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $journal->clear($key1);
        $metadata = $journal->get($key1);
        $this->assertFalse(file_exists($journal->getFilename()));
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $journal->set($key1, new CacheItemMetadata(40));
        $journal->set($key2, new CacheItemMetadata(50));
        $this->assertTrue(file_exists($journal->getFilename()));
        $this->assertSame("[abc]\nexpiresAt = 40\n[def]\nexpiresAt = 50\n", file_get_contents($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(40, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(50, $metadata->expiresAt);

        $journal->clear();
        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
    }
}
