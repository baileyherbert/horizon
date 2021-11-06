<?php

namespace Horizon\Translation;

use Horizon\Translation\Language\Definition;
use Horizon\Translation\Language\NamespaceDefinition;

class LanguageParser {

	/**
	 * @var array
	 */
	private $namespaces = array();

	/**
	 * @var array
	 */
	private $headers = array();

	/**
	 * @var array
	 */
	private $variables = array();

	/**
	 * @var float
	 */
	public $timeTaken = 0;

	/**
	 * Constructs a new LanguageParser object for the given SIT data.
	 *
	 * @param string $fileData
	 */
	public function __construct($fileData) {
		$startTime = microtime(true);

		$fileData = $this->normalizeFile($fileData);
		$lines = explode("\n", $fileData);

		$currentNamespace = null;
		$lineNumber = 1;

		foreach ($lines as $line) {
			$line = $this->normalizeLine($line);

			if (!empty($line)) {
				if (substr($line, 0, 1) == '@') {
					preg_match("/(@[^\s\\n]+)/", $line, $matches);
					$command = $matches[1];

					if ($command == '@var' || $command == '@arg') {
						$this->parseVariable(substr($line, strlen($command)), $lineNumber);
					}
					elseif ($command == '@namespace') {
						$currentNamespace = $this->parseNamespace(substr($line, strlen($command)), $lineNumber);
						$this->namespaces[$currentNamespace] = new NamespaceDefinition($currentNamespace);
					}
					elseif (in_array($command, $this->getAllowedHeaders())) {
						$this->parseHeader($command, substr($line, strlen($command)), $lineNumber);
					}
					else {
						throw new LanguageException(sprintf('Parse error: Unknown command "%s" on line %d.', $command, $lineNumber));
					}
				}
				else {
					$definition = $this->parseDefinition($line, $lineNumber);
					$this->namespaces[$currentNamespace]->addDefinition($definition);
				}
			}

			$lineNumber++;
		}

		$this->timeTaken = (microtime(true) - $startTime);
	}

	/**
	 * Gets the parsed headers.
	 *
	 * @return array
	 */
	public function &getHeaders() {
		return $this->headers;
	}

	/**
	 * Gets the parsed namespaces.
	 *
	 * @return array
	 */
	public function &getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * Gets the parsed variables.
	 *
	 * @return array
	 */
	public function &getVariables() {
		return $this->variables;
	}

	/**
	 * Parses the variable and stores it. Variables can consist of booleans, strings, numbers, or nil/null.
	 *
	 * @param string $line
	 * @param int $lineNumber
	 */
	protected function parseVariable($line, $lineNumber) {
		// Find the components of the variable
		preg_match('/\\s*(["\'](?:[^\'"\\\\]|\\\\.)*["\'])\\s+(["\'](?:[^\'"\\\\]|\\\\.)*["\']|true|false|null|nil|[-]{0,1}\\d*[.]{0,1}\\d*[^.])\\s*/', $line, $components);

		// Make sure it is a correct declaration
		if (count($components) !== 3) {
			throw new LanguageException(sprintf('Parse error: Incorrect variable declaration on line %d.', $lineNumber));
		}

		// Extract the components
		$variableName = $this->parseQuotedString($components[1]);
		$variableValue = $this->parseMixedValue($components[2]);

		// Check for duplicates
		if (isset($this->variables[$variableName])) {
			throw new LanguageException(sprintf('Parse error: Variable "%s" already declared on line %d.', $variableName, $lineNumber));
		}

		// Store the variable
		$this->variables[strtolower($variableName)] = $variableValue;
	}

	/**
	 * Parses the namespace and verifies it is in the correct format. Returns the parsed version.
	 *
	 * @param string $line
	 * @param int $lineNumber
	 * @return string
	 */
	protected function parseNamespace($line, $lineNumber) {
		$namespace = trim($line);

		if (!preg_match('/^([^0-9][a-zA-Z0-9._:-]+)$/', $namespace)) {
			throw new LanguageException(sprintf('Parse error: Invalid namespace "%s" on line %d.', $namespace, $lineNumber));
		}

		return strtolower($namespace);
	}

