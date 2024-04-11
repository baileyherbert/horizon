<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\Model;

/**
 * @method $this columns(string $name) Sets the columns to select.
 * @method $this from(string $tableName) Sets the table to select from.
 *
 * @method $this join(string $type, string|null $tableName, string|null $tableAlias, string|null $leftColumn, string|null $rightColumn) Creates a JOIN.
 * @method $this innerJoin(string|null $tableName, string|null $tableAlias, string|null $leftColumn, string|null $rightColumn) Creates an INNER JOIN.
 * @method $this leftJoin(string|null $tableName, string|null $tableAlias, string $leftColumn, string $rightColumn) Creates a LEFT JOIN.
 * @method $this rightJoin(string|null $tableName, string|null $tableAlias, string $leftColumn, string $rightColumn) Creates a RIGHT JOIN.
 *
 * @method $this distinct() Sets the distinct condition.
 *
 * @method $this where(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method $this orWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method $this andWhere(string $column, string $operator, mixed $equals) Creates a match condition.
 * @method $this whereMatch(string $column, string $against, string|null $mode, string|null $separator) Creates a full-text match condition.
 *
 * @method $this enclose(callable $callback) Encloses statements in parenthesis.
 * @method $this andEnclose(callable $callback) Encloses statements in parenthesis.
 * @method $this orEnclose(callable $callback) Encloses statements in parenthesis.
 *
 * @method $this limit(int $limit) Limits the query to the specified number of rows.
 * @method $this offset(int $offset) Offsets the results by the specified number of rows.
 *
 * @method $this orderBy(string $column, string $direction) Orders the results.
 * @method $this orderByMatch(string $direction, int|null $index) Orders the results by a relevancy score from a `whereMatch` statement.
 * @method $this orderByMatches(string $direction, string|null $verb) Orders the results by a combination of relevancy scores from all `whereMatch` statements, using the `GREATEST` verb by default.
 *
 * @method $this forUpdate() Locks selected rows from being read or written to.
 * @method $this forShare() Locks selected rows from being written to.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 * @method $this setModel(string $model) Overrides the model to use for the results.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method void each(callable $callback) Selects rows one by one and executes the given callback for each.
 * @method object|Model first() Fetches the first row in the query as an object, or a model if configured.
 * @method int count() Sets the column to COUNT(*), executes the query, and returns the rows.
 */
abstract class SelectHelper {

}
