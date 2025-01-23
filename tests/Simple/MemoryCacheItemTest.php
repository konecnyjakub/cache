<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("MemoryCacheItem")]
final class MemoryCacheItemTest extends TestCase
{
    public function testIsExpired(): void
    {
        $item = new MemoryCacheItem("abc", -1);
        $this->assertTrue($item->isExpired());

        $item = new MemoryCacheItem("abc", 30);
        $this->assertFalse($item->isExpired());

        $item = new MemoryCacheItem("abc", null);
        $this->assertFalse($item->isExpired());

        $item = new MemoryCacheItem("abc", DateInterval::createFromDateString("30 seconds"));
        $this->assertFalse($item->isExpired());

        $dateInterval = DateInterval::createFromDateString("1 second");
        $dateInterval->invert = 1;
        $item = new MemoryCacheItem("abc", $dateInterval);
        $this->assertTrue($item->isExpired());
    }
}
