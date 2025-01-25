Cache
================

[![Total Downloads](https://poser.pugx.org/konecnyjakub/cache/downloads)](https://packagist.org/packages/konecnyjakub/cache) [![Latest Stable Version](https://poser.pugx.org/konecnyjakub/cache/v/stable)](https://gitlab.com/konecnyjakub/cache/-/releases) [![build status](https://gitlab.com/konecnyjakub/cache/badges/master/pipeline.svg?ignore_skipped=true)](https://gitlab.com/konecnyjakub/cache/-/commits/master) [![coverage report](https://gitlab.com/konecnyjakub/cache/badges/master/coverage.svg)](https://gitlab.com/konecnyjakub/cache/-/commits/master) [![License](https://poser.pugx.org/konecnyjakub/cache/license)](https://gitlab.com/konecnyjakub/cache/-/blob/master/LICENSE.md)

This is a simple [PSR-16](https://www.php-fig.org/psr/psr-16/) caching library.

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

$cache = new MemcachedCache(defaultTtl: 2);
$cache->set("one", "abc"); // this item will expire after 2 seconds
$cache->set("two", "def", 3); // this item will expire after 3 seconds
```

Be aware that different instances have access to same values.

#### More engines?

There are more ways to cache things, it is possible that more engines will be added in future versions. Likely candidates are Redis and memcached.

### PSRs

At the moment, this package only implements PSR-16 (Simple cache). Support for PSR-6 is planned but there is no eta.
