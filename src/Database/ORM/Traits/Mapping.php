<?php

namespace Horizon\Database\ORM\Traits;

use DateTime;
use DB;
use Exception;

use Horizon\Database\Model;
use Horizon\Database\ORM\Relationship;
use Horizon\Database\QueryBuilder;
use Horizon\Database\ModelField;
use Horizon\Database\ORM\DocParser;
use Horizon\Database\ORM\Relationships\OneToOneRelationship;
use Horizon\Database\ORM\Relationships\BelongsToOneRelationship;
use Horizon\Database\QueryBuilder\Documentation\DeleteHelper;
use Horizon\Database\QueryBuilder\Documentation\SelectHelper;
use Horizon\Database\QueryBuilder\Documentation\UpdateHelper;

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
			$tableName = last(explode('\\', get_class($this)));
			$tableName = trim(strtolower(preg_replace('/([A-Z]+)/', '_$1', $tableName)), '_');

			if (substr($tableName, -1) != 's') {
				$tableName .= 's';
			}

			$this->table = $tableName;
		}

		return $this->table;
	}

	/**
	 * Gets the name of the primary key for this table.
	 *
	 * @return string|string[]
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
		$key = $this->getPrimaryKey();

		if (is_array($key)) {
			return array_map(function($name) {
				if (isset($this->rowFieldsCommitted[$name])) {
					return $this->rowFieldsCommitted[$name]->remoteFormat;
				}
			}, $key);
		}

		if (isset($this->rowFieldsCommitted[$key])) {
			return $this->rowFieldsCommitted[$key]->remoteFormat;
		}

		return null;
	}

	/**
	 * Saves changes to the row or creates the row if it doesn't exist.
	 */
	public function save() {
		$keyName = $this->getPrimaryKey();
		$connection = DB::connection($this->getConnection());

		// Map the changes into their remote formats
		$changes = array_map(function(ModelField $field) {
			return $field->remoteFormat;
		}, $this->rowFieldsPending);

		// Create a new row if there is no value for the primary key
		if (!$this->hasPrimaryKey()) {
			$builder = $connection->insert()->into($this->getTable())->values($changes);
			$returned = $builder->exec();

			// Save the new primary key value
			if (!is_array($keyName) && !array_key_exists($keyName, $this->rowFieldsPending)) {
				$this->writeCommittedField($keyName, $returned);
			}

			// Save the instance to cache
			$connection->getDatabase()->cache()->saveModelInstance($this);

			// Emit the inserted event
			$this->emit('inserted', $returned);

			// Initialize with data
			$this->initWithData();
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
					$changes[$fieldName] = db_ref($fieldName);
				}
			}

			$this->createUpdateQuery()->values($changes)->exec();
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
	 * Creates an `SELECT` query builder that matches this model's target connection and primary key.
	 *
	 * @return SelectHelper
	 */
	protected function createSelectQuery($keyValue = null) {
		$connection = DB::connection($this->getConnection());
		$builder = $connection->select()->from($this->getTable());

		$key = $this->getPrimaryKey();
		$keyValue = is_null($keyValue) ? $this->getPrimaryKeyValue() : $keyValue;

		if (is_array($key)) {
			if (!is_array($keyValue)) {
				throw new Exception('Primary key is multiple columns but you passed a single value');
			}

			foreach ($key as $index => $keyName) {
				$builder->where($keyName, '=', $keyValue[$index]);
			}
		}
		else {
			if (is_array($keyValue)) {
				throw new Exception('Primary key is a single column but you passed an array of values');
			}

			$builder->where($key, '=', $keyValue);
		}

		return $builder;
	}

	/**
	 * Creates an `UPDATE` query builder that matches this model's target connection and primary key.
	 *
	 * @return UpdateHelper
	 */
	protected function createUpdateQuery() {
		$connection = DB::connection($this->getConnection());
		$builder = $connection->update()->table($this->getTable());

		$key = $this->getPrimaryKey();
		$keyValue = $this->getPrimaryKeyValue();

		if (is_array($key)) {
			foreach ($key as $index => $keyName) {
				$builder->where($keyName, '=', $keyValue[$index]);
			}
		}
		else {
			$builder->where($key, '=', $keyValue);
		}

		return $builder;
	}

	/**
	 * Creates a `DELETE` query builder that matches this model's target connection and primary key.
	 *
	 * @return DeleteHelper
	 */
	protected function createDeleteQuery() {
		$connection = DB::connection($this->getConnection());
		$builder = $connection->delete()->from($this->getTable());

		$key = $this->getPrimaryKey();
		$keyValue = $this->getPrimaryKeyValue();

		if (is_array($key)) {
			foreach ($key as $index => $keyName) {
				$builder->where($keyName, '=', $keyValue[$index]);
			}
		}
		else {
			$builder->where($key, '=', $keyValue);
		}

		return $builder;
	}

	/**
	 * Returns true if this model has a primary key value.
	 *
	 * @return bool
	 */
	protected function hasPrimaryKey() {
		$value = $this->getPrimaryKeyValue();

		if (is_array($value)) {
			foreach ($value as $keyValue) {
				if (is_null($keyValue)) {
					return false;
				}
			}
		}

		return !is_null($value);
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

			$this->createDeleteQuery()->exec();
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
				$cache = DB::connection($this->getConnection())->cache();

				if ($cache->hasRelationship($this, $fieldName)) {
					return $cache->getRelationship($this, $fieldName);
				}

				$result = $value->get();
				$cache->saveRelationship($this, $fieldName, $result);

				return $result;
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

		// Check for a setter function
		if (method_exists($this, $setterName = '__set' . str_replace('_', '', $fieldName))) {
			$this->$setterName($field);
		}

		// Check for model relationships and update them
		if ($value instanceof Model && method_exists($this, $fieldName)) {
			$relationship = $this->$fieldName();

			if ($relationship instanceof OneToOneRelationship || $relationship instanceof BelongsToOneRelationship) {
				$relationship->set($value);
				$cache = DB::connection($this->getConnection())->cache();
				$cache->clearRelationship($this, $fieldName);
				return;
			}

			throw new Exception(sprintf('Cannot indirectly update relationship for field %s', $fieldName));
		}

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
		if ($this === $object) {
			return true;
		}

		$localTable = $this->getTable();
		$localKey = serialize($this->getPrimaryKey());
		$localValue = serialize($this->getPrimaryKeyValue());

		$remoteTable = $object->getTable();
		$remoteKey = serialize($object->getPrimaryKey());
		$remoteValue = serialize($object->getPrimaryKeyValue());

		return $localTable === $remoteTable &&
			$localKey === $remoteKey &&
			$localValue === $remoteValue;
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
	private function writeCommittedField($fieldName, $value) {
		$localFormat = $this->getLocalFormat($fieldName, $value);
		$field = new ModelField(null, $localFormat, $value);

		$this->rowFieldsCommitted[$fieldName] = $field;

		if ($fieldName === $this->getPrimaryKey()) {
			DB::connection($this->getConnection())->getDatabase()->cache()->saveModelInstance($this);
		}
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

	/**
	 * Converts the given value into the correct format for local usage.
	 *
	 * @param string $fieldName
	 * @param mixed $value
	 * @return mixed
	 */
	private function getLocalFormat($fieldName, $value) {
		$parser = DocParser::get($this);
		$type = $parser->getReadTypes($fieldName)[0];

		if (is_null($type)) {
			throw new Exception(sprintf('Unknown field "%s"', $fieldName));
		}

		switch ($type) {
			case '\DateTime':
			case 'DateTime': {
				if ($value instanceof DateTime) return $value;
				if (is_string($value)) return new DateTime($value);
				if (is_int($value)) return new DateTime('@' . $value);
				if (is_null($value)) return $value;

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
				if (is_null($value)) return $value;

				throw new Exception(sprintf(
					'Failed to convert value of type %s into boolean for field %s',
					gettype($value),
					$fieldName
				));
			}
		}

		// JSON
		if ($type === 'object' || $type === 'array' || ends_with($type, '[]')) {
			if (is_string($value)) {
				return json_decode($value, $type !== 'object');
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
		$type = $parser->getReadTypes($fieldName)[0];

		if (is_null($type)) {
			throw new Exception(sprintf('Unknown field "%s"', $fieldName));
		}

		switch ($type) {
			case '\DateTime':
			case 'DateTime': {
				if ($value instanceof DateTime) return $value->format('Y-m-d H:i:s');
				if (is_int($value)) return (new DateTime('@' . $value))->format('Y-m-d H:i:s');
				if (is_string($value)) return $value;
				if (is_null($value)) return $value;

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
				if (is_null($value)) return $value;

				throw new Exception(sprintf(
					'Failed to convert value of type %s into TINYINT for field %s',
					gettype($value),
					$fieldName
				));
			}
		}

		// JSON
		if ($type === 'object' || $type === 'array' || ends_with($type, '[]')) {
			if (is_object($value) || is_array($value)) {
				return json_encode($value, JSON_UNESCAPED_SLASHES);
			}
		}

		return $value;
	}

	/**
	 * Returns the internal array of committed fields. This is an internal method, do not use!
	 *
	 * @return Field[]
	 */
	private function getCommittedFields() {
		return $this->rowFieldsCommitted;
	}

}
