<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method InsertHelper into(string $tableName) Sets the table to insert into.
 * @method InsertHelper values(array $values) Sets values to insert.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 */
class InsertHelper
{

}