<?php

namespace Horizon\Updates;

class ScriptLine
{

    /**
     * @var Script
     */
    protected $script;

    /**
     * @var string
     */
    protected $line;

    /**
     * @var int
     */
    protected $lineNumber;

    /**
     * @var array
     */
    protected $tokens;

    /**
     * @var array
     */
    protected $arguments = array();

    /**
     * @var string
     */
    protected $command;

    /**
     * var @bool
     */
    protected $silenceErrors = false;

    /**
     * Constructs a new ScriptLine instance.
     *
     * @param string $line
     */
    public function __construct(Script $script, $lineNumber, $line)
    {
        $this->script = $script;

        $this->lineNumber = $lineNumber;
        $this->line = $this->convertString(trim($line));
        $this->removeComments();
        $this->line = $this->revertString($this->line);

        $this->parseParameters();
    }

    /**
     * Strips tricky characters from a string to help with processing and extraction.
     *
     * @param string $str
     * @return string
     */
    protected function convertString($str)
    {
        // Remove double backslashes temporarily
        $str = str_replace('\\\\', "%{\s:special_double_backslash}$", $str);

        return $str;
    }

    /**
     * Reverts changes made by the convertString() method.
     *
     * @param string $str
     * @return string
     */
    protected function revertString($str)
    {
        // Restore double backslashes
        $str = str_replace("%{\s:special_double_backslash}$", '\\\\', $str);

        return $str;
    }

    /**
     * Removes comments from the line without interfering with string parameters.
     */
    protected function removeComments()
    {
        $commentDelimiter = '#';
        $tokens = array();

        $quoteActive = false;
        $quoteStartIndex = null;
        $quoteCharacter = null;
        $quoteString = null;

        for ($index = 0; $index < strlen($this->line); $index++) {
            $char = $this->line[$index];
            $previousChar = (($index > 0) ? $this->line[$index - 1] : null);

            if (!$quoteActive) {
                if ($char == chr(34) || $char == chr(39)) {
                    $quoteActive = true;
                    $quoteCharacter = $char;
                }
                else if ($char == $commentDelimiter) {
                    $this->line = substr($this->line, 0, $index);
                    break;
                }
            }
            else {
                if ($char == $quoteCharacter) {
                    if ($previousChar !== chr(92)) {
                        $quoteActive = false;
                    }
                }
            }
        }

        if ($quoteActive) {
            throw new ScriptException(sprintf('Unexpected end of line, expecting closing of string (%s) on line %d', $quoteCharacter, $this->lineNumber));
        }
    }

