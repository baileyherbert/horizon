<?php

namespace Horizon\Database\Migration;

class SchemaRecorderBucket {

	private $statements = array();

	/**
	 * Registers a statement in the bucket.
	 *
	 * @param SchemaStatement $statement
	 * @return void
	 */
	public function push(SchemaStatement $statement) {
		$this->statements[] = $statement;
	}

	/**
	 * Returns an array of all statements that were captured during this bucket's lifetime.
	 *
	 * @return SchemaStatement[]
	 */
	public function all() {
		return $this->statements;
	}

	/**
	 * Returns an array of all statements that raised exceptions during this bucket's lifetime.
	 *
	 * @return SchemaStatement[]
	 */
	public function failed() {
		return array_filter($this->statements, function($stmt) {
			return $stmt->gasException() !== null;
		});
	}

	/**
	 * Starts the bucket and begins recording statements.
	 *
	 * @return void
	 */
	public function start() {
		SchemaRecorder::startBucket($this);
	}

	/**
	 * Stops the bucket from recording any further statements.
	 *
	 * @return void
	 */
	public function stop() {
		SchemaRecorder::stopBucket($this);
	}

}
