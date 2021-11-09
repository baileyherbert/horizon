<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class InsertTest extends TestCase
{

    public function testBasicQuery()
    {
        $builder = (new QueryBuilder('test_'))->insert();
        $builder->into('table')->values(array(
            'id' => 1,
            'username' => 'john.doe'
        ));

        $this->assertEquals('INSERT INTO `test_table` (`id`, `username`) VALUES (?, ?);', $builder->compile());
        $this->assertEquals(array(1, 'john.doe'), $builder->getParameters());
    }

    public function testMultipleInserts()
    {
        $builder = (new QueryBuilder())->insert();
        $builder->into('table')->values(array(
            array('id' => 1, 'username' => 'john.doe'),
            array('id' => 2, 'username' => 'jane.doe')
        ));

        $this->assertEquals('INSERT INTO `table` (`id`, `username`) VALUES (?, ?), (?, ?);', $builder->compile());
        $this->assertEquals(array(1, 'john.doe', 2, 'jane.doe'), $builder->getParameters());
    }

    public function testMultipleInsertsAsParameters()
    {
        $builder = (new QueryBuilder())->insert();
        $builder->into('table')->values(
            array('id' => 1, 'username' => 'john.doe'),
            array('id' => 2, 'username' => 'jane.doe')
        );

        $this->assertEquals('INSERT INTO `table` (`id`, `username`) VALUES (?, ?), (?, ?);', $builder->compile());
        $this->assertEquals(array(1, 'john.doe', 2, 'jane.doe'), $builder->getParameters());
    }

    public function testFunctions()
    {
        $builder = (new QueryBuilder())->insert();
        $builder->into('table')->values(
            array('id' => array('NOW()', 5, 10), 'username' => 'john.doe'),
            array('id' => 2, 'username' => 'jane.doe')
        );

        $this->assertEquals('INSERT INTO `table` (`id`, `username`) VALUES (NOW(?, ?), ?), (?, ?);', $builder->compile());
        $this->assertEquals(array(5, 10, 'john.doe', 2, 'jane.doe'), $builder->getParameters());
    }

}