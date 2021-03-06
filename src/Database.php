<?php

namespace hugopakula\SimpleDB;

use hugopakula\SimpleDB\Exceptions\RequestException;

abstract class Database {
    protected static $cons = [], $commitKeys = [], $defaultCredentials = [];
    protected $conName = null;
    protected $transaction = false;

    CONST DATABASE_TYPE = null; // Override in extended class
    CONST DEFAULT_CONNECTIONS_LOCATION = __DIR__ . '/../../db_credentials.json';
    CONST DEFAULT_CONNECTION_NAME = 'default';
    CONST DEFAULT_CONNECTION_PORT = null; // Override in extended class

    abstract public function setCon($conName = null, array $altCredentials = []): bool;
    abstract public function query(string $rawQuery): ?Query;
    abstract public function commit(string $commitKey = null);
    abstract public function rollback(RequestException $e = null);

    public function __construct(string $conName = null, array $altCredentials = null) {
        self::loadDefaultCredentials(self::DEFAULT_CONNECTIONS_LOCATION, false);

        if(is_null($altCredentials) || !isset($altCredentials[0]))
            $altCredentials = [];

        $db = $this->setCon($conName, $altCredentials);
        if(!$db)
            throw new RequestException('Invalid Database.');

        if($this->getCon()->inTransaction())
            $this->startTransaction();
    }

    /**
     * method: getCon
     * Returns the current PDO object or null if not set
     *
     * @return null|\PDO
     */
    public function getCon(): ?\PDO {
        if(array_key_exists(static::DATABASE_TYPE, static::$cons)) {
            if(array_key_exists($this->conName, self::$cons[static::DATABASE_TYPE]))
                return self::$cons[static::DATABASE_TYPE][$this->conName];
        }

        return null;
    }

    // Transaction management functions
    public function startTransaction(string $commitKey = null): bool {
        if(is_null($this->getCommitKey())) {
            if($this->transaction)
                $this->rollback();

            if(!is_null($commitKey))
                $this->setCommitKey($commitKey);
        }
    }

    protected function setCommitKey(string $commitKey) {
        if(empty(self::$commitKeys[$this->conName]))
            self::$commitKeys[$this->conName] = $commitKey;
    }

    protected function getCommitKey(): ?string {
        if(array_key_exists($this->conName, self::$commitKeys))
            return self::$commitKeys[$this->conName];

        return null;
    }

    protected function releaseCommit() {
        unset(self::$commitKeys[$this->conName]);
    }

    // All credentials loader
    public static function loadDefaultCredentials(string $file, bool $force = true): bool {
        if(!$force && !empty(self::$defaultCredentials))
            return true;

        if(file_exists($file)) {
            $credentials = file_get_contents($file);
            if($parsed = json_decode($credentials, true)) {
                $defaultCredentials = [];
                self::$defaultCredentials = $defaultCredentials;

                if(!empty($parsed)) {
                    foreach($parsed as $database => $cons) {
                        foreach($cons as $name => $connection) {
                            if(array_key_exists('host', $connection) && array_key_exists('user', $connection)
                                && array_key_exists('pass', $connection) && array_key_exists('db', $connection)) {
                                if(!array_key_exists($database, $defaultCredentials))
                                    $defaultCredentials[$database] = [];

                                $defaultCredentials[$database][$name] = [
                                    'host' => $connection['host'],
                                    'user' => $connection['user'],
                                    'pass' => $connection['pass'],
                                    'db'   => $connection['db'],
                                    'port' => @$connection['port'] ?: static::DEFAULT_CONNECTION_PORT
                                ];
                            }
                        }
                    }
                }

                self::$defaultCredentials = $defaultCredentials;
                return true;
            }
        }

        return false;
    }
}