<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use MyTester\TestCase;

final class FileCacheTest extends TestCase
{
    public function shutDown(): void
    {
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

        $cache->set($key, $value, -1);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->set($key, $value, 30);
        $this->assertTrue($cache->has($key));
        $this->assertSame($value, $cache->get($key, $default));

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
        $cache = new FileCache(__DIR__, "fileCache");

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
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
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
        $cache->clear();
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
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
