<?php

namespace Horizon\Http\Traits;

use Horizon\Http\Cookie\Session;

trait HasHttpCookies {

	/**
	 * @var Session
	 */
	protected $horizonSession;

	/**
	 * Gets the session object for storing or reading persistent data. It is important to note that Horizon does not
	 * initialize sessions by default; rather, they are only initialized when used. Calling this method will cause
	 * session initialization if not done already.
	 *
	 * @return Session
	 */
	public function session() {
		if (!isset($this->horizonSession)) {
			$this->horizonSession = new Session();
		}

		return $this->horizonSession;
	}

	/**
	 * @see session()
	 * @return Session
	 */
	public function getSession() {
		return $this->session();
	}

	/**
	 *
	 */
	public function cookies() {

	}

	/**
	 *
	 */
	public function cookie($name, $default = null) {

	}

}
