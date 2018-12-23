<?php

namespace Horizon\Database\QueryBuilder\Documentation;

use Horizon\Database\Model;

/**
 * @method $this tables() Sets the query to show tables.
 * @method $this tableStatus() Sets the query to show tables.
 * @method $this columns(string $table) Sets the query to show tables (optionally against a pattern).
 * @method $this databases() Sets the query to show databases.
 *
 * @method $this createTable(string $table) Sets the query to show table creation query.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 * @method object|Model first() Fetches the first row in the query as an object, or a model if configured.
 */
abstract class ShowHelper
{

}
