<?php

namespace Horizon\View\Extensions;

use Exception;
use Horizon\Foundation\Application;
use Horizon\View\Twig\TwigTranspiler;
use Horizon\View\ViewException;
use Horizon\View\ViewExtension;
use Twig_SimpleFunction;

/**
 * An extension which implements transpilers to support Blade template syntax.
 */
class BladeExtension extends ViewExtension {

	protected $endings = array();

	/**
	 * Gets an array of Twig functions which can be called from within template files. By default this method scans
	 * the local class for all methods starting with the word "Twig" and calls them to get an extension instance.
	 *
	 * @return Twig_SimpleFunction[]
	 */
	public function getFunctions() {
		return array(
			new Twig_SimpleFunction('camel_case', 'camel_case'),
			new Twig_SimpleFunction('kebab_case', 'kebab_case'),
			new Twig_SimpleFunction('snake_case', 'snake_case'),
			new Twig_SimpleFunction('title_case', 'title_case'),
			new Twig_SimpleFunction('studly_case', 'studly_case'),
			new Twig_SimpleFunction('starts_with', 'starts_with'),
			new Twig_SimpleFunction('ends_with', 'ends_with'),
			new Twig_SimpleFunction('str_before', 'str_before'),
			new Twig_SimpleFunction('str_after', 'str_after'),
			new Twig_SimpleFunction('str_contains', 'str_contains'),
			new Twig_SimpleFunction('str_finish', 'str_finish'),
			new Twig_SimpleFunction('str_is', 'str_is'),
			new Twig_SimpleFunction('str_limit', 'str_limit'),
			new Twig_SimpleFunction('str_plural', 'str_plural'),
			new Twig_SimpleFunction('str_random', 'str_random'),
			new Twig_SimpleFunction('str_replace_first', 'str_replace_first'),
			new Twig_SimpleFunction('str_replace_last', 'str_replace_last'),
			new Twig_SimpleFunction('str_singular', 'str_singular'),
			new Twig_SimpleFunction('str_slug', 'str_slug'),
			new Twig_SimpleFunction('str_start', 'str_start'),
			new Twig_SimpleFunction('str_substring', 'str_substring'),
			new Twig_SimpleFunction('str_length', 'str_length'),
			new Twig_SimpleFunction('str_find', 'str_find'),
			new Twig_SimpleFunction('str_ucfirst', 'str_ucfirst'),
			new Twig_SimpleFunction('str_upper', 'str_upper'),
			new Twig_SimpleFunction('str_lower', 'str_lower'),
			new Twig_SimpleFunction('strtoupper', 'str_upper'),
			new Twig_SimpleFunction('strtolower', 'str_lower'),

			new Twig_SimpleFunction('array_get', 'array_get'),
			new Twig_SimpleFunction('array_has', 'array_has'),
			new Twig_SimpleFunction('array_first', 'array_first'),
			new Twig_SimpleFunction('array_last', 'array_last'),
			new Twig_SimpleFunction('array_random', 'array_random'),
			new Twig_SimpleFunction('array_keys', 'array_keys'),
			new Twig_SimpleFunction('array_values', 'array_values'),
			new Twig_SimpleFunction('in_array', 'in_array'),
			new Twig_SimpleFunction('range', 'range'),
			new Twig_SimpleFunction('head', 'head'),
			new Twig_SimpleFunction('last', 'last'),
			new Twig_SimpleFunction('count', 'count'),

			new Twig_SimpleFunction('abort', 'abort'),
			new Twig_SimpleFunction('bcrypt', 'bcrypt'),
			new Twig_SimpleFunction('blank', 'blank'),
			new Twig_SimpleFunction('config', 'config'),
			new Twig_SimpleFunction('csrf_token', 'csrf_token'),
			new Twig_SimpleFunction('session', 'session'),
			new Twig_SimpleFunction('config', 'config'),
			new Twig_SimpleFunction('env', 'env'),
			new Twig_SimpleFunction('session', 'session'),
			new Twig_SimpleFunction('request', 'request'),

			new Twig_SimpleFunction('rand', 'rand'),
			new Twig_SimpleFunction('md5', 'md5'),
			new Twig_SimpleFunction('sha1', 'sha1'),
			new Twig_SimpleFunction('hash', 'hash'),
			new Twig_SimpleFunction('trim', 'trim'),
			new Twig_SimpleFunction('ltrim', 'ltrim'),
			new Twig_SimpleFunction('rtrim', 'rtrim'),
			new Twig_SimpleFunction('explode', 'explode'),
			new Twig_SimpleFunction('implode', 'implode'),
			new Twig_SimpleFunction('strlen', 'strlen'),
			new Twig_SimpleFunction('substr', 'substr'),
			new Twig_SimpleFunction('ucfirst', 'ucfirst'),
			new Twig_SimpleFunction('lcfirst', 'lcfirst'),
			new Twig_SimpleFunction('ucwords', 'ucwords'),
			new Twig_SimpleFunction('sprintf', 'sprintf'),
			new Twig_SimpleFunction('str_replace', 'str_replace'),
			new Twig_SimpleFunction('str_repeat', 'str_repeat'),
			new Twig_SimpleFunction('str_word_count', 'str_word_count'),
			new Twig_SimpleFunction('strpos', 'strpos'),
			new Twig_SimpleFunction('stripos', 'stripos'),
			new Twig_SimpleFunction('wordwrap', 'wordwrap'),

			new Twig_SimpleFunction('date', 'date'),
			new Twig_SimpleFunction('date_diff', 'date_diff'),
			new Twig_SimpleFunction('date_format', 'date_format'),
			new Twig_SimpleFunction('time', 'time'),
			new Twig_SimpleFunction('microtime', 'microtime'),

			new Twig_SimpleFunction('json_encode', 'json_encode'),
			new Twig_SimpleFunction('json_decode', 'json_decode'),
			new Twig_SimpleFunction('base64_encode', 'base64_encode'),
			new Twig_SimpleFunction('base64_decode', 'base64_decode'),
		);
	}

