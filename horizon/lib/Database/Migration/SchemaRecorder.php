<?php

namespace Horizon\Database\Migration;

use Exception;
use Horizon\Database\DatabaseFacade;

/**
 * This is a utility for recording queries across all database connections.
 *
 * To start, create a bucket using `SchemaRecorder::createBucket()`. This bucket will automatically collect any
 * queries that execute and can be used to view them. When finished, use `$bucket->stop()` to close the bucket and
 * release the query listeners.
 */
class SchemaRecorder {

	/**
	 * An array of active buckets.
	 *
	 * @var SchemaRecorderBucket[]
	 */
	private static $buckets = array();

	/**
	 * Callables that handle queries from drivers, indexed by connection name.
	 *
	 * @var callable[]
	 */
	private static $handlers = array();

	/**
	 * Creates and returns a bucket for collecting queries.
	 *
	 * @param bool $autoRecord
	 * @return SchemaRecorderBucket
	 */
	public static function createBucket($autoRecord = true) {
		$bucket = new SchemaRecorderBucket();

		if ($autoRecord) {
			static::$buckets[] = $bucket;
		}

		return $bucket;
	}

	/**
	 * Activates the given bucket and starts sending queries into it.
	 *
	 * @param SchemaRecorderBucket $bucket
	 * @return void
	 */
	public static function startBucket(SchemaRecorderBucket $bucket) {
		$index = array_search($bucket, static::$buckets);

		if ($index === false) {
			static::$buckets[] = $bucket;

			// Start listening upon the first bucket
			if (count(static::$buckets) === 1) {
				static::startListening();
			}
		}
	}

	/**
	 * Deactivates the given bucket and stops sending queries into it.
	 *
	 * @param SchemaRecorderBucket $bucket
	 * @return void
	 */
	public static function stopBucket(SchemaRecorderBucket $bucket) {
		$index = array_search($bucket, static::$buckets);

		if ($index !== false) {
			unset(static::$buckets[$index]);

			// Start listening upon the last bucket
			if (count(static::$buckets) === 0) {
				static::stopListening();
			}
		}
	}

	/**
	 * Manually records a statement in all active buckets.
	 *
	 * @param string $connectionName
	 * @param string $statement
	 * @param Exception|null $exception
	 * @return void
	 */
	public static function push($connectionName, $statement, $exception = null) {
		if (!empty(static::$buckets)) {
			foreach (static::$buckets as $bucket) {
				$bucket->push(new SchemaStatement($connectionName, $statement, $exception));
			}
		}
	}

	/**
	 * Returns the handler function to use for query listening.
	 *
	 * @param string $connectionName
	 * @return callable
	 */
	private static function getHandler($connectionName) {
		if (isset(static::$handlers[$connectionName])) {
			return static::$handlers[$connectionName];
		}

		$handler = function ($statement, array $bindings, $millisTaken, $exception = null) use ($connectionName) {
			static::push($connectionName, $statement, $exception);
		};

		return static::$handlers[$connectionName] = $handler;
	}

	/**
	 * Starts listening to queries on all database connections.
	 *
	 * @return void
	 */
	private static function startListening() {
		foreach (config('database') as $connectionName => $config) {
			$handler = static::getHandler($connectionName);
			$database = DatabaseFacade::connection($connectionName)->getDatabase();
			$database->on('query', $handler);
		}
	}

	/**
	 * Stops listening to queries on all database connections.
	 *
	 * @return void
	 */
	private static function stopListening() {
		foreach (config('database') as $connectionName => $config) {
			$handler = static::getHandler($connectionName);
			$database = DatabaseFacade::connection($connectionName)->getDatabase();
			$database->remove('query', $handler);
		}
	}

}
