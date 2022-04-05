<?php

namespace Horizon\Http\Cookie\Drivers;

use Horizon\Http\Cookie\Drivers\Database\SessionCookieHandler;

class DatabaseDriver extends CookieDriver {

	protected function startSession() {
		$handler = new SessionCookieHandler();
		session_set_save_handler($handler, true);

		parent::startSession();
	}

}
