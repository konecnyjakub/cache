<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use MyTester\Attributes\Group;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("CacheItem")]
#[Group("simpleCaches")]
#[Group("cacheItem")]
final class CacheItemTest extends TestCase
{
    public function testIsExpired(): void
    {
        $item = new CacheItem("abc", -1);
        $this->assertTrue($item->isExpired());

        $item = new CacheItem("abc", 30);
        $this->assertFalse($item->isExpired());

        $item = new CacheItem("abc", null);
        $this->assertFalse($item->isExpired());

        $item = new CacheItem("abc", DateInterval::createFromDateString("30 seconds"));
        $this->assertFalse($item->isExpired());

        $dateInterval = DateInterval::createFromDateString("1 second");
        $dateInterval->invert = 1;
        $item = new CacheItem("abc", $dateInterval);
        $this->assertTrue($item->isExpired());
    }
}
