<?php

namespace Horizon\Database;

use Exception;
use Horizon\Database\Exception\DatabaseException;
use Horizon\Database\Exception\MigrationException;
use Horizon\Database\Exception\SkipMigrationException;
use Horizon\Database\Migration\MigrationBatch;
use Horizon\Database\Migration\Schema;
use Horizon\Database\Migration\SchemaConnection;
use Horizon\Database\Migration\SchemaRecorder;
use Horizon\Database\Migration\SchemaRecorderBucket;
use Horizon\Database\Migration\SchemaStatement;
use Horizon\Events\EventEmitter;
use Horizon\Foundation\Application;
use Horizon\Foundation\Framework;
use Horizon\Support\Path;
use JsonSerializable;

/**
 * The base class for all migrations.
 */
abstract class Migration implements JsonSerializable {

	/**
	 * The name of the database connection to use for operations in this migration by default.
	 *
	 * If not specified, the default connection in the migrator instance that invokes this migration will be used
	 * instead, which defaults to `main`.
	 *
	 * @var string|null
	 */
	protected $connection;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * The migrator attached to this instance (if applicable).
	 *
	 * @var Migrator|null
	 */
	private $migrator;

	/**
	 * The recorder bucket for this migration to log its queries (only applicable when dry run is enabled).
	 *
	 * @var SchemaRecorderBucket
	 */
	private $bucket;

	/**
	 * Constructs a new `Migration` instance.
	 *
	 * @param string $path The absolute path to the migration file.
	 */
	public function __construct($path, $migrator = null) {
		$this->path = $path;
		$this->migrator = $migrator;
		$this->bucket = SchemaRecorder::createBucket(false);
	}

	/**
	 * Returns the name of the migration file.
	 *
	 * @param bool $includeExtension
	 * @return string
	 */
	public function getFileName($includeExtension = true) {
		$fileName = Path::basename($this->path);

		if (!$includeExtension) {
			$dotIndex = strrpos($fileName, '.');

			if ($dotIndex !== false) {
				$fileName = substr($fileName, 0, $dotIndex);
			}
		}

		return $fileName;
	}

	/**
	 * Returns the application path to the migration file for recording.
	 *
	 * @return string
	 */
	public function getRecordName() {
		$recordName = null;

		if (starts_with($this->path, app_path(), true)) {
			$recordName = substr($this->path, strlen(app_path()) + 1);
		}

		else if (starts_with($this->path, Framework::path(), true)) {
			$recordName = ':' . substr($this->path, strlen(Framework::path()) + 1);
		}

		if ($recordName === null) {
			throw new Exception('Migration is outside of application root: ' . $this->path);
		}

		$recordName = str_replace('\\', '/', $recordName);
		$recordName = preg_replace("/\.php$/", "", $recordName);

		return $recordName;
	}

	/**
	 * Returns an absolute path to the migration file.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Returns the name of the connection this migration should use by default.
	 *
	 * @return string
	 */
	public function getConnectionName() {
		if ($this->connection !== null) {
			return $this->connection;
		}

		if ($this->migrator !== null) {
			return $this->migrator->getConnectionName();
		}

		return 'main';
	}

	/**
	 * Run the migration.
	 */
	public abstract function up();

	/**
	 * Revert the migration.
	 */
	public function down() {
		throw new SkipMigrationException('Not implemented');
	}

	/**
	 * Creates and returns a new instance of this migration that is scoped to the given migrator.
	 *
	 * @param Migrator $migrator
	 * @return static
	 */
	public function scope(Migrator $migrator) {
		return new static($this->path, $migrator);
	}

	/**
	 * Returns the migrator instance for this migration if applicable. Will throw an error if this instance wasn't
	 * spawned from a migrator.
	 *
	 * @return Migrator
	 */
	public function getMigrator() {
		if (!$this->migrator) {
			throw new Exception('Attempt to get migrator instance on an unscoped migration');
		}

		return $this->migrator;
	}

	/**
	 * Returns `true` if this migration needs to be executed.
	 *
	 * @return bool
	 */
	public function isPending() {
		return !$this->getMigrator()->hasMigrationRecord($this);
	}

	/**
	 * Returns `true` if this migration has already been executed.
	 *
	 * @return bool
	 */
	public function isFinished() {
		return $this->getMigrator()->hasMigrationRecord($this);
	}

	/**
	 * Returns the timestamp that this migration was executed at, or `null` if it hasn't run.
	 *
	 * @return int|null
	 */
	public function getTime() {
		return $this->getMigrator()->getMigrationTime($this);
	}

	/**
	 * Returns the number of the batch that this migration was executed in or `null` if it hasn't run.
	 *
	 * @return int|null
	 */
	public function getBatchId() {
		$record = $this->getMigrator()->getMigrationRecord($this);

		if ($record != null) {
			return $record->batch;
		}
	}

