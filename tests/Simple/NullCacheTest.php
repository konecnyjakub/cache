<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("NullCache")]
final class NullCacheTest extends TestCase
{
    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $default = "default";
        $cache = new NullCache();

        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->set($key, $value, -1);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->set($key, $value, 30);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->delete($key);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));
    }

    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $default = "default";
        $key2 = "two";
        $value2 = "def";
        $cache = new NullCache();

        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], -1);
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30);
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $cache->deleteMultiple([$key1, $key2, ]);
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30);
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
    }

    public function testExceptions(): void
    {
        $cache = new NullCache();
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
