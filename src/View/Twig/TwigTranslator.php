<?php

namespace Horizon\View\Twig;

use Horizon\Foundation\Application;

class TwigTranslator {

	/**
	 * Translates the template with the provided namespaces.
	 *
	 * @param string $value
	 * @param array $namespaces
	 * @return string
	 */
	public function compile($value, array $namespaces) {
		if (is_null($namespaces) || empty($namespaces)) {
			return $value;
		}

		$bucket = Application::kernel()->translation()->bucket();
		return $bucket->autoTranslate($value, array(), $namespaces);
	}

}
