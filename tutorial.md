# Booosta Mysqli module - Tutorial

## Abstract

This tutorial covers the mysqli module of the Booosta PHP framework. If you are new to this framework, we strongly
recommend, that you first read the [general tutorial of Booosta](https://github.com/buzanits/booosta-installer/blob/master/tutorial/tutorial.md).

## Purpose

The purpose of this module is to provide access to a Mysql or MariaDB database for the framework. When it is active and 
properly configured, every class derived from the Booosta `Base` class has access to the configured database connection.

## Installation

If you follow the instructions in the [installer module](https://github.com/buzanits/booosta-installer), this module is 
automatically installed as a dependency. If for any reason this module is not yet installed, it can be loaded with

```
composer require booosta/mysqli
```

This also loads addtional dependent modules.

## Configuration

This module is configured in the main configuration file `local/config.incl.php`.

```
Framework::$CONFIG = [
# ...
'db_hostname'            => 'mydbhost',
'db_user'                => 'myuser',
'db_password'            => 'my-secret-password',
'db_database'            => 'mydb',
#...
];
```
During installation of Booosta with composer, this values are added to the config file automatically.

## Usage

### Basic methods

Whenever you derive a new class from the Booosta `Base` class (every class in Booosta is usually a child
class of this), this class has a member variable `DB`, which can be accessed with `$this->DB`. It is an
object with several public methods, that provide database access. The most important methods deal with
SQL queries.

```
# query
# executes a SQL query that does not return a result from the DB (like: insert into...)
# returns 0 at success and -1 at failure
$error = $this->DB->query($sql);

# query_value
# executes a SQL query that returns exactly one value (like: select name from user where id=1)
# returns a scalar value - string, int or float
$result = $this->DB->query_value($sql);

# query_value_set
# executes a SQL query that returns an array of columns (like: select name from user)
# returns an array
$result = $this->DB->query_value_set($sql);

# query_list
# executes a SQL query that returns an array representing a single row (like: select * from user where id=1)
# returns an array
$result = $this->DB->query_list($sql, $numerical_index = false);

# query_arrays
# executes a SQL query that returns an array of arrays, each representing a DB row (like: select * from user)
# returns an array
$result = $this->DB->query_arrays($sql);
```

When assigning the result of `query_list` to variables with the `list()` statement, the parameter `$numerical_index` 
has to be set to true, as `list` only works on arrays with numerical indexes:

```
list($firstname, $lastname) = $this->DB->query_list("select firstname, lastname from user where id=1", true);
```

### Variable escaping

Whenever you work with SQL statements in a programming language, there is the risk of injection. Variables passed
by a website to the PHP script can contain malicious code that is inserted in the SQL by variables. To prevent this,
variables can be escaped in the SQL statement. This means they are replaced by a `?`, with an additional parameter
containing the value:
```
$result = $this->DB->query_value("select name from user where id=?", $user_id);
$result = $this->DB->query_arrays("select * from user where id>=? and id<=?", [$first_id, $last_id]);
list($firstname, $lastname) = $this->DB->query_list("select firstname, lastname from user where id=?", $user_id, true);
```
As you see, a single value can be given as a scalar, several values must be put in an array. The `?` are
replaced in the order of their appearance in the $sql string. If you use values from variables in your SQL 
statements you are highly requested to use this escaping, even if you do not use values from user inputs.

### Additional methods

There are some addtional methods, that are not used that often, but can be very useful.

```
# multi_query
# executes a SQL string, that contains multiple queries seperated by `;` (or any other seperator that is
# configured in your DB).
# returns 0 on success and -1 on error
$error = $this->DB->multi_query($sql);

# get_error
# retrieves the last error on the DB connection
$errormsg = $this->DB->get_error();

# last_insert_id
# returns the last id that has been inserted on this DB connection in an AUTO_INCERMENT field.
$id = $this->DB->last_insert_id();

# query_index_array
# returns an array with the first result column as index and the second as value
$result = $this->DB->query_index_array("select id, name from user");
# $result[1] == 'alice'; $result[2] == 'bob';
```

### Transactions

Mysql/MariaDB offer transactions on some storage engines (like InnoDB). You can send the statements dealing
with transactions to the DB using `query()` or you use the methods of this module. The advantage of the latter
is, that if some day transaction handling is changed in Mysql or MariaDB, this must be changed in the module
only and not everywhere in the code.

```
# start transaction
$this->DB->transaction_start();

# commit current transaction
$this->DB->transaction_commit();

# rollback current transaction
$this->DB->transaction_rollback();
```

### Geo coordinates

Mysql/MariaDB have a special data type called `geometry`. It can be used to store coordinates on the earth.
It is rather complicated to handle with SQL statements. While it is of course possible to deal with them with
`query()` or `query_value()` and so on, there are methods in this module that make life easier:

```
# set_geo_coordinates($lat, $lon, $table, $id, $field = "coordinates", $idfield = "id")
# Stores coordinates in a `geometry` field named `$field` in the table `$table`. The record already must exist
# and have the ID `$id`. If the name of the ID field is not `id`, you have to provide it as `$idfield`.
$this->DB->set_geo_coordinates(48.1619487, 16.3842647, 'address', 1911);

# get_geo_coordinates($table, $id, $field = "coordinates", $idfield = "id")
# Reads coordinates from a `geometry` field named `$field` from the table `$table` with ID `$id`. If the name of 
# the ID field is not `id`, you have to provide it as `$idfield`.
list($lat, $lon) = $this->DB->get_geo_coordinates('address', 1911);
```

### Additional methods

```
# DB_tablenames
# returns an array of the names of all tables in the database. If the parameter is omitted, the currently
# configures Database is used
$tables = $this->DB->DB_tablenames($database = null)

# DB_fields
# returns a list of objects representing a column in the table
$fields = $this->DB->DB_fields($tablename);
$fields = $this->DB->DB_fields($database, $tablename);
```



