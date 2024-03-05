<?php

use Horizon\Foundation\Application;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    public function testConfigurationReading()
    {
        $this->assertNotNull(config('app.timezone'));
        $this->assertNull(config('session.does.not.exist'));
        $this->assertTrue(is_array(config('session')));
    }

}
