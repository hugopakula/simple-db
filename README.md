simple-db
=========

A high-level abstraction for querying databases using the PHP PDO Driver.

Description
===========

Using this class, you can establish and share connections among many queries, re-run queries easily, and easily prevent [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection) attacks.

Usage
=====

After installation and setup, using the connection manager and query manager is simple.

    <?php
    use hugopakula\SimpleDB;
    
    $SQL = new SimpleDB\SQL();
    $getUsers = $SQL->query('SELECT id, full_name FROM users');
    $users = $getUsers->getResult();
    
    foreach($users as $user) {
        echo 'id: ' . $user['id'] . "\t\t"
        . 'name: ' . $user['full_name'] . "\n";
    }

The SQL query manager also supports escaped values in queries. When retrieving a single result, pass `$single: true` to the query constructor, or connection querier.

    <?php
    $currentUser = 12;
    $user = $SQL->query('SELECT full_name FROM users WHERE id = ?', [$currentUser], true);
    
    echo 'id: ' . $currentUser . "\t\t"
    . 'name: ' . $user['full_name'] . "\n"; 

Versioning
==========

This class is currently in pre-release versions (<1.0.0), however, will adhere to the [Semantic Versioning v2.0.0](https://semver.org/spec/v2.0.0.html) specification when it reaches final release stage. A [changelog](CHANGELOG.md) is provided in this repo for historic reference.

**To-do**
- [ ] Add examples to `/demo` and provide sample credentials file
- [ ] Add DB type variance and abstraction for connection manager
- [ ] Add generic query abstraction
- [ ] Add named escape values
- [ ] Abstract credentials loader
