<?php

namespace Horizon\Database\ORM\Traits;

use DateTime;
use DB;
use Exception;

use Horizon\Database\Model;
use Horizon\Database\ORM\Relationship;
use Horizon\Database\QueryBuilder;
use Horizon\Database\Cache;
use Horizon\Database\ModelField;
use Horizon\Database\ORM\DocParser;
use Horizon\Database\ORM\Relationships\OneToOneRelationship;
use Horizon\Database\ORM\Relationships\BelongsToOneRelationship;

trait Mapping {

	use Relationships;

	protected $table;
	protected $primaryKey = 'id';

	/**
	 * An array containing the fields that are committed to the database.
	 *
	 * @var ModelField[]
	 */
	private $rowFieldsCommitted = [];

	/**
	 * An array containing the fields that will be committed to the database on the next save.
	 *
	 * @var ModelField[]
	 */
	private $rowFieldsPending = [];

	/**
	 * An array containing the fields (as names) that should be set to themselves on the next save.
	 *
	 * @var string[]
	 */
	private $rowFieldsEqualize = [];

	/**
	 * Name of database connection to use for this model.
	 *
	 * @var string|null
	 */
	protected $connection = null;

	/**
	 * Gets the name of the table for this instance.
	 *
	 * @return string
	 */
	public function getTable() {
		if (is_null($this->table)) {
			$tableNameParts = explode('\\', strtolower(get_class($this)));
			$tableName = array_pop($tableNameParts);

			if (substr($tableName, 0, -1) != 's') {
				$tableName .= 's';
			}

			$this->table = $tableName;
		}

		return $this->table;
	}

	/**
	 * Gets the name of the primary key for this table.
	 *
	 * @return string
	 */
	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	/**
	 * Gets the value of the primary key in the row.
	 *
	 * @return mixed
	 */
	public function getPrimaryKeyValue() {
		$keyName = $this->getPrimaryKey();

		if (isset($this->rowFieldsCommitted[$keyName])) {
			return $this->rowFieldsCommitted[$keyName]->remoteFormat;
		}

		return null;
	}

	/**
	 * Saves changes to the row or creates the row if it doesn't exist.
	 */
	public function save() {
		$keyName = $this->getPrimaryKey();
		$keyValue = $this->getPrimaryKeyValue();
		$connection = DB::connection($this->getConnection());

		// Map the changes into their remote formats
		$changes = array_map(function(ModelField $field) {
			return $field->remoteFormat;
		}, $this->rowFieldsPending);

		// Create a new row if there is no value for the primary key
		if (is_null($keyValue)) {
			$builder = $connection->insert()->into($this->getTable())->values($changes);
			$returned = $builder->exec();

			// Save the new primary key value
			if (!array_key_exists($keyName, $this->rowFieldsPending)) {
				$this->writeCommittedField($keyName, $returned);
			}

			// Save the instance to cache
			Cache::setModelInstance($this, $this, $returned);

			// Emit the inserted event
			$this->emit('inserted', $returned);
		}

		// Update an existing row
		else {
			// Skip if there are no pending changes
			if (empty($this->rowFieldsPending)) {
				return;
			}

			// Set fields in the equalize store equal to themselves
			if (!empty($this->rowFieldsEqualize)) {
				foreach ($this->rowFieldsEqualize as $fieldName) {
					$changes[$fieldName] = ref($fieldName);
				}
			}

			$builder = $connection->update()->table($this->getTable())->values($changes);
			$builder->where($keyName, '=', $keyValue);
			$builder->exec();

			$this->emit('updated');
		}

		// Commit all pending changes
		foreach ($this->rowFieldsPending as $fieldName => $field) {
			$this->writeCommittedField($fieldName, $field->remoteFormat);

			// Emit a property-set event
			$this->emit('property', $fieldName, $field->localFormat);
		}

		$this->emit('saved', $changes);
		$this->rowFieldsPending = array();
		$this->rowFieldsEqualize = array();
	}

	/**
	 * Deletes the row from the database, returning a copy of the data from before deletion.
	 *
	 * @return array
	 */
	public function delete() {
		$oldData = null;

		if (!is_null($this->getPrimaryKeyValue())) {
			$oldData = array_map(function(ModelField $field) {
				return $field->localFormat;
			}, $this->rowFieldsCommitted);

			$keyName = $this->getPrimaryKey();
			$keyValue = $this->getPrimaryKeyValue();

			$builder = \DB::connection($this->getConnection())->delete()->from($this->getTable());
			$builder->where($keyName, '=', $keyValue);
			$builder->exec();
		}

		$this->emit('deleted');
		$this->rowFieldsCommitted = array();
		$this->rowFieldsPending = array();
		$this->rowFieldsEqualize = array();

		return $oldData;
	}

	public function __isset($name) {
		return (method_exists($this, $name) || array_key_exists($name, $this->rowFieldsCommitted));
	}

	public function __get($fieldName) {
		// Check for a corresponding method and return their values or relationships
		if (method_exists($this, $fieldName)) {
			$value = $this->$fieldName();

			if ($value instanceof Relationship || $value instanceof QueryBuilder) {
				return $value->get();
			}

			return $value;
		}

		// Check for a field in the pending store
		if (array_key_exists($fieldName, $this->rowFieldsPending)) {
			$field = $this->rowFieldsPending[$fieldName];
		}

		// Check for a field in the committed store
		else if (array_key_exists($fieldName, $this->rowFieldsCommitted)) {
			$field = $this->rowFieldsCommitted[$fieldName];
		}

		// Check fields that are not found for validity
		else {
			if (!DocParser::get($this)->hasField($fieldName)) {
				throw new Exception(sprintf('Unknown field "%s"', $fieldName));
			}

			return null;
		}

		// Run getter functions
		if (method_exists($this, $getterName = '__get' . str_replace('_', '', $fieldName))) {
			$this->$getterName($field);
		}

		return $field->localFormat;
	}

	public function __set($fieldName, $value) {
		$field = $this->getField($fieldName, $value);

		// Skip if the same value is already committed
		if (array_key_exists($fieldName, $this->rowFieldsCommitted)) {
			if ($this->rowFieldsCommitted[$fieldName]->remoteFormat === $field->remoteFormat) {
				return;
			}
		}

		// Skip if the same value is already pending
		if (array_key_exists($fieldName, $this->rowFieldsPending)) {
			if ($this->rowFieldsPending[$fieldName]->remoteFormat === $field->remoteFormat) {
				return;
			}
		}

		// Delete a pending equalize operation for the same field
		if (($index = array_search($fieldName, $this->rowFieldsPending)) !== false) {
			unset($this->rowFieldsEqualize[$index]);
		}

		// Check for model relationships and update them
		if ($value instanceof Model && method_exists($this, $fieldName)) {
			$relationship = $this->$fieldName();

			if ($relationship instanceof OneToOneRelationship || $relationship instanceof BelongsToOneRelationship) {
				$relationship->set($value);
				return;
			}

			throw new Exception(sprintf('Cannot indirectly update relationship for field %s', $fieldName));
		}

		// Check for a setter function
		if (method_exists($this, $setterName = '__set' . str_replace('_', '', $fieldName))) {
			$this->$setterName($field);
		}

		// Emit the changed event
		$this->emit('changed', $fieldName, $field->localFormat);

		// Save the new field to the pending store
		$this->rowFieldsPending[$fieldName] = $field;
	}

	/**
	 * Gets the name of the connection this model uses, or null if default.
	 *
	 * @return string|null
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Sets the name of the connection this model uses. Set to NULL for default.
	 *
	 * @param string|null $name
	 * @return void
	 */
	public function setConnection($name) {
		$this->connection = $name;
	}

	/**
	 * Checks if this object equals another.
	 *
	 * @param Model $object
	 * @return bool
	 */
	public function equals(Model $object) {
		return ($object->id === $this->id && $object->getTable() === $this->getTable());
	}

	/**
	 * Forcefully skips updates to the specified field by setting its value equal to itself. This is primarily useful
	 * for auto-updating fields such as `updated_at` timestamps.
	 *
	 * @param string $fieldName
	 * @return void
	 */
	public function skip($fieldName) {
		$this->rowFieldsEqualize[] = $fieldName;
	}

	/**
	 * Writes the value of the specified field to the model's committed store. This is an internal method.
	 *
	 * @param string $fieldName
	 * @param string|int|double $value
	 * @return void
	 */
	protected function writeCommittedField($fieldName, $value) {
		$localFormat = $this->getLocalFormat($fieldName, $value);
		$field = new ModelField(null, $localFormat, $value);

		$this->rowFieldsCommitted[$fieldName] = $field;
	}

	/**
	 * Converts the given value into the correct format for local usage.
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return mixed
	 */
	private function getLocalFormat($fieldName, $value) {
		$parser = DocParser::get($this);
		$type = $parser->getReadType($fieldName);

		if (is_null($type)) {
			throw new Exception(sprintf('Unknown field "%s"', $fieldName));
		}

		switch ($type) {
			case 'DateTime': {
				if ($value instanceof DateTime) return $value;
				if (is_string($value)) return new DateTime($value);
				if (is_int($value)) return new DateTime('@' . $value);

				throw new Exception(sprintf(
					'Failed to convert value of type %s into DateTime for field %s',
					gettype($value),
					$fieldName
				));
			}

			case 'bool':
			case 'boolean': {
				if (is_bool($value)) return $value;
				if (is_string($value)) return in_array(strtolower($value), ['true', '1']);
				if (is_int($value)) return $value >= 1;

				throw new Exception(sprintf(
					'Failed to convert value of type %s into boolean for field %s',
					gettype($value),
					$fieldName
				));
			}
		}

		return $value;
	}

	/**
	 * Converts the given value into the correct format for remote storage.
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return mixed
	 */
	private function getRemoteFormat($fieldName, $value) {
		$parser = DocParser::get($this);
		$type = $parser->getReadType($fieldName);

		if (is_null($type)) {
			throw new Exception(sprintf('Unknown field "%s"', $fieldName));
		}

		switch ($type) {
			case 'DateTime': {
				if ($value instanceof DateTime) return $value->format('Y-m-d H:i:s');
				if (is_int($value)) return (new DateTime('@' . $value))->format('Y-m-d H:i:s');
				if (is_string($value)) return $value;

				throw new Exception(sprintf(
					'Failed to convert value of type %s into a DATETIME string for field %s',
					gettype($value),
					$fieldName
				));
			}

			case 'bool':
			case 'boolean': {
				if (is_string($value)) return in_array(strtolower($value), ['true', '1']) ? 1 : 0;
				if (is_int($value)) return $value >= 1 ? 1 : 0;
				if (is_bool($value)) return $value ? 1 : 0;

				throw new Exception(sprintf(
					'Failed to convert value of type %s into TINYINT for field %s',
					gettype($value),
					$fieldName
				));
			}
		}

		return $value;
	}

	/**
	 * Converts the given value into a `Field` instance containing the remote and local values.
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return ModelField
	 */
	private function getField($fieldName, $value) {
		$parser = DocParser::get($this);

		if (!$parser->hasField($fieldName)) {
			throw new Exception(sprintf('Unknown field "%s"', $fieldName));
		}

		return new ModelField(
			$value,
			$this->getLocalFormat($fieldName, $value),
			$this->getRemoteFormat($fieldName, $value)
		);
	}

}
