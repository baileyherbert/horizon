<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class UpdateTest extends TestCase
{

    public function testBasicQuery()
    {
        $builder = (new QueryBuilder('test_'))->update();
        $builder->table('table')->values(array(
            'email' => 'john@doe.org',
            'username' => 'john.doe'
        ));
        $builder->where('id', '=', 1);

        $this->assertEquals('UPDATE `test_table` SET `email` = ?, `username` = ? WHERE `id` = ?;', $builder->compile());
        $this->assertEquals(array('john@doe.org', 'john.doe', 1), $builder->getParameters());
    }

    public function testLimit()
    {
        $builder = (new QueryBuilder('test_'))->update();
        $builder->table('table')->values(array(
            'email' => 'john@doe.org',
            'username' => 'john.doe'
        ));
        $builder->where('id', '=', 1);
        $builder->limit(10);

        $this->assertEquals('UPDATE `test_table` SET `email` = ?, `username` = ? WHERE `id` = ? LIMIT 10;', $builder->compile());
        $this->assertEquals(array('john@doe.org', 'john.doe', 1), $builder->getParameters());
    }

    public function testMultipleOrderBys()
    {
        $builder = (new QueryBuilder('test_'))->update();
        $builder->table('table')->values(array(
            'email' => 'john@doe.org',
            'username' => 'john.doe'
        ));
        $builder->where('id', '=', 1);
        $builder->limit(10);
        $builder->orderBy('id', 'desc', 'username', 'asc');

        $this->assertEquals('UPDATE `test_table` SET `email` = ?, `username` = ? WHERE `id` = ? ORDER BY `id` DESC, `username` ASC LIMIT 10;', $builder->compile());
        $this->assertEquals(array('john@doe.org', 'john.doe', 1), $builder->getParameters());
    }

    public function testEnclosures()
    {
        $builder = (new QueryBuilder('test_'))->update();
        $builder->table('table')->values(array(
            'id' => '5'
        ));

        $builder->where('id', '=', 1);
        $builder->orEnclose(function() use ($builder) {
            $builder->where('id', '>', 1);
            $builder->andWhere('id', '<', 10);
        });

        $this->assertEquals('UPDATE `test_table` SET `id` = ? WHERE `id` = ? OR ( `id` > ? AND `id` < ? );', $builder->compile());
        $this->assertEquals(array('5', 1, 1, 10), $builder->getParameters());
    }

    public function testFunction()
    {
        $builder = (new QueryBuilder('test_'))->update();
        $builder->table('table')->values(array(
            'id' => array('NOW()', 5, 10)
        ));

        $builder->where('id', '=', 1);
        $builder->orEnclose(function() use ($builder) {
            $builder->where('id', '>', 'NOW()');
            $builder->andWhere('id', '<', 10);
        });

        $this->assertEquals('UPDATE `test_table` SET `id` = NOW(?, ?) WHERE `id` = ? OR ( `id` > NOW() AND `id` < ? );', $builder->compile());
        $this->assertEquals(array(5, 10, 1, 10), $builder->getParameters());
    }

}