<?php

namespace Horizon\Logging;

use Horizon\Events\EventEmitter;

class Logger extends EventEmitter {

    /**
     * The name of the logger (if applicable).
     *
     * @var string
     */
    protected $name;

    /**
     * The parent logger instance (if applicable). When set, all logging input sent to this instance will be forwarded
     * up to the parent.
     *
     * @var Logger
     */
    protected $parent;

    /**
     * Constructs a new `Logger` instance.
     *
     * @param string $name
     * @param Logger|null $parent
     */
    public function __construct($name, $parent = null) {
        $this->name = $name;
        $this->parent = $parent;

        // Forward events to the parent if applicable
        if ($parent !== null) {
            $this->on('log', function($event) use ($parent) {
                $parent->emit('log', $event);
            });
        }
    }

    /**
     * Creates a new child logger instance that forwards events up to this logger.
     *
     * @param string $name
     * @return static
     */
    public function createLogger($name) {
        return new static($name, $this);
    }

    public function verbose() {
        return $this->writeLine(LogLevel::VERBOSE, func_get_args());
    }

    public function debug() {
        return $this->writeLine(LogLevel::DEBUG, func_get_args());
    }

    public function info() {
        return $this->writeLine(LogLevel::INFO, func_get_args());
    }

    public function warn() {
        return $this->writeLine(LogLevel::WARN, func_get_args());
    }

    public function error() {
        return $this->writeLine(LogLevel::ERROR, func_get_args());
    }

    /**
     * Writes a log event to the event.
     *
     * @param int $level
     * @param array $args
     * @return void
     */
    protected function writeLine($level, $args) {
        $event = new LogEvent();
        $event->level = $level;
        $event->name = $this->name;
        $event->logger = $this;
        $event->timestamp = time();
        $event->args = $args;

        $this->emit('log', $event);
    }

}
