<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Events;
use Konecnyjakub\Cache\TestEventsLogger;
use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use MyTester\Attributes\Group;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("MemoryCachePool")]
#[Group("cachePools")]
#[Group("memoryCache")]
final class MemoryCachePoolTest extends TestCase
{
    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $ttl = 30;
        $pool = new MemoryCachePool();

        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());

        $item->set($value);
        $item->expiresAfter(-1);
        $this->assertTrue($pool->save($item));
        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());

        $item->set($value);
        $item->expiresAfter($ttl);
        $this->assertTrue($pool->save($item));
        $this->assertTrue($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());

        $this->assertTrue($pool->deleteItem($key));
        $this->assertFalse($pool->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
    }

    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $key2 = "two";
        $value2 = "def";
        $pool = new MemoryCachePool();

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

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(-1);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(-1);
        $this->assertTrue($pool->saveDeferred($items[$key1]));
        $this->assertTrue($pool->saveDeferred($items[$key2]));
        $this->assertTrue($pool->commit());
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

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(30);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(30);
        $this->assertTrue($pool->saveDeferred($items[$key1]));
        $this->assertTrue($pool->saveDeferred($items[$key2]));
        $this->assertTrue($pool->commit());
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

        $this->assertTrue($pool->deleteItems([$key1, $key2, ]));
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

        $items[$key1]->set($value1);
        $items[$key1]->expiresAfter(30);
        $items[$key2]->set($value2);
        $items[$key2]->expiresAfter(30);
        $this->assertTrue($pool->saveDeferred($items[$key1]));
        $this->assertTrue($pool->saveDeferred($items[$key2]));
        $this->assertTrue($pool->clear());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
    }

    public function testDefaultTtl(): void
    {
        $key = "ttl";
        $value = "abc";
        $pool = new MemoryCachePool(defaultTtl: -1);

        $item = $pool->getItem($key);
        $item->set($value);
        $this->assertTrue($pool->save($item));
        $this->assertFalse($pool->hasItem($key));

        $item->expiresAfter(30);
        $this->assertTrue($pool->save($item));
        $this->assertTrue($pool->hasItem($key));
    }

    public function testSerializer(): void
    {
        $pool = new MemoryCachePool();

        $key = "number";
        $value = 123;
        $item = $pool->getItem($key);
        $item->set($value);
        $this->assertTrue($pool->save($item));
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
        $pool = new MemoryCachePool(eventDispatcher: $eventDispatcher);
        $key = "one";
        $value = "abc";
        $pool->getItem($key);
        $pool->save(new CacheItem($key, $value));
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
        $pool = new MemoryCachePool();

        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $items[$key1]->set($value1);
        $items[$key1]->setTags($tags1);
        $pool->saveDeferred($items[$key1]);
        $items[$key2]->set($value2);
        $items[$key2]->setTags($tags2);
        $pool->saveDeferred($items[$key2]);
        $pool->commit();
        $this->assertTrue($pool->hasItem($key1));
        $this->assertTrue($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame($value1, $items[$key1]->get());
        $this->assertTrue($items[$key1]->isHit());
        $this->assertSame($tags1, $items[$key1]->getTags());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());
        $this->assertSame($tags2, $items[$key2]->getTags());

        $this->assertTrue($pool->invalidateTags(["tag3"]));
        $this->assertTrue($pool->hasItem($key1));
        $this->assertTrue($pool->hasItem($key2));

        $this->assertTrue($pool->invalidateTags(["tag1"]));
        $this->assertFalse($pool->hasItem($key1));
        $this->assertTrue($pool->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame([], $items[$key1]->getTags());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());
        $this->assertSame($tags2, $items[$key2]->getTags());
    }

    public function testExceptions(): void
    {
        $cache = new MemoryCachePool();
        $this->assertThrowsException(function () use ($cache) {
            $cache->getItem("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->deleteItem("{");
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->getItems(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->deleteItems(["one", "{"]);
        }, InvalidKeyException::class);

        $this->assertThrowsException(function () use ($cache) {
            $cache->hasItem("{");
        }, InvalidKeyException::class);
    }
}
