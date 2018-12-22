<?php

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{

    public function testConfigurationReading()
    {
        $this->assertNotNull(config('session.driver'));
        $this->assertNull(config('session.does.not.exist'));

        $this->assertInternalType('array', config('session'));
    }

}
