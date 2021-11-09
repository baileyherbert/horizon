<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class ShowTest extends TestCase
{

    public function testShowTables()
    {
        $builder = (new QueryBuilder())->show();
        $builder->tables();

        $this->assertEquals('SHOW TABLES;', $builder->compile());
    }

    public function testShowTableStatus()
    {
        $builder = (new QueryBuilder())->show();
        $builder->tableStatus();

        $this->assertEquals('SHOW TABLE STATUS;', $builder->compile());
    }

    public function testShowColumns()
    {
        $builder = (new QueryBuilder('test_'))->show();
        $builder->columns('tbl');

        $this->assertEquals('SHOW COLUMNS FROM `test_tbl`;', $builder->compile());
    }

    public function testShowDatabases()
    {
        $builder = (new QueryBuilder())->show();
        $builder->databases();

        $this->assertEquals('SHOW DATABASES;', $builder->compile());
    }

    public function testShowCreateTable()
    {
        $builder = (new QueryBuilder('test_'))->show();
        $builder->createTable('tbl');

        $this->assertEquals('SHOW CREATE TABLE `test_tbl`;', $builder->compile());
    }

}