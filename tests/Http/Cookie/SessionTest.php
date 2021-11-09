<?php

use PHPUnit\Framework\TestCase;
use Horizon\Http\Cookie\Session;

class SessionTest extends TestCase
{

    public function setUp()
    {
        $this->session = new Session();
    }

    /**
     * Tests the ability to persistently store a key and value.
     */
    public function testSessionPut()
    {
        // Put data
        $this->session->put('testSessionPut', true);

        // Verify the data was added
        $this->assertTrue($this->session->get('testSessionPut'));
    }

    /**
     * Tests the ability to check if a key exists and contains a value.
     */
    public function testSessionHas()
    {
        // Should return false if the session doesn't have the key
        $this->assertFalse($this->session->has('testSessionHas'));

        // Put data
        $this->session->put('testSessionHas', true);
        $this->session->put('testSessionHasNull', null);

        // Should return true for values that exist and are [not] null
        $this->assertTrue($this->session->has('testSessionHas'));

        // Should return false if the value exists but [is] null
        $this->assertFalse($this->session->has('testSessionHasNull'));
    }

    /**
     * Tests the ability to check if a key exists.
     */
    public function testSessionExists()
    {
        // Should return false if the session doesn't have the key
        $this->assertFalse($this->session->exists('testSessionExists'));

        // Put data
        $this->session->put('testSessionExists', true);
        $this->session->put('testSessionExistsNull', null);

        // Should return true for existent keys regardless of value
        $this->assertTrue($this->session->exists('testSessionExists'));
        $this->assertTrue($this->session->exists('testSessionExistsNull'));
    }

    /**
     * Tests the ability to retrieve stored data.
     */
    public function testSessionGet()
    {
        // Nonexistent keys should return null (or second parameter value if specified)
        $this->assertNull($this->session->get('testSessionGet'));
        $this->assertTrue($this->session->get('testSessionGet', true));

        // Put data
        $this->session->put('testSessionGet', true);

        // Test that the data is retrieved
        $this->assertTrue($this->session->get('testSessionGet'));
    }

    /**
     * Tests the ability to retrieve and automatically forget stored data.
     */
    public function testSessionPull()
    {
        // Nonexistent keys should return null (or second parameter value if specified)
        $this->assertNull($this->session->pull('testSessionPull'));
        $this->assertTrue($this->session->pull('testSessionPull', true));

        // Put data
        $this->session->put('testSessionPull', true);

        // Pull and verify the data is correct
        $this->assertTrue($this->session->pull('testSessionPull'));

        // Verify the key was forgotten
        $this->assertNull($this->session->get('testSessionPull'));
    }

    /**
     * Tests the ability to explicitly forget a stored key.
     */
    public function testSessionForget()
    {
        // Put data
        $this->session->put('testSessionForget', true);

        // Pull and verify the data is correct
        $this->assertTrue($this->session->get('testSessionForget'));

        // Forget data
        $this->session->forget('testSessionForget');

        // Verify the key was forgotten
        $this->assertNull($this->session->get('testSessionForget'));
    }

    /**
     * Tests the ability to flash one-time session keys, and verifies that they don't last loner
     * than intended.
     */
    public function testSessionFlashStore()
    {
        // Store flash data
        $this->session->flash('testFlashKey', true);

        // Verify the key does not currently work
        $this->assertNull($this->session->get('testFlashKey'));

        // Create new sessions to simulate second and third pageloads
        $secondPageSession = new Session();
        $thirdPageSession = new Session();

        // Second page should have the key
        $this->assertTrue($secondPageSession->get('testFlashKey'));

        // Third page should not have the key
        $this->assertNull($thirdPageSession->get('testFlashKey'));
    }

    /**
     * Tests the ability to reflash all keys for the next pageload.
     */
    public function testSessionReflash()
    {
        // Store flash data
        $this->session->flash('testFlashKey', true);

        // Create new session to simulate second pageload
        $secondPageSession = new Session();

        // Reflash
        $secondPageSession->reflash();

        // Create new session to simulate third pageload
        $thirdPageSession = new Session();

        // Third page should still have the key
        $this->assertTrue($thirdPageSession->get('testFlashKey'));
    }

    /**
     * Tests the ability to reflash specific keys for the next pageload.
     */
    public function testSessionReflashKeep()
    {
        // Store flash data
        $this->session->flash('testFlashKey', true);
        $this->session->flash('forgetThisKey', true);

        // Create new session to simulate second pageload
        $secondPageSession = new Session();

        // Reflash
        $secondPageSession->keep(array('testFlashKey'));

        // Create new session to simulate third pageload
        $thirdPageSession = new Session();

        // Third page should still have the testFlashKey key, but not forgetThisKey
        $this->assertTrue($thirdPageSession->get('testFlashKey'));
        $this->assertNull($thirdPageSession->get('forgetThisKey'));
    }

}