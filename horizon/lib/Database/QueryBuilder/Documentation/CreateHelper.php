<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\QueryBuilder\ColumnDefinition;

/**
 * @method $this table(string $tableName) Sets the table to target.
 *
 * @method $this column(ColumnDefinition $column) Adds a column to the table.
 * @method $this columns(ColumnDefinition[] $columns) Sets the columns to create.
 *
 * @method $this primary(string $column) Sets the column(s) to use for the primary key.
 * @method $this unique(string $column) Sets the column(s) to use for a unique index.
 * @method $this index(string $column) Sets the column(s) to use for an index.
 * @method $this foreign(string|string[] $column, $foreignTable, string|string[] $foreignColumn, $onDelete, $onUpdate) Sets the column(s) to use for an index.
 *
 * @method $this engine(string $engine) Sets the table engine.
 * @method $this charset(string $charset) Sets the character set.
 * @method $this collate(string $collate) Sets the character collation.
 * @method $this opt(string $option, string $value) Sets an option manually.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 */
abstract class CreateHelper {

}
