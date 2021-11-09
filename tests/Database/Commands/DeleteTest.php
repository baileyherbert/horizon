<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class DeleteTest extends TestCase
{

    public function testBasicQuery()
    {
        $builder = (new QueryBuilder('test_'))->delete();
        $builder->from('table');
        $builder->where('id', '=', 1);

        $this->assertEquals('DELETE FROM `test_table` WHERE `id` = ?;', $builder->compile());
        $this->assertEquals(array(1), $builder->getParameters());
    }

    public function testLimit()
    {
        $builder = (new QueryBuilder('test_'))->delete();
        $builder->from('table');
        $builder->where('id', '=', 1);
        $builder->where('username', '=', 'john.doe');
        $builder->limit(10);

        $this->assertEquals('DELETE FROM `test_table` WHERE `id` = ? AND `username` = ? LIMIT 10;', $builder->compile());
        $this->assertEquals(array(1, 'john.doe'), $builder->getParameters());
    }

    public function testMultipleOrderBys()
    {
        $builder = (new QueryBuilder('test_'))->delete();
        $builder->from('table');
        $builder->where('id', '=', 1);
        $builder->where('username', '=', 'john.doe');
        $builder->limit(10);
        $builder->orderBy('id', 'desc', 'username', 'asc');

        $this->assertEquals('DELETE FROM `test_table` WHERE `id` = ? AND `username` = ? ORDER BY `id` DESC, `username` ASC LIMIT 10;', $builder->compile());
        $this->assertEquals(array(1, 'john.doe'), $builder->getParameters());
    }

    public function testEnclosures()
    {
        $builder = (new QueryBuilder('test_'))->delete();
        $builder->from('table');

        $builder->where('id', '=', 1);
        $builder->orEnclose(function() use ($builder) {
            $builder->where('id', '>', 1);
            $builder->andWhere('id', '<', 10);
        });

        $this->assertEquals('DELETE FROM `test_table` WHERE `id` = ? OR ( `id` > ? AND `id` < ? );', $builder->compile());
        $this->assertEquals(array(1, 1, 10), $builder->getParameters());
    }

    public function testFunction()
    {
        $builder = (new QueryBuilder('test_'))->delete();
        $builder->from('table');

        $builder->where('id', '=', array('NOW()', 10, 20));
        $builder->orEnclose(function() use ($builder) {
            $builder->where('id', '>', 'NOW()');
            $builder->andWhere('id', '<', 10);
        });

        $this->assertEquals('DELETE FROM `test_table` WHERE `id` = NOW(?, ?) OR ( `id` > NOW() AND `id` < ? );', $builder->compile());
        $this->assertEquals(array(10, 20, 10), $builder->getParameters());
    }

}