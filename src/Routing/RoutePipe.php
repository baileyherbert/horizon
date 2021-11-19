<?php

namespace Horizon\Routing;

use Exception;
use Horizon\Http\Pipe;

class RoutePipe {

	private $uri;
	private $className;
	private $instance;

	public function __construct($uri, $className) {
		$this->uri = $uri;
		$this->className = $className;
		$this->instance = new $className;
	}

	/**
	 * Sets the value of a pipe's property.
	 *
	 * @param string $propName
	 * @param mixed $value
	 * @return $this
	 */
	public function set($propName, $value) {
		if (property_exists($this->instance, $propName)) {
			$this->instance->{$propName} = $value;
		}
		else {
			throw new Exception(sprintf('Pipe: %s::%s does not exist', $this->className, $propName));
		}

		return $this;
	}

	/**
	 * Pushes a value onto a pipe's array property.
	 *
	 * @param string $propName
	 * @param mixed $value
	 * @return $this
	 */
	public function push($propName, $value) {
		if (property_exists($this->instance, $propName)) {
			if (is_null($this->instance->{$propName})) {
				$this->instance->{$propName} = [];
			}

			if (is_array($this->instance->{$propName})) {
				$this->instance->{$propName}[] = $value;
			}
			else {
				throw new Exception(sprintf('Pipe: %s::%s is not an array', $this->className, $propName));
			}
		}
		else {
			throw new Exception(sprintf('Pipe: %s::%s does not exist', $this->className, $propName));
		}

		return $this;
	}

	/**
	 * Returns the pipe instance.
	 *
	 * @return Pipe
	 */
	public function getInstance() {
		return $this->instance;
	}

	/**
	 * Returns the base URI for matching.
	 *
	 * @return string
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * Returns the full classname of the pipe instance to use.
	 *
	 * @return string
	 */
	public function getClassName() {
		return $this->className;
	}

}
