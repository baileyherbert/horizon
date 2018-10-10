<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method UpdateHelper table(string $tableName) Sets the table to target.
 * @method UpdateHelper values(array $values) Sets values to update.
 *
 * @method UpdateHelper where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method UpdateHelper orWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method UpdateHelper andWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 *
 * @method UpdateHelper enclose(callable $callback) Encloses statements in parenthesis.
 * @method UpdateHelper andEnclose(callable $callback) Encloses statements in parenthesis.
 * @method UpdateHelper orEnclose(callable $callback) Encloses statements in parenthesis.
 *
 * @method UpdateHelper limit(int $limit) Limits the query to the specified number of rows.
 * @method UpdateHelper orderBy(string $column, string $direction, ...) Orders the results.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query, returning the number of affected rows.
 * @method object[]|\Horizon\Database\Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|\Horizon\Database\Model first() Fetches the first row in the query as an object, or a model if configured.
 */
class UpdateHelper
{

}