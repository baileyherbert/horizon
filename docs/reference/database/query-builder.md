# Query builder

While you can manually type your queries, it is not recommended. Not only do queries typically look ugly in code,
but writing them yourself creates extra work if you wish to stay secure.

For this reason, Horizon has a complete query builder, equipped with automatic prepared statements and support for
virtually every type of query.

## Global access

There are two global classes in the root namespace called `Database` and `DB` which point to Horizon's internal
query builder interface. Use these to get started.

## Queries

To start a query builder instance, you must call the method for the type of query you want.

```php
Database::alter()
Database::create()
Database::delete()
Database::drop()
Database::insert()
Database::select()
Database::show()
Database::update()
```

To run a query without using the query builder, call the `query()` method.

```php
Database::query('SELECT * FROM users');
```

### Alter

|Method|Description|
|---|---|
|`table($tableName)`|Sets the table to alter.|
|`addColumn($column, $after)`|Adds a column to the table.|
|`modifyColumn($column, $after)`|Changes the properties of a column.|
|`changeColumn($column, $newName, $after)`|Changes the name and properties of a column.|
|`dropColumn($columnName)`|Removes a column.|
|`addPrimaryKey($column,...)`|Adds a primary key.|
|`addIndex($column,...)`|Adds an index.|
|`addUniqueIndex($column,...)`|Adds a unique index.|
|`dropPrimaryKey()`|Drops the current primary key.|
|`dropIndex($name)`|Drops an index.|
|`dropForeignKey($name)`|Drops a foreign key.|
|`rename($newTableName)`|Changes the table name.|
|`engine($engine)`|Sets the table engine.|
|`charset($charset)`|Sets the character set.|
|`collate($collate)`|Sets the character collation.|
|`opt($option, $value)`|Sets an option manually.|

### Create

|Method|Description|
|---|---|
|`table($tableName)`|Sets the table to target.|
|`column($column)`|Adds a column to the table.|
|`columns($columns)`|Sets the columns to create.|
|`primary($column, ...)`|Sets the column(s) to use for the primary key.|
|`unique($column, ...)`|Sets the column(s) to use for a unique index.|
|`index($column, ...)`|Sets the column(s) to use for an index.|
|`engine($engine)`|Sets the table engine.|
|`charset($charset)`|Sets the character set.|
|`collate($collate)`|Sets the character collation.|
|`opt($option, $value)`|Sets an option manually.|

### Delete

|Method|Description|
|---|---|
|`from($tableName)`|Sets the table to delete from.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback, $operator)`|Encloses statements in parenthesis.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`orWhere($column, $operator, $equals)`|Creates a match condition.|
|`andWhere($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback)`|Encloses statements in parenthesis.|
|`andEnclose($callback)`|Encloses statements in parenthesis.|
|`orEnclose($callback)`|Encloses statements in parenthesis.|
|`limit($limit)`|Limits the query to the specified number of rows.|
|`orderBy($column, $direction, ...)`|Orders the results.|

### Drop

|Method|Description|
|---|---|
|`table($tableName)`|Sets the table to target.|
|`database($databaseName)`|Sets the database to target.|
|`ifExists()`|Sets the query to only drop if the target exists.|

### Insert

|Method|Description|
|---|---|
|`into($tableName)`|Sets the table to insert into.|
|`values($values)`|Sets values to insert.|

### Select

|Method|Description|
|---|---|
|`columns($name, ...)`|Sets the columns to select.|
|`count()`|Sets the column to COUNT(*).|
|`from($tableName)`|Sets the table to select from.|
|`distinct()`|Sets the distinct condition.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback, $operator)`|Encloses statements in parenthesis.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`orWhere($column, $operator, $equals)`|Creates a match condition.|
|`andWhere($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback)`|Encloses statements in parenthesis.|
|`andEnclose($callback)`|Encloses statements in parenthesis.|
|`orEnclose($callback)`|Encloses statements in parenthesis.|
|`limit($limit)`|Limits the query to the specified number of rows.|
|`orderBy($column, $direction, ...)`|Orders the results.|

### Show

