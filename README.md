Cache
================

[![Total Downloads](https://poser.pugx.org/konecnyjakub/cache/downloads)](https://packagist.org/packages/konecnyjakub/cache) [![Latest Stable Version](https://poser.pugx.org/konecnyjakub/cache/v/stable)](https://gitlab.com/konecnyjakub/cache/-/releases) [![build status](https://gitlab.com/konecnyjakub/cache/badges/master/pipeline.svg?ignore_skipped=true)](https://gitlab.com/konecnyjakub/cache/-/commits/master) [![coverage report](https://gitlab.com/konecnyjakub/cache/badges/master/coverage.svg)](https://gitlab.com/konecnyjakub/cache/-/commits/master) [![License](https://poser.pugx.org/konecnyjakub/cache/license)](https://gitlab.com/konecnyjakub/cache/-/blob/master/LICENSE.md)

This is a simple [PSR-6](https://www.php-fig.org/psr/psr-6/) and [PSR-16](https://www.php-fig.org/psr/psr-16/) caching library.

Installation
------------

The best way to install Cache is via Composer. Just add konecnyjakub/cache to your dependencies.

Quick start
-----------

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\MemoryCache;

$cache = new MemoryCache();
$cache->set("one", "abc");
$cache->has("one"); // true
$cache->get("one"); // abc
$cache->delete("one");
$cache->has("one"); // false
$cache->get("one"); // null
```

MemoryCache is a very simple cache that simply stores values in memory during current request/script. Different instances are completely separated so they do not contain the same values. This is not the recommended engine for pretty much anything as it is not persistent.

Advanced usage
--------------

### PSRs

This package implements both PSR-6 and PSR-16. Usage with PSR-16 is described below, all engines and their features have a PSR-6 counterpart in namespace Konecnyjakub\Cache\Pools.

### Available engines

This package has more engines for caching which are more useful than MemoryCache. Each available one will be described in detail below.

#### Memory

It was already mentioned in quick start as it is the most simple engine that does something. It supports setting default lifetime of items (it is used if you do not specify it for an item).

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\MemoryCache;

$cache = new MemoryCache(defaultTtl: 2);
$cache->set("one", "abc"); // this item will expire after 2 seconds
$cache->set("two", "def", 3); // this item will expire after 3 seconds
```

#### Null

NullCache is a cache engine does not store anything. It is useful in situations where you want to disable cache. It does not have any special features.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\NullCache;

$cache = new NullCache();
$cache->set("one", "abc");
$cache->has("one"); // false
```

#### File

FileCache is another available cache engine. This is the recommended one if you cannot use anything better because it does not require anything extra. It stores values in files on the disc. You have to tell in which directory it should save things and you can set default lifetime of items.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\FileCache;

$cache = new FileCache("/var/cache/myapp", defaultTtl: 2);
$cache->set("one", "abc");
$cache->has("one"); // true
$cache->get("one"); // abc
```

By default, different instance save to the same directory (if same value is passed for parameter directory) but it is possible to use a sub-directory to separate their files (and in turn values).

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\FileCache;

$cache1 = new FileCache("/var/cache/myapp", namespace: "pool1");
$cache2 = new FileCache("/var/cache/myapp", namespace: "pool2");
$cache1->set("one", "abc");
$cache2->set("two", "def");
$cache1->has("one"); // true
$cache2->has("one"); // false
$cache1->has("two"); // false
$cache2->has("two"); // true
```

Files for expired items are not automatically deleted, it has to be done manually at the moment.

#### Apcu

ApcuCache is an advanced cache engine, it uses PHP extension apcu to store values. You should use it if possible, it only requires a PHP extension. It supports setting default lifetime for items.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\ApcuCache;

$cache = new ApcuCache(defaultTtl: 2);
$cache->set("one", "abc"); // this item will expire after 2 seconds
$cache->set("two", "def", 3); // this item will expire after 3 seconds
```

By default, different instances have access to same values unless you set a namespace for them.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\ApcuCache;

$cache1 = new ApcuCache(namespace: "pool1");
$cache2 = new ApcuCache(namespace: "pool2");
$cache1->set("one", "abc");
$cache2->set("two", "def");
$cache1->has("one"); // true
$cache2->has("one"); // false
$cache1->has("two"); // false
$cache2->has("two"); // true
```

Warning: if you use both an instance of ApcuCache without namespace and an instance with namespace, calling the clear method on instance without namespace clears everything in the apcu cache, even values saved from instances with namespace. For this reason we recommend either using only one instance (without namespace) or multiple instances but with different namespace for each of them.

#### Memcached

MemcachedCache is an advanced cache engine, it stores values on a memcached server. It requires PHP extension memcached an a memcached server. It supports setting default lifetime for items.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\MemcachedCache;
use Memcached;

$client = new Memcached();
$client->addServer("localhost", 11211);
$cache = new MemcachedCache($client, defaultTtl: 2);
$cache->set("one", "abc"); // this item will expire after 2 seconds
$cache->set("two", "def", 3); // this item will expire after 3 seconds
```

Be aware that different instances have access to same values.

#### Redis

RedisCache is an advanced cache engine, it uses a redis server to store values. It requires PHP extension redis an a redis server. You should use it if possible. It supports setting default lifetime for items.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\RedisCache;
use Redis;

$client = new Redis();
$client->connect("localhost");
$client->select(0); // optional, if you want to use a different database than the default one (0)
$cache = new RedisCache($client, defaultTtl: 2);
$cache->set("one", "abc"); // this item will expire after 2 seconds
$cache->set("two", "def", 3); // this item will expire after 3 seconds
```

By default, different instances have access to same values unless you set a namespace for them (or they use a different database).

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\RedisCache;
use Redis;

$client = new Redis();
$client->connect("localhost");
$cache1 = new RedisCache($client, namespace: "pool1");
$cache2 = new RedisCache($client, namespace: "pool2");
$cache1->set("one", "abc");
$cache2->set("two", "def");
$cache1->has("one"); // true
$cache2->has("one"); // false
$cache1->has("two"); // false
$cache2->has("two"); // true
```

Warning: if you use both an instance of RedisCache without namespace and an instance with namespace, calling the clear method on instance without namespace clears everything in the apcu cache, even values saved from instances with namespace. For this reason we recommend either using only one instance (without namespace) or multiple instances but with different namespace for each of them.

#### Chain

ChainCache allows using multiple engines at the same time. Methods has/get/getMultiple try all engines in the order they were registered until one returns data. Methods set/setMultiple/delete/deleteMultiple/clear are run on all available engines.

```php
<?php
declare(strict_types=1);

use Konecnyjakub\Cache\Simple\ApcuCache;
use Konecnyjakub\Cache\Simple\ChainCache;
use Konecnyjakub\Cache\Simple\FileCache;

$apcuCache = new ApcuCache();
$fileCache = new FileCache("/var/cache/myapp");
$cache = new ChainCache();
$cache->addEngine($fileCache);
$cache->addEngine($apcuCache);

$apcuCache->set("one", "abc");
$cache->has("one"); // true
$apcuCache->has("one"); // true
$fileCache->has("one"); // false

$cache->set("two", "def");
$cache->has("two"); // true
$apcuCache->has("two"); // true
$fileCache->has("two"); // true

$cache->delete("two");
$cache->has("two"); // false
$apcuCache->has("two"); // false
$fileCache->has("two"); // false
```

#### More engines?

While all planned engines are present, there are more ways to cache things so is possible that more engines will be added in future versions. If there is one you would like to see, open a feature request and it will be considered.

#### Events

Our engines (except ChainCache) can call your code when an event occurs - an item is successfully read from cache (cache hit), an item could not be read from cache or was expired (cache miss), an item was saved into cache, an item was deleted from cache and all items were deleted from cache. Just pass a [PSR-14 ](https://www.php-fig.org/psr/psr-14/) event dispatcher as parameter eventDispatcher into the engine's constructor. Classes for all possible events are in namespace Konecnyjakub\Cache\Events.

### PSRs

This package implements both PSR-16 and PSR-6.
