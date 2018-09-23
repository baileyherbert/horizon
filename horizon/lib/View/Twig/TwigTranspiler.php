<?php

namespace Horizon\View\Twig;

use Horizon\Framework\Kernel;

use Horizon\Utils\Path;
use Horizon\Utils\TimeProfiler;

use Horizon\Http\MiniRequest;
use Horizon\Routing\RouteLoader;
use Horizon\Routing\RouteParameterBinder;

use Horizon\Extend\Extension;
use Horizon\View\ViewExtension;

class TwigTranspiler
{

    /**
     * @var Extension
     */
    private $extension;

    /**
     * @var ViewExtension[]
     */
    private $extensions;

    /**
     * @var string[]
     */
    private $transpilers;

    /**
     * @var string[]
     */
    private $translateNamespaces = array();

    /**
     * @var string
     */
    private $templateFileName;

    /**
     * Constructs a new precompiler instance, optionally binding the template to an extension.
     *
     * @param Extension $extension
     */
    public function __construct(Extension $extension = null)
    {
        $this->extension = $extension;
        $this->extensions = (new TwigExtensionLoader())->getExtensions();
        $this->transpilers = $this->findTranspilers();
    }

    /**
     * Finds all tags from extensions which are to be transpiled.
     *
     * @return string[]
     */
    protected function findTranspilers()
    {
        $found = array();

        foreach ($this->extensions as $extension) {
            $transpilers = $extension->getTranspilers();

            foreach ($transpilers as $tag => $command) {
                $tagName = strtolower($tag);

                if (!isset($found[$tagName])) {
                    $found[$tagName] = $command;
                }
            }
        }

        return $found;
    }

    /**
     * Prepares the string for the Twig compiler by compiling Horizon statements.
     *
     * @param string $value
     * @param string $templateFileName
     * @return string
     */
    public function precompile($value, $templateFileName = null)
    {
        $this->templateFileName = $templateFileName;

        $value = $this->compileStatements($value);
        $translated = (new TwigTranslator())->compile($value, $this->translateNamespaces);

        return ltrim($translated);
    }

    /**
     * Compiles all statements in the string.
     *
     * @param string $value
     * @return string
     */
    public function compileStatements($value)
    {
        return preg_replace_callback(
            '/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \)[;]*)?/x', function ($match) {
                return $this->compileStatement($match);
            }, $value
        );
    }

    /**
     * Compiles a statement.
     *
     * @param array $match
     * @return string
     */
    public function compileStatement($match)
    {
        if (count($match) < 2) {
            return $match[0];
        }

        // Get arguments and method
        $tagName = strtolower($match[1]);
        $arguments = (count($match) >= 4) ? $this->parseArguments($match[3]) : '';

        // Handle the special @translate tag
        if ($tagName === 'translate') {
            $this->translateNamespaces[] = trim($arguments, '"\'');
            return;
        }

        // Return the compiled value
        if (isset($this->transpilers[$tagName])) {
            $transpiler = $this->transpilers[$tagName];

            if (is_string($transpiler)) {
                return $this->transpile($tagName, $transpiler, $arguments);
            }
            else if (is_callable($transpiler)) {
                return $transpiler($arguments);
            }
        }
    }

    /**
     * Parses arguments in the form of ('value', 'value') and returns an array of values.
     *
     * @param string $value
     * @return array
     */
    protected function parseArguments($value)
    {
        $value = rtrim($value, ';');

        if (substr($value, 0, 1) != "(") return array();
        if (substr($value, -1) != ")") return array();

        $value = trim($value, '()');

        return $value;
    }

    /**
     * Converts or transpiles the tag into its intended Twig function.
     *
     * @param string $shortcutName
     * @param string $targetName
     * @param string $arguments
     * @return string
     */
    protected function transpile($shortcutName, $targetName, $arguments)
    {
        return sprintf('{{ %s(%s) }}', $targetName, $this->buildArgumentString($arguments));
    }

    /**
     * Processes an arguments string and converts it to Twig format, allowing a Blade-like syntax.
     *
     * @param string $arguments
     * @return string
     */
    protected function buildArgumentString($arguments)
    {
        $result = '';
        $i = 0;

        $inString = false;
        $disableString = false;
        $stringCharacter = '';

        while ($i < strlen($arguments)) {
            $char = $arguments[$i];
            $insertCharacter = $char;
            $previous = (isset($arguments[$i - 1])) ? $arguments[$i - 1] : null;

            if ($char === chr(92)) {
                $disableString = !$disableString;
            }
            else if ($char !== chr(34) && $char !== chr(39)) {
                $disableString = false;
            }

            if (!$disableString) {
                if (!$inString && ($char === chr(34) || $char === chr(39))) {
                    $inString = true;
                    $stringCharacter = $char;
                }
                else if ($inString && $char === $stringCharacter) {
                    $inString = false;
                    $stringCharacter = false;
                }
            }

            if (!$inString && $char === chr(43)) {
                $insertCharacter = chr(126);
            }

            if (!$inString && $char === chr(36) && ($previous === chr(32) || $previous === chr(44) || $previous === chr(43))) {
                $insertCharacter = '';
            }

            if (!empty($insertCharacter)) {
                $result .= $insertCharacter;
            }

            $i++;
        }

        return $result;
    }

}
