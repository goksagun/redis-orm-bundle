<?php

namespace Goksagun\RedisOrmBundle\Tests\Utils;

use Goksagun\RedisOrmBundle\Utils\FileHelper;
use PHPUnit\Framework\TestCase;

class FileHelperTest extends TestCase
{
    public function testGetClassFromFile()
    {
        $path = __DIR__.'/Resources/FileHelperClassName.php';

        $this->assertSame('Goksagun\RedisOrmBundle\Tests\Utils\Resources\FileHelperClassName', FileHelper::getClassFromFile($path));
    }
}