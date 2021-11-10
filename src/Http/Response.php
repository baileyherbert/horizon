<?php

namespace Horizon\Http;

use Exception;
use Horizon\Foundation\Application;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Horizon\View\Template;
use Horizon\Support\Str;

class Response extends SymfonyResponse {

	/**
	 * @var bool If the response has halted and page load should cease.
	 */
	protected $halted = false;

	/**
	 * @var array Variables to be sent to the view during rendering.
	 */
	protected $context = array();

	/**
	 * @var resource|null The file handle to stream.
	 */
	protected $sendFileHandle;

	/**
	 * @var string|null The optional name of the file. If set, this will force the file to download.
	 */
	protected $sendFileName;

	/**
	 * @var int|null The number of bytes in the output file.
	 */
	protected $sendFileSize;

	/**
	 * Sets the value of a header in the response.
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setHeader($key, $value) {
		$this->headers->set($key, $value);
	}

	/**
	 * Gets the value of a header in the response.
	 */
	public function getHeader($key, $default = null) {
		$this->headers->get($key, $default);
	}

	/**
	 * Writes to the response content.
	 *
	 * @param mixed $data
	 */
	protected function writeObject($data = '') {
		if (is_bool($data)) {
			$data = $data ? 'true' : 'false';
		}

		if (is_array($data)) {
			$data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}

		if (is_object($data)) {
			$data = (string)$data;
		}

		if (is_null($data)) {
			$data = 'NULL';
		}

		$this->content .= $data;
	}

	/**
	 * Writes to the response content.
	 *
	 * @param mixed $data
	 */
	public function write() {
		$args = func_get_args();

		if (empty($args)) {
			$args = array('');
		}

		foreach ($args as $i => $arg) {
			$this->writeObject($arg);

			if ($i < (count($args) - 1)) {
				$this->writeObject(' ');
			}
		}
	}

	/**
	 * Writes a new line to the response content.
	 */
	public function writeLine() {
		$args = func_get_args();

		if (empty($args)) {
			$args = array('');
		}

		foreach ($args as $i => $arg) {
			$this->write($arg);

			if ($i < (count($args) - 1)) {
				$this->write(' ');
			}
		}

		$this->write("\n");
	}

	/**
	 * Writes JSON to the output, and automatically sets the `Content-Type` header to `application/json`.
	 */
	public function json() {
		call_user_func_array(array($this, 'write'), func_get_args());
		$this->setHeader('Content-Type', 'application/json');
	}

	/**
	 * Turns the response into a redirection.
	 *
	 * @param string $to
	 * @param int $code
	 */
	public function redirect($to = null, $code = 302) {
		if ($to == null) {
			$to = Application::kernel()->http()->request()->getRequestUri();
		}

		if (Str::startsWith($to, '/') && !Str::startsWith($to, '//')) {
			$to = '/' . ltrim(trim($_SERVER['SUBDIRECTORY'], '/') . '/' . ltrim($to, '/'), '/');
		}

		$this->setStatusCode($code);
		$this->setHeader('Location', $to);
	}

	/**
	 * Stops page execution gracefully, meaning any currently-running code will continue, but further controllers or
	 * middleware will not be executed.
	 */
	public function halt() {
		$this->halted = true;
	}

	/**
	 * Gets whether the page execution is being or has been halted.
	 *
	 * @return bool
	 */
	public function isHalted() {
		return $this->halted;
	}

	/**
	 * Returns the value of the specified context variable or `null` if not set.
	 *
	 * @param string $key
	 * @return mixed|null
	 */
	public function getContext($key) {
		if (isset($this->context[$key])) {
			return $this->context[$key];
		}
	}

	/**
	 * Sets a context variable for the response. Template files can access these context variables using their names.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function setContext($key, $value) {
		$this->context[$key] = $value;
	}

	/**
	 * Removes a variable included in the response if it exists.
	 *
	 * @param string $key
	 * @return void
	 */
	public function removeContext($key) {
		if (isset($this->context[$key])) {
			unset($this->context[$key]);
		}
	}

	/**
	 * Alias for `setContext()`
	 *
	 * @deprecated Please use `setContext()` instead.
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function with($key, $value) {
		return $this->setContext($key, $value);
	}

	/**
	 * Alias for `removeContext()`
	 *
	 * @deprecated Please use `removeContext()` instead.
	 * @param string $key
	 * @return void
	 */
	public function without($key) {
		return $this->removeContext($key);
	}

	/**
	 * Renders a view to the response.
	 *
	 * @param string $templateName
	 * @param array $context
	 */
	public function view($templateFile, array $context = array()) {
		$variables = $this->buildContextVariables($context);
		$view = new Template($templateFile, $variables);
		$content = $view->render();

		if (!$this->isHalted()) {
			$this->content .= $content;
		}
	}

	/**
	 * Sends a file as the response using a stream. If the file does not exist or lacks read permissions, an exception
	 * will be thrown. Note that this halts the response immediately, and any other output sent with `write()` or
	 * `writeLine()` will be ignored.
	 *
	 * The `$name` parameter is optional and accepts the name of the file to send. When set, the file will forcefully
	 * be downloaded as a binary file, rather than displayed in the browser.
	 *
	 * @param string $path
	 * @param string|null $name
	 * @return void
	 */
	public function sendFile($path, $name = null) {
		if (!@is_readable($path)) {
			throw new Exception(sprintf('Attempt to send file that could not be found or read: %s', $path));
		}

		$handle = @fopen($path, 'r');
		if ($handle === false) {
			throw new Exception(sprintf('Unable to open file for streaming: %s', $path));
		}

		$this->sendFileHandle = $handle;
		$this->sendFileName = $name;
		$this->sendFileSize = filesize($path);
		$this->halted = true;
	}

	/**
	 * Sends HTTP headers and content.
	 *
	 * @return $this
	 */
	public function send() {
		if (!is_null($this->sendFileHandle)) {
			$handle = $this->sendFileHandle;
			$name = $this->sendFileName;

			if (!is_null($name)) {
				$this->setHeader('content-type', 'application/octet-stream');
				$this->setHeader('content-disposition', 'attachment; filename="' . $name . '"');
				$this->setHeader('content-length', $this->sendFileSize);
			}

			$this->sendHeaders();

			@flock($handle, LOCK_SH);

			while (!feof($handle)) {
				$buffer = fread($handle, 1048576);
				echo $buffer;
				@ob_flush();
				@flush();
			}

			@flock($handle, LOCK_UN);
			fclose($handle);
		}
		else {
			$this->sendHeaders();
			$this->sendContent();
		}

		if (\function_exists('fastcgi_finish_request')) {
			fastcgi_finish_request();
		} elseif (!\in_array(\PHP_SAPI, array('cli', 'phpdbg'), true)) {
			static::closeOutputBuffers(0, true);
		}

		return $this;
	}

	/**
	 * Fills the provided array with variables stored in the response. If any keys already exist, the values are
	 * unchanged.
	 *
	 * @param array $context
	 * @return array
	 */
	protected function buildContextVariables(array $context) {
		foreach ($this->context as $key => $value) {
			if (!array_key_exists($key, $context)) {
				if (is_callable($value)) {
					$value = call_user_func($value);
				}

				$context[$key] = $value;
			}
		}

		return $context;
	}

}
