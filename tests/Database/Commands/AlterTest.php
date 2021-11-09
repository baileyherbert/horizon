<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\ColumnDefinition;

class AlterTest extends TestCase
{

    public function testAddColumn()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('posts');
        $builder->addColumn(ColumnDefinition::integer('id', 11)->unsigned());

        $this->assertEquals('ALTER TABLE `test_posts` ADD `id` INT(11) UNSIGNED NOT NULL;', $builder->compile());
    }

    public function testDropColumn()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('posts');
        $builder->dropColumn('id');

        $this->assertEquals('ALTER TABLE `test_posts` DROP `id`;', $builder->compile());
    }

    public function testAlterColumn()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('posts');
        $builder->modifyColumn(ColumnDefinition::integer('timestamp', 11)->autoIncrements(), 'id');

        $this->assertEquals('ALTER TABLE `test_posts` MODIFY `timestamp` INT(11) NOT NULL AUTO_INCREMENT AFTER `id`;', $builder->compile());
    }

    public function testChangeColumn()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('posts');
        $builder->changeColumn(ColumnDefinition::integer('timestamp', 11)->autoIncrements(), 'timestamp2', 'id');

        $this->assertEquals('ALTER TABLE `test_posts` CHANGE `timestamp` `timestamp2` INT(11) NOT NULL AUTO_INCREMENT AFTER `id`;', $builder->compile());
    }

    public function testMakeColumnFirst()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('posts');
        $builder->changeColumn(ColumnDefinition::integer('timestamp', 11)->autoIncrements(), 'timestamp2', 'FIRST');

        $this->assertEquals('ALTER TABLE `test_posts` CHANGE `timestamp` `timestamp2` INT(11) NOT NULL AUTO_INCREMENT FIRST;', $builder->compile());
    }

    public function testAddPrimaryKey()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('people');
        $builder->addPrimaryKey('id', 'name');

        $this->assertEquals('ALTER TABLE `test_people` ADD PRIMARY KEY (`id`, `name`);', $builder->compile());
    }

    public function testAddIndex()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('people');
        $builder->addIndex('id', 'name');

        $this->assertEquals('ALTER TABLE `test_people` ADD INDEX `people_id_name` (`id`, `name`);', $builder->compile());
    }

    public function testAddUniqueIndex()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('people');
        $builder->addUniqueIndex('id', 'name');

        $this->assertEquals('ALTER TABLE `test_people` ADD UNIQUE INDEX `people_id_name` (`id`, `name`);', $builder->compile());
    }

    public function testAddForeignKey()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('tbl_a');
        $builder->addForeignKey(array('id', 'name'), 'tbl_b', array('id2', 'name2'), 'cascade', 'set null');

        $this->assertEquals('ALTER TABLE `test_tbl_a` ADD FOREIGN KEY `fk_tbl_a_id_name_tbl_b_id2_name2` (`id`, `name`) REFERENCES `test_tbl_b` (`id2`, `name2`) ON DELETE CASCADE ON UPDATE SET NULL;', $builder->compile());
    }

    public function testDropIndexesAndKeys()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('tbla');

        $builder->dropPrimaryKey();
        $builder->dropIndex('tbl_test_index');
        $builder->dropForeignKey('fk_tbla_col_tblb_col');

        $this->assertEquals('ALTER TABLE `test_tbla` DROP PRIMARY KEY, DROP INDEX `tbl_test_index`, DROP FOREIGN KEY `fk_tbla_col_tblb_col`;', $builder->compile());
    }

    public function testRenameTable()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('tbl_a');
        $builder->rename('tbl_b');

        $this->assertEquals('ALTER TABLE `test_tbl_a` RENAME `test_tbl_b`;', $builder->compile());
    }

    public function testChangeEngine()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('tbl_a');
        $builder->engine('InnoDB');

        $this->assertEquals('ALTER TABLE `test_tbl_a` ENGINE = InnoDB;', $builder->compile());
    }

    public function testChangeCharacterSet()
    {
        $builder = (new QueryBuilder('test_'))->alter();
        $builder->table('tbl_a');
        $builder->charset('utf8');
        $builder->collate('utf8_bin');

        $this->assertEquals('ALTER TABLE `test_tbl_a` CHARACTER SET = utf8, COLLATE = utf8_bin;', $builder->compile());
    }

}