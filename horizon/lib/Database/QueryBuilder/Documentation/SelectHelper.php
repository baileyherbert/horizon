<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method SelectHelper columns(string $name, ...) Sets the columns to select.
 * @method SelectHelper count() Sets the column to COUNT(*).
 *
 * @method SelectHelper from(string $tableName, ...) Sets the table to select from.
 *
 * @method SelectHelper distinct() Sets the distinct condition.
 *
 * @method SelectHelper where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method SelectHelper orWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method SelectHelper andWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 *
 * @method SelectHelper enclose(callable $callback) Encloses statements in parenthesis.
 * @method SelectHelper andEnclose(callable $callback) Encloses statements in parenthesis.
 * @method SelectHelper orEnclose(callable $callback) Encloses statements in parenthesis.
 *
 * @method SelectHelper limit(int $limit) Limits the query to the specified number of rows.
 * @method SelectHelper offset(int $offset) Offsets the results by the specified number of rows.
 *
 * @method SelectHelper orderBy(string $column, string $direction, ...) Orders the results.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 */
class SelectHelper
{

}