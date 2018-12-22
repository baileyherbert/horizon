<?php

use Horizon\Framework\Core;
use PHPUnit\Framework\TestCase;

class HorizonTest extends TestCase
{

    public function testConstants()
    {
        $this->assertNotNull(Core::version());
        $this->assertEquals('jupiter', Core::edition());

        $this->assertNotNull(Core::path());
        $this->assertNotNull(Core::path('vendor'));

        $this->assertFileExists(Core::path('horizon/composer.json'));
    }

    public function testEnvironment()
    {
        $this->assertEquals('test', Core::environment());

        $this->assertTrue(Core::environment('test'));
        $this->assertFalse(Core::environment('production'));
    }

}
