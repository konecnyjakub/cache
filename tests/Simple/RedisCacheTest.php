<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use MyTester\Attributes\RequiresPhpExtension;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;
use Redis;

#[TestSuite("RedisCache")]
final class RedisCacheTest extends TestCase
{
    private Redis $client;
    private string $host;

    public function startUp(): void
    {
        parent::startUp();
        if (!extension_loaded("redis")) {
            return;
        }
        $this->client = new Redis();
        $this->host = is_string(getenv("REDIS_HOST")) ? getenv("REDIS_HOST") : "localhost";
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->client->flushDB();
    }

    #[RequiresPhpExtension("redis")]
    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $default = "default";
        $cache = new RedisCache($this->client, $this->host);

        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->set($key, $value, -1);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $cache->set($key, $value, DateInterval::createFromDateString("30 seconds"));
        $this->assertTrue($cache->has($key));
        $this->assertSame($value, $cache->get($key, $default));

        $cache->delete($key);
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));
    }

    #[RequiresPhpExtension("redis")]
    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $default = "default";
        $key2 = "two";
        $value2 = "def";
        $cache = new RedisCache($this->client, $this->host);

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

    #[RequiresPhpExtension("redis")]
    public function testDefaultTtl(): void
    {
        $key = "ttl";
        $value = "abc";
        $cache = new RedisCache($this->client, $this->host, defaultTtl: -1);

        @$cache->set($key, $value); // phpcs:ignore Generic.PHP.NoSilencedErrors
        $this->assertFalse($cache->has($key));

        $cache->set($key, $value, 30);
        $this->assertTrue($cache->has($key));
    }

    #[RequiresPhpExtension("redis")]
    public function testExceptions(): void
    {
        $cache = new RedisCache($this->client, $this->host);
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
