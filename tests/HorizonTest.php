<?php

use Horizon\Foundation\Framework;
use PHPUnit\Framework\TestCase;

class HorizonTest extends TestCase
{

    public function testConstants()
    {
        $this->assertNotNull(Framework::version());

        $this->assertNotNull(Framework::path());
        $this->assertNotNull(Framework::path('src'));

        $this->assertFileExists(Framework::path('composer.json'));
    }

    public function testEnvironment()
    {
        $this->assertEquals('test', Framework::environment());

        $this->assertTrue(Framework::environment('test'));
        $this->assertFalse(Framework::environment('web'));
    }

}
