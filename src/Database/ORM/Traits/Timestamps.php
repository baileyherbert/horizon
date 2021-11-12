<?php

namespace Horizon\Database\ORM\Traits;

use DateTime;

/**
 * Adding this trait to a model will activate the `created_at` and `updated_at` timestamp columns on that model,
 * enable type hinting, and add a method to skip the update timestamp for a specific save.
 *
 * @property-read DateTime $created_at
 * @property-write DateTime|int $created_at
 *
 * @property-read DateTime $updated_at
 * @property-write DateTime|int $updated_at
 */
trait Timestamps {

	/**
	 * Prevents the `updated_at` column from being bumped to the current time on the next save.
	 *
	 * @return void
	 */
	public function skipUpdateTimestamp() {
		$this->skip('updated_at');
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

		$this->writeCommittedField('updated_at', $row->updated_at);
		$this->writeCommittedField('created_at', $row->created_at);
	}

}
