<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("NullCache")]
final class NullCacheTest extends TestCase
{
    public function testGet(): void
    {
        $cache = new NullCache();
        $this->assertSame(null, $cache->get("abc"));
        $this->assertSame("def", $cache->get("abc", "def"));
        $this->assertThrowsException(function () use ($cache) {
            $cache->get("{");
        }, InvalidKeyException::class);
    }

    public function testSet(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->set("abc", null));
        $this->assertTrue($cache->set("abc", null));
        $this->assertThrowsException(function () use ($cache) {
            $cache->set("{", "abc");
        }, InvalidKeyException::class);
    }

    public function testDelete(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->delete("abc"));
        $this->assertTrue($cache->delete("abc"));
        $this->assertThrowsException(function () use ($cache) {
            $cache->delete("{");
        }, InvalidKeyException::class);
    }

    public function testClear(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->clear());
        $this->assertTrue($cache->clear());
    }

    public function testGetMultiple(): void
    {
        $cache = new NullCache();
        $this->assertSame(
            ["one" => "abc", "two" => "abc", ],
            $cache->getMultiple(["one", "two", ], "abc")
        );
        $this->assertThrowsException(function () use ($cache) {
            $cache->getMultiple(["one", "{"]);
        }, InvalidKeyException::class);
    }

    public function testSetMultiple(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->setMultiple(["one" => "abc", "two" => "def", ]));
        $this->assertThrowsException(function () use ($cache) {
            $cache->setMultiple(["{" => "abc", ]);
        }, InvalidKeyException::class);
    }

    public function testDeleteMultiple(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->deleteMultiple(["one", "two", ]));
        $this->assertTrue($cache->deleteMultiple(["one", "two", ]));
        $this->assertThrowsException(function () use ($cache) {
            $cache->deleteMultiple(["one", "{"]);
        }, InvalidKeyException::class);
    }

    public function testHas(): void
    {
        $cache = new NullCache();
        $this->assertFalse($cache->has("abc"));
        $this->assertThrowsException(function () use ($cache) {
            $cache->has("{");
        }, InvalidKeyException::class);
    }
}
