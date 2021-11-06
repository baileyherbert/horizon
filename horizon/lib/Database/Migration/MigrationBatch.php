<?php

namespace Horizon\Database\Migration;

use Horizon\Database\Migration;
use Horizon\Database\Migrator;

class MigrationBatch {

	/**
	 * @var Migrator
	 */
	private $migrator;

	/**
	 * @var int
	 */
	private $batchId;

	/**
	 * @var Migration[]
	 */
	private $migrations;

	/**
	 * Constructs a new `MigrationBatch` instance.
	 *
	 * @param Migrator $migrator
	 * @param int $batchId
	 * @param Migration[] $migrations
	 */
	public function __construct(Migrator $migrator, $batchId, $migrations) {
		$this->migrator = $migrator;
		$this->batchId = $batchId;
		$this->migrations = $migrations;
	}

	/**
	 * Returns the batch number.
	 *
	 * @return int
	 */
	public function getId() {
		return $this->batchId;
	}

	/**
	 * Returns an array of migrations in this batch. The array is sorted so that the first migration in the array is
	 * the first migration executed in the batch.
	 *
	 * @return Migration[]
	 */
	public function getMigrations() {
		return $this->migrations;
	}

	/**
	 * Returns a unix seconds timestamp representing the time that this batch started executing.
	 *
	 * @return int
	 */
	public function getTime() {
		if (empty($this->migrations)) {
			return time();
		}

		return $this->migrations[0]->getTime();
	}

	/**
	 * Rolls back the migrations in this batch. To track progress, listen to the `migration:start` and
	 * `migration:finish` events on the migrator instance.
	 *
	 * @return void
	 */
	public function rollback() {
		$migrations = array_reverse($this->getMigrations());
		$direction = $this->migrator->direction;

		$this->migrator->direction = Migrator::DIRECTION_DOWN;
		$this->migrator->run($migrations);
		$this->migrator->direction = $direction;
	}

}
