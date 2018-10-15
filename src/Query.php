<?php

namespace hugopakula\SimpleDB;

abstract class Query {
    /**
     * @var null|Database
     */
    protected $db = null;

    /**
     * @var null|\PDOStatement
     */
    protected $prevQuery = null;
    /**
     * @var null|\PDOStatement
     */
    protected $query = null;

    /**
     * @var null|string
     */
    protected $prevRawQuery = null;
    /**
     * @var null|string
     */
    protected $rawQuery = null;

    /**
     * @var $prevResult array|null|QueryError
     * @var $result array|null|QueryError
     * @var $prevError QueryError|null
     * @var $error QueryError|null
     */
    protected $prevResult = null;
    protected $result = null;
    protected $prevError = null;
    protected $error = null;

    protected $prevTransaction = null;
    protected $transaction = null;

    protected $executions = 0;

    abstract public function __construct(Database &$db, string $rawQuery);
    abstract public function executeRaw(string $rawQuery);

    abstract public function getResult(bool $prev = false);
    abstract public function getError(): ?QueryError;
}