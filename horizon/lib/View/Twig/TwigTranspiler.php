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
     * @var string
     */
    private $extensionReferenceHash;

    /**
     * Constructs a new precompiler instance, optionally binding the template to an extension.
     *
     * @param TwigFileLoader $loader
     */
    public function __construct(TwigFileLoader $loader)
    {
        $this->extensions = (new TwigExtensionLoader($loader))->getExtensions();
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
        $this->extensionReferenceHash = md5($templateFileName);

        $value = $this->correctWhitespace($value);
        $value = $this->compileTags($value);
        $value = $this->compileStatements($value);
        $value = $this->compileVariables($value);

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

        return $match[0];
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

        return substr($value, 1, -1);
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
        $word = '';

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

            if (!$inString && $char === chr(36)) {
                if ($previous === null || !$disableString) {
                    $insertCharacter = '';
                }
            }

            if (!$inString) {
                if (preg_match('/[a-zA-z]/', $char)) {
                    $word .= $char;

                    if (strlen($arguments) == $i + 1) {
                        if ($word == 'this') {
                            $result = substr($result, 0, -3) . "'" . $this->extensionReferenceHash . "'";
                            break;
                        }
                    }
                }
                else {
                    if (!empty($word)) {
                        if ($word == 'this') {
                            $result = substr($result, 0, -4) . "'" . $this->extensionReferenceHash . "'";
                        }
                    }

                    $word = '';
                }
            }

            if ($insertCharacter !== '') {
                $result .= $insertCharacter;
            }

            $i++;
        }

        return $result;
    }

    protected function compileVariables($value)
    {
        $result = '';
        $i = 0;

        $inBrackets = false;
        $inOuterString = false;

        $inString = false;
        $disableString = false;
        $stringCharacter = '';
        $word = '';

        $escaped = false;

        while ($i < strlen($value)) {
            $char = $value[$i];
            $insertCharacter = $char;

            $previous = (isset($value[$i - 1])) ? $value[$i - 1] : null;
            $next = (isset($value[$i + 1])) ? $value[$i + 1] : null;
            $nextAfter = (isset($value[$i + 2])) ? $value[$i + 2] : null;

            if (!$inBrackets) {
                if ($char === chr(92) && $next === chr(64)) {
                    $insertCharacter = chr(64);
                    $i++;
                }

                if ($char === chr(64) && $next === chr(123) && $nextAfter === chr(123)) {
                    $escaped = true;
                    $insertCharacter = '';
                }

                if ($char === chr(123) && $previous === chr(123)) {
                    if (!$escaped) {
                        $inBrackets = true;
                    }
                    else {
                        $escaped = false;
                    }
                }
            }

            if ($inBrackets) {
                if ($inString) {
                    if ($char === chr(92)) {
                        $disableString = !$disableString;
                    }
                    else if ($char !== chr(34) && $char !== chr(39)) {
                        $disableString = false;
                    }
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

                if ($char === chr(125) && $next === chr(125)) {
                    $inBrackets = false;
                }
            }

            if ($inBrackets && !$inString) {
                if ($char === chr(36)) {
                    $insertCharacter = '';
                }

                if ($char === chr(43)) {
                    $insertCharacter = '~';
                }

                if ($char === chr(45) && $next === chr(62)) {
                    $insertCharacter = '.';
                }

                if ($char === chr(62) && $previous === chr(45)) {
                    $insertCharacter = '';
                }

                if ($char === chr(124) && $next === chr(124)) {
                    $insertCharacter = 'or';
                }

                if ($char === chr(124) && $previous === chr(124)) {
                    $insertCharacter = '';
                }

                if ($char === chr(33) && $next !== chr(61)) {
                    $insertCharacter = 'not ';
                }

                if ($char === chr(38) && $next === chr(38)) {
                    $insertCharacter = 'and';
                }

                if ($char === chr(38) && $previous === chr(38)) {
                    $insertCharacter = '';
                }
            }

            if ($insertCharacter !== '') {
                $result .= $insertCharacter;
            }

            $i++;
        }

        return $result;
    }

    protected function compileTags($value)
    {
        $result = '';
        $i = 0;

        $inBrackets = false;
        $inOuterString = false;

        $inString = false;
        $disableString = false;
        $stringCharacter = '';
        $word = '';

        $inUnescapedTag = false;
        $inCommentTag = false;

        while ($i < strlen($value)) {
            $char = $value[$i];
            $insertCharacter = $char;

            $previous = (isset($value[$i - 1])) ? $value[$i - 1] : null;
            $next = (isset($value[$i + 1])) ? $value[$i + 1] : null;
            $nextAfter = (isset($value[$i + 2])) ? $value[$i + 2] : null;
            $nextAfterThat = (isset($value[$i + 3])) ? $value[$i + 3] : null;

            if (!$inString && !$inBrackets) {
                if (!$inUnescapedTag && $char === chr(123) && $next === chr(33) && $nextAfter === chr(33)) {
                    $i += 2;
                    $insertCharacter = '{{ (';
                    $inUnescapedTag = true;
                }
                else if ($inUnescapedTag && $char === chr(33) && $next === chr(33) && $nextAfter === chr(125)) {
                    $i += 2;
                    $insertCharacter = ') | raw }}';
                    $inUnescapedTag = false;
                }

                if (!$inCommentTag && $char === chr(123) && $next === chr(123) && $nextAfter === chr(45) && $nextAfterThat === chr(45)) {
                    $inCommentTag = true;
                    $i += 3;
                    $insertCharacter = '{#';
                }
                else if ($inCommentTag && $char === chr(45) && $next === chr(45) && $nextAfter === chr(125) && $nextAfterThat === chr(125)) {
                    $inCommentTag = false;
                    $i += 3;
                    $insertCharacter = '#}';
                }
            }

            if (!$inBrackets) {
                if ($char === chr(123) && $previous === chr(123)) {
                    $inBrackets = true;
                }
            }

            if ($inBrackets) {
                if ($inString) {
                    if ($char === chr(92)) {
                        $disableString = !$disableString;
                    }
                    else if ($char !== chr(34) && $char !== chr(39)) {
                        $disableString = false;
                    }
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

                if ($char === chr(125) && $next === chr(125)) {
                    $inBrackets = false;
                }
            }

            if ($insertCharacter !== '') {
                $result .= $insertCharacter;
            }

            $i++;
        }

        return $result;
    }

    protected function correctWhitespace($str)
    {
        $lines = explode("\n", $str);
        $newLines = array();
        $correctionIndent = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $indented = strlen($line) - strlen($trimmed);

            if (preg_match('/^{#.+#}$/', $trimmed)) {
                continue;
            }

            if (preg_match('/^@(\w+)\s*\(/', $trimmed)) {
                $newLines[] = $trimmed;
                $correctionIndent = $indented;

                if (preg_match('/^@section/', $trimmed) || preg_match('/^@block/', $trimmed)) {
                    $correctionIndent = null;
                }
            }
            else if (preg_match('/^@(\w+)\s*\(/', $trimmed) || preg_match('/^@(end)/', $trimmed)) {
                $newLines[] = $trimmed;
            }
            else {
                if ($correctionIndent !== null) {
                    if ($indented == ($correctionIndent + 1) || $indented == ($correctionIndent + 4)) {
                        $correctBy = $indented - $correctionIndent;
                        $line = substr($line, $correctBy);
                    }
                    else {
                        if ($indented <= $correctionIndent) {
                            $correctionIndent = null;
                        }
                    }
                }

                $newLines[] = $line;
            }
        }

        return implode("\n", $newLines);
    }

}
