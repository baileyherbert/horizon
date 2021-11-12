<?php

namespace Horizon\Database;

class ModelField {

	/**
	 * The value for the field that was set on the model.
	 *
	 * @var mixed
	 */
	public $input;

	/**
	 * The value for the field that will be returned by the model's getter.
	 *
	 * @var mixed
	 */
	public $localFormat;

	/**
	 * The value for the field that will be written into the database.
	 *
	 * @var mixed
	 */
	public $remoteFormat;

	public function __construct($input, $localFormat, $remoteFormat) {
		$this->input = $input;
		$this->localFormat = $localFormat;
		$this->remoteFormat = $remoteFormat;
	}

}
