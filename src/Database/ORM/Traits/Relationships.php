<?php

namespace Horizon\Database\ORM\Traits;

use Horizon\Database\ORM\Relationships\OneToOneRelationship;
use Horizon\Database\ORM\Relationships\OneToManyRelationship;
use Horizon\Database\ORM\Relationships\BelongsToOneRelationship;
use Horizon\Database\ORM\Relationships\BelongsToManyRelationship;
use Horizon\Database\QueryBuilder\StringBuilder;

trait Relationships {

	/**
	 * This model has another derivative model.
	 *
	 * @param string $model
	 * @param string $foreignKey
	 * @param string $localKey
	 * @return OneToOneRelationship
	 */
	protected function hasOne($model, $foreignKey = null, $localKey = null) {
		if (is_null($localKey)) {
			$localKey = $this->primaryKey;
		}

		if (is_null($foreignKey)) {
			$foreignKey = StringBuilder::getSingularModelName($this) . '_' . $localKey;
		}

		return new OneToOneRelationship($this, $model, $foreignKey, $localKey);
	}

	/**
	 * This model is derived from another model.
	 *
	 * @param string $model
	 * @param string $localKey
	 * @param string $parentKey
	 * @return BelongsToOneRelationship
	 */
	protected function belongsTo($model, $localKey = null, $parentKey = null) {
		$o = new $model;

		if (is_null($localKey)) {
			$localKey = StringBuilder::getSingularModelName($model) . '_' . $o->getPrimaryKey();
		}

		if (is_null($parentKey)) {
			$parentKey = $o->getPrimaryKey();
		}
		unset($o);

		return new BelongsToOneRelationship($this, $model, $parentKey, $localKey);
	}

	/**
	 * This model has multiple derivative models.
	 *
	 * @param string $model
	 * @param string $foreignKey
	 * @param string $localKey
	 * @return OneToManyRelationship
	 */
	protected function hasMany($model, $foreignKey = null, $localKey = null) {
		if (is_null($localKey)) {
			$localKey = $this->primaryKey;
		}

		if (is_null($foreignKey)) {
			$foreignKey = StringBuilder::getSingularModelName($this) . '_' . $this->getPrimaryKey();
		}

		return new OneToManyRelationship($this, $model, $foreignKey, $localKey);
	}

	/**
	 * This model is associated with multiple models through a map table.
	 *
	 * @param string $model
	 * @param string $mapTable
	 * @param string $localKey
	 * @param string $parentKey
	 * @return BelongsToManyRelationship
	 */
	protected function belongsToMany($model, $mapTable = null, $localKey = null, $parentKey = null) {
		if (is_null($localKey)) {
			$localKey = StringBuilder::getSingularModelName($this) . '_' . $this->getPrimaryKey();
		}

		if (is_null($parentKey)) {
			$o = new $model();
			$parentKey = StringBuilder::getSingularModelName($model) . '_' . $o->getPrimaryKey();
			unset($o);
		}

		return new BelongsToManyRelationship($this, $model, $parentKey, $localKey, $mapTable);
	}

}