|Method|Description|
|---|---|
|`tables()`|Sets the query to show tables.|
|`tableStatus()`|Sets the query to show tables.|
|`columns($table)`|Sets the query to show tables (optionally against a pattern).|
|`databases()`|Sets the query to show databases.|
|`createTable($table)`|Sets the query to show table creation query.|

### Update

|Method|Description|
|---|---|
|`table($tableName)`|Sets the table to target.|
|`values($values)`|Sets values to update.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback, $operator)`|Encloses statements in parenthesis.|
|`where($column, $operator, $equals)`|Creates a match condition.|
|`orWhere($column, $operator, $equals)`|Creates a match condition.|
|`andWhere($column, $operator, $equals)`|Creates a match condition.|
|`enclose($callback)`|Encloses statements in parenthesis.|
|`andEnclose($callback)`|Encloses statements in parenthesis.|
|`orEnclose($callback)`|Encloses statements in parenthesis.|
|`limit($limit)`|Limits the query to the specified number of rows.|
|`orderBy($column, $direction, ...)`|Orders the results.|

## Getting results

Once you've built your queries using the methods above, you have a few options.

### Execution

For modification queries (`INSERT`, `UPDATE`, `ALTER`, etc), you should use `exec`. This will return the number of affected
rows if applicable, or a boolean for success.

```php
$query->exec();
```

### Fetch rows

To execute the query and get all returned rows as objects (or models if using via ORM), call the `get` method.

```php
$rows = $query->get();
```

### Compile

Compiles the query into a string which you may use for your own purposes.

```php
$query->compile();
```

#### Prepared parameters

Mainly for internal use, if the compiled query has any `?` bindings, this array contains their real values.

```php
$query->getParameters();
```

## Features

### Enclosures

Encloses are parenthesis around conditions to form more advanced statements. And yes, you can put enclosures inside
other enclosures.

```php
// SELECT * FROM `table` WHERE ( `id` = ? OR `balance` >= ? )
// OR ( `id` = ? OR `balance` > ? );

$query->enclose(function($builder) {
    $builder->where('id', '=', 10);
    $builder->orWhere('balance', '>=', 1000);
});

$query->orEnclose(function($builder) {
    $builder->where('id', '=', 9);
    $builder->orWhere('balance', '>', 10000);
});
```

### Functions

To supply functions in where conditions, such as `NOW()`, use a string. For functions with parameters, use an array.

```php
// WHERE `time` = NOW()
$query->where('time', '=', 'NOW()');

// WHERE `time` = NOW(10, 20)
$query->where('time', '=', array('NOW()', 10, 20));
```

### Raw references

The `Horizon\Database\QueryBuilder\RawReference` helper class can be used to insert a raw segment into a query. This is
a fallback for more complicated cases where the query builder might not handle it properly.

```php
$query->where('time', '=', new RawReference('NOW()'));
```

The global `db_verbatim()` helper function builds upon this concept, allowing you to pass multiple values which will
be inserted directly into the query, delimited by spaces.

```php
Database::update()->values(array(
    'views' => db_verbatim(db_ref('views'), '+1')
));
```

### Column references

The global `db_ref()` helper function can be used to insert a column name into a query. This is useful when referencing
a column in your query.

```php
$query->where('balance', '>=', db_ref('minimum_balance'));
```

## Examples

### Selecting rows

```php
Database::select()
    ->columns('username', 'email')
    ->from('users')
    ->where('id', '=', 5)
    ->andWhere('deleted', '=', false);
```

#### Joins

```php
Database::select()
    ->columns('table1.a', 'table2.b')
    ->from('table1', 'table2')
```

### Inserting rows

```php
Database::insert()
    ->into('table')
    ->values(array(
        'id' => 1,
        'username' => 'john.doe'
    ));
```

You can also supply an array of arrays into `values` for multiple inserts at once.

```php
->values(array(
    array('id' => 1, 'username' => 'john.doe'),
    array('id' => 2, 'username' => 'jane.doe')
));
```

### Updating rows

```php
Database::update()
    ->table('table')
    ->values(array(
        'email' => 'john@doe.org',
        'username' => 'john.doe'
    ))
    ->where('id', '=', 1);
```

### Deleting rows

```php
Database::delete()
    ->from('table')
    ->where('id', '=', 1)
    ->limit(1);
```
