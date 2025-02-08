<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Events;
use Konecnyjakub\Cache\TestEventsLogger;
use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("FileCachePool")]
final class FileCachePoolTest extends TestCase
{
    public function shutDown(): void
    {
        rmdir(__DIR__ . DIRECTORY_SEPARATOR . "fileCache");
    }

    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $ttl = 30;
        $pool = new FileCachePool(__DIR__, "fileCache");

        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $this->assertFalse(file_exists($pool->getFilePath($key)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key)));

        $item->set($value);
        $item->expiresAfter(-1);
        $pool->save($item);
        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $this->assertTrue(file_exists($pool->getFilePath($key)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key)));

        $item->set($value);
        $item->expiresAfter($ttl);
        $pool->save($item);
        $this->assertTrue($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());
        $this->assertTrue(file_exists($pool->getFilePath($key)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key)));

        $pool->deleteItem($key);
        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $this->assertFalse(file_exists($pool->getFilePath($key)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key)));
    }

    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $default = "default";
        $key2 = "two";
        $value2 = "def";
        $pool = new FileCachePool(__DIR__, "fileCache");

        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        $this->assertFalse(file_exists($pool->getFilePath($key1)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key1)));
        $this->assertFalse(file_exists($pool->getFilePath($key2)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key2)));

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(-1);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(-1);
        $pool->saveDeferred($items[$key1]);
        $pool->saveDeferred($items[$key2]);
        $pool->commit();
        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        $this->assertTrue(file_exists($pool->getFilePath($key1)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key1)));
        $this->assertTrue(file_exists($pool->getFilePath($key2)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key2)));

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(30);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(30);
        $pool->saveDeferred($items[$key1]);
        $pool->saveDeferred($items[$key2]);
        $pool->commit();
        $this->assertTrue($pool->hasItem($key1));
        $this->assertTrue($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame($value1, $items[$key1]->get());
        $this->assertTrue($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());
        $this->assertTrue(file_exists($pool->getFilePath($key1)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key1)));
        $this->assertTrue(file_exists($pool->getFilePath($key2)));
        $this->assertTrue(file_exists($pool->getMetaFilePath($key2)));

        $pool->deleteItems([$key1, $key2, ]);
        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        $this->assertFalse(file_exists($pool->getFilePath($key1)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key1)));
        $this->assertFalse(file_exists($pool->getFilePath($key2)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key2)));

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(30);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(30);
        $pool->saveDeferred($items[$key1]);
        $pool->saveDeferred($items[$key2]);
        $this->assertTrue($pool->clear());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        $this->assertFalse(file_exists($pool->getFilePath($key1)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key1)));
        $this->assertFalse(file_exists($pool->getFilePath($key2)));
        $this->assertFalse(file_exists($pool->getMetaFilePath($key2)));
    }

    public function testDefaultTtl(): void
    {
        $key = "ttl";
        $value = "abc";
        $pool = new FileCachePool(__DIR__, "fileCache", defaultTtl: -1);

        $item = $pool->getItem($key);
        $item->set($value);
        $pool->save($item);
        $this->assertFalse($pool->hasItem($key));

        $item->expiresAfter(30);
        $pool->save($item);
        $this->assertTrue($pool->hasItem($key));
    }

    public function testSerializer(): void
    {
        $pool = new FileCachePool(__DIR__, "fileCache");

        $key = "number";
        $value = 123;
        $item = $pool->getItem($key);
        $item->set($value);
        $pool->save($item);
        $item = $pool->getItem($key);
        $this->assertSame($value, $item->get());
        $this->assertType("int", $item->get());
    }

    public function testEvents(): void
    {
        $eventsLogger = new TestEventsLogger();
        $listenerProvider = new AutoListenerProvider();
        $listenerProvider->addSubscriber($eventsLogger);
        $eventDispatcher = new EventDispatcher($listenerProvider);
        $pool = new FileCachePool(__DIR__, "fileCache", eventDispatcher: $eventDispatcher);
        $key = "one";
        $value = "abc";
        $pool->getItem($key);
        $pool->save(new CacheItem($key, $value, defaultTtl: 1000000000));
        $pool->getItem($key);
        $pool->deleteItem($key);
        $pool->clear();
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

    public function testExceptions(): void
    {
        $this->assertThrowsException(function () {
            new FileCachePool("/non-existings");
        }, InvalidDirectoryException::class);

        $pool = new FileCachePool(__DIR__, "fileCache");
        $this->assertThrowsException(function () use ($pool) {
            $pool->getItem("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($pool) {
            $pool->deleteItem("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($pool) {
            $pool->getItems(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($pool) {
            $pool->deleteItems(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($pool) {
            $pool->hasItem("{");
        }, InvalidKeyException::class);
    }
}
