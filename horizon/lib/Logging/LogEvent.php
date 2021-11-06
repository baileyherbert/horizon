<?php

namespace Horizon\Logging;

class LogEvent {

	/**
	 * The logging level for this event.
	 *
	 * - `LogLevel::VERBOSE` (0)
	 * - `LogLevel::DEBUG` (1)
	 * - `LogLevel::INFO` (2)
	 * - `LogLevel::WARN` (3)
	 * - `LogLevel::ERROR` (4)
	 *
	 * @var int
	 */
	public $level;

	/**
	 * The name of the logger instance.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The logger instance that spawned this event.
	 *
	 * @var Logger
	 */
	public $logger;

	/**
	 * The timestamp at which this event was emitted.
	 *
	 * @var int
	 */
	public $timestamp;

	/**
	 * The arguments for this event that should be converted into a single string.
	 *
	 * @var array
	 */
	public $args;

}
