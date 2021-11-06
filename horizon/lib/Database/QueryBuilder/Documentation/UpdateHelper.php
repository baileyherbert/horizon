<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\Model;

/**
 * @method $this table(string $tableName) Sets the table to target.
 * @method $this values(array $values) Sets values to update.
 *
 * @method $this where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method $this orWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method $this andWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 *
 * @method $this enclose(callable $callback) Encloses statements in parenthesis.
 * @method $this andEnclose(callable $callback) Encloses statements in parenthesis.
 * @method $this orEnclose(callable $callback) Encloses statements in parenthesis.
 *
 * @method $this limit(int $limit) Limits the query to the specified number of rows.
 * @method $this orderBy(string $column, string $direction) Orders the results.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query, returning the number of affected rows.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|Model first() Fetches the first row in the query as an object, or a model if configured.
 */
abstract class UpdateHelper {

}