	/**
	 * Gets an array of Horizon tags (in @tag format) to transpile into Twig tags (in {{ tag() }} format).
	 *
	 * @return array
	 */
	public function getTranspilers() {
		return array(
			'include' => function($template) { return "{% include $template %}"; },
			'extend' => function($template) { return "{% extends $template %}"; },
			'extends' => function($template) { return "{% extends $template %}"; },
			'section' => function($template) {
				$name = trim($template, chr(34) . chr(39));
				$this->endings[] = 'endblock';

				return "{% block $name %}";
			},

			'set' => function($args) {
				$args = trim($args);
				$variableName = '';
				$variableValue = '';

				// First, find the variable name
				$quoteChar = null;
				$quoted = '';
				$offset = 0;
				for ($i = 0; $i < strlen($args); $i++) {
					$char = $args[$i];

					if (is_null($quoteChar)) {
						if ($char === chr(39)) $quoteChar = $char; // '
						elseif ($char === chr(34)) $quoteChar = $char; // "
						elseif ($char === chr(36)) $quoteChar = $char; // $
						else throw new Exception('Missing variable name in @set(name, value)');
					}
					else {
						if ($char === $quoteChar) {
							$variableName = $quoted;
							$offset = $i + 1;
							break;
						}
						elseif ($char === chr(44) && $quoteChar === chr(36)) {
							$variableName = $quoted;
							$offset = $i;
							break;
						}
						else {
							$quoted .= $char;
						}
					}
				}

				// Next, find the comma separator
				$comma = false;
				for ($i = $offset; $i < strlen($args); $i++) {
					$char = $args[$i];

					if ($char === chr(44)) {
						$offset = $i + 1;
						$comma = true;
						break;
					}
					elseif ($char !== chr(32) && $char !== chr(9)) {
						throw new Exception('Unknown syntax in @set()');
					}
				}

				// If there wasn't a comma, return a capture block
				if (!$comma) {
					$this->endings[] = 'endset';
					return "{% set {$variableName} %}";
				}

				// Next, extract the value
				$variableValue = trim(substr($args, $offset));

				return "{% set {$variableName} = {$variableValue} %}";
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

			'embed' => function($args) {
				if (!preg_match('/^[\'"]([^<>:;,?"*]+)[\'"]$/', $args, $matches)) {
					throw new ViewException(sprintf('Invalid argument at @embed(%s)', $args));
				}

				$fileName = $matches[1];
				$path = Application::paths()->embeds($fileName);

				if (!file_exists($path)) {
					throw new ViewException(sprintf('Embed %s was not found', $path));
				}

				return file_get_contents($path);
			}
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
	public function transpileFor($args) {
		if (preg_match('/^\$([\w.]+)\s+=\s+([^;]+);\s+\$([\w.]+)\s+(<|<=)\s+([^;]+);\s+\$([\w.]+)\+\+$/', $args, $matches)) {
			$variableName = $matches[1];
			$startValue = $matches[2];
			$endValue = $matches[5];
			$comparator = $matches[4];

			if (starts_with($startValue, '$')) $startValue = substr($startValue, 1);
			if (starts_with($endValue, '$')) $endValue = substr($endValue, 1);

			$startValue = str_replace('->', '.', $startValue);
			$endValue = str_replace('->', '.', $endValue);

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
	public function transpileForEach($args) {
		if (preg_match('/^\$([\w.>-]+)\s*(\([^)]*\))?\s+as\s+\$([\w]+)$/', $args, $matches)) {
			$this->endings[] = 'endfor';

			$array = str_replace('->', '.', $matches[1]) . (isset($matches[2]) ? $matches[2] : '');
			return "{% for {$matches[3]} in {$array} %}";
		}

		if (preg_match('/^\$([\w.>-]+)\s*(\([^)]*\))?\s+as\s+\$([\w]+)\s*=>\s*\$([\w]+)$/', $args, $matches)) {
			$this->endings[] = 'endfor';

			$array = str_replace('->', '.', $matches[1]) . (isset($matches[2]) ? $matches[2] : '');
			return "{% for {$matches[3]}, {$matches[4]} in {$array} %}";
		}

		throw new \Exception(sprintf('Invalid format in @foreach: %s', $args));
	}

	/**
	 * Transpiles @else tags.
	 *
	 * @param string $args
	 * @return string
	 */
	public function transpileElse($args) {
		return "{% else %}";
	}

	/**
	 * Transpiles @if tags.
	 *
	 * @param string $args
	 * @return string
	 */
	public function transpileIf($args) {
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
	public function transpileElseIf($args) {
		$meat = $this->transpileConditionLogic($args);

		return "{% elseif {$meat} %}";
	}

	/**
	 * Transpiles @verbatim tags.
	 *
	 * @return string
	 */
	public function transpileVerbatim() {
		$this->endings[] = 'endverbatim';

		return "{% verbatim %}";
	}

	/**
	 * Transpiles @unescaped tags.
	 *
	 * @return string
	 */
	public function transpileUnescaped() {
		$this->endings[] = 'endautoescape';

		return "{% autoescape false %}";
	}

	protected function transpileConditionLogic($str) {
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

	protected function getFilter($name) {
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
	public function transpileEnd($args) {
		if (empty($this->endings)) {
			throw new \Exception('Invalid end tag with no matching statement');
		}

		$ending = array_pop($this->endings);

		return "{% {$ending} %}";
	}

}
