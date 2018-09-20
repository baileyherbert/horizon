<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\QueryBuilder\ColumnDefinition;

/**
 * @method CreateHelper table(string $tableName) Sets the table to target.
 *
 * @method CreateHelper column(ColumnDefinition $column) Adds a column to the table.
 * @method CreateHelper columns(ColumnDefinition[] $columns) Sets the columns to create.
 *
 * @method CreateHelper primary(string $column, ...) Sets the column(s) to use for the primary key.
 * @method CreateHelper unique(string $column, ...) Sets the column(s) to use for a unique index.
 * @method CreateHelper index(string $column, ...) Sets the column(s) to use for an index.
 * @method CreateHelper foreign(string|string[] $column, $foreignTable, string|string[] $foreignColumn, $onDelete, $onUpdate) Sets the column(s) to use for an index.
 *
 * @method CreateHelper engine(string $engine) Sets the table engine.
 * @method CreateHelper charset(string $charset) Sets the character set.
 * @method CreateHelper collate(string $collate) Sets the character collation.
 * @method CreateHelper opt(string $option, string $value) Sets an option manually.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 */
class CreateHelper
{

}