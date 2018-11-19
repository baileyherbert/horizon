<?php

namespace Horizon\View\Extensions;

use Horizon\View\ViewExtension;

/**
 * An extension which implements transpilers to support Blade template syntax.
 */
class BladeExtension extends ViewExtension
{

    protected $endings = array();

    public function getTranspilers()
    {
        return array(
            'include' => function($template) { return "{% include $template %}"; },
            'extend' => function($template) { return "{% extends $template %}"; },
            'section' => function($template) {
                $name = trim($template, chr(34) . chr(39));
                $this->endings[] = 'endblock';

                return "{% block $name %}";
            },

            'for' => array($this, 'transpileFor'),
            'foreach' => array($this, 'transpileForEach'),
            'forelse' => array($this, 'transpileForEach'),
            'empty' => array($this, 'transpileElse'),
            'else' => array($this, 'transpileElse'),

            'if' => array($this, 'transpileIf'),
            'elseif' => array($this, 'transpileElseIf'),

            'verbatim' => array($this, 'transpileVerbatim'),
            'unescaped' => array($this, 'transpileUnescaped'),

            'end' => array($this, 'transpileEnd'),
            'endfor' => array($this, 'transpileEnd'),
            'endforeach' => array($this, 'transpileEnd'),
            'endforelse' => array($this, 'transpileEnd'),
            'endsection' => array($this, 'transpileEnd'),
            'endif' => array($this, 'transpileEnd'),
        );
    }

    /**
     * Transpiles @for tags, supporting the following format:
     *
     *    @for ($i = 0; $i < 10; $i++)
     *
     * @param string $args
     * @return string
     */
    public function transpileFor($args)
    {
        if (preg_match('/^\$([\w.]+)\s+=\s+(\d+);\s+\$([\w.]+)\s+(<|<=)\s+(\d+);\s+\$([\w.]+)\+\+$/', $args, $matches)) {
            $variableName = $matches[1];
            $startValue = $matches[2];
            $endValue = $matches[5];
            $comparator = $matches[4];

            if ($comparator == '<') $endValue--;
            else if ($comparator != '<=') {
                throw new \Exception(sprintf('Invalid comparator "%s" in @for loop: %s', $comparator, $args));
            }

            $this->endings[] = 'endfor';
            return "{% for {$variableName} in {$startValue}..{$endValue} %}";
        }

        throw new \Exception(sprintf('Invalid format in @for: %s', $args));
    }

    /**
     * Transpiles @foreach tags, supporting the following formats:
     *
     *    @foreach ($array as $index => $value)
     *    @foreach ($array as $value)
     *
     * @param string $args
     * @return string
     */
    public function transpileForEach($args)
    {
        if (preg_match('/^\$([\w.>-]+)\s+as\s+\$([\w]+)$/', $args, $matches)) {
            $this->endings[] = 'endfor';

            $array = str_replace('->', '.', $matches[1]);
            return "{% for {$matches[2]} in {$array} %}";
        }

        if (preg_match('/^\$([\w.>-]+)\s+as\s+\$([\w]+)\s*=>\s*\$([\w]+)$/', $args, $matches)) {
            $this->endings[] = 'endfor';

            $array = str_replace('->', '.', $matches[1]);
            return "{% for {$matches[2]}, {$matches[3]} in {$array} %}";
        }

        throw new \Exception(sprintf('Invalid format in @foreach: %s', $args));
    }

    /**
     * Transpiles @else tags.
     *
     * @param string $args
     * @return string
     */
    public function transpileElse($args)
    {
        return "{% else %}";
    }

    /**
     * Transpiles @if tags.
     *
     * @param string $args
     * @return string
     */
    public function transpileIf($args)
    {
        $meat = $this->transpileConditionLogic($args);
        $this->endings[] = 'endif';

        return "{% if {$meat} %}";
    }

    /**
     * Transpiles @elseif tags.
     *
     * @param string $args
     * @return string
     */
    public function transpileElseIf($args)
    {
        $meat = $this->transpileConditionLogic($args);
        $this->endings[] = 'endif';

        return "{% elseif {$meat} %}";
    }

    /**
     * Transpiles @verbatim tags.
     *
     * @return string
     */
    public function transpileVerbatim()
    {
        $this->endings[] = 'endverbatim';

        return "{% verbatim %}";
    }

    /**
     * Transpiles @unescaped tags.
     *
     * @return string
     */
    public function transpileUnescaped()
    {
        $this->endings[] = 'endautoescape';

        return "{% autoescape false %}";
    }

