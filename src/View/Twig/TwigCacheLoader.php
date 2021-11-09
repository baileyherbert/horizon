<?php

namespace Horizon\View\Twig;

class TwigCacheLoader extends TwigLoader {

	/**
	 * Checks if caching is enabled in the configuration.
	 *
	 * @return bool
	 */
	protected function isCacheEnabled() {
		return true;
	}

}
