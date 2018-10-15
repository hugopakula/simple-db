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

    public function makeResultsPreviousResults() {
        $this->prevQuery       = $this->query;
        $this->prevError       = $this->error;
        $this->prevResult      = $this->result;
        $this->prevRawQuery    = $this->rawQuery;
        $this->prevTransaction = $this->transaction;

        $this->query    = null;
        $this->error    = null;
        $this->result   = null;
        $this->rawQuery = null;
    }

    /**
     * Method: getInsertId
     * Returns insert ID for last request if of type INSERT, UPDATE or DELETE; null otherwise.
     *
     * @param bool $prev
     * @return int|null
     */
    public function getInsertId(bool $prev = false): ?int {
        switch($prev) {
            case true:
                return is_array($this->prevResult) && array_key_exists('insert_id', $this->prevResult)
                    ? $this->prevResult['insert_id']
                    : null;
            case false:
                return is_array($this->result) && array_key_exists('insert_id', $this->result)
                    ? $this->result['insert_id']
                    : null;
        }

    }

    /**
     * Method: getAffectedRows
     * Returns number of affected rows for last request if of type INSERT, UPDATE or DELETE; null otherwise.
     *
     * @param bool $prev
     * @return int|null
     */
    public function getAffectedRows(bool $prev = false): ?int {
        switch($prev) {
            case true:
                return is_array($this->prevResult) && array_key_exists('rows_affected', $this->prevResult)
                    ? $this->prevResult['rows_affected']
                    : null;
            case false:
                return is_array($this->result) && array_key_exists('rows_affected', $this->result)
                    ? $this->result['rows_affected']
                    : null;
        }
    }

    /**
     * Method: getRawQuery
     * Returns the (current/prev) raw query. $unsafeSimulateEscaped should only be used in development;
     * see SQL::unsafeFillEscapeValues() for functionality details.
     *
     * @param bool $prev
     * @param bool $unsafeSimulateEscaped
     * @return null|string
     */
    public function getRawQuery(bool $prev = false, $unsafeSimulateEscaped = false): ?string {
        switch($prev) {
            case true:
                return is_string($this->prevRawQuery)
                    ? ($unsafeSimulateEscaped && $this->prevEscaped
                        ? SQL::unsafeFillEscapeValues($this->prevRawQuery, $this->prevEscaped)
                        : $this->prevRawQuery)
                    : null;
            case false:
                return is_string($this->rawQuery)
                    ? ($unsafeSimulateEscaped && $this->escaped
                        ? SQL::unsafeFillEscapeValues($this->rawQuery, $this->escapeValues)
                        : $this->rawQuery)
                    : null;
        }
    }

    public function getResult(bool $prev = false) { // result | error
        if($prev)
            return $this->prevResult;

        return $this->result;
    }

    public function getError(bool $prev = false): ?\hugopakula\SimpleDB\QueryError {
        switch($prev) {
            case true:
                return $this->prevError instanceof QueryError
                    ? $this->prevError
                    : null;
            case false:
                return $this->error instanceof QueryError
                    ? $this->error
                    : null;
        }
    }

    public function getQuery(bool $prev = false): ?\PDOStatement {
        switch($prev) {
            case true:
                return $this->prevQuery instanceof \PDOStatement
                    ? $this->prevQuery
                    : null;
            case false:
                return $this->query instanceof \PDOStatement
                    ? $this->query
                    : null;
        }
    }

    public function getExecutions(): int {
        return $this->executions;
    }

    public function __toString() {
        return $this->getRawQuery();
    }
}