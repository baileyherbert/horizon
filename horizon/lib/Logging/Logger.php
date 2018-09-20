<?php

namespace Horizon\Logging;

use Horizon\Enum\Log\LogType;


class Logger
{

    protected $name;
    protected $log = '';

    public function __construct($loggerName = '')
    {
        $this->name = $loggerName;
    }

    /**
     * Writes information to the logger.
     */
    public function info()
    {
        $parts = array();
        foreach (func_get_args() as $value) $parts[] = strval($value);
        $this->log(LogType::INFO, implode(' ', $parts));
    }

    /**
     * Writes debugging information to the logger.
     */
    public function debug()
    {
        $parts = array();
        foreach (func_get_args() as $value) $parts[] = strval($value);
        $this->log(LogType::DEBUG, implode(' ', $parts));
    }

    /**
     * Writes a notice to the logger.
     */
    public function notice()
    {
        $parts = array();
        foreach (func_get_args() as $value) $parts[] = strval($value);
        $this->log(LogType::NOTICE, implode(' ', $parts));
    }

    /**
     * Writes a warning to the logger.
     */
    public function warn()
    {
        $parts = array();
        foreach (func_get_args() as $value) $parts[] = strval($value);
        $this->log(LogType::WARN, implode(' ', $parts));
    }

    /**
     * Writes an error to the logger.
     */
    public function error()
    {
        $parts = array();
        foreach (func_get_args() as $value) $parts[] = strval($value);
        $this->log(LogType::ERROR, implode(' ', $parts));
    }

    /**
     * Writes a line of the specified type to the logger.
     *
     * @param string $logType
     * @param string $message
     */
    protected function log($logType, $message)
    {
        $message = explode("\n", $message);
        if (count($message) > 1) {
            for ($x = 1; $x < count($message); $x++) {
                $message[$x] = '           ' . str_repeat(' ', strlen($logType)) . $message[$x];
            }
        }

        $formatted = sprintf("%s %s: %s\n", date('H:i:s'), $logType, implode("\n", $message));
        $this->log .= $formatted;
    }

    /**
     * Gets the log output.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->log;
    }

}