<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class SelectTest extends TestCase
{

    public function testBasicQuery()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table')->where('id', '=', 5);

        $this->assertEquals('SELECT * FROM `p_table` WHERE `id` = ?;', $builder->compile());
    }

    public function testColumns()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->columns('a', 'b')->from('table');

        $this->assertEquals('SELECT `a`, `b` FROM `p_table`;', $builder->compile());
    }

    public function testMultipleTables()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->columns('table1.a', 'table2.b')->from('table1', 'table2');

        $this->assertEquals('SELECT `table1`.`a`, `table2`.`b` FROM `p_table1`, `p_table2`;', $builder->compile());
    }

    public function testDistinct()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->distinct()->columns('a', 'b')->from('table');

        $this->assertEquals('SELECT DISTINCT `a`, `b` FROM `p_table`;', $builder->compile());
    }

    public function testLimit()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table')->limit(10);

        $this->assertEquals('SELECT * FROM `table` LIMIT 10;', $builder->compile());
    }

    public function testLimitWithOffset()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table')->limit(10)->offset(20);

        $this->assertEquals('SELECT * FROM `table` LIMIT 10 OFFSET 20;', $builder->compile());
    }

    public function testMultipleWheres()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table');
        $builder->where('id', '=', 10);
        $builder->where('balance', '>=', 1000);

        $this->assertEquals('SELECT * FROM `p_table` WHERE `id` = ? AND `balance` >= ?;', $builder->compile());
    }

    public function testWhereOr()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table');
        $builder->where('id', '=', 10);
        $builder->orWhere('balance', '>=', 1000);

        $this->assertEquals('SELECT * FROM `p_table` WHERE `id` = ? OR `balance` >= ?;', $builder->compile());
    }

    public function testFunction()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table');
        $builder->where('time', '=', 'NOW()');

        $this->assertEquals('SELECT * FROM `p_table` WHERE `time` = NOW();', $builder->compile());
    }

    public function testFunctionWithArgs()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table');
        $builder->where('time', '=', array('NOW()', 10, 20));

        $this->assertEquals('SELECT * FROM `p_table` WHERE `time` = NOW(?, ?);', $builder->compile());
        $this->assertEquals(array(10, 20), $builder->getParameters());
    }

    public function testBasicEnclosure()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');

        $builder->enclose(function($builder) {
            $builder->where('id', '=', 10);
            $builder->orWhere('balance', '>=', 1000);
        });

        $builder->orEnclose(function($builder) {
            $builder->where('id', '=', 9);
            $builder->orWhere('balance', '>', 10000);
        });

        $this->assertEquals('SELECT * FROM `table` WHERE ( `id` = ? OR `balance` >= ? ) OR ( `id` = ? OR `balance` > ? );', $builder->compile());
    }

    public function testNestedEnclosure()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');

        $builder->enclose(function($builder) {
            $builder->enclose(function($builder) {
                $builder->where('id', '=', 10);
                $builder->orWhere('balance', '>=', 1000);
            });

            $builder->andEnclose(function($builder) {
                $builder->where('id', '=', 9);
                $builder->andWhere('balance', '>', 10000);
            });
        });

        $this->assertEquals('SELECT * FROM `table` WHERE ( ( `id` = ? OR `balance` >= ? ) AND ( `id` = ? AND `balance` > ? ) );', $builder->compile());
    }

    public function testMixedQueryWithEnclosure()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');
        $builder->where('x', '=', 1);

        $builder->orEnclose(function($builder) {
            $builder->enclose(function($builder) {
                $builder->where('id', '=', 10);
                $builder->orWhere('balance', '>=', 1000);
            });
        });

        $this->assertEquals('SELECT * FROM `table` WHERE `x` = ? OR ( ( `id` = ? OR `balance` >= ? ) );', $builder->compile());
    }

    public function testSingleOrderBy()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');
        $builder->orderBy('id', 'asc');

        $this->assertEquals('SELECT * FROM `table` ORDER BY `id` ASC;', $builder->compile());
    }

    public function testMultipleOrderBy()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');
        $builder->orderBy('id', 'asc', 'username', 'desc');

        $this->assertEquals('SELECT * FROM `table` ORDER BY `id` ASC, `username` DESC;', $builder->compile());
    }

    public function testRandomOrderBy()
    {
        $builder = (new QueryBuilder())->select();
        $builder->from('table');
        $builder->orderBy('RAND()');

        $this->assertEquals('SELECT * FROM `table` ORDER BY RAND();', $builder->compile());
    }

    public function testGetParameters()
    {
        $builder = (new QueryBuilder('p_'))->select();
        $builder->from('table');
        $builder->where('id', '=', 10);
        $builder->where('balance', '>=', 1000);

        $this->assertEquals(array(10, 1000), $builder->getParameters());
    }

}