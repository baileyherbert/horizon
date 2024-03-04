# Migrations

Horizon offers migrations that can be managed both programatically and through the built-in `ace` command line
interface. These migrations allow you to make incremental revisions to your database schemas during development and
deployment.

## Generating migrations

Use the `ace make:migration` command to create a new migration file. Enter the name of the migration as its argument.
The name is up to you and, while it does not need to be unique, should briefly and accurately describe what the
migration does.

For our example, we will create a new `users` table by running:

```
php ace make:migration users
```

This will create a file with the current timestamp and the migration name, like
`/app/database/migrations/1635902615_users.php`. It will also open the file automatically for supported editors.

## Migration structure

The generated migration will look something like below. Note that the class name must be unchanged.

```php
use Horizon\Database\Migration;
use Horizon\Database\Migration\Schema;
use Horizon\Database\Migration\Blueprint;

/**
 * Creates the users table.
 */
class Migration_1635902615 extends Migration {

	/**
	 * Run the migration.
	 */
	public function up() {
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->string('email');
			$table->string('password');
			$table->timestamps();
		});
	}

	/**
	 * Revert the migration.
	 */
	public function down() {
		Schema::drop('users');
	}

}
```

### Setting the connection

Horizon allows you to specify multiple database connections in the `app/config/database.php` file. Migrations can
choose which connection to use. The default connection for a migration is `main`.

```php
class Migration_1635902615 extends Migration {

	protected $connection = 'main';

}
```

---

## Commands

### Run migrations

The `migration:run` command will execute all outstanding migrations.

```
php ace migration:run
```

### Roll back migrations

The `migration:rollback` command is used to revert the most recent migration batch.

```
php ace migration:rollback
```

Roll back to a specific batch number with the `--batch` option. Batches start at `1`.

```
php ace migration:rollback --batch=1
```

Roll back a specific number of the most recent batches with the `--step` option. For example, passing `2` will revert
the last two migration runs.

```
php ace migration:rollback --step=2
```

Roll back all migrations by passing the `--all` flag or by passing `--batch=0`.

```
php ace migration:rollback --all
```

### Drop all tables and migrate

The `migration:fresh` command will drop all tables and run migrations from the beginning, effectively giving you a
fresh start. Be careful though! This will drop _all_ tables in all databases, not just those created via migrations.

```
php ace migration:fresh
```

### Check migration status

The `migration:status` command checks and displays the current status of migrations.

```
php ace migration:status
```

---

## Tables

### Creating tables

To create a new database table, use the `create()` method on the `Schema` helper. The first argument is the name of the
table, and the second argument is a closure that receives and uses a `Blueprint` instance to build the table.

```php
use Horizon\Database\Migration\Schema;
use Horizon\Database\Migration\Blueprint;

Schema::create('users', function(Blueprint $table) {
	$table->increments('id');
	$table->string('email');
	$table->string('password');
	$table->timestamps();
});
```

### Checking for tables and columns

To check if a table or column exists, use the `hasTable` and `hasColumn` helper methods.

```php
Schema::hasTable('users');
Schema::hasColumn('users', 'email');
```

### Changing database connections

To change which connection is used for a schema operation, use the `connection` method.

```php
Schema::connection('connection_name')->hasTable('users');
```

### Changing table options

The `engine` property on the `Blueprint` instance can be used to change the storage engine for a specific table.

```php
Schema::create('users', function(Blueprint $table) {
    $table->engine = 'InnoDB';
});
```

The `charset` and `collation` properties can be used to specifiy the character set and collation for the created
table.

```php
Schema::create('users', function(Blueprint $table) {
    $table->charset = 'utf8mb4';
    $table->collation = 'utf8mb4_unicode_ci';
});
```

### Updating tables

The `table()` method on the `Schema` helper can also update existing tables. It works just like the `create()` method
for creating new tables.

```php
Schema::table('users', function (Blueprint $table) {
    $table->integer('votes');
});
```

### Renaming tables

The `rename()` method can be used to rename an existing table.

```php
Schema::rename('from', 'to');
```

### Dropping tables

You can use the `drop` and `dropIfExists` methods to drop tables.

```php
Schema::drop('users');
Schema::dropIfExists('users');
```

---

## Migrations API

Horizon offers a class to programatically interact with and query state for migrations in the application.

```php
use Horizon\Database\Migrator;

$migrator = new Migrator();
$migrator->dryRun = false;
$migrator->direction = Migrator::DIRECTION_UP;

$migrations = $migrator->getMigrations();
```

For example, you can check when a migration was executed using the `getMigrationTime()` method. This will return `null`
if the migration hasn't been executed yet.

```php
$migration = $migrations[0];

if ($migrator->getMigrationTime($migration) == null) {
	// The migration hasn't run yet
}
```

The full API is currently undocumented, but there are methods to do just about anything, including running migrations.

The `ace` commands listed above use this migrator class directly. Feel free to look at their
[source code](https://github.com/baileyherbert/horizon/tree/master/src/Ace/Commands/Migrations) to learn more about its
capabilities and usage.
