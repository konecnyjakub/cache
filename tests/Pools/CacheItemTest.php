<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Pools;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("CacheItem")]
final class CacheItemTest extends TestCase
{
    public function testGetKey(): void
    {
        $key = "abc";
        $item = new CacheItem($key);
        $this->assertSame($key, $item->getKey());
    }

    public function testGet(): void
    {
        $key = "abc";
        $value = "def";
        $item = new CacheItem($key, $value, false);
        $this->assertSame(null, $item->get());

        $item = new CacheItem($key, $value, true);
        $this->assertSame($value, $item->get());
    }

    public function testIsHit(): void
    {
        $key = "abc";
        $item = new CacheItem($key, "", false);
        $this->assertFalse($item->isHit());

        $item = new CacheItem($key, "", true);
        $this->assertTrue($item->isHit());
    }

    public function testSet(): void
    {
        $key = "abc";
        $value = "def";
        $item = new CacheItem($key, "", true);
        $item->set($value);
        $this->assertSame($value, $item->get());
    }

    public function testGetTtl(): void
    {
        $key = "abc";
        $item = new CacheItem($key, "", true);
        $this->assertSame(0, $item->getTtl());

        $defaultTtl = 1;
        $item = new CacheItem($key, "", true, $defaultTtl);
        $this->assertSame($defaultTtl, $item->getTtl());

        $ttl = 10;
        $item->expiresAfter($ttl);
        $this->assertSame($ttl, $item->getTtl());

        $ttl = 30;
        $item->expiresAfter(\DateInterval::createFromDateString("$ttl seconds"));
        $this->assertSame($ttl, $item->getTtl());

        $item->expiresAfter(null);
        $this->assertSame($defaultTtl, $item->getTtl());
    }
}
