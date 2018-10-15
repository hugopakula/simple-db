<?php

namespace hugopakula\SimpleDB\MongoDB;

use hugopakula\SimpleDB\Database;

class Query extends \hugopakula\SimpleDB\Query {
    public function __construct(Database &$db, string $rawQuery) {
        parent::__construct($db, $rawQuery);
    }

    public function executeRaw(string $rawQuery) {
        // TODO: Implement executeRaw() method.
    }
}