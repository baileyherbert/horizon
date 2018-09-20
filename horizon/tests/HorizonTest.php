<?php

use PHPUnit\Framework\TestCase;

class HorizonTest extends TestCase
{

    public function testConstants()
    {
        $this->assertNotNull(Horizon::VERSION);

        $this->assertNotNull(Horizon::ROOT_DIR);
        $this->assertNotNull(Horizon::VENDOR_DIR);

        $this->assertNotNull(Horizon::APP_DIR);
        $this->assertNotNull(Horizon::APP_CONFIG_DIR);
        $this->assertNotNull(Horizon::APP_SRC_DIR);

        $this->assertNotNull(Horizon::HORIZON_DIR);
        $this->assertNotNull(Horizon::HORIZON_LIB_DIR);
        $this->assertNotNull(Horizon::HORIZON_TESTS_DIR);

        $this->assertFileExists(Horizon::COMPOSER_FILE);
    }

    public function testEnvironment()
    {
        $this->assertEquals('test', Horizon::environment());

        $this->assertTrue(Horizon::environment('test'));
        $this->assertFalse(Horizon::environment('production'));
    }

}