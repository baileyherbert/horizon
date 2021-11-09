<?php

namespace Horizon\Http\Traits;

use Horizon\Exception\HorizonException;

trait HasFlashing {

	private function _flashRequireHasSession() {
		if (!method_exists($this, 'session')) {
			throw new HorizonException(0x0001, 'Request trait HasFlashing requires trait HasHttpCookies');
		}
	}

	/**
	 *
	 */
	public function flash() {
		// Require session
		$this->_flashRequireHasSession();
	}

	/**
	 *
	 */
	public function flashExcept() {
		// Require session
		$this->_flashRequireHasSession();
	}

	/**
	 *
	 */
	public function flashOnly() {
		// Require session
		$this->_flashRequireHasSession();
	}

	/**
	 *
	 */
	public function old($key = null, $default = null) {
		// Require session
		$this->_flashRequireHasSession();
	}

}
