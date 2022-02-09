<?php

namespace Horizon\Support\Profiler;

class ProfilerAssetGroup {

	/**
	 * @var ProfilerAsset[]
	 */
	public $assets = [];

	/**
	 * @var ProfilerAsset[]
	 */
	private $keymap = [];

	/**
	 * The duration of `null` assets.
	 *
	 * @var float
	 */
	private $duration = 0;

	/**
	 * Adds an asset to the group.
	 *
	 * @param ProfilerAsset $asset
	 * @return void
	 */
	public function addAsset(ProfilerAsset $asset) {
		if (is_null($asset->description)) {
			$this->duration += $asset->duration;
			return;
		}

		if (array_key_exists($asset->description, $this->keymap)) {
			$this->keymap[$asset->description]->duration += $asset->duration;
			return;
		}

		$this->keymap[$asset->description] = $asset;
		$this->assets[] = $asset;
	}

	/**
	 * Returns the number of seconds that all assets in the group collectively ran for.
	 *
	 * @return float
	 */
	public function getRuntime() {
		$total = $this->duration;

		foreach ($this->assets as $asset) {
			$total += $asset->duration;
		}

		return $total;
	}

	/**
	 * Returns true if there's at least one asset in this group with a description.
	 *
	 * @return bool
	 */
	public function hasDescriptiveAssets() {
		foreach ($this->assets as $asset) {
			if (is_string($asset->description)) {
				return true;
			}
		}

		return false;
	}

}
