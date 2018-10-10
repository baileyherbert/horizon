<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method DeleteHelper from(string $tableName) Sets the table to delete from.
 *
 * @method DeleteHelper where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method DeleteHelper enclose(callable $callback, string $operator) Encloses statements in parenthesis.
 *
 * @method DeleteHelper where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method DeleteHelper orWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method DeleteHelper andWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 *
 * @method DeleteHelper enclose(callable $callback) Encloses statements in parenthesis.
 * @method DeleteHelper andEnclose(callable $callback) Encloses statements in parenthesis.
 * @method DeleteHelper orEnclose(callable $callback) Encloses statements in parenthesis.
 *
 * @method DeleteHelper limit(int $limit) Limits the query to the specified number of rows.
 * @method DeleteHelper orderBy(string $column, string $direction, ...) Orders the results.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|\Horizon\Database\Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|\Horizon\Database\Model first() Fetches the first row in the query as an object, or a model if configured.
 */
class DeleteHelper
{

}