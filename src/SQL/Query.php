<?php

namespace hugopakula\SimpleDB\SQL;

use hugopakula\SimpleDB\Database;
use hugopakula\SimpleDB\Exceptions\RequestException;
use hugopakula\SimpleDB\Exceptions\RollbackException;

class Query extends \hugopakula\SimpleDB\Query {
    /**
     * @var null|Connection
     */
    private $sql = null;

    private $prevEscaped = null;
    private $escaped = null;

    private $prevEscapeValues = null;
    private $escapeValues = null;

    /**
     * Query constructor.
     * @param Database $db
     * @param string $rawQuery
     * @param array $escapeValues
     * @param bool $single
     * @param bool $execute
     * @throws RequestException
     * @throws RollbackException
     */
    public function __construct(Database &$db, string $rawQuery, array $escapeValues = [], bool $single = false, bool $execute = true) {
        $this->sql = &$db;
        $this->db = $this->sql->getCon();

        if($execute)
            $this->executeRaw($rawQuery, $escapeValues, $single);
    }

    /**
     * Method: execute
     * Helper method for making SQL queries.
     *
     * Pass only $query to execute basic SQL Query.
     * Pass both $query and $params to create new prepared query and execute with first set of values;
     * To make subsequent queries on same prepared statement, pass null $query and parameters to repeat with new values.
     *
     * @param string|null $query
     * @param array $params
     * @param bool $single
     * @return null
     * @throws RequestException
     * @throws RollbackException
     */
    public function executeRaw(string $query = null, array $params = [], bool $single = false) {
        if($this->getExecutions() > 0)
            $this->makeResultsPreviousResults();

        $this->transaction = $this->db->inTransaction();

        try {
            if(is_string($query)) {
                $this->rawQuery = $query;

                if(empty($params)) {
                    $this->escaped = false;
                    $this->_executeBasic($query, $single);
                } else {
                    $this->escaped = true;
                    $this->escapeValues = $params;
                    $this->_executePrepared($query, $params, $single);
                }
            } else if($this->executions > 0 && is_null($query)) {
                if($this->prevEscaped) {
                    if(empty($params)) // If new escape values not provided, repeat query with same values
                        $this->escapeValues = $params = $this->getEscapeValues(true);

                    $this->rawQuery = $this->getRawQuery(true);
                    $this->escaped = true;

                    $this->_executePrepared($this->getRawQuery(), $params, $single);
                } else if(!$this->prevEscaped && empty($params)) {
                    $this->rawQuery = $this->getRawQuery(true);
                    $this->escaped = false;

                    $this->_executeBasic($this->getRawQuery(), $single);
                }
            }

            $this->executions++;
            return $this->getResult();
        } catch(RequestException $e) {
            $this->error = $this->result = new QueryError($e);

            if($this->transaction) {
                $this->sql->rollback($e);
            } else throw $e;
        }
    }

    /**
     * Method: _executePrepared
     * Execute a prepared SQL query; if $query not provided, use the existing prepared statement with new values
     *
     * @param string|null $query
     * @param array $params
     * @param bool $single
     * @throws RequestException
     */
    private function _executePrepared(string $query = null, array $params = [], bool $single) {
        $method = substr(is_string($query) ? $query : $this->getRawQuery(true), 0, 6);

        try {
            if(is_string($query)) {
                $this->query = $this->db->prepare($query);
            } else {
                if(!$this->prevEscaped)
                    throw new RequestException('Could not use previous query because it was not escaped.');
            }

            $this->query->execute($params);

            switch($method) {
                case 'SELECT':
                    if(!$single) $this->result = $this->query->fetchAll(\PDO::FETCH_ASSOC);
                    else $this->result = $this->query->fetch(\PDO::FETCH_ASSOC);
                    break;
                case 'INSERT':
                case 'UPDATE':
                case 'DELETE':
                    $this->result = ['insert_id' => $this->db->lastInsertId(), 'rows_affected' => $this->query->rowCount()];
                    break;
            }
        } catch(\PDOException $e) {
            $this->result = false;

            $code = is_int($e->getCode()) ? $e->getCode() : 0;

            throw new RequestException('Could not ' . $method . ':' . $e->getMessage(), $code, $e, [], $this);
        }
    }

    /**
     * Method: _executeBasic
     * Execute a regular SQL query. Update $this->lastResult.
     *
     * @param string $query
     * @param bool $single
     * @throws RequestException
     */
    private function _executeBasic(string $query, bool $single) {
        $method = substr($query, 0, 6);

        try {
            $this->query = $this->db->query($query);

            switch($method) {
                case 'SELECT':
                    if(!$single) $this->result = $this->query->fetchAll(\PDO::FETCH_ASSOC);
                    else $this->result = $this->query->fetch(\PDO::FETCH_ASSOC);
                    break;
                case 'INSERT':
                case 'UPDATE':
                case 'DELETE':
                    $this->result = ['insert_id' => $this->db->lastInsertId(), 'rows_affected' => $this->query->rowCount()];
                    break;
                case 'CREATE':
                    $this->result = true;
                    break;
            }
        } catch(\PDOException $e) {
            $this->result = false;

            $code = is_int($e->getCode()) ? $e->getCode() : 0;

            throw new RequestException('Could not ' . $method . ': ' . $e->getMessage(), $code, $e, [], $this);
        }
    }



    public function getEscapeValues(bool $prev = false): ?array {
        switch($prev) {
            case true:
                return is_array($this->prevEscapeValues)
                    ? $this->prevEscapeValues
                    : null;
            case false:
                return is_array($this->escapeValues)
                    ? $this->escapeValues
                    : null;
        }
    }

    public function makeResultsPreviousResults() {
        parent::makeResultsPreviousResults();

        $this->prevEscaped      = $this->escaped;
        $this->prevEscapeValues = $this->escapeValues;

        $this->escaped      = null;
        $this->escapeValues = null;
    }

    public function __toString() {
        return $this->getRawQuery();
    }
}