	/**
	 * Returns the identifier for this migration in the database. This is only available if it has executed before, so
	 * returns `null` otherwise.
	 *
	 * @return int|null
	 */
	public function getRecordId() {
		$record = $this->getMigrator()->getMigrationRecord($this);

		if ($record != null) {
			return $record->id;
		}
	}

	/**
	 * Returns the the batch that this migration was executed in or `null` if it hasn't run.
	 *
	 * @return MigrationBatch|null
	 */
	public function getBatch() {
		$number = $this->getBatchId();

		if ($number !== null) {
			return $this->getMigrator()->getBatch($number);
		}
	}

	/**
	 * Runs the migration.
	 *
	 * When in dry run mode, this will return an array of queries that attempted to run. Otherwise, returns `null`.
	 *
	 * @param MigrationBatch $batch
	 * @return SchemaStatement[]|null
	 */
	public function run(MigrationBatch $batch) {
		$migrator = $this->getMigrator();

		switch ($migrator->dryRun) {
			case false: return $this->runAsMigration($migrator, $batch);
			case true: return $this->runAsTest($migrator);
			default: throw new Exception("Migrator::dryRun must be type boolean, got " . gettype($migrator->dryRun));
		}
	}

	/**
	 * Runs the migration normally, executing the directional method and saving a record to the schema table. If there
	 * is an error, an exception will be thrown.
	 *
	 * @param Migrator $migrator
	 * @param MigrationBatch $batch
	 * @return void
	 */
	private function runAsMigration(Migrator $migrator, MigrationBatch $batch) {
		$connectionName = $this->getConnectionName();
		$schemaConnection = $migrator->getConnection();
		$startTime = time();

		Schema::withDefaultConnection($connectionName, function() use($migrator) {
			$this->executeDirectionMethod($migrator);
		});

		// For upward migrations, create a new record in the schema table
		if ($migrator->direction === Migrator::DIRECTION_UP) {
			$record = array(
				'name' => $this->getRecordName(),
				'batch' => $batch->getId(),
				'migration_time' => timestamp_to_datetime($startTime)
			);

			$record['id'] = $schemaConnection->insert()->into('horizon_schema')->values($record)->exec();
			$migrator->setBatchMilestone(
				$batch,
				$this,
				$record
			);
		}

		// For downward migrations, delete the record from the schema table
		else {
			$schemaConnection->delete()->from('horizon_schema')->where('id', '=', $this->getRecordId())->exec();
			$migrator->removeBatchMilestone(
				$batch,
				$this
			);
		}
	}

	/**
	 * Runs the migration as a test, with all queries intercepted and validated for syntax errors.
	 *
	 * @param Migrator $migrator
	 * @return SchemaStatement[]
	 */
	private function runAsTest(Migrator $migrator) {
		$connectionName = $this->getConnectionName();

		// Start monitoring and validating queries
		Application::kernel()->database()->validationMode(true);
		$this->bucket->start();

		// Run the migration as normal, but catch exceptions so we can stop the bucket
		try {
			Schema::withDefaultConnection($connectionName, function() use($migrator) {
				$this->executeDirectionMethod($migrator);
			});
		}
		catch (Exception $ex) {
			$this->bucket->stop();
			Application::kernel()->database()->validationMode(false);

			// Database exceptions will be logged in the bucket and shouldn't be thrown
			if (!($ex instanceof DatabaseException)) {
				throw $ex;
			}

			return $this->bucket->all();
		}

		$this->bucket->stop();
		Application::kernel()->database()->validationMode(false);

		return $this->bucket->all();
	}

	/**
	 * Executes the `up` or `down` method according to the direction on the given migrator.
	 *
	 * @param Migrator $migrator
	 * @return void
	 */
	private function executeDirectionMethod(Migrator $migrator) {
		switch ($migrator->direction) {
			case Migrator::DIRECTION_DOWN: return $this->down();
			case Migrator::DIRECTION_UP: return $this->up();
			default: throw new Exception("Unknown direction {$migrator->direction}");
		}
	}

	/**
	 * Returns the recorder bucket for this migration (only useful during dry runs).
	 *
	 * @return SchemaRecorderBucket
	 */
	public function getBucket() {
		return $this->bucket;
	}

	/**
	 * Serializes the migration as JSON.
	 *
	 * @return void
	 */
	public function jsonSerialize() {
		return array(
			'id' => $this->getRecordId(),
			'name' => $this->getRecordName(),
			'path' => $this->getPath(),
			'batch' => $this->getBatchId(),
			'status' => $this->isFinished() ? 'finished' : 'pending',
			'timestamp' => $this->getTime()
		);
	}

}
