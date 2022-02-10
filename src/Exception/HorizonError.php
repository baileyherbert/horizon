<?php

namespace Horizon\Exception;

use Exception;

class HorizonError {

	private $message;
	private $severity;
	private $severityLevel;
	private $severityLabel;
	private $file;
	private $line;
	private $context;
	private $contextObject;

	/**
	 * Constructs a new HorizonError instance, which represents a processed error that is ready to be rendered, logged,
	 * and reported.
	 *
	 * @param string $message
	 * @param int $severity
	 * @param string $file
	 * @param int $line
	 * @param string $context
	 * @param \Exception|null $contextObject
	 */
	public function __construct($message, $severity, $file, $line, $context, $contextObject = null) {
		$this->message = $message;
		$this->severity = $severity;
		$this->file = $file;
		$this->line = $line;
		$this->context = $context;
		$this->contextObject = $contextObject;

		$this->severityLevel = static::getSeverityLevelFromConstant($severity);
		$this->severityLabel = static::getSeverityLabelFromLevel($this->severityLevel);
	}

	/**
	 * Gets the message of the error.
	 *
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Gets the original severity constant of the error.
	 *
	 * @return int
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * Gets the severity level of the error.
	 *
	 * @return int
	 */
	public function getLevel() {
		return $this->severityLevel;
	}

	/**
	 * Gets the absolute file path from which the error was triggered.
	 *
	 * @return string
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Gets the line at which the error was triggered.
	 *
	 * @return int
	 */
	public function getLine() {
		return $this->line;
	}

	/**
	 * Gets the severity label (e.g. 'Notice', 'Warning', 'Fatal error', etc).
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->severityLabel;
	}

	/**
	 * Gets the context from which this error was caught ('exception' = uncaught exception, 'shutdown' = fatal error
	 * which terminated page, 'runtime' = non-fatal error while page was executing).
	 *
	 * @return string
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * If this error was triggered by an uncaught exception, returns the original exception object. Otherwise, returns
	 * null.
	 *
	 * @return \Exception|null
	 */
	public function getException() {
		if ($this->context == 'exception' && !is_null($this->contextObject)) {
			if ($this->contextObject instanceof \Exception) {
				return $this->contextObject;
			}
		}

		return new AutoException($this);
	}

	/**
	 * Converts a PHP error level constant into a severity level for the purposes of error handling. The returned
	 * level is between 1 (least severe) and 6 (most severe).
	 *
	 * @param int $constant
	 * @return int
	 */
	protected static function getSeverityLevelFromConstant($constant) {
		static $severities = array(
			E_NOTICE => 1,
			E_USER_NOTICE => 1,
			E_STRICT => 2,
			E_DEPRECATED => 3,
			E_USER_DEPRECATED => 3,
			E_WARNING => 4,
			E_USER_WARNING => 4,
			E_CORE_WARNING => 4,
			E_ERROR => 5,
			E_USER_ERROR => 5,
			E_CORE_ERROR => 5,
			E_RECOVERABLE_ERROR => 5,
			E_PARSE => 6
		);

		if (isset($severities[$constant])) {
			return $severities[$constant];
		}

		return 5;
	}

	/**
	 * Converts a severity level into a label (such as "Warning" or "Fatal error") for the purposes of logging or
	 * rendering.
	 *
	 * @param int $severity
	 * @return string
	 */
	protected static function getSeverityLabelFromLevel($severity) {
		static $labels = array(
			1 => 'Notice',
			2 => 'Strict',
			3 => 'Deprecated',
			4 => 'Warning',
			5 => 'Fatal error',
			6 => 'Parse error'
		);

		if (isset($labels[$severity])) {
			return $labels[$severity];
		}

		return 'Unknown error';
	}

	/**
	 * Converts the given exception into a HorizonError instance.
	 *
	 * @param Exception|Error $ex
	 * @return HorizonError
	 */
	public static function fromException($exception, $uncaught = true) {
		$reflect = new \ReflectionClass($exception);
		$shortName = $reflect->getShortName();
		$prefix = $uncaught ? 'Uncaught ' : '';

		$message = sprintf(
			"{$prefix}exception '%s' with message '%s' in %s:%d Stack trace: %s",
			$shortName,
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$exception->getTraceAsString()
		);

		return new static($message, E_ERROR, $exception->getFile(), $exception->getLine(), 'exception', $exception);

	}

}
