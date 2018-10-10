<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method AlterHelper table(string $tableName) Sets the table to alter.
 *
 * @method AlterHelper addColumn(ColumnDefinition $column, string|null $after) Adds a column to the table.
 * @method AlterHelper modifyColumn(ColumnDefinition $column, string|null $after) Changes the properties of a column.
 * @method AlterHelper changeColumn(ColumnDefinition $column, string $newName, string|null $after) Changes the name and properties of a column.
 * @method AlterHelper dropColumn(string $columnName) Removes a column.
 *
 * @method AlterHelper addPrimaryKey(string $column,...) Adds a primary key.
 * @method AlterHelper addIndex(string $column,...) Adds an index.
 * @method AlterHelper addUniqueIndex(string $column,...) Adds a unique index.
 * @method AlterHelper addForeignKey(string|string[] $column, $foreignTable, string|string[] $foreignColumn, $onDelete, $onUpdate) Adds a foreign key.
 *
 * @method AlterHelper dropPrimaryKey() Drops the current primary key.
 * @method AlterHelper dropIndex(string $name) Drops an index.
 * @method AlterHelper dropForeignKey(string $name) Drops a foreign key.
 *
 * @method AlterHelper rename(string $newTableName) Changes the table name.
 *
 * @method AlterHelper engine(string $engine) Sets the table engine.
 * @method AlterHelper charset(string $charset) Sets the character set.
 * @method AlterHelper collate(string $collate) Sets the character collation.
 * @method AlterHelper opt(string $option, string $value) Sets an option manually.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query, returning the number of affected rows.
 * @method object[]|\Horizon\Database\Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|\Horizon\Database\Model first() Fetches the first row in the query as an object, or a model if configured.
 */
class AlterHelper
{

}