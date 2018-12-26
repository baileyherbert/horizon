<?php

namespace Horizon\View\Twig;

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
            return null;
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

        $length = strlen($arguments);

        while ($i < $length) {
            $char = $arguments[$i];
            $insertCharacter = $char;
            $previous = ($i > 0) ? $arguments[$i - 1] : null;

            if ($char === '\\') {
                $disableString = !$disableString;
            }
            else if ($char !== '"' && $char !== "'") {
                $disableString = false;
            }

            if (!$disableString) {
                if (!$inString && ($char === '"' || $char === "'")) {
                    $inString = true;
                    $stringCharacter = $char;
                }
                else if ($inString && $char === $stringCharacter) {
                    $inString = false;
                    $stringCharacter = false;
                }
            }

            if (!$inString && $char === '+') {
                $insertCharacter = '~';
            }

            if (!$inString && $char === '$') {
                if ($previous === null || !$disableString) {
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

    protected function compileVariables($value)
    {
        $result = '';
        $i = 0;

        $inBrackets = false;

        $inString = false;
        $disableString = false;
        $stringCharacter = '';

        $escaped = false;

        $length = strlen($value);

        while ($i < $length) {
            $char = $value[$i];
            $insertCharacter = $char;

            $previous = ($i > 0) ? $value[$i - 1] : null;
            $next = ($i < ($length - 1)) ? $value[$i + 1] : null;
            $nextAfter = ($i < ($length - 2)) ? $value[$i + 2] : null;

            if (!$inBrackets) {
                if ($char === '\\' && $next === '@') {
                    $insertCharacter = '@';
                    $i++;
                }

                if ($char === '@' && $next === '{' && $nextAfter === '{') {
                    $escaped = true;
                    $insertCharacter = '';
                }

                if ($char === '{' && $previous === '{') {
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
                    if ($char === '\\') {
                        $disableString = !$disableString;
                    }
                    else if ($char !== '"' && $char !== "'") {
                        $disableString = false;
                    }
                }

                if (!$disableString) {
                    if (!$inString && ($char === '"' || $char === "'")) {
                        $inString = true;
                        $stringCharacter = $char;
                    }
                    else if ($inString && $char === $stringCharacter) {
                        $inString = false;
                        $stringCharacter = false;
                    }
                }

                if ($char === '}' && $next === '}') {
                    $inBrackets = false;
                }
            }

            if ($inBrackets && !$inString) {
                if ($char === '$') {
                    $insertCharacter = '';
                }

                if ($char === '+') {
                    $insertCharacter = '~';
                }

                if ($char === '-' && $next === '>') {
                    $insertCharacter = '.';
                }

                if ($char === '>' && $previous === '-') {
                    $insertCharacter = '';
                }

                if ($char === '|' && $next === '|') {
                    $insertCharacter = 'or';
                }

                if ($char === '|' && $previous === '|') {
                    $insertCharacter = '';
                }

                if ($char === '!' && $next !== '=') {
                    $insertCharacter = 'not ';
                }

                if ($char === '&' && $next === '&') {
                    $insertCharacter = 'and';
                }

                if ($char === '&' && $previous === '&') {
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

        $inString = false;
        $disableString = false;
        $stringCharacter = '';

        $inUnescapedTag = false;
        $inCommentTag = false;

        $length = strlen($value);

        while ($i < $length) {
            $char = $value[$i];
            $insertCharacter = $char;

            $previous = ($i > 0) ? $value[$i - 1] : null;
            $next = ($i < ($length - 1)) ? $value[$i + 1] : null;
            $nextAfter = ($i < ($length - 2)) ? $value[$i + 2] : null;
            $nextAfterThat = ($i < ($length - 3)) ? $value[$i + 3] : null;

            if (!$inString && !$inBrackets) {
                if (!$inUnescapedTag && $char === '{' && $next === '!' && $nextAfter === '!') {
                    $i += 2;
                    $insertCharacter = '{{ (';
                    $inUnescapedTag = true;
                }
                else if ($inUnescapedTag && $char === '!' && $next === '!' && $nextAfter === '}') {
                    $i += 2;
                    $insertCharacter = ') | raw }}';
                    $inUnescapedTag = false;
                }

                if (!$inCommentTag && $char === '{' && $next === '{' && $nextAfter === '-' && $nextAfterThat === '-') {
                    $inCommentTag = true;
                    $i += 3;
                    $insertCharacter = '{#';
                }
                else if ($inCommentTag && $char === '-' && $next === '-' && $nextAfter === '}' && $nextAfterThat === '}') {
                    $inCommentTag = false;
                    $i += 3;
                    $insertCharacter = '#}';
                }
            }

            if (!$inBrackets) {
                if ($char === '{' && $previous === '{') {
                    $inBrackets = true;
                }
            }

            if ($inBrackets) {
                if ($inString) {
                    if ($char === '\\') {
                        $disableString = !$disableString;
                    }
                    else if ($char !== '"' && $char !== "'") {
                        $disableString = false;
                    }
                }

                if (!$disableString) {
                    if (!$inString && ($char === '"' || $char === "'")) {
                        $inString = true;
                        $stringCharacter = $char;
                    }
                    else if ($inString && $char === $stringCharacter) {
                        $inString = false;
                        $stringCharacter = false;
                    }
                }

                if ($char === '}' && $next === '}') {
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
