<?php

namespace Horizon\Support\Profiler;

class ProfilerEvent {

	/**
	 * The event description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The extra information for this event or `null` if not provided.
	 *
	 * @var mixed|null
	 */
	public $extraInformation;

	/**
	 * The number of seconds in runtime at which this event was recorded.
	 *
	 * @var float
	 */
	public $timestamp;

	/**
	 * Constructs a new `ProfilerEvent` instance.
	 *
	 * @param string $description
	 * @param mixed|null $extraInformation
	 * @param float $timestamp
	 */
	public function __construct($description, $extraInformation, $timestamp) {
		$this->description = $description;
		$this->extraInformation = $extraInformation;
		$this->timestamp = $timestamp;
	}

}
