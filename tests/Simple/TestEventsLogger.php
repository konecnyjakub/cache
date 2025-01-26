<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use Konecnyjakub\Cache\Events;
use Konecnyjakub\EventDispatcher\IEventSubscriber;

final class TestEventsLogger implements IEventSubscriber
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
}
