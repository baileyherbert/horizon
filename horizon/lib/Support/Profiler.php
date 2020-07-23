<?php

namespace Horizon\Support;

class Profiler
{

    protected static $records = array();
    protected static $active = array();
    protected static $totals = array();

    /**
     * Starts a timer for the specified profile name.
     *
     * @param string $profile
     * @param mixed $data Optional data to attach to the profile.
     */
    public static function start($profile, $data = null)
    {
        if (!isset(static::$active[$profile])) {
            static::$active[$profile] = array(
                'start' => static::getTime(),
                'end' => null
            );

            if (!is_null($data)) {
                static::$active[$profile]['data'] = $data;
            }

            if (!isset(static::$records[$profile])) {
                static::$records[$profile] = array();
            }

            if (!isset(static::$totals[$profile])) {
                static::$totals[$profile] = 0;
            }
        }
    }

    /**
     * Tests the execution time of the given `$fn` callable under the specified profile name.
     *
     * @param string $profile
     * @param callable $fn
     * @return float
     */
    public static function test($profile, $fn) {
        static::start($profile);
        $fn();
        return static::stop($profile);
    }

    /**
     * Stops a timer for the specified profile name and returns the total time taken for this run alone.
     *
     * @param string $profile
     * @return float
     */
    public static function stop($profile)
    {
        if (!isset(static::$active[$profile])) {
            return;
        }

        // Get the start and end time
        $startTime = static::$active[$profile]['start'];
        $endTime = static::getTime();
        $timeTaken = $endTime - $startTime;

        // Store the end time
        static::$active[$profile]['end'] = $endTime;
        static::$active[$profile]['duration'] = $timeTaken;

        // Record the profile results
        static::$records[$profile][] = static::$active[$profile];
        static::$totals[$profile] += static::$active[$profile]['duration'];

        // Remove the profile
        unset(static::$active[$profile]);

        // Return the time taken
        return $timeTaken;
    }

    /**
     * Gets the total time taken so far for a profile. If there isn't any data available for the profile, this will
     * return `0`.
     *
     * @param string $profileName
     * @return float
     */
    public static function time($profileName)
    {
        $total = 0;

        // Add historical totals
        if (isset(static::$totals[$profileName])) {
            $total += static::$totals[$profileName];
        }

        // Add totals from currently-active profiles
        if (isset(static::$active[$profileName])) {
            $profile = static::$active[$profileName];
            $total += static::getTime() - $profile['start'];
        }

        return $total;
    }

    /**
     * Gets the current time in microseconds.
     *
     * @return float
     */
    protected static function getTime()
    {
        return microtime(true);
    }

    /**
     * Returns an array of profiles in the order that they started.
     *
     * @return array
     */
    public static function getWaterfall() {
        $waterfall = array();

        // Add historical records
        foreach (static::$records as $name => $profiles) {
            foreach ($profiles as $profile) {
                $waterfall[] = array_merge(array(
                    'profile' => $name,
                    'duration' => 0
                ), $profile);
            }
        }

        // Add active records
        foreach (static::$active as $name => $profile) {
            $waterfall[] = array_merge(array(
                'profile' => $name,
                'duration' => static::getTime() - $profile['start']
            ), $profile);
        }

        // Sort the waterfall by start time
        usort($waterfall, function($a, $b) {
            if ($a['start'] === $b['start']) return 0;
            return $a['start'] > $b['start'];
        });

        return $waterfall;
    }

    /**
     * Returns an associative array, where the keys are the profile names, and the values are the total times.
     *
     * @param bool $includeActive If set to `true`, the total times reported in the array will include pending profiles.
     * @return array
     */
    public static function getTotalTimes($includeActive = true) {
        $totals = static::$totals;

        // Add active profiles
        if ($includeActive) {
            foreach (static::$active as $name => $profile) {
                $totals[$name] += static::getTime() - $profile['start'];
            }
        }

        arsort($totals);
        return $totals;
    }

}
