<?php

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    public function testConfigurationReading()
    {
        $this->assertNotNull(Horizon::config('session.driver'));
        $this->assertNull(Horizon::config('session.does.not.exist'));

        $this->assertInternalType('array', Horizon::config('session'));
    }

}