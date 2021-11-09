<?php

use PHPUnit\Framework\TestCase;
use Horizon\Database\QueryBuilder;
use Horizon\Database\QueryBuilder\ColumnDefinition;

class CreateTest extends TestCase
{

    public function testBasicQuery()
    {
        $builder = (new QueryBuilder('test_'))->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11)->unsigned());
        $builder->column(ColumnDefinition::varChar('username', 64));

        $this->assertEquals('CREATE TABLE `test_users` (`id` INT(11) UNSIGNED NOT NULL, `username` VARCHAR(64) NOT NULL);', $builder->compile());
    }

    public function testBasicQueryWithPrimaryKey()
    {
        $builder = (new QueryBuilder())->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11)->unsigned()->autoIncrements());
        $builder->column(ColumnDefinition::varChar('username', 64));
        $builder->primary('id');

        $this->assertEquals('CREATE TABLE `users` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `username` VARCHAR(64) NOT NULL, PRIMARY KEY (`id`));', $builder->compile());
    }

    public function testBasicQueryWithCompositePrimaryKey()
    {
        $builder = (new QueryBuilder())->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11)->unsigned()->autoIncrements());
        $builder->column(ColumnDefinition::varChar('username', 64));
        $builder->primary('id', 'username');

        $this->assertEquals('CREATE TABLE `users` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `username` VARCHAR(64) NOT NULL, PRIMARY KEY (`id`, `username`));', $builder->compile());
    }

    public function testBasicQueryWithIndex()
    {
        $builder = (new QueryBuilder())->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11)->unsigned()->autoIncrements());
        $builder->column(ColumnDefinition::varChar('username', 64));
        $builder->primary('id');
        $builder->index('username');

        $this->assertEquals('CREATE TABLE `users` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `username` VARCHAR(64) NOT NULL, PRIMARY KEY (`id`), INDEX `users_username` (`username`));', $builder->compile());
    }

    public function testBasicQueryWithCompositeIndex()
    {
        $builder = (new QueryBuilder())->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11)->unsigned()->autoIncrements());
        $builder->column(ColumnDefinition::varChar('username', 64));
        $builder->primary('id');
        $builder->index('username', 'id');

        $this->assertEquals('CREATE TABLE `users` (`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, `username` VARCHAR(64) NOT NULL, PRIMARY KEY (`id`), INDEX `users_username_id` (`username`, `id`));', $builder->compile());
    }

    public function testBasicQueryWithOptions()
    {
        $builder = (new QueryBuilder())->create();
        $builder->table('users');
        $builder->column(ColumnDefinition::integer('id', 11));
        $builder->primary('id');

        $builder->engine('InnoDB');
        $builder->charset('charset');
        $builder->collate('coll');

        $this->assertEquals('CREATE TABLE `users` (`id` INT(11) NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB CHARACTER SET = charset COLLATE = coll;', $builder->compile());
    }

    public function testForeignKey()
    {
        $builder = (new QueryBuilder('p_'))->create();
        $builder->table('messages');
        $builder->column(ColumnDefinition::integer('sender_id', 11));
        $builder->column(ColumnDefinition::integer('recipient_id', 11));
        $builder->foreign('sender_id', 'users', 'id');
        $builder->foreign('recipient_id', 'users', 'id');

        $this->assertEquals('CREATE TABLE `p_messages` (`sender_id` INT(11) NOT NULL, `recipient_id` INT(11) NOT NULL, FOREIGN KEY `fk_messages_sender_id_users_id` (`sender_id`) REFERENCES `p_users` (`id`), FOREIGN KEY `fk_messages_recipient_id_users_id` (`recipient_id`) REFERENCES `p_users` (`id`));', $builder->compile());
    }

    public function testAdvancedForeignKey()
    {
        $builder = (new QueryBuilder('p_'))->create();
        $builder->table('messages');
        $builder->column(ColumnDefinition::integer('sender_id', 11));
        $builder->foreign(array('sender_id', 'username'), 'users', array('id', 'username'), 'CASCADE', 'RESTRICT');

        $this->assertEquals('CREATE TABLE `p_messages` (`sender_id` INT(11) NOT NULL, FOREIGN KEY `fk_messages_sender_id_username_users_id_username` (`sender_id`, `username`) REFERENCES `p_users` (`id`, `username`) ON DELETE CASCADE ON UPDATE RESTRICT);', $builder->compile());
    }

}