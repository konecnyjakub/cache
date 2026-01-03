<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\Group;
use MyTester\Attributes\RequiresPhpExtension;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;
use PDO;

#[TestSuite("SqliteJournal")]
#[RequiresPhpExtension("pdo")]
#[Group("journals")]
final class SqliteJournalTest extends TestCase
{
    public function testProcess(): void
    {
        $pdo = new PDO("sqlite::memory:");
        $journal = new SqliteJournal($pdo);
        $key1 = "abc";
        $key2 = "def";

        $this->assertSame([], iterator_to_array($journal->getKeysByTags([])));

        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata()));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(30, ["tag1", "tag2", ])));
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
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->set($key1, new CacheItemMetadata(40)));
        $this->assertTrue($journal->set($key2, new CacheItemMetadata(50)));
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(40, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(50, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);

        $this->assertTrue($journal->clear());
        $this->assertSame([], iterator_to_array($journal->getKeysByTags(["tag1", ])));
        $metadata = $journal->get($key1);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
        $metadata = $journal->get($key2);
        $this->assertSame(null, $metadata->expiresAt);
        $this->assertSame([], $metadata->tags);
    }
}