    /**
     * Extracts parameters (command, strings, numbers, booleans, constants) from the string and stores them.
     */
    protected function parseParameters()
    {
        $length = strlen($this->line);

        if ($length == 0) {
            return;
        }

        $inActiveString = false;
        $inActiveNumeric = false;
        $inActiveConstant = false;

        $stringStartAt = null;
        $numericStartAt = null;
        $constantStartAt = null;

        $stringQuoteCharacter = null;
        $stringEscaped = false;
        $numericHasDecimal = false;

        for ($i = 0; $i <= $length; $i++) {
            $char = ($i < $length) ? $this->line[$i] : null;
            $previousChar = (($i > 0) ? $this->line[$i - 1] : null);

            // Extract the command (first parameter)
            if (is_null($this->command)) {
                if ($i == 0 && $char == chr(64)) {
                    $this->silenceErrors = true;
                    continue;
                }

                $this->parseCommand($i, $char, $length);
                continue;
            }

            // If we're not in an active segment and this character is a space, skip
            if ((!$inActiveString && !$inActiveNumeric && !$inActiveConstant) && $char == chr(32)) {
                continue;
            }

            // Extract a string
            if ($inActiveString) {
                if ($char == $stringQuoteCharacter && !$stringEscaped) {
                    $inActiveString = false;
                    $stringValue = substr($this->line, $stringStartAt + 1, ($i - $stringStartAt - 1));

                    if (preg_match("/^\\$[a-zA-Z]\w*$/", $stringValue)) {
                        $variableName = strtolower(ltrim($stringValue, '$'));
                        $variableValue = $this->getVariableValue($variableName);

                        if (!is_null($variableValue)) {
                            $stringValue = $variableValue;
                        }
                    }

                    $this->arguments[] = array('type' => 'string', 'value' => $stringValue);
                }

                else if ($char == chr(92)) {
                    $stringEscaped = !$stringEscaped;
                }

                else if ($char === null) {
                    throw new ScriptException(sprintf('Unexpected end of line, expecting closing of string (%s) on line %d', $stringQuoteCharacter, $this->lineNumber));
                }

                continue;
            }

            // Extract a numeric
            if ($inActiveNumeric) {
                if ($char == chr(32) || $char === null) {
                    $inActiveNumeric = false;
                    $stringForm = substr($this->line, $numericStartAt, ($i - $numericStartAt));
                    $numericForm = 0;

                    if (strlen($stringForm) == 0 || $stringForm == '-') {
                        throw new ScriptException(sprintf('Malformed numeric on line %d offset %d', $this->line, $i));
                    }

                    if (strpos($stringForm, '.') === false) $numericForm = (int)$stringForm;
                    else $numericForm = (double)$stringForm;

                    if (preg_match("/^0[1-7]{3}$/", $stringForm)) {
                        $this->arguments[] = array('type' => 'octal', 'value' => $stringForm);
                        continue;
                    }

                    $this->arguments[] = array('type' => 'numeric', 'value' => $numericForm);
                    continue;
                }

                if ($char == chr(46)) {
                    if (!$numericHasDecimal) {
                        $numericHasDecimal = true;
                    }
                    else {
                        throw new ScriptException(sprintf('Unexpected character "%s" in numeric on line %d offset %d', $char, $this->line, $i));
                    }
                }

                if (!preg_match("/[\d.]/", $char)) {
                    throw new ScriptException(sprintf('Unexpected character "%s" in numeric on line %d offset %d', $char, $this->line, $i));
                }

                continue;
            }

            // Extract a constant
            if ($inActiveConstant) {
                if ($char == chr(32) || $char === null) {
                    $inActiveConstant = false;
                    $stringForm = strtolower(substr($this->line, $constantStartAt, ($i - $constantStartAt)));

                    // It was a boolean
                    if ($stringForm == 'true' || $stringForm == 'false') {
                        $this->arguments[] = array('type' => 'boolean', 'value' => ($stringForm == 'true'));
                        continue;
                    }

                    // Verify that this is an allowed constant
                    $this->validateConstant($stringForm);

                    // Add it
                    $this->arguments[] = array('type' => 'constant', 'value' => $stringForm);
                    continue;
                }

                if (!preg_match("/[\w.-]/", $char)) {
                    throw new ScriptException(sprintf('Unexpected character "%s" in constant on line %d offset %d', $char, $this->line, $i));
                }

                continue;
            }

            if ($char === null) {
                continue;
            }

            // Start new numeric parameter
            if (preg_match("/[0-9.-]/", $char)) {
                $inActiveNumeric = true;
                $numericHasDecimal = false;
                $numericStartAt = $i;
            }

            // Start new string parameter
            else if ($char == chr(34) || $char == chr(39)) {
                $inActiveString = true;
                $stringQuoteCharacter = $char;
                $stringStartAt = $i;
                $stringEscaped = false;
            }

            // Start new constant parameter
            else if (preg_match("/\w/", $char)) {
                $inActiveConstant = true;
                $constantStartAt = $i;
            }

            // Unknown character
            else {
                throw new ScriptException(sprintf('Unexpected character "%s" at offset %d on line %d', $char, $i, $this->line));
            }
        }
    }

    /**
     * Helper method to extract the command from the line. Do not call this directly.
     *
     * @internal
     * @param int $i
     * @param int $maxLength
     * @param string $char
     */
    protected function parseCommand($i, $char, $maxLength)
    {
        $start = ($this->silenceErrors) ? 1 : 0;

        if ($i == $maxLength) {
            $this->command = strtolower($this->line, $start);
            return;
        }

        if ($char == chr(32)) {
            $this->command = strtolower(substr($this->line, $start, $i));
        }
        else {
            if (!preg_match("/\w/", $char)) {
                throw new ScriptException(sprintf('Unexpected character "%s" in command on line %d offset %d', $char, $this->lineNumber, $i));
            }
            else if ($i == 0 && is_numeric($char)) {
                throw new ScriptException(sprintf('Command must not start with a number on line %d offset %d', $this->lineNumber, $i));
            }
        }
    }

    /**
     * Throws an exception if the specified constant is invalid.
     */
    protected function validateConstant($constant)
    {
        $allowed = array('payload', 'ok', 'backup');

        if (!in_array($constant, $allowed)) {
            throw new ScriptException(sprintf('Unknown constant "%s" on line %d', $constant, $this->lineNumber));
        }
    }

    /**
     * Gets the processed line string.
     *
     * @return string
     */
    public function getText()
    {
        return $this->line;
    }

    /**
     * Gets the processed command name.
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Gets the processed arguments.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Gets an array of ordered argument values.
     *
     * @return array
     */
    public function getArgumentValues()
    {
        $args = $this->getArguments();
        $values = array();

        foreach ($args as $arg) {
            $values[] = $arg['value'];
        }

        return $values;
    }

    /**
     * Gets whether or not the line is empty or blank.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->line);
    }

    /**
     * Gets whether or not the line is silenced (errors are caught and ignored).
     *
     * @return bool
     */
    public function isSilent()
    {
        return $this->silenceErrors;
    }

    protected function getVariableValue($name)
    {
        switch ($name) {
            case 'version': return $this->script->getVersion()->getName();
        }

        return null;
    }

}