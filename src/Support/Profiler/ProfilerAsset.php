<?php

namespace Horizon\Support\Profiler;

class ProfilerAsset {

	/**
	 * The asset's name or description, or `null` if the asset is not identifiable.
	 *
	 * @var string|null
	 */
	public $description;

	/**
	 * The number of seconds the asset took to load.
	 *
	 * @var float
	 */
	public $duration;

	/**
	 * Constructs a new `ProfilerAsset` instance.
	 *
	 * @param string|null $description
	 * @param float $duration
	 */
	public function __construct($description, $duration) {
		$this->description = $description;
		$this->duration = $duration;
	}

}
