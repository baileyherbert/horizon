<?php

namespace Horizon\Exception;

class HorizonException extends \Exception {

	private $details;

	/**
	 * @param int $code Unique exception code.
	 * @param string $details Optional additional information.
	 */
	public function __construct($code, $details = null) {
		parent::__construct($this->generateMessage($code), $code);

		$this->details = $details;
		$this->message = sprintf('%s: %s (%s)', $this->getHex(), $this->getMessage(), $this->details);
	}

	/**
	 * Generates an exception message from the provided unique code.
	 *
	 * @param int $code
	 * @return string
	 */
	private function generateMessage($code) {
		static $ERRORS = array(
			0x0001 => 'Missing required trait',
			0x0002 => 'Missing configuration file',
			0x0003 => 'Configuration file did not return an array',
			0x0004 => 'Driver load failed',
			0x0005 => 'Critical file missing',
			0x0006 => 'Critical class missing',
			0x0007 => 'Request instance not loaded',
			0x0008 => 'Response instance not loaded',
			0x0009 => 'View initialization error'
		);

		// Find the error and return it
		if (isset($ERRORS[$code])) {
			return $ERRORS[$code];
		}

		// Not found
		return 'Unknown exception';
	}

	public function getDetails() {
		return $this->details;
	}

	public function getHex() {
		$hex = dechex($this->getCode());
		return '0x' . str_pad($hex, 4, "0", STR_PAD_LEFT);
	}

	public function __toString() {
		return sprintf(
			'HorizonException: %s',
			$this->getMessage()
		);
	}
}
