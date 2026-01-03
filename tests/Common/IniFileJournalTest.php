<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\AfterTestSuite;
use MyTester\Attributes\BeforeTestSuite;
use MyTester\Attributes\Group;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("IniFileJournal")]
#[Group("journals")]
final class IniFileJournalTest extends TestCase
{
    #[BeforeTestSuite]
    public function createCacheDir(): void
    {
        mkdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    #[AfterTestSuite]
    public function removeCacheDir(): void
    {
        (new IniFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache"))->clear();
        rmdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function testProcess(): void
    {
        $journal = new IniFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
        $key1 = "abc";
        $key2 = "def";

        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata()));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(30, ["tag1", "tag2", ])));
        $this->assertTrue(file_exists($journal->getFilename()));
        $this->assertSame(
            "[abc]\nexpiresAt = 30\ntags[] = tag1\ntags[] = tag2\n",
            file_get_contents($journal->getFilename())
        );
        $this->assertSame([$key1, ], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(30, $metadata->expiresAt);
        $this->assertSame(["tag1", "tag2", ], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->clear($key1));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertFalse(file_exists($journal->getFilename()));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(40)));
        $this->assertTrue($journal->set($key2, new CacheItemMetadata(50)));
        $this->assertTrue(file_exists($journal->getFilename()));
        $this->assertSame("[abc]\nexpiresAt = 40\n[def]\nexpiresAt = 50\n", file_get_contents($journal->getFilename()));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(40, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(50, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->clear());
        $this->assertFalse(file_exists($journal->getFilename()));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
    }
}
