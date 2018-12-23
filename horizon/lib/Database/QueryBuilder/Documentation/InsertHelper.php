<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method $this into(string $tableName) Sets the table to insert into.
 * @method $this values(array $values) Sets values to insert.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 */
abstract class InsertHelper
{

}
