<?php

namespace Horizon\Database\Migration;

use Exception;
use Horizon\Database\DatabaseConnection;
use Horizon\Database\DatabaseFacade;

class SchemaStatement {

	/**
	 * @var string
	 */
	private $statement;

	/**
	 * @var Exception|null
	 */
	private $exception;

	/**
	 * @var string
	 */
	private $connectionName;

	/**
	 * Constructs a new `SchemaStatement` instance.
	 *
	 * @param string $connectionName
	 * @param string $statement
	 * @param Exception|null $exception
	 */
	public function __construct($connectionName, $statement, $exception = null) {
		$this->connectionName = $connectionName;
		$this->statement = $statement;
		$this->exception = $exception;
	}

	/**
	 * Returns the exception for this statement if it raised one, otherwise `null`.
	 *
	 * @return Exception|null
	 */
	public function getException() {
		return $this->exception;
	}

	/**
	 * Returns the query text.
	 *
	 * @return string
	 */
	public function getQuery() {
		return $this->statement;
	}

	/**
	 * Returns the name of the connection that emitted this statement.
	 *
	 * @return string
	 */
	public function getConnectionName() {
		return $this->connectionName;
	}

	/**
	 * Returns database connection instance that emitted this statement.
	 *
	 * @return DatabaseConnection
	 */
	public function getConnection() {
		return DatabaseFacade::connection($this->connectionName);
	}

}
