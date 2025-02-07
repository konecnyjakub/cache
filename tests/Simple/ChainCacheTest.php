<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Simple;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("ChainCache")]
final class ChainCacheTest extends TestCase
{
    public function testSingleKeyProcess(): void
    {
        $key = "abc";
        $value = "def";
        $default = "default";
        $cache1 = new MemoryCache();
        $cache2 = new MemoryCache();
        $cache = new ChainCache();
        $cache->addEngine($cache1);
        $cache->addEngine($cache2);

        $this->assertFalse($cache->has($key));
        $this->assertFalse($cache1->has($key));
        $this->assertFalse($cache2->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertSame($default, $cache1->get($key, $default));
        $this->assertSame($default, $cache2->get($key, $default));

        $cache->set($key, $value, -1);
        $this->assertFalse($cache->has($key));
        $this->assertFalse($cache1->has($key));
        $this->assertFalse($cache2->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertSame($default, $cache1->get($key, $default));
        $this->assertSame($default, $cache2->get($key, $default));

        $cache2->set($key, $value);
        $this->assertTrue($cache->has($key));
        $this->assertFalse($cache1->has($key));
        $this->assertTrue($cache2->has($key));
        $this->assertSame($value, $cache->get($key, $default));
        $this->assertSame($default, $cache1->get($key, $default));
        $this->assertSame($value, $cache2->get($key, $default));
        $cache2->delete($key);

        $cache->set($key, $value, 30);
        $this->assertTrue($cache->has($key));
        $this->assertTrue($cache1->has($key));
        $this->assertTrue($cache2->has($key));
        $this->assertSame($value, $cache->get($key, $default));
        $this->assertSame($value, $cache1->get($key, $default));
        $this->assertSame($value, $cache2->get($key, $default));

        $cache->delete($key);
        $this->assertFalse($cache->has($key));
        $this->assertFalse($cache1->has($key));
        $this->assertFalse($cache2->has($key));
        $this->assertSame($default, $cache->get($key, $default));
        $this->assertSame($default, $cache1->get($key, $default));
        $this->assertSame($default, $cache2->get($key, $default));
    }

    public function testMultiKeysProcess(): void
    {
        $key1 = "one";
        $value1 = "abc";
        $default = "default";
        $key2 = "two";
        $value2 = "def";
        $cache1 = new MemoryCache();
        $cache2 = new MemoryCache();
        $cache = new ChainCache();
        $cache->addEngine($cache1);
        $cache->addEngine($cache2);

        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache1->has($key1));
        $this->assertFalse($cache2->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertFalse($cache1->has($key2));
        $this->assertFalse($cache2->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache1->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache2->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], -1);
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache1->has($key1));
        $this->assertFalse($cache2->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertFalse($cache1->has($key2));
        $this->assertFalse($cache2->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache1->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache2->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30);
        $this->assertTrue($cache->has($key1));
        $this->assertTrue($cache1->has($key1));
        $this->assertTrue($cache2->has($key1));
        $this->assertTrue($cache->has($key2));
        $this->assertTrue($cache1->has($key2));
        $this->assertTrue($cache2->has($key2));
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
            $cache1->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $value1, $key2 => $value2, ],
            $cache2->getMultiple([$key1, $key2, ], $default)
        );

        $cache->deleteMultiple([$key1, $key2, ]);
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache1->has($key1));
        $this->assertFalse($cache2->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertFalse($cache1->has($key2));
        $this->assertFalse($cache2->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache1->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache2->getMultiple([$key1, $key2, ], $default)
        );

        $cache->setMultiple([$key1 => $value1, $key2 => $value2, ], 30);
        $cache->clear();
        $this->assertFalse($cache->has($key1));
        $this->assertFalse($cache1->has($key1));
        $this->assertFalse($cache2->has($key1));
        $this->assertFalse($cache->has($key2));
        $this->assertFalse($cache1->has($key2));
        $this->assertFalse($cache2->has($key2));
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache1->getMultiple([$key1, $key2, ], $default)
        );
        $this->assertSame(
            [$key1 => $default, $key2 => $default, ],
            $cache2->getMultiple([$key1, $key2, ], $default)
        );
    }
}
