<?php

namespace Horizon\Http\Traits;

use Symfony\Component\HttpFoundation\ParameterBag;

trait HasJsonInput
{

	private $json;
	private $jsonError;

	/**
	 * Parses the request content as JSON and returns the specified key. If the key does not exist, it returns the
	 * default value provided (or null). If the key parmater is ommited, the entire decoded array is provided.
	 *
	 * @param mixed $default Return value if key is not found.
	 * @return array|mixed|null
	 */
	public function json($key = null, $default = null)
	{
		if (!isset($this->json)) {
			// Decode the content with errors suppressed
			$decoded = @json_decode($this->getContent(), true);

			// Create a parameter bag with the content
			$this->json = new ParameterBag((array) $decoded);

			// Store errors
			$this->jsonStoreError();
		}

		if (is_null($key)) {
			// Return the entire array
			return $this->json->all();
		}

		return $this->json->get($key, $default, true);
	}

	/**
	 * Checks if the JSON decode operation encountered an error and stores it if so.
	 */
	private function jsonStoreError()
	{
		// Get the last error (symfony/polyfill-php55)
		$error = json_last_error_msg();

		// Use null for no error
		if ($error == 'No error') {
			$error = null;
		}

		// Store the error
		$this->jsonError = $error;
	}

	/**
	 * If the parse failed when calling json(), the error will be returned by this method as a string. If no error
	 * occurred, null will be returned.
	 *
	 * @return null|string
	 */
	public function jsonError() {
		return $this->jsonError;
	}

}
