<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use Konecnyjakub\Cache\Events;
use Konecnyjakub\Cache\TestEventsLogger;
use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use MyTester\Attributes\AfterTest;
use MyTester\Attributes\RequiresPhpExtension;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("ApcuCachePool")]
#[RequiresPhpExtension("apcu")]
final class ApcuCachePoolTest extends TestCase
{
    #[AfterTest]
    public function clearCache(): void
    {
        (new ApcuCachePool())->clear();
    }

    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $ttl = 30;
        $pool = new ApcuCachePool();

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
        $pool = new ApcuCachePool();

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
        $pool = new ApcuCachePool(defaultTtl: -1);

        $item = $pool->getItem($key);
        $item->set($value);
        $this->assertTrue($pool->save($item));
        $this->assertFalse($pool->hasItem($key));

        $item->expiresAfter(30);
        $this->assertTrue($pool->save($item));
        $this->assertTrue($pool->hasItem($key));
    }

    public function testNamespace(): void
    {
        $key1 = "one";
        $pool1 = new ApcuCachePool();
        $this->assertSame($key1, $pool1->getKey($key1));

        $key2 = "two";
        $namespace = "test";
        $pool2 = new ApcuCachePool(namespace: $namespace);
        $this->assertSame($namespace . ":" . $key2, $pool2->getKey($key2));

        $item1 = $pool1->getItem($key1);
        $item1->set("abc");
        $this->assertTrue($pool1->save($item1));
        $this->assertTrue($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key1));
        $item2 = $pool2->getItem($key2);
        $item2->set("def");
        $this->assertTrue($pool2->save($item2));
        $this->assertTrue($pool2->hasItem($key2));
        $this->assertFalse($pool1->hasItem($key2));
        $this->assertTrue($pool1->clear());
        $this->assertFalse($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key2)); // FIXME: should return true
        $this->assertTrue($pool1->save($item1));
        $this->assertTrue($pool2->save($item2));
        $this->assertTrue($pool2->clear());
        $this->assertFalse($pool2->hasItem($key2));
        $this->assertTrue($pool1->hasItem($key1));
    }

    public function testSerializer(): void
    {
        $pool = new ApcuCachePool();

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
        $pool = new ApcuCachePool(eventDispatcher: $eventDispatcher);
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

    public function testExceptions(): void
    {
        $pool = new ApcuCachePool();
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
