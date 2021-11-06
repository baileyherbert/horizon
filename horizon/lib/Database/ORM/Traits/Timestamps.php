<?php

namespace Horizon\Database\ORM\Traits;

use DateTime;
use Exception;

/**
 * @property-read DateTime $created_at
 * @property-write DateTime|int $created_at
 *
 * @property-read DateTime $updated_at
 * @property-write DateTime|int $updated_at
 */
trait Timestamps {

	/**
	 * Converts the table's `created_at` value to a PHP `DateTime` instance.
	 *
	 * @param string $value
	 * @return DateTime
	 */
	protected function __getCreatedAt($value) {
		return new DateTime($value);
	}

	/**
	 * Converts a PHP timestamp or `DateTime` instance to the SQL DateTime format.
	 *
	 * @param DateTime|int $value
	 * @return DateTime
	 */
	protected function __setCreatedAt($value) {
		if (is_int($value)) {
			return timestamp_to_datetime($value);
		}

		if ($value instanceof DateTime) {
			return $value->format('Y-m-d H:i:s');
		}

		throw new Exception("Expected integer or DateTime, got " . gettype($value));
	}

	/**
	 * Converts the table's `created_at` value to a PHP `DateTime` instance.
	 *
	 * @param string $value
	 * @return DateTime
	 */
	protected function __getUpdatedAt($value) {
		return new DateTime($value);
	}

	/**
	 * Converts a PHP timestamp or `DateTime` instance to the SQL DateTime format.
	 *
	 * @param DateTime|int $value
	 * @return DateTime
	 */
	protected function __setUpdatedAt($value) {
		if (is_int($value)) {
			return timestamp_to_datetime($value);
		}

		if ($value instanceof DateTime) {
			return $value->format('Y-m-d H:i:s');
		}

		throw new Exception("Expected integer or DateTime, got " . gettype($value));
	}

	/**
	 * Prevents the `updated_at` column from being bumped to the current time on the next save.
	 *
	 * @return void
	 */
	public function skipUpdateTimestamp() {
		$this->fixes[] = 'updated_at';
	}

	/**
	 * Synchronizes the timestamps with the database. This is useful if you need the timestamps after row update or
	 * creation, as the framework will not fetch them automatically for performance reasons.
	 *
	 * @return void
	 */
	public function refreshTimestamps() {
		$keyName = $this->getPrimaryKey();
		$keyValue = $this->getPrimaryKeyValue();

		$row = \DB::connection($this->getConnection())
			->select()
			->columns('updated_at', 'created_at')
			->from($this->getTable())
			->where($keyName, '=', $keyValue)
			->first();

		$this->storage['updated_at'] = $row->updated_at;
		$this->storage['created_at'] = $row->created_at;
	}

}
