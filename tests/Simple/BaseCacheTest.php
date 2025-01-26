<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Konecnyjakub\EventDispatcher\AutoListenerProvider;
use Konecnyjakub\EventDispatcher\EventDispatcher;
use Konecnyjakub\EventDispatcher\IEventSubscriber;
use Konecnyjakub\Cache\Events;
use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("BaseCache")]
final class BaseCacheTest extends TestCase
{
    public function testEvents(): void
    {
        $eventsLogger = new class implements IEventSubscriber
        {
            /** @var object[] */
            public array $events = [];

            public function onCacheHit(Events\CacheHit $event): void
            {
                $this->events[] = $event;
            }

            public function onCacheMiss(Events\CacheMiss $event): void
            {
                $this->events[] = $event;
            }

            public function onCacheSave(Events\CacheSave $event): void
            {
                $this->events[] = $event;
            }

            public function onCacheDelete(Events\CacheDelete $event): void
            {
                $this->events[] = $event;
            }

            public function onCacheClear(Events\CacheClear $event): void
            {
                $this->events[] = $event;
            }

            public static function getSubscribedEvents(): iterable
            {
                return [
                    Events\CacheHit::class => [
                        ["onCacheHit", ],
                    ],
                    Events\CacheMiss::class => [
                        ["onCacheMiss", ],
                    ],
                    Events\CacheSave::class => [
                        ["onCacheSave", ],
                    ],
                    Events\CacheDelete::class => [
                        ["onCacheDelete", ],
                    ],
                    Events\CacheClear::class => [
                        ["onCacheClear", ],
                    ],
                ];
            }
        };
        $listenerProvider = new AutoListenerProvider();
        $listenerProvider->addSubscriber($eventsLogger);
        $eventDispatcher = new EventDispatcher($listenerProvider);
        $cache = new MemoryCache(eventDispatcher: $eventDispatcher);
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
}