    protected function transpileConditionLogic($str)
    {
        $statement = '';
        $filters = array();

        $i = 0;

        $inString = false;
        $disableString = false;
        $stringCharacter = '';

        $isVariable = false;
        $variable = '';

        $isSymbol = false;
        $symbol = '';

        $isFilter = false;
        $filterName = '';
        $filterArgs = array();
        $filterVariable = '';
        $depth = 0;

        while ($i < strlen($str)) {
            $char = $str[$i];
            $previous = (isset($str[$i - 1]) ? $str[$i - 1] : null);
            $next = (isset($str[$i + 1]) ? $str[$i + 1] : null);

            $insert = $char;

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

            if (!$inString) {
                if (!$isVariable && !$isSymbol) {
                    if ($char === chr(38) && $next === chr(38)) {
                        $insert = 'and';
                    }
                    else if ($char === chr(38) && $previous === chr(38)) {
                        $insert = '';
                    }
                    else if ($char === chr(124) && $next === chr(124)) {
                        $insert = 'or';
                    }
                    else if ($char === chr(124) && $previous === chr(124)) {
                        $insert = '';
                    }
                    else if ($char === chr(33) && $next !== chr(61)) {
                        $insert = 'not ';
                    }
                }

                if ($char === chr(36)) {
                    $isVariable = true;
                    $insert = '';
                }
                else if ($isVariable) {
                    if (preg_match('/[\w\d.>-]/', $char)) {
                        if ($char === chr(45) && $next === chr(62)) {
                            $insert = '.';
                        }

                        if ($char === chr(62) && $previous === chr(45)) {
                            $insert = '';
                        }

                        $variable .= $insert;
                    }
                    else {
                        if (empty($variable)) {
                            throw new \Exception('Blade parse error: Unexpected character $');
                        }

                        $variable = null;
                        $isVariable = false;
                    }
                }

                if (!$isVariable && !$inString && !$isSymbol) {
                    if (preg_match('/\w/', $char)) {
                        if (!$isSymbol) {
                            $isSymbol = true;
                            $symbol = $char;
                        }
                    }
                }
                else if ($isSymbol) {
                    if (preg_match('/\w/', $char)) {
                        $symbol .= $char;
                    }
                    else {
                        $isSymbol = false;

                        if ($char === chr(40)) {
                            $realFilterName = $this->getFilter($symbol);

                            if (!is_null($realFilterName)) {
                                $isFilter = true;
                                $filterName = $realFilterName;
                                $filterArgs = array();
                                $filterVariable = '';
                                $depth = 0;

                                $insert = '';
                                $statement = substr($statement, 0, -strlen($symbol));
                            }
                        }
                    }
                }
            }

            if ($isFilter) {
                $isParenthesis = false;

                if ($char === chr(40)) {
                    $depth++;
                    $isParenthesis = true;
                }
                else if ($char === chr(41)) {
                    $depth--;
                    $isParenthesis = true;
                }

                if ($depth == 0) {
                    if ($filterVariable !== '') {
                        $filterArgs[] = trim($filterVariable);
                    }

                    $var = str_replace('->', '.', ltrim(array_shift($filterArgs), '$'));

                    $isFilter = false;
                    $filters[] = array(
                        'name' => $filterName,
                        'args' => $filterArgs
                    );

                    if ($filterName == 'empty') {
                        $not = false;

                        if (substr($statement, -4, -1) == "not") {
                            $statement = substr($statement, 0, -4);
                            $not = true;
                        }

                        $insert = "$var is " . ($not ? "not " : "") . "empty";
                    }
                    else {
                        $insert = $var . '|' . $filterName;

                        if (count($filterArgs) > 0) {
                            $insert .= '(' . implode(', ', $filterArgs) . ')';
                        }
                    }
                }
                else if (!$isParenthesis) {
                    $insert = '';

                    if ($char === chr(44) && !$inString) {
                        $filterArgs[] = trim($filterVariable);
                        $filterVariable = '';
                    }
                    else {
                        $filterVariable .= $char;
                    }
                }
            }

            if ($insert !== '') {
                $statement .= $insert;
            }

            $i++;
        }

        return $statement;
    }

    protected function getFilter($name)
    {
        static $filters = array(
            'length' => 'length',
            'count' => 'length',
            'abs' => 'abs',
            'batch' => 'batch',
            'ucfirst' => 'capitalize',
            'capitalize' => 'capitalize',
            'date' => 'date',
            'default' => 'default',
            'e' => 'escape',
            'escape' => 'escape',
            'first' => 'first',
            'array_shift' => 'first',
            'last' => 'last',
            'array_pop' => 'last',
            'format' => 'format',
            'join' => 'join',
            'implode' => 'join',
            'json_encode' => 'json_encode',
            'keys' => 'keys',
            'array_keys' => 'keys',
            'strtolower' => 'lower',
            'lower' => 'lower',
            'merge' => 'merge',
            'nl2br' => 'nl2br',
            'number_format' => 'number_format',
            'raw' => 'raw',
            'replace' => 'replace',
            'reverse' => 'reverse',
            'strrev' => 'reverse',
            'round' => 'round',
            'slice' => 'slice',
            'sort' => 'sort',
            'split' => 'split',
            'explode' => 'split',
            'striptags' => 'striptags',
            'title' => 'title',
            'trim' => 'trim',
            'upper' => 'upper',
            'strtoupper' => 'upper',
            'url_encode' => 'url_encode',
            'empty' => 'empty'
        );

        return (isset($filters[$name])) ? $filters[$name] : null;
    }

    /**
     * Transpiles @end tags.
     *
     * @param string $args
     * @return string
     */
    public function transpileEnd($args)
    {
        if (empty($this->endings)) {
            throw new \Exception('Invalid end tag with no matching statement');
        }

        $ending = array_pop($this->endings);

        return "{% {$ending} %}";
    }

}
