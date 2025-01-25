<?php
declare(strict_types=1);

namespace Konecnyjakub\Cache\Common;

use MyTester\Attributes\TestSuite;
use MyTester\TestCase;

#[TestSuite("PhpSerializer")]
final class PhpSerializerTest extends TestCase
{
    public function testProcess(): void
    {
        $serializer = new PhpSerializer();

        $value = "abc";
        $serializedValue = 's:3:"abc";';
        $this->assertSame($serializedValue, $serializer->serialize($value));
        $this->assertSame($value, $serializer->unserialize($serializedValue));

        $value = 123;
        $serializedValue = "i:123;";
        $this->assertSame($serializedValue, $serializer->serialize($value));
        $this->assertSame($value, $serializer->unserialize($serializedValue));

        $value = true;
        $serializedValue = "b:1;";
        $this->assertSame($serializedValue, $serializer->serialize($value));
        $this->assertSame($value, $serializer->unserialize($serializedValue));

        $value = null;
        $serializedValue = "N;";
        $this->assertSame($serializedValue, $serializer->serialize($value));
        $this->assertSame($value, $serializer->unserialize($serializedValue));

        $value = ["one" => "abc", "two" => "def", ];
        $serializedValue = 'a:2:{s:3:"one";s:3:"abc";s:3:"two";s:3:"def";}';
        $this->assertSame($serializedValue, $serializer->serialize($value));
        $this->assertSame($value, $serializer->unserialize($serializedValue));
    }
}
