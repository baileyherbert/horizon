<?php

namespace Horizon\Database\QueryBuilder\Documentation;

/**
 * @method ShowHelper tables() Sets the query to show tables.
 * @method ShowHelper tableStatus() Sets the query to show tables.
 * @method ShowHelper columns(string $table) Sets the query to show tables (optionally against a pattern).
 * @method ShowHelper databases() Sets the query to show databases.
 *
 * @method ShowHelper createTable(string $table) Sets the query to show table creation query.
 *
 * @method string compile() Gets the query as a prepared string.
 * @method array getParameters() Gets an array of parameter values for prepared statements.
 *
 * @method object|int|bool exec() Executes the query.
 * @method object[]|Model[] get() Fetches all rows in the query as objects, or models if configured.
 */
class ShowHelper
{

}