	/**
	 * Parses the header and stores it.
	 *
	 * @param string $command
	 * @param string $line
	 * @param int $lineNumber
	 */
	protected function parseHeader($command, $line, $lineNumber) {
		// Extract the components
		$headerName = ltrim($command, '@');
		$headerValue = trim($line);

		// Check for duplicates
		if (isset($this->headers[$headerName])) {
			throw new LanguageException(sprintf('Parse error: Header "%s" already declared on line %d.', $headerName, $lineNumber));
		}

		// Store the header
		$this->headers[$headerName] = $headerValue;
	}

	/**
	 * Parses the definition and returns it.
	 *
	 * @param string $line
	 * @param int $lineNumber
	 * @return Definition
	 */
	protected function parseDefinition($line, $lineNumber) {
		// Find the components of the variable
		preg_match('/\\s*(["](?:[^"\\\\]|\\\\.)*["]|[\'](?:[^\'\\\\]|\\\\.)*[\'])\\s*[=]{0,1}\\s*(["](?:[^"\\\\]|\\\\.)*["]|[\'](?:[^\'\\\\]|\\\\.)*[\'])\\s*(\\/[a-zA-Z]+){0,1}/', $line, $components);

		// Make sure it is a correct declaration
		if (count($components) !== 3 && count($components) !== 4) {
			throw new LanguageException(sprintf('Parse error: Incorrect definition declaration on line %d.', $lineNumber));
		}

		// Extract the components
		$originalText = $this->parseQuotedString($components[1]);
		$translatedText = $this->parseQuotedString($components[2]);
		$flags = array();

		if ($translatedText == "%") {
			$translatedText = $originalText;
		}

		// Extract flags
		if (count($components) == 4) {
			$joinedFlags = substr($components[3], 1);
			$flags = str_split($joinedFlags);

			// Verify that all flags are valid
			foreach ($flags as $flag) {
				if (!in_array($flag, $this->getAllowedFlags())) {
					throw new LanguageException(sprintf('Parse error: Unknown flag "%s" on line %d.', $flag, $lineNumber));
				}
			}
		}

		return new Definition($originalText, $translatedText, $flags);
	}

	/**
	 * Parses and returns a quoted string.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function parseQuotedString($string) {
		$quoteCharacter = substr($string, 0, 1);

		$string = str_replace('\\' . $quoteCharacter, $quoteCharacter, $string);
		$string = str_replace('\\\\' . $quoteCharacter, '\\' . $quoteCharacter, $string);

		return substr($string, 1, -1);
	}

	/**
	 * Parses and returns a value of mixed type (booleans, null, numbers, or strings).
	 *
	 * @param string $string
	 * @return string
	 */
	protected function parseMixedValue($value) {
		if ($value == 'true' || $value == 'false') {
			return $value == 'true';
		}

		if ($value == 'null' || $value == 'nil') {
			return null;
		}

		if (is_numeric($value)) {
			return $value;
		}

		return $this->parseQuotedString($value);
	}

	/**
	 * Normalizes the newline character of the given data to '\n' and returns the corrected data.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function normalizeFile($text) {
		$text = str_replace("\r\n", "\n", $text);
		$text = trim($text);

		return $text;
	}

	/**
	 * Normalizes a line by trimming off whitespace and removing comments.
	 *
	 * @param string $text
	 * @return string
	 */
	protected function normalizeLine($text) {
		// Find a comment character outside of any quotes
		preg_match('/(?s)(?:(?<!\\\\)\'(?:\\\\\'|[^\'])*\'|(?<!\\\\)"(?:\\\\"|[^"])*")(*SKIP)(*F)|#/', $text, $matches, PREG_OFFSET_CAPTURE);

		// Remove comments from the line
		if (!empty($matches)) {
			$offset = $matches[0][1];
			$text = substr($text, 0, $offset);
		}

		// Trim the text
		$text = trim($text);

		return $text;
	}

	/**
	 * Returns a reference to an array of headers supported by the parser.
	 */
	protected function &getAllowedHeaders() {
		static $array = array(
			'@author',
			'@name',
			'@version',
			'@date',
			'@copyright',
			'@url',
			'@website',
			'@horizon',
			'@repo',
			'@updates',
			'@update',
			'@description',
			'@desc',
			'@direction',
			'@mode'
		);

		return $array;
	}

	/**
	 * Returns a reference to an array of flags supported by the parser.
	 */
	protected function &getAllowedFlags() {
		static $array = array(
			'i',
			'x',
			'r',
			'h'
		);

		return $array;
	}

}
