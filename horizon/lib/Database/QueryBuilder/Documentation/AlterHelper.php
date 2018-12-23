<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\QueryBuilder\ColumnDefinition;

/**
 * @method $this table(string $tableName) Sets the table to alter.
 *
 * @method $this addColumn(ColumnDefinition $column, string|null $after) Adds a column to the table.
 * @method $this modifyColumn(ColumnDefinition $column, string|null $after) Changes the properties of a column.
 * @method $this changeColumn(ColumnDefinition $column, string $newName, string|null $after) Changes the name and properties of a column.
 * @method $this dropColumn(string $columnName) Removes a column.
 *
 * @method $this addPrimaryKey(string $column) Adds a primary key.
 * @method $this addIndex(string $column) Adds an index.
 * @method $this addUniqueIndex(string $column) Adds a unique index.
 * @method $this addForeignKey(string|string[] $column, $foreignTable, string|string[] $foreignColumn, $onDelete, $onUpdate) Adds a foreign key.
 *
 * @method $this dropPrimaryKey() Drops the current primary key.
 * @method $this dropIndex(string $name) Drops an index.
 * @method $this dropForeignKey(string $name) Drops a foreign key.
 *
 * @method $this rename(string $newTableName) Changes the table name.
 *
 * @method $this engine(string $engine) Sets the table engine.
 * @method $this charset(string $charset) Sets the character set.
 * @method $this collate(string $collate) Sets the character collation.
 * @method $this opt(string $option, string $value) Sets an option manually.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query, returning the number of affected rows.
 */
abstract class AlterHelper
{

}
