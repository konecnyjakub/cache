<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("ItemKeyValidator")]
final class ItemKeyValidatorTest extends TestCase
{
    public function testIsKeyValid(): void
    {
        $validator = new ItemKeyValidator();
        $this->assertFalse($validator->isKeyValid(123));
        $this->assertFalse($validator->isKeyValid(""));
        $this->assertFalse($validator->isKeyValid("{"));
        $this->assertTrue($validator->isKeyValid("one"));
        $this->assertTrue($validator->isKeyValid("123"));
    }

    public function testIsKeysValid(): void
    {
        $validator = new ItemKeyValidator();
        $this->assertFalse($validator->isKeysValid(["one", 123, ]));
        $this->assertFalse($validator->isKeysValid(["one", "", ]));
        $this->assertFalse($validator->isKeysValid(["one", "{", ]));
        $this->assertTrue($validator->isKeysValid(["one", "123", ]));
    }
}
