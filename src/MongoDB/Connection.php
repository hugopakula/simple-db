<?php

namespace hugopakula\SimpleDB\MongoDB;

use hugopakula\SimpleDB\Database;
use hugopakula\SimpleDB\Exceptions\RequestException;
use hugopakula\SimpleDB\Query;

class Connection extends Database {
    public function setCon($conName = null, array $altCredentials = []): bool {
        // TODO: Implement setCon() method.
    }

    public function query(string $rawQuery): ?Query {
        // TODO: Implement query() method.
    }

    public function commit(string $commitKey = null) {
        // TODO: Implement commit() method.
    }

    public function rollback(RequestException $e = null) {
        // TODO: Implement rollback() method.
    }
}