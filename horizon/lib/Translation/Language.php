<?php

namespace Horizon\Translation;

use Horizon\Translation\Language\Definition;
use Horizon\Translation\Language\NamespaceDefinition;

class Language
{

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var NamespaceDefinition[]
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
     * @var float|null
     */
    private $parseTime = null;

    /**
     * Constructs a new Language object for a file at the given path, which is expected to be in SIT format.
     *
     * @param string $languageFilePath
     */
    public function __construct($languageFilePath)
    {
        // Ensure the file exists
        if (!file_exists($languageFilePath)) {
            throw new LanguageException('Failed to load language file: link not found');
        }

        // Parse the file
        $parser = new LanguageParser(file_get_contents($languageFilePath));

        // Store the data
        $this->filePath = $languageFilePath;
        $this->namespaces = $parser->getNamespaces();
        $this->headers = $parser->getHeaders();
        $this->variables = $parser->getVariables();
        $this->parseTime = $parser->timeTaken;
    }

    /**
     * Checks if the specified header is set. Header names are case-insensitive.
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        $name = strtolower($name);

        return isset($this->headers[$name]);
    }

    /**
     * Gets the specified header, or null if not found. Header names are case-insensitive.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        $name = strtolower($name);

        return isset($this->headers[$name]) ?
            $this->headers[$name] : null;
    }

    /**
     * Gets an array of headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if the namespace exists. Namespace names are case-insensitive.

     * @param string $name
     * @return boolean
     */
    public function hasNamespace($name)
    {
        $name = strtolower($name);

        return isset($this->namespaces[$name]);
    }

    /**
     * Gets the specified namespace. Namespace names are case-insensitive.
     *
     * @return NamespaceDefinition|null
     */
    public function getNamespace($name)
    {
        $name = strtolower($name);

        return isset($this->namespaces[$name]) ?
            $this->namespaces[$name] : null;
    }

    /**
     * Gets an associative array of all namespaces.
     *
     * @return NamespaceDefinition[]
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Checks if the specified variable is set. Variable names are case-insensitive.
     *
     * @param string $name
     * @return boolean
     */
    public function hasVariable($name)
    {
        $name = strtolower($name);

        return isset($this->variables[$name]);
    }

    /**
     * Gets the value of the specified variable, or null if not found. Variable names are case-insensitive.
     *
     * @return bool|null|string|int|double
     */
    public function getVariable($name)
    {
        $name = strtolower($name);

        return isset($this->variables[$name]) ?
            $this->variables[$name] : null;
    }

    /**
     * Gets an associative array of all variables.
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Gets the total time (in seconds) it took to parse this language file.
     *
     * @return float|null
     */
    public function getParseTime()
    {
        return $this->parseTime;
    }

    /**
     * Finds a matching definition and returns the translated text, or null if not found. If a namespace constraint
     * is provided, only that namespace will be searched for the definition. The constraint also supports an array
     * of namespaces.
     *
     * @param string $text
     * @param string|string[]|null $namespaceConstraint
     * @return string
     */
    public function translate($text, $namespaceConstraint = null)
    {
        $target = $this->namespaces;
        $text = preg_replace("/({{\s*)([a-zA-Z._]+)(\s*}})/", "{{ $2 }}", $text);

        if (!is_null($namespaceConstraint)) {
            if (is_string($namespaceConstraint)) {
                $namespace = $this->getNamespace($namespaceConstraint);

                if ($namespace) {
                    return $namespace->translate($text);
                }

                return null;
            }
            else {
                $target = array();

                foreach ($namespaceConstraint as $name) {
                    $namespace = $this->getNamespace($name);

                    if ($namespace) {
                        $target[] = $namespace;
                    }
                }
            }
        }

        foreach ($target as $namespace) {
            $translation = $namespace->translate($text);

            if ($translation !== null) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * Gets the language mode (right-to-left to left-to-right) as a shortened string (ltr or rtl).
     *
     * @return string
     */
    public function getMode()
    {
        $mode = 'ltr';

        if ($this->hasHeader('direction')) {
            $mode = $this->getHeader('direction');
        }

        if ($this->hasHeader('mode')) {
            $mode = $this->getHeader('mode');
        }

        return $mode;
    }

}