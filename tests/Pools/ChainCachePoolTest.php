<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use MyTester\Attributes\Group;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("ChainCachePool")]
#[Group("cachePools")]
#[Group("chainCache")]
final class ChainCachePoolTest extends TestCase
{
    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $pool1 = new MemoryCachePool();
        $pool2 = new MemoryCachePool();
        $pool = new ChainCachePool();
        $pool->addEngine($pool1);
        $pool->addEngine($pool2);

        $this->assertFalse($pool->hasItem($key));
        $this->assertFalse($pool1->hasItem($key));
        $this->assertFalse($pool2->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool1->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool2->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(-1);
        $this->assertTrue($pool->save($item));
        $this->assertFalse($pool->hasItem($key));
        $this->assertFalse($pool1->hasItem($key));
        $this->assertFalse($pool2->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool1->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool2->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());

        $item = $pool2->getItem($key);
        $item->set($value);
        $pool2->save($item);
        $this->assertTrue($pool->hasItem($key));
        $this->assertFalse($pool1->hasItem($key));
        $this->assertTrue($pool2->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());
        $item = $pool1->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool2->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());
        $pool2->deleteItem($key);

        $item = $pool->getItem($key);
        $item->set($value);
        $item->expiresAfter(30);
        $this->assertTrue($pool->save($item));
        $this->assertTrue($pool->hasItem($key));
        $this->assertTrue($pool1->hasItem($key));
        $this->assertTrue($pool2->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());
        $item = $pool1->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());
        $item = $pool2->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame($value, $item->get());
        $this->assertTrue($item->isHit());

        $this->assertTrue($pool->deleteItem($key));
        $this->assertFalse($pool->hasItem($key));
        $this->assertFalse($pool1->hasItem($key));
        $this->assertFalse($pool2->hasItem($key));
        $item = $pool->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool1->getItem($key);
        $this->assertSame($key, $item->getKey());
        $this->assertSame(null, $item->get());
        $this->assertFalse($item->isHit());
        $item = $pool2->getItem($key);
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
        $pool1 = new MemoryCachePool();
        $pool2 = new MemoryCachePool();
        $pool = new ChainCachePool();
        $pool->addEngine($pool1);
        $pool->addEngine($pool2);

        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        $this->assertFalse($pool1->hasItem($key2));
        $this->assertFalse($pool2->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool1->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool2->getItems([$key1, $key2, ]);
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
        $this->assertFalse($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        $this->assertFalse($pool1->hasItem($key2));
        $this->assertFalse($pool2->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool1->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool2->getItems([$key1, $key2, ]);
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
        $this->assertTrue($pool1->hasItem($key1));
        $this->assertTrue($pool2->hasItem($key1));
        $this->assertTrue($pool->hasItem($key2));
        $this->assertTrue($pool1->hasItem($key2));
        $this->assertTrue($pool2->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame($value1, $items[$key1]->get());
        $this->assertTrue($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool1->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame($value1, $items[$key1]->get());
        $this->assertTrue($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool2->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame($value1, $items[$key1]->get());
        $this->assertTrue($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame($value2, $items[$key2]->get());
        $this->assertTrue($items[$key2]->isHit());

        $this->assertTrue($pool->deleteItems([$key1, $key2, ]));
        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        $this->assertFalse($pool1->hasItem($key2));
        $this->assertFalse($pool2->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool1->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool2->getItems([$key1, $key2, ]);
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
        $this->assertTrue($pool->clear());
        $this->assertFalse($pool->hasItem($key1));
        $this->assertFalse($pool1->hasItem($key1));
        $this->assertFalse($pool2->hasItem($key1));
        $this->assertFalse($pool->hasItem($key2));
        $this->assertFalse($pool1->hasItem($key2));
        $this->assertFalse($pool2->hasItem($key2));
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool1->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
        /** @var array{one: CacheItem, two: CacheItem} $items */
        $items = $pool2->getItems([$key1, $key2, ]);
        $this->assertSame($key1, $items[$key1]->getKey());
        $this->assertSame(null, $items[$key1]->get());
        $this->assertFalse($items[$key1]->isHit());
        $this->assertSame($key2, $items[$key2]->getKey());
        $this->assertSame(null, $items[$key2]->get());
        $this->assertFalse($items[$key2]->isHit());
    }
}
