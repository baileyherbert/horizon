<?php

namespace Horizon\Support\Profiler;

class ProfilerAssetGroup {

	/**
	 * @var ProfilerAsset[]
	 */
	public $assets = [];

	/**
	 * Adds an asset to the group.
	 *
	 * @param ProfilerAsset $asset
	 * @return void
	 */
	public function addAsset(ProfilerAsset $asset) {
		$this->assets[] = $asset;
	}

	/**
	 * Returns the number of seconds that all assets in the group collectively ran for.
	 *
	 * @return float
	 */
	public function getRuntime() {
		$total = 0;

		foreach ($this->assets as $asset) {
			$total += $asset->duration;
		}

		return $total;
	}

}
