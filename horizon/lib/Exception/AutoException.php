<?php

namespace Horizon\Exception;

class AutoException extends \Exception {

	public function __construct(HorizonError $error) {
		$this->message = $error->getMessage();
		$this->file = $error->getFile();
		$this->line = $error->getLine();
	}

}
