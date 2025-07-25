<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use DateInterval;
use Konecnyjakub\Cache\Events;
use Konecnyjakub\Cache\TestEventsLogger;
use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use MyTester\Attributes\AfterTest;
use MyTester\Attributes\BeforeTestSuite;
use MyTester\Attributes\RequiresPhpExtension;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;
use Redis;

#[TestSuite("RedisCache")]
#[RequiresPhpExtension("redis")]
final class RedisCacheTest extends TestCase
{
    private Redis $client;

    #[BeforeTestSuite]
    public function prepareCache(): void
    {
        if (!extension_loaded("redis")) {
            return;
        }
        $host = getenv("REDIS_HOST");
        if (!is_string($host)) {
            $host = "localhost";
        }
        $this->client = new Redis();
        $this->client->connect($host);
    }

    #[AfterTest]
    public function clearCache(): void
    {
        $this->client->flushDB();
    }

    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $default = "default";
        $cache = new RedisCache($this->client);

        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $this->assertTrue($cache->set($key, $value, -1));
        $this->assertFalse($cache->has($key));
        $this->assertSame($default, $cache->get($key, $default));

        $this->assertTrue($cache->set($key, $value, DateInterval::createFromDateString("30 seconds")));
        $this->assertTrue($cache->has($key));
        $this->assertSame($value, $cache->get($key, $default));

        $this->assertTrue($cache->delete($key));
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
        $cache = new RedisCache($this->client);

        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], -1));
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30));
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache->has($key2));
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $this->assertTrue($cache->deleteMultiple([$key1, $key2, ]));
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );

        $this->assertTrue($cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30));
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
    }

    public function testDefaultTtl(): void
    {
        $key = "ttl";
        $value = "abc";
        $cache = new RedisCache($this->client, defaultTtl: -1);

        $this->assertTrue(@$cache->set($key, $value)); // phpcs:ignore Generic.PHP.NoSilencedErrors
        $this->assertFalse($cache->has($key));

        $this->assertTrue($cache->set($key, $value, 30));
        $this->assertTrue($cache->has($key));
    }

    public function testNamespace(): void
    {
        $key1 = "one";
        $cache1 = new RedisCache($this->client);
        $this->assertSame($key1, $cache1->getKey($key1));

        $key2 = "two";
        $namespace = "test";
        $cache2 = new RedisCache($this->client, namespace: $namespace);
        $this->assertSame($namespace . ":" . $key2, $cache2->getKey($key2));

        $this->assertTrue($cache1->set($key1, "abc"));
        $this->assertTrue($cache1->has($key1));
        $this->assertFalse($cache2->has($key1));
        $this->assertTrue($cache2->set($key2, "def"));
        $this->assertFalse($cache1->has($key2));
        $this->assertTrue($cache2->has($key2));
        $this->assertTrue($cache1->clear());
        $this->assertFalse($cache1->has($key1));
        $this->assertFalse($cache2->has($key2)); // FIXME: should return true
        $this->assertTrue($cache1->set($key1, "abc"));
        $this->assertTrue($cache2->set($key2, "def"));
        $this->assertTrue($cache2->clear());
        $this->assertFalse($cache2->has($key2));
        $this->assertTrue($cache1->has($key1));
    }

    public function testSerializer(): void
    {
        $cache = new RedisCache($this->client);

        $key = "number";
        $value = 123;
        $this->assertTrue($cache->set($key, $value));
        $this->assertSame($value, $cache->get($key));
        $this->assertType("int", $cache->get($key));
    }

    public function testEvents(): void
    {
        $eventsLogger = new TestEventsLogger();
        $listenerProvider = new AutoListenerProvider();
        $listenerProvider->addSubscriber($eventsLogger);
        $eventDispatcher = new EventDispatcher($listenerProvider);
        $cache = new RedisCache($this->client, eventDispatcher: $eventDispatcher);
        $key = "one";
        $value = "abc";
        $cache->get($key);
        $cache->set($key, $value);
        $cache->get($key);
        $cache->delete($key);
        $cache->clear();
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
        $this->assertSame($value, $event->value);
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
        $cache = new RedisCache($this->client);
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
