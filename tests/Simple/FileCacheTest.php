<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Konecnyjakub\Cache\Common\IniFileJournal;
use Konecnyjakub\Cache\Events;
use Konecnyjakub\Cache\TestEventsLogger;
use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use MyTester\Attributes\AfterTest;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("FileCache")]
final class FileCacheTest extends TestCase
{
    #[AfterTest]
    public function clearCache(): void
    {
        (new FileCache(__DIR__, "fileCache"))->clear();
        rmdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $default = "default";
        $cache = new FileCache(__DIR__, "fileCache");

        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertFalse(file_exists($cache->getFilePath($key)));

        $this->assertTrue($cache->set($key, $value, -1));
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertTrue(file_exists($cache->getFilePath($key)));

        $this->assertTrue($cache->set($key, $value, 30));
        $this->assertTrue($cache->has($key));
        $this->assertSame($value, $cache->get($key, $default));
        $this->assertTrue(file_exists($cache->getFilePath($key)));

        $this->assertTrue($cache->delete($key));
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertFalse(file_exists($cache->getFilePath($key)));
    }

    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $default = "default";
        $key2 = "two";
        $value2 = "def";
        $cache = new FileCache(__DIR__, "fileCache");

        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertFalse(file_exists($cache->getFilePath($key1)));
        $this->assertFalse(file_exists($cache->getFilePath($key2)));

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], -1));
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertTrue(file_exists($cache->getFilePath($key1)));
        $this->assertTrue(file_exists($cache->getFilePath($key2)));

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30));
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertTrue(file_exists($cache->getFilePath($key1)));
        $this->assertTrue(file_exists($cache->getFilePath($key2)));

        $this->assertTrue($cache->deleteMultiple([$key1, $key2, ]));
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertFalse(file_exists($cache->getFilePath($key1)));
        $this->assertFalse(file_exists($cache->getFilePath($key2)));

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30));
        $this->assertTrue(file_exists($cache->getFilePath($key1)));
        $this->assertTrue(file_exists($cache->getFilePath($key2)));
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertFalse(file_exists($cache->getFilePath($key1)));
        $this->assertFalse(file_exists($cache->getFilePath($key2)));
    }

    public function testDefaultTtl(): void
    {
        $key = "ttl";
        $value = "abc";
        $cache = new FileCache(__DIR__, "fileCache", defaultTtl: -1);

        $this->assertTrue($cache->set($key, $value));
        $this->assertFalse($cache->has($key));

        $this->assertTrue($cache->set($key, $value, 30));
        $this->assertTrue($cache->has($key));
    }

    public function testSerializer(): void
    {
        $cache = new FileCache(__DIR__, "fileCache");

        $key = "number";
        $value = 123;
        $this->assertTrue($cache->set($key, $value));
        $this->assertSame($value, $cache->get($key));
        $this->assertType("int", $cache->get($key));
    }

    public function testEvents(): void
    {
        $eventsLogger = new TestEventsLogger();
        $listenerProvider = new AutoListenerProvider();
        $listenerProvider->addSubscriber($eventsLogger);
        $eventDispatcher = new EventDispatcher($listenerProvider);
        $cache = new FileCache(__DIR__, "fileCache", eventDispatcher: $eventDispatcher);
        $key = "one";
        $value = "abc";
        $cache->get($key);
        $cache->set($key, $value);
        $cache->get($key);
        $cache->delete($key);
        $cache->clear();
        $this->assertCount(5, $eventsLogger->events);
        /** @var Events\CacheMiss $event */
        $event = $eventsLogger->events[0];
        $this->assertType(Events\CacheMiss::class, $event);
        $this->assertSame($key, $event->key);
        /** @var Events\CacheSave $event */
        $event = $eventsLogger->events[1];
        $this->assertType(Events\CacheSave::class, $event);
        $this->assertSame($key, $event->key);
        $this->assertSame($value, $event->value);
        /** @var Events\CacheHit $event */
        $event = $eventsLogger->events[2];
        $this->assertType(Events\CacheHit::class, $event);
        $this->assertSame($key, $event->key);
        $this->assertSame($value, $event->value);
        /** @var Events\CacheDelete $event */
        $event = $eventsLogger->events[3];
        $this->assertType(Events\CacheDelete::class, $event);
        $this->assertSame($key, $event->key);
        /** @var Events\CacheClear $event */
        $event = $eventsLogger->events[4];
        $this->assertType(Events\CacheClear::class, $event);
    }

    public function testTags(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $tags1 = ["tag1", "tag2", ];
        $key2 = "two";
        $value2 = "def";
        $tags2 = ["tag2", ];
        $journal = new IniFileJournal(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
        $cache = new FileCache(__DIR__, "fileCache", journal: $journal);

        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertTrue($cache->set($key1, $value1, tags: $tags1));
        $this->assertTrue($cache->set($key2, $value2, tags: $tags2));
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));

        $this->assertTrue($cache->invalidateTags(["tag3", ]));
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));

        $this->assertTrue($cache->invalidateTags(["tag1", ]));
        $this->assertFalse($cache->has($key1));
        $this->assertTrue($cache->has($key2));
    }

    public function testExceptions(): void
    {
        $this->assertThrowsException(function () {
            new FileCache("/non-existings");
        }, InvalidDirectoryException::class);

        $cache = new FileCache(__DIR__, "fileCache");
        $this->assertThrowsException(function () use ($cache) {
            $cache->get("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->set("{", "abc");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->delete("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->getMultiple(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->setMultiple(["{" => "abc", ]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->deleteMultiple(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->has("{");
        }, InvalidKeyException::class);
    }
}
