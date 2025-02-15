<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("SimpleFileJournal")]
final class SimpleFileJournalTest extends TestCase
{
    public function startUp(): void
    {
        mkdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function shutDown(): void
    {
        (new SimpleFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache"))->clear();
        rmdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function testProcess(): void
    {
        $journal = new SimpleFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
        $key1 = "abc";
        $key2 = "def";

        $this->assertFalse(file_exists($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertFalse(file_exists($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata()));
        $this->assertFalse(file_exists($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertFalse(file_exists($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(30)));
        $this->assertTrue(file_exists($journal->getFilename($key1)));
        $this->assertSame("expiresAt=30", file_get_contents($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(30, $metadata->expiresAt);
        $this->assertFalse(file_exists($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->clear($key1));
        $this->assertFalse(file_exists($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertFalse(file_exists($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(40)));
        $this->assertTrue($journal->set($key2, new CacheItemMetadata(50)));
        $this->assertTrue(file_exists($journal->getFilename($key1)));
        $this->assertSame("expiresAt=40", file_get_contents($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(40, $metadata->expiresAt);
        $this->assertTrue(file_exists($journal->getFilename($key2)));
        $this->assertSame("expiresAt=50", file_get_contents($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(50, $metadata->expiresAt);

        $this->assertTrue($journal->clear());
        $this->assertFalse(file_exists($journal->getFilename($key1)));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertFalse(file_exists($journal->getFilename($key2)));
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
    }
}
