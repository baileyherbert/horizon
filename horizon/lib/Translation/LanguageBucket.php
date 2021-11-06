<?php

namespace Horizon\Translation;

use Horizon\Translation\Language;
use Horizon\Translation\Language\Definition;
use Horizon\Support\Arr;

class LanguageBucket {

	/**
	 * @var Language
	 */
	private $languages = array();

	/**
	 * @var Definition[]
	 */
	private $definitions = array();

	/**
	 * @var NamespaceDefinition[]
	 */
	private $namespaces = array();

	/**
	 * Constructs a new LanguageBucket instance.
	 */
	public function __construct() {

	}

	/**
	 * Translates the provided text automatically by looking for matching definitions within it. Returns the final
	 * text with variables replaced.
	 *
	 * Warning - This is a very expensive operation and should not be performed repeatedly with large definition sets.
	 * To perform auto translation, all compiled definitions are compared per call.
	 *
	 * If an array of $namespaces are provided, only those namespaces will be used for translation.
	 */
	public function autoTranslate($text, $variables = array(), $namespaces = array()) {
		list($keys, $values) = $this->getFlattenedVariables($variables);

		// Get all compiled definitions
		$compiled = $this->getCompiledDefinitions($namespaces, $keys, $values);

		// Replace
		$text = preg_replace(array_keys($compiled), array_values($compiled), $text);

		return $text;
	}

	/**
	 * Gets an array of compiled definitions and their translated texts. If an array of namespace names is provided,
	 * only definitions in those namespaces will be loaded. Otherwise, all definitions will be loaded.
	 *
	 * @param array $namespaces
	 * @param array $variables
	 * @return array
	 */
	protected function getCompiledDefinitions(array &$namespaces, array &$variableKeys = array(), &$variableValues = array()) {
		$targets = $this->namespaces;
		$compiled = array();

		if (!empty($namespaces)) {
			$targets = array();

			foreach ($namespaces as $name) {
				if ($this->getNamespace($name)) {
					$targets[] = $this->getNamespace($name);
				}
			}
		}

		foreach ($targets as $namespace) {
			foreach ($namespace->getDefinitions() as $definition) {
				$pattern = $definition->compile();
				$translated = $definition->getTranslation();

				if (!isset($compiled[$pattern])) {
					if (!empty($variableKeys) && strpos($translated, '{{') !== false) {
						$translated = preg_replace($variableKeys, $variableValues, $translated);
					}

					$compiled[$pattern] = $translated;
				}
			}
		}

		return $compiled;
	}

	/**
	 * Translates the provided text and replaces variables. If the text is not found in any language definition set,
	 * returns the provided text, with variables still replaced.
	 *
	 * @param string $text
	 * @param array $variables
	 */
	public function translate($text, $variables = array()) {
		$text = preg_replace("/({{\s*)([a-zA-Z._]+)(\s*}})/", "{{ $2 }}", $text);
		$match = $this->fastLookup($text);

		if (is_null($match)) {
			$match = $text;
		}

		if (!empty($variables)) {
			$match = $this->replaceVariables($match, $variables);
		}

		return $match;
	}

	/**
	 * Flattens and returns the provided array using dot notation.
	 *
	 * @param array $variables
	 * @return array
	 */
	protected function getFlattenedVariables(array &$variables) {
		$replacements = Arr::dot($variables);

		$keys = array();
		$values = array();

		foreach ($replacements as $key => $value) {
			if (!is_object($value) && !is_array($value)) {
				$keys[] = "/({{\\s*)(\\Q{$key}\\E)(\\s*}})/";
				$values[] = (string) $value;
			}
		}

		return array(&$keys, &$values);
	}

	/**
	 * Replaces variables in a translation with data from the provided array. Missing variables will be untouched.
	 *
	 * @param string $match
	 * @param array $variables
	 * @return string
	 */
	protected function replaceVariables(&$match, array &$variables) {
		list($keys, $values) = $this->getFlattenedVariables($variables);
		$text = preg_replace($keys, $values, $match);

		return $text;
	}

	/**
	 * Gets the specified namespace, or null if it doesn't exist.
	 *
	 * @param string $name
	 * @return NamespaceDefinition|null
	 */
	public function getNamespace($name) {
		if (isset($this->namespaces[$name])) {
			return $this->namespaces[$name];
		}

		return null;
	}

	/**
	 * Adds a language to the bucket.
	 */
	public function add(Language $language) {
		$this->languages[] = $language;

		// Store namespaces
		$this->storeNamespaces($language);

		// Store definitions in fast index
		$this->storeDefinitions($language);
	}

	/**
	 * Stores all namespaces in the language for fast lookup.
	 *
	 * @param Language $language
	 */
	protected function storeNamespaces(Language $language) {
		foreach ($language->getNamespaces() as $name => $namespace) {
			if (isset($this->namespaces[$name])) {
				$this->namespaces[$name]->mergeWith($namespace);
			}
			else {
				$this->namespaces[$name] = $namespace;
			}
		}
	}

	/**
	 * Stores all definitions in the language for fast lookup. Duplicate definitions are ignored.
	 *
	 * @param Language $language
	 */
	protected function storeDefinitions(Language $language) {
		foreach ($language->getNamespaces() as $name => $namespace) {
			foreach ($namespace->getDefinitions() as $key => $definition) {
				if (!isset($this->definitions[$key])) {
					$this->definitions[$key] = $definition;
				}
			}
		}
	}

	/**
	 * Performs a fast lookup using the fast index. Returns the translated text or null. If there are multiple
	 * definitions for the text, the first to load is returned (if you need one in specific, use namespaces).
	 *
	 * @param string $text
	 * @return string|null
	 */
	protected function fastLookup($text) {
		$lookup = array($text, trim($text));

		foreach ($lookup as $key) {
			if (isset($this->definitions[$key])) {
				return $this->definitions[$key]->getTranslation();
			}
		}

		return null;
	}

}
