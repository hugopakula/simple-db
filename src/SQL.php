<?php

namespace hugopakula\SimpleDB;

use hugopakula\SimpleDB\SQL\Query;
use hugopakula\SimpleDB\Exceptions\RequestException;
use hugopakula\SimpleDB\Exceptions\RollbackException;

require_once __DIR__ . '/../autoload.php';

class SQL extends Database {
    CONST DATABASE_TYPE = 'sql';
    CONST DEFAULT_CONNECTION_PORT = 3306;
    CONST ERROR_DUPLICATE_KEY = '23000';

    /**
     * SQL constructor; If a connection has not already been made to the desired database, a new connection will be
     * established. Else, the same connection (PDO object) will be used.
     *
     * @param string|null $database
     * @param array|null $altCredentials
     * @throws RequestException
     */
    public function __construct(string $database = null, array $altCredentials = null) {
        parent::__construct($database, $altCredentials);
    }

    /**
     * Method: query
     * Using the current connection, make a new query. Transactions are inherited through the same connection.
     *
     * @param string $rawQuery
     * @param array $escapeValues
     * @param bool $single
     * @return Query|null
     * @throws RequestException
     * @throws RollbackException
     */
    public function query(string $rawQuery, array $escapeValues = [], bool $single = false): ?\hugopakula\SimpleDB\Query {
        $query = new Query($this, $rawQuery, empty($escapeValues) ? [] : $escapeValues, $single);

        return $query;
    }

    /**
     * Method: startTransaction
     * Start a transaction for current connection (if the connection is not already engaged in a transaction).
     * If this instance of SQL is inside transaction, roll it back prior to starting new transaction.
     *
     * If $commitKey is provided, the same key must be provided again to commit the transaction.
     *
     * @param string $commitKey
     *
     * @return bool
     */
    public function startTransaction(string $commitKey = null): bool {
        parent::startTransaction($commitKey);

        if(!$this->getCon()->inTransaction())
            $this->getCon()->beginTransaction();

        $this->transaction = $this->getCon()->inTransaction();
        return $this->transaction;
    }

    /**
     * Method: commit
     * Commit the current SQL connection's transaction; if a commit key is set, only commit if the passed key equals the lock key.
     *
     * @param string $commitKey
     */
    public function commit(string $commitKey = null) {
        if($this->transaction && (!$this->getCommitKey() || $commitKey == $this->getCommitKey())) {
            $this->getCon()->commit();
            $this->transaction = false;

            $this->releaseCommit();
        }
    }

    /**
     * Method: rollback
     * Rollback current transaction; if rollback is result of failed query, throw RollbackException
     *
     * @param RequestException|null $e
     * @throws RollbackException
     */
    public function rollback(RequestException $e = null) {
        if($this->transaction) {
            $this->getCon()->rollBack();
            $this->transaction = false;

            $this->releaseCommit();

            if(!is_null($e))
                throw new RollbackException('Forced rollback: ' . $e->getMessage(), $e->getCode(), $e->getPrevious(), [], $e->getErredQuery());
        }
    }

    /**
     * method: setCon
     * Sets $this->conName and self::$cons[$this->conName] based on the provided $database.
     * If $database is a string, set con to credentials in self::$defaultCredentials[self::DATABASE_TYPE] at index $database.
     * If $database is an array, it must include indexes: host, user, pass, db.
     * port (optional) - defaults to self::DEFAULT_CONNECTION_PORT
     *
     * @param null|string|array $conName
     * @param array $altCredentials
     * @return bool
     */
    public function setCon($conName = null, array $altCredentials = []): bool {
        $credentials = [];

        if(is_null($conName))
            $conName = self::DEFAULT_CONNECTION_NAME;

        $dbTypeCredentials = array_key_exists(self::DATABASE_TYPE, self::$defaultCredentials)
            ? self::$defaultCredentials[self::DATABASE_TYPE]
            : [];

        if(is_string($conName) && array_key_exists($conName, $dbTypeCredentials))
            $credentials = $dbTypeCredentials[$conName];
        else if(is_array($conName) && array_key_exists('host', $conName) && array_key_exists('user', $conName)
            && array_key_exists('pass', $conName) && array_key_exists('db', $conName))
            $credentials = $conName;

        if(is_array($credentials) && !empty($credentials)) {
            $this->conName = is_string($conName)
                ? $conName
                : (is_null($conName) ? self::DEFAULT_CONNECTION_NAME : null);

            $dsn = 'mysql:host=' . $credentials['host']
            . ';port=' . ($credentials['port'] ?: self::DEFAULT_CONNECTION_PORT)
            . ';dbname=' . (isset($altCredentials[2]) ? $altCredentials[2] : $credentials['db'])
            . ';charset=utf8mb4';

            // TODO: Add charset variance
            // TODO: Add custom exception for failed connection

            if(!array_key_exists(self::DATABASE_TYPE, self::$cons))
                self::$cons[self::DATABASE_TYPE] = [];

            self::$cons[self::DATABASE_TYPE][$this->conName] = new \PDO(
                $dsn,
                isset($altCredentials[0]) ? $altCredentials[0] : $credentials['user'],
                isset($altCredentials[1]) ? $altCredentials[1] : $credentials['pass'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            return true;
        }

        return false;
    }

    /**
     * Method: closeConnections
     * Destroy the PDO objects in self::$cons, thus closing connection.
     */
    public static function closeConnections() {
        if(is_array(self::$cons) && count(self::$cons) >= 1) {
            foreach(self::$cons as $id => $con) {
                self::$cons[$id] = null;
            }
        }
    }

    /**
     * Method: unsafeFillEscapeValues
     * FOR DEVELOPMENT PURPOSES ONLY
     * Fills value placeholders (?) in $query with corresponding value in $escapeValues
     *
     * @param string $query
     * @param array $escapeValues
     * @return string
     */
    public static function unsafeFillEscapeValues(string $query, array $escapeValues): string {
        $query = str_replace('?', '%s', $query);

        foreach($escapeValues as $i => $escapeValue) {
            $escapeValues[$i] = '"' . $escapeValue . '"';
        }

        return vsprintf($query, $escapeValues);
    }
}
