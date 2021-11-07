<?php

namespace Horizon\Database;

use Horizon\Database\Exception\MigrationException;
use Horizon\Database\Migration\Blueprint;
use Horizon\Database\Migration\MigrationBatch;
use Horizon\Database\Migration\Schema;
use Horizon\Events\EventEmitter;
use Horizon\Logging\Logger;
use Horizon\Support\Arr;

class Migrator extends EventEmitter {

	const DIRECTION_UP = 0;
	const DIRECTION_DOWN = 1;

	/**
	 * An array of all migrations registered in the system. The keys in this array are the migration record names.
	 *
	 * @var Migration[]
	 */
	private $migrations;

	/**
	 * An array of all schema records from the `horizon_schema` table. The keys in this array are the migration record
	 * names.
	 *
	 * @var array
	 */
	private $schemaRecords;

	/**
	 * An array of previously-executed batches, reconstructed from the `horizon_schema` table. The keys in this array
	 * are the batch numbers, starting at 1.
	 *
	 * @var MigrationBatch[]
	 */
	private $batches;

	/**
	 * Whether or not to perform a "dry run" for this migration, where queries will be logged rather than executed.
	 *
	 * @var bool
	 */
	public $dryRun = false;

	/**
	 * The algorithm to use for sorting migrations by file name.
	 *
	 * @var int
	 */
	public $sortAlgorithm = SORT_NATURAL;

	/**
	 * The direction to execute for this migration (defaults to up).
	 *
	 * Use one of the following constants:
	 *
	 * - `Migrator::DIRECTION_UP`
	 * - `Migrator::DIRECTION_DOWN`
	 *
	 * @var int
	 */
	public $direction = Migrator::DIRECTION_UP;

	/**
	 * The default connection to use for all migrations that run inside this migrator. This is also where the
	 * `horizon_schema` table will be hosted.
	 *
	 * @var string
	 */
	public $connection = 'main';

	/**
	 * The logger instance that this migrator will output information to. You can replace this with your own logger
	 * instance, or listen to the 'log' event on this instance.
	 *
	 * @var Logger
	 */
	public $logger;

	/**
	 * Constructs a new `Migrator` instance.
	 */
	public function __construct() {
		$this->logger = new Logger('migrator');
	}

	/**
	 * Initializes the migrator if necessary, otherwise does nothing.
	 *
	 * @return void
	 */
	private function init() {
		if ($this->migrations === null) {
			$this->initMigrations();
			$this->initSchemaRecords();
			$this->initBatches();
		}
	}

	/**
	 * Initializes the migrations array.
	 *
	 * @return void
	 */
	private function initMigrations() {
		$migrations = app()->all('Horizon\Database\Migration')->all();
		$migrations = Arr::sort($migrations, function($migration) {
			return $migration->getFileName(false);
		}, $this->sortAlgorithm);

		$this->migrations = array();

		foreach ($migrations as $migration) {
			$this->migrations[$migration->getRecordName()] = $migration->scope($this);
		}
	}

	/**
	 * Initializes schema records from the `horizon_schema` table.
	 *
	 * @return void
	 */
	private function initSchemaRecords() {
		$this->schemaRecords = array();

		if (Schema::hasTable('horizon_schema')) {
			$records = DatabaseFacade::select()
				->from('horizon_schema')
				->orderBy('id', 'asc')
				->get();

			foreach ($records as $record) {
				$this->schemaRecords[$record->name] = $record;
			}
		}
	}

	/**
	 * Initializes batches from schema records.
	 *
	 * @return void
	 */
	private function initBatches() {
		$batches = array();

		foreach ($this->schemaRecords as $key => $record) {
			if (array_key_exists($key, $this->migrations)) {
				$migration = $this->migrations[$key];
				$batchNumber = $record->batch;

				if (!isset($batches[$batchNumber])) {
					$batches[$batchNumber] = array();
				}

				$batches[$batchNumber][] = $migration;
			}
		}

		ksort($batches);
		$this->batches = array();

		foreach ($batches as $batchNumber => $migrations) {
			$this->batches[] = new MigrationBatch($this, $batchNumber, $migrations);
		}
	}

	/**
	 * Returns an array of migration instances from the `app/database/migrations` directory. he array is ordered so
	 * that the first migration in the array should be executed first.
	 *
	 * @return Migration[]
	 */
	public function getMigrations() {
		$this->init();
		return array_values($this->migrations);
	}

	/**
	 * Returns an array of migration instances that need to be executed. The array is ordered so that the first
	 * migration in the array should be executed first.
	 *
	 * @return Migration[]
	 */
	public function getPendingMigrations() {
		$migrations = $this->getMigrations();
		$pending = array();

		foreach ($migrations as $migration) {
			if (!$this->hasMigrationRecord($migration)) {
				$pending[] = $migration;
			}
		}

		return $pending;
	}

	/**
	 * Returns an array of migration instances that have already been executed. The array is ordered so that the last
	 * migration in the array was executed most recently.
	 *
	 * @return Migration[]
	 */
	public function getFinishedMigrations() {
		$migrations = $this->getMigrations();
		$finished = array();

		foreach ($migrations as $migration) {
			if ($this->hasMigrationRecord($migration)) {
				$finished[] = $migration;
			}
		}

		return $finished;
	}

	/**
	 * Returns the timestamp that the given migration was executed at, or `null` if it has not yet run.
	 *
	 * @param Migration $migration
	 * @return int|null
	 */
	public function getMigrationTime(Migration $migration) {
		$record = $this->getMigrationRecord($migration);

		if ($record !== null) {
			return datetime_to_timestamp($record->migration_time);
		}
	}

	/**
	 * Queries and returns an array of migration records from the `horizon_schema` table.
	 *
	 * @return object[]
	 */
	public function getMigrationRecords() {
		$this->init();
		return array_values($this->schemaRecords);
	}

	/**
	 * Returns the schema record from the database for a given migration or `null` if the migration has not run.
	 *
	 * @param Migration $migration
	 * @return object|null
	 */
	public function getMigrationRecord(Migration $migration) {
		$this->init();

		$name = $migration->getRecordName();

		if (array_key_exists($name, $this->schemaRecords)) {
			return $this->schemaRecords[$name];
		}
	}

	/**
	 * Returns `true` if the given migration has a record in the `horizon_schema` table. You can use this to check if
	 * a migration has run yet.
	 *
	 * @param Migration $migration
	 * @return bool
	 */
	public function hasMigrationRecord(Migration $migration) {
		$this->init();

		$name = $migration->getRecordName();
		return array_key_exists($name, $this->schemaRecords);
	}

	/**
	 * Returns an array of all batches.
	 *
	 * @return MigrationBatch[]
	 */
	public function getBatches() {
		$this->init();

		return array_values($this->batches);
	}

	/**
	 * Returns the batch matching the specified identifier.
	 *
	 * @param int $id
	 * @return MigrationBatch|null
	 */
	public function getBatch($id) {
		$this->init();

		if (isset($this->batches[$id])) {
			return $this->batches[$id];
		}
	}

	/**
	 * Creates a new batch for the next execution.
	 *
	 * @return MigrationBatch
	 */
	public function createBatch() {
		$this->init();
		$this->createSchemaTable();

		$batches = $this->getBatches();
		$nextBatchNumber = empty($batches) ? 1 : ($batches[count($batches) - 1]->getId() + 1);

		$batch = new MigrationBatch($this, $nextBatchNumber, array(), array());
		$this->batches[$nextBatchNumber] = $batch;

		return $batch;
	}

	/**
	 * Creates the `horizon_schema` table if it doesn't exist.
	 *
	 * @return void
	 */
	private function createSchemaTable() {
		if (!Schema::hasTable('horizon_schema')) {
			Schema::create('horizon_schema', function(Blueprint $blueprint) {
				$blueprint->increments('id');
				$blueprint->string('name')->unique();
				$blueprint->integer('batch', false, true);
				$blueprint->dateTime('migration_time');
			});
		}
	}

	/**
	 * Returns the name of the database connection to use for this migrator.
	 *
	 * @return string
	 */
	public function getConnectionName() {
		if ($this->connection !== null) {
			return $this->connection;
		}

		return 'main';
	}

	/**
	 * Returns the database connection to use for this migrator.
	 *
	 * @return DatabaseConnection
	 */
	public function getConnection() {
		return DatabaseFacade::connection($this->getConnectionName());
	}

	/**
	 * Inserts a newly executed migration and its schema record into the migrator, and updates the corresponding batch.
	 *
	 * @param MigrationBatch $batch
	 * @param Migration $migration
	 * @param array $record
	 * @return void
	 */
	public function setBatchMilestone(MigrationBatch $batch, Migration $migration, array $record) {
		$this->schemaRecords[$migration->getRecordName()] = (object) $record;
		$batch->getMigrations()[] = $migration;
	}

	/**
	 * Removes an executed migration and its schema record from the migrator, and updates the corresponding batch.
	 *
	 * @param MigrationBatch $batch
	 * @param Migration $migration
	 * @return void
	 */
	public function removeBatchMilestone(MigrationBatch $batch, Migration $migration) {
		if (isset($this->schemaRecords[$migration->getRecordName()])) {
			unset($this->schemaRecords[$migration->getRecordName()]);

			$migrations = $batch->getMigrations();
			$index = array_search($migration, $migrations);

			if ($index !== false) {
				unset($migrations[$index]);
			}
		}
	}

	/**
	 * Executes all outstanding migrations and returns the batch (if the direction is `UP`). If there is nothing to do,
	 * returns `null`.
	 *
	 * @param Migration[]|null $migrations
	 * @return MigrationBatch|null
	 * @throws MigrationException
	 */
	public function run($migrations = null) {
		$this->init();

		$migrations = $migrations ?: $this->getPendingMigrations();
		$batch = null;

		if (!empty($migrations)) {
			foreach ($migrations as $migration) {
				$startTime = microtime(true);
				$migrationBatch = $migration->isFinished() ? $migration->getBatch() : $batch;

				$this->emit('migration:start', $migration);

				if ($migrationBatch === null) {
					$migrationBatch = $batch = $this->createBatch();
				}

				$migration->run($migrationBatch);

				$took = microtime(true) - $startTime;
				$this->emit('migration:finish', $migration, round($took, 3));
			}
		}

		return $batch;
	}

}
