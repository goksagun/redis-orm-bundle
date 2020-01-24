<?php

namespace Goksagun\RedisOrmBundle\Tests\Utils;

use Goksagun\RedisOrmBundle\Utils\StringHelper;
use PHPUnit\Framework\TestCase;

class StringHelperTest extends TestCase
{
    public function testSlug()
    {
        $this->assertSame('foo-bar-baz', StringHelper::slug('Foo bar Baz'));
        $this->assertSame('foo-bar-baz', StringHelper::slug('Foo\\Bar\\Baz'));
        $this->assertSame('foo-bag-baz', StringHelper::slug('Foo Bağ Baz'));
        $this->assertSame('foo-bag-baz', StringHelper::slug('Foo ?Bağ $Baz!  '));
        $this->assertSame('foo_bag_baz', StringHelper::slug('Foo ?Bağ $Baz!  ', '_'));
    }
}