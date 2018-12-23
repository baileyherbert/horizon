<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method $this table(string $tableName) Sets the table to target.
 * @method $this database(string $databaseName) Sets the database to target.
 * @method $this ifExists() Sets the query to only drop if the target exists.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 */
abstract class DropHelper
{

}
