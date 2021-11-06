<?php

namespace Horizon\View;

use Horizon\Foundation\Application;
use Horizon\View\Twig\TwigLoader;

class Template {

	/**
	 * @var string
	 */
	protected $templatePath;

	/**
	 * @var array
	 */
	protected $context;

	/**
	 * Constructs a new Template instance.
	 *
	 * @param string $templateFile
	 * @param array $context
	 */
	public function __construct($templateFile, $context = array()) {
		$this->context = $context;
		$this->templatePath = $templateFile;

		if (!starts_with($this->templatePath, '@component/')) {
			Application::kernel()->view()->setContext($context);
		}
	}

	/**
	 * Gets the relative path of the template file.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->templatePath;
	}

	/**
	 * Gets the context variables for rendering the template.
	 *
	 * @return array
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Compiles the template and returns the generated content as a string.
	 *
	 * @return string
	 */
	public function render() {
		$response = (new TwigLoader($this))->render();

		if (!starts_with($this->templatePath, '@component/')) {
			Application::kernel()->view()->setContext(array());
		}

		return $response;
	}

}
