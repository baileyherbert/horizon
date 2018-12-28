<?php

use Horizon\Foundation\Framework;
use PHPUnit\Framework\TestCase;

class HorizonTest extends TestCase
{

    public function testConstants()
    {
        $this->assertNotNull(Framework::version());
        $this->assertEquals('jupiter', Framework::edition());

        $this->assertNotNull(Framework::path());
        $this->assertNotNull(Framework::path('vendor'));

        $this->assertFileExists(Framework::path('horizon/composer.json'));
    }

    public function testEnvironment()
    {
        $this->assertEquals('test', Framework::environment());

        $this->assertTrue(Framework::environment('test'));
        $this->assertFalse(Framework::environment('production'));
    }

}
