<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method DropHelper table(string $tableName) Sets the table to target.
 * @method DropHelper database(string $databaseName) Sets the database to target.
 * @method DropHelper ifExists() Sets the query to only drop if the target exists.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|\Horizon\Database\Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|\Horizon\Database\Model first() Fetches the first row in the query as an object, or a model if configured.
 */
class DropHelper
{

}