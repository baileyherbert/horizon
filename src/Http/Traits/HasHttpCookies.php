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
	 * Returns true if there is an active session and cookies will be sent to the client.
	 *
	 * @return bool
	 */
	public function hasSession() {
		return isset($this->horizonSession);
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
