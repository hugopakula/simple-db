simple-db
=========

A high-level abstraction for querying databases using the PHP PDO Driver.

Description
===========

Using this class, you can establish and share connections among many queries, re-run queries easily, and easily prevent [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection) attacks.

Usage
=====

After installation and setup, using the connection manager and query manager is simple.

```php
<?php
use hugopakula\SimpleDB;
    
$SQL = new SimpleDB\SQL();
$getUsers = $SQL->query('SELECT id, full_name FROM users');

foreach($getUsers->getResult() as $user) {
    echo 'id: ' . $user['id'] . "\t\t"
    . 'name: ' . $user['full_name'] . "\n";
}
```

The SQL query manager also supports escaped values in queries. When retrieving a single result, pass `$single: true` to the query constructor, or connection querier.
```php
<?php
$currentUser = 12;
$user = $SQL->query('SELECT full_name FROM users WHERE id = ?', [$currentUser], true)->getResult();

echo 'id: ' . $currentUser . "\t\t"
. 'name: ' . $user['full_name'] . "\n"; 
```

Transactions
============

The database manager includes global support for transactions in queries, including extra "commit key" functionality.
Transactions are especially useful for ensuring the [atomicity](https://en.wikipedia.org/wiki/Atomicity_(database_systems)) of your data.
For example:

```php
<?php
use hugopakula\SimpleDB;

$SQL = new simpleDB\SQL();

$payFrom = 12;
$payTo = 17;
$amt = 1500;

try {
    $SQL->startTransaction('payment'); // Locks transaction with a commit key
    
    $SQL->query('INSERT INTO transactions (from, to, amount, ts) VALUES (?, ?, ?, ?)', [$payFrom, $payTo, $amt, time()]);
    $SQL->query('UPDATE user_balances SET balance = balance + ? WHERE user_id = ?', [$amt, $payTo]);
    $SQL->query('UPDATE user_balances SET balance = balance - ? WHERE user_id = ?', [$amt, $payFrom]);
    
    $SQL->commit('payment'); // Commit key must match
} catch(SimpleDB\Exceptions\RollbackException $e) {
    // If a rollback exception is caught, the transaction is automatically rolled back
    echo 'Payment failed!';
    
    // DEVELOPMENT: You can retrieve the failed query as follows:
    // echo $e->getErredQuery()->getRawQuery();
}
```

The benefit of commit keys is when multiple instances of a single database exists, and each attempts to make queries within the same transaction. (Example coming soon)

Versioning
==========

This class is currently in pre-release versions (<1.0.0), however, will adhere to the [Semantic Versioning v2.0.0](https://semver.org/spec/v2.0.0.html) specification when it reaches final release stage. A [changelog](CHANGELOG.md) is provided in this repo for historic reference.

**To-do**
- [x] Add examples to `/demo` and provide sample credentials file
- [x] Add DB type variance and abstraction for connection manager
- [x] Add generic query abstraction
- [ ] Add named escape values to SQL Query manager
- [ ] Abstract credentials loader
- [ ] Add commit keys demo
