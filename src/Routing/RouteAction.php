<?php

namespace Horizon\Routing;

use Closure;
use Horizon\Http\Exception\HttpResponseException;

class RouteAction {

	/**
	 * Parses the provided route action and returns a fully-qualified closure.
	 *
	 * @param string $uri
	 * @param Closure|callable $action
	 */
	public static function parse($action) {
		if (is_null($action)) {
			return self::missingAction();
		}

		if (is_array($action) && count($action) === 2) {
			if (is_object($action[0])) {
				return $action;
			}

			if (get_class($action[0])) {
				$action[0] = get_class($action[0]);
			}

			return $action[0] . '::' . $action[1];
		}

		if (!is_string($action) && is_callable($action)) {
			return $action;
		}

		if (is_string($action) && strpos($action, '@') !== false) {
			$action = str_replace('@', '::', $action);
		}

		if (is_string($action) && strpos($action, '::') === false) {
			return $action . '::%';
		}

		return $action;
	}

	/**
	 * Returns a closure that raises a 404 error.
	 *
	 * @return Closure
	 */
	private static function missingAction() {
		return function() {
			throw new HttpResponseException(404);
		};
	}

}
