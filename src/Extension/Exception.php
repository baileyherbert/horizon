<?php

namespace Horizon\Extension;

class Exception extends \Exception {

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * Exception constructor.
	 *
	 * @param string $extensionPath
	 * @param string $message
	 */
	public function __construct($extensionPath, $message) {
		parent::__construct($message);
		$this->path = $extensionPath;
	}

	/**
	 * Returns the full absolute path to the extension which triggered this exception.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the basename for the directory of the extension which triggered this exception.
	 *
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

}
