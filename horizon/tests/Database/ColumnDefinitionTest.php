<?php


use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\ColumnDefinition;

class ColumnDefinitionTest extends TestCase
{

    public function testIntegerColumn()
    {
        $col = ColumnDefinition::integer('colname', 8);
        $this->assertEquals('`colname` INT(8) NOT NULL', $col->compile());
    }

    public function testIntegerColumnWithParameters()
    {
        $col = ColumnDefinition::integer('colname', 8);
        $col->unsigned();
        $col->zeroFill();

        $this->assertEquals('`colname` INT(8) UNSIGNED ZEROFILL NOT NULL', $col->compile());
    }

    public function testVarCharColumn()
    {
        $col = ColumnDefinition::varChar('colname', 64);
        $this->assertEquals('`colname` VARCHAR(64) NOT NULL', $col->compile());
    }

    public function testVarCharColumnWithParameters()
    {
        $col = ColumnDefinition::varChar('colname', 64);
        $col->charset('charset_name');
        $col->collate('collate_name');

        $this->assertEquals('`colname` VARCHAR(64) CHARACTER SET charset_name COLLATE collate_name NOT NULL', $col->compile());
    }

    public function testOptions()
    {
        $col = ColumnDefinition::varChar('test', 8)->isNull();
        $this->assertEquals('`test` VARCHAR(8) NULL', $col->compile());

        $col = ColumnDefinition::varChar('test', 8)->autoIncrements();
        $this->assertEquals('`test` VARCHAR(8) NOT NULL AUTO_INCREMENT', $col->compile());

        $col = ColumnDefinition::varChar('test', 8)->autoIncrements()->default('hi');
        $this->assertEquals('`test` VARCHAR(8) NOT NULL DEFAULT \'hi\' AUTO_INCREMENT', $col->compile());

        $col = ColumnDefinition::varChar('test', 8)->autoIncrements()->comment('This isn\'t a comment.');
        $this->assertEquals('`test` VARCHAR(8) NOT NULL AUTO_INCREMENT COMMENT \'This isn\\\'t a comment.\'', $col->compile());
    }

}