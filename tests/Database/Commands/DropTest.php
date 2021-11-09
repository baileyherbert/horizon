<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class DropTest extends TestCase
{

    public function testDropTable()
    {
        $builder = (new QueryBuilder('test_'))->drop();
        $builder->table('tbl')->ifExists();

        $this->assertEquals('DROP TABLE IF EXISTS `test_tbl`;', $builder->compile());
    }

    public function testDropDatabase()
    {
        $builder = (new QueryBuilder('test_'))->drop();
        $builder->database('db')->ifExists();

        $this->assertEquals('DROP DATABASE IF EXISTS `db`;', $builder->compile());
    }

}