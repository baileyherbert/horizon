<?php

namespace Horizon\Translation\Language;

class NamespaceDefinition {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var Definition[]
	 */
	protected $definitions = array();

	/**
	 * Constructs a new NamespaceDefinition object.
	 *
	 * @param string $name
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * Adds the definition to the namespace.
	 *
	 * @param Definition $definition
	 */
	public function addDefinition(Definition $definition) {
		$this->definitions[$definition->getOriginal()] = $definition;
	}

	/**
	 * Finds the definition matching the original text, using the definition's flags.
	 *
	 * @param string $originalText
	 * @return Definition|null
	 */
	public function getDefinition($originalText) {
		foreach ($this->definitions as $original => $definition) {
			if ($original == $originalText || $definition->is($originalText)) {
				return $definition;
			}
		}

		return null;
	}

	/**
	 * Returns all definitions.
	 *
	 * @return Definition[]
	 */
	public function getDefinitions() {
		return $this->definitions;
	}

	/**
	 * Finds a matching definition and returns the translated string, or null if no definition was found.
	 *
	 * @param string $originalText
	 * @return string|null
	 */
	public function translate($originalText) {
		$definition = $this->getDefinition($originalText);

		if ($definition) {
			return $definition->getTranslation();
		}

		return null;
	}

	public function mergeWith(NamespaceDefinition $neighbor, $override = true) {
		foreach ($neighbor->getDefinitions() as $key => $value) {
			if (!isset($this->definitions[$key]) || (isset($this->definitions[$key]) && $override)) {
				$this->definitions[$key] = $value;
			}
		}
	}

}
