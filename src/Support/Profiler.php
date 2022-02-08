<?php

namespace Horizon\Support;

use Horizon\Support\Profiler\ProfilerAsset;
use Horizon\Support\Profiler\ProfilerAssetGroup;
use Horizon\Support\Profiler\ProfilerEvent;

/**
 * This class is used to track the performance and timeline of the application throughout its lifecycle.
 *
 * @package Horizon\Support
 */
class Profiler {

	/**
	 * The time at which the application was started.
	 *
	 * @var float|null
	 */
	private static $startTime;

	/**
	 * @var ProfilerEvent[]
	 */
	private static $events = [];

	/**
	 * @var ProfilerAssetGroup[]
	 */
	private static $assetGroups = [];

	/**
	 * Records an event on the timeline at the current time.
	 *
	 * @param string $description A brief description of the event.
	 * @param mixed|null $extraInformation Optional information associated with the event.
	 * @return ProfilerEvent
	 */
	public static function record($description, $extraInformation = null) {
		if (!isset(static::$startTime)) {
			static::$startTime = microtime(true);
		}

		$timestamp = microtime(true) - static::$startTime;
		$event = new ProfilerEvent($description, $extraInformation, $timestamp);

		return static::$events[] = $event;
	}

	/**
	 * Records the load duration of an asset.
	 *
	 * @param string $groupName The name of the asset group (such as `database` or `views`).
	 * @param string $description The asset name or description.
	 * @param float|callback $duration The number of seconds it took for the asset to load, or a function to time.
	 * @return ProfilerAsset
	 */
	public static function recordAsset($groupName, $description, $duration) {
		if (!isset(static::$assetGroups[$groupName])) {
			static::$assetGroups[$groupName] = new ProfilerAssetGroup();
		}

		if (is_callable($duration)) {
			$startTime = microtime(true);
			$duration();
			$duration = microtime(true) - $startTime;
		}

		$asset = new ProfilerAsset($description, $duration);

		$group = static::$assetGroups[$groupName];
		$group->addAsset($asset);

		return $asset;
	}

	/**
	 * Returns all events recorded in the profiler.
	 *
	 * @return ProfilerEvent[]
	 */
	public static function getEvents() {
		return static::$events;
	}

	/**
	 * Returns all asset groups in the profiler.
	 *
	 * @return ProfilerAssetGroup[]
	 */
	public static function getAssetGroups() {
		return static::$assetGroups;
	}

	/**
	 * Returns the specified asset group or creates it if needed.
	 *
	 * @return ProfilerAssetGroup
	 */
	public static function getAssetGroup($groupName) {
		if (!isset(static::$assetGroups[$groupName])) {
			static::$assetGroups[$groupName] = new ProfilerAssetGroup();
		}

		return static::$assetGroups[$groupName];
	}

	/**
	 * Returns the number of seconds that the app has been running.
	 *
	 * @return float
	 */
	public static function getRunTime() {
		return microtime(true) - static::$startTime;
	}

}
