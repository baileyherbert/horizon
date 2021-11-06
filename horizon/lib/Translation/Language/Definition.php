<?php

namespace Horizon\Translation\Language;

class Definition
{

	/**
	 * @var string
	 */
	protected $originalText;

	/**
	 * @var string
	 */
	protected $formattedText;

	/**
	 * @var string
	 */
	protected $translatedText;

	/**
	 * @var string[]
	 */
	protected $flags = array();

	/**
	 * @var string|null
	 */
	protected $compiled;

	/**
	 * Constructs a new Definition object.
	 *
	 * @param string $original
	 * @param string $translated
	 * @param string[] $flags
	 */
	public function __construct($original, $translated = null, $flags = array())
	{
		$this->originalText = $original;
		$this->translatedText = ($translated != $original) ? $translated : null;
		$this->flags = $flags;

		// Convert variables to a common spacing format
		if (strpos($original, '{{') !== false) {
			$this->formattedText = preg_replace("/({{\s*)([a-zA-Z._]+)(\s*}})/", "{{ $2 }}", $original);
		}

		// Flag: Allow HTML (h)
		if (!$this->hasFlag('h')) {
			$this->translatedText = str_replace(array('&', '<', '>'), array('&amp;', '&lt;', '&gt;'), $this->translatedText);
		}
	}

	/**
	 * Gets the original text.
	 *
	 * @return string
	 */
	public function getOriginal()
	{
		return $this->originalText;
	}

	/**
	 * Gets the translated text.
	 *
	 * @return string
	 */
	public function getTranslation()
	{
		if ($this->translatedText == null) {
			return $this->getOriginal();
		}

		return $this->translatedText;
	}

	/**
	 * Gets the single-character flags for the definition.
	 *
	 * @return string[]
	 */
	public function getFlags()
	{
		return $this->flags;
	}

	/**
	 * Checks whether the definition has the specified single-character flag.
	 *
	 * @param string $flag
	 * @return bool
	 */
	public function hasFlag($flag)
	{
		return in_array($flag, $this->flags);
	}

	/**
	 * Checks whether the definition's original text matches the specified text.
	 *
	 * @param string $text
	 * @return bool
	 */
	public function is($text)
	{
		$original = (!is_null($this->formattedText)) ? $this->formattedText : $this->getOriginal();

		if ($text == $original) {
			return true;
		}

		$isCaseInsensitive = $this->hasFlag('i');
		$isIgnoringWhitespace = $this->hasFlag('x');

		if ($isCaseInsensitive) {
			$original = strtolower($original);
			$text = strtolower($text);

			if ($original == $text) {
				return true;
			}
		}

		if ($isIgnoringWhitespace) {
			$text = preg_replace('/\s+/', ' ', trim($text));
			$original = preg_replace('/\s+/', ' ', trim($original));

			if ($original == $text) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compiles the definition into a regular expression for quick matching or replacing.
	 *
	 * @return string
	 */
	public function compile()
	{
		if (!is_null($this->compiled)) {
			return $this->compiled;
		}

		if ($this->hasFlag('r')) {
			return $this->originalText;
		}

		$text = preg_replace("/({{\s*)([a-zA-Z._]+)(\s*}})/", "\\E{{\\s*\\Q$2\\E\\s*}}\\Q", $this->getOriginal());

		if ($this->hasFlag('x')) {
			$text = preg_replace("/(\s+)(?=(?:[^}]|{[^{]*})*$)/", "\\E\\s+\\Q", $text);
		}

		$pattern = '/(\\Q' . $text . '\\E)(?=(?:[^}]|{[^{]*})*$)/';
		if ($this->hasFlag('i')) $pattern .= 'i';

		$this->compiled = $pattern;
		return $pattern;
	}

}
