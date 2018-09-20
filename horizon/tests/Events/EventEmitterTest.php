<?php

use PHPUnit\Framework\TestCase;
use Horizon\Events\EventEmitter;

class EventEmitterTest extends TestCase
{

    /**
     * Tests that a one-time callback runs when invoked.
     */
    public function testSingleOccurrenceEvent()
    {
        $emitter = new EventEmitterHelper();
        $i = 0;

        // Counter function
        $emitter->once('test', function() use (&$i) {
            $i++;
        });

        // Emit
        $emitter->call();

        // Test
        $this->assertEquals(1, $i, 'Callback was not executed.');
    }

    /**
     * Tests that a one-time callback only runs one time when invoked multiple times.
     */
    public function testSingleOccurrenceRepeatedEvent()
    {
        $emitter = new EventEmitterHelper();
        $i = 0;

        // Counter function
        $emitter->once('test', function() use (&$i) {
            $i++;
        });

        // Emit
        $emitter->call();
        $emitter->call();

        // Test
        $this->assertEquals(1, $i, 'One-time callback was executed more or less than 1 time.');
    }

    /**
     * Tests that a recurrent callback runs each time it is invoked.
     */
    public function testMultiOccurrenceEvent()
    {
        $emitter = new EventEmitterHelper();
        $i = 0;

        // Counter function
        $emitter->on('test', function() use (&$i) {
            $i++;
        });

        // Emit twice
        $emitter->call();
        $emitter->call();

        // Test
        $this->assertEquals(2, $i, 'Callback was not executed twice.');
    }

    /**
     * Tests that callbacks are provided arguments of various types exactly as sent from the emitter.
     */
    public function testCallbackArguments()
    {
        $emitter = new EventEmitterHelper();
        $results = array();

        // Counter function
        $emitter->once('test', function() use (&$results) {
            $results = func_get_args();
        });

        // Emit twice
        $emitter->callWithArguments();

        // Test
        $this->assertEquals(true, $results[0], 'Callback arguments not working properly');
        $this->assertEquals(false, $results[1], 'Callback arguments not working properly');
        $this->assertEquals(0, $results[2], 'Callback arguments not working properly');
        $this->assertEquals(100, $results[3], 'Callback arguments not working properly');
        $this->assertEquals('Hello, world.', $results[4], 'Callback arguments not working properly');
    }

    /**
     * Tests that a callback, when removed, is not executed further.
     */
    public function testRemoveCallback()
    {
        $emitter = new EventEmitterHelper();
        $i = 0;

        // Callback
        $callback = (function() use (&$i) {
            $i++;
        });

        // Bind callback
        $emitter->once('test', $callback);

        // Remove callback
        $emitter->remove('test', $callback);

        // Emit
        $emitter->call();

        // Test
        $this->assertEquals(0, $i, 'Callback was executed after being removed.');
    }
}

class EventEmitterHelper extends EventEmitter
{
    public function call()
    {
        $this->emit('test');
    }

    public function callWithArguments()
    {
        $this->emit('test', true, false, 0, 100, 'Hello, world.');
    }
}