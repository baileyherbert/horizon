<?php

namespace Horizon\Translation;

use Horizon\Foundation\Application;
use Horizon\Support\Profiler;

/**
 * Kernel for translation files.
 */
class Kernel {

	/**
	 * @var LanguageBucket
	 */
	protected $bucket;

	/**
	 * Starts the translation kernel.
	 */
	public function boot() {
		Profiler::record('Boot translation kernel');

		$this->bucket = new LanguageBucket();

		foreach (Application::collect('Horizon\Translation\Language') as $language) {
			$this->add($language);
		}
	}

	/**
	 * Gets the global language bucket.
	 *
	 * @return LanguageBucket
	 */
	public function bucket() {
		return $this->bucket;
	}

	/**
	 * Adds a language to the global language bucket.
	 *
	 * @param Language $language
	 */
	public function add(Language $language) {
		$this->bucket->add($language);
	}

}
