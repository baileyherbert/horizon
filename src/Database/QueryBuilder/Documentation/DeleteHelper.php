<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method $this from(string $tableName) Sets the table to delete from.
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
 * @method object|int|bool exec() Executes the query.
 */
abstract class DeleteHelper {

}
