<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;

class QueryBuilderTest extends TestCase
{

    public function testPrefix()
    {
        $builder = new QueryBuilder('test_');
        $this->assertEquals('test_', $builder->getPrefix());

        $builder->setPrefix('test2.');
        $this->assertEquals('test2.', $builder->getPrefix());
    }

    public function testSetCommand()
    {
        $builder = new QueryBuilder();
        $this->assertEquals($builder, $builder->select());
    }

}