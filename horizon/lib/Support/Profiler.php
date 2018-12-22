<?php

namespace Horizon\Support;

class Profiler
{

    protected static $profiles = array();

    /**
     * Starts a timer for the specified profile name.
     *
     * @param string $profile
     */
    public static function start($profile)
    {
        if (!isset(static::$profiles[$profile])) {
            static::$profiles[$profile] = array('start' => null, 'end' => null, 'time' => 0);
        }

        static::$profiles[$profile]['start'] = static::getTime();
    }

    /**
     * Stops a timer for the specified profile name and returns the total time taken for this run alone.
     *
     * @param string $profile
     * @return int
     */
    public static function stop($profile)
    {
        if (!isset(static::$profiles[$profile])) {
            return;
        }

        // Get the start and end time
        $startTime = static::$profiles[$profile]['start'];
        $endTime = static::getTime();
        $timeTaken = ($endTime - $startTime);

        // Store the end time
        static::$profiles[$profile]['end'] = $endTime;

        // Store the total time taken
        static::$profiles[$profile]['time'] += $timeTaken;

        // Return the time taken
        return $timeTaken;
    }

    /**
     * Gets the total time taken so far for a profile name.
     *
     * @param string $profileName
     * @return int
     */
    public static function time($profileName)
    {
        if (!isset(static::$profiles[$profileName])) {
            return null;
        }

        $profile = static::$profiles[$profileName];

        if (!isset($profile['end'])) {
            return $profile['time'] + (static::getTime() - $profile['start']);
        }

        return $profile['time'];
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

}
