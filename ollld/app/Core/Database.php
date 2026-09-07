<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

final class Database
{
    private $config;
    private $schemaPath;
    private $connection;

    public function __construct(array $config, $schemaPath)
    {
        $this->config = $config;
        $this->schemaPath = $schemaPath;
        $this->connection = null;
    }

    public function connection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createConnection();
        }

        return $this->connection;
    }

    public function ensureSchema()
    {
        $this->ensureDatabaseExists();
        $this->runSchemaFile();
    }

    public function tableCount($table)
    {
        $safeTable = str_replace('`', '', (string) $table);
        $row = $this->fetchOne('SELECT COUNT(*) AS aggregate_count FROM `' . $safeTable . '`');

        return $row ? (int) $row['aggregate_count'] : 0;
    }

    public function fetchAll($sql, array $params = array())
    {
        $statement = $this->prepareAndExecute($sql, $params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($sql, array $params = array())
    {
        $statement = $this->prepareAndExecute($sql, $params);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ? $row : null;
    }

    public function fetchValue($sql, array $params = array())
    {
        $row = $this->fetchOne($sql, $params);

        if (! $row) {
            return null;
        }

        return reset($row);
    }

    public function execute($sql, array $params = array())
    {
        $statement = $this->prepareAndExecute($sql, $params);

        return $statement->rowCount();
    }

    public function insert($table, array $data)
    {
        $columns = array_keys($data);
        $quotedColumns = array();
        $placeholders = array();
        $params = array();

        foreach ($columns as $column) {
            $quotedColumns[] = '`' . str_replace('`', '', $column) . '`';
            $placeholders[] = ':' . $column;
            $params[$column] = $data[$column];
        }

        $sql = 'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $this->execute($sql, $params);

        return (int) $this->connection()->lastInsertId();
    }

    private function createConnection()
    {
        $this->ensureDatabaseExists();

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $connection = new PDO($dsn, $this->config['username'], $this->config['password'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));

        return $connection;
    }

    private function ensureDatabaseExists()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['charset']
        );

        $connection = new PDO($dsn, $this->config['username'], $this->config['password'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));

        $databaseName = str_replace('`', '``', $this->config['database']);
        $connection->exec(
            'CREATE DATABASE IF NOT EXISTS `' . $databaseName . '` CHARACTER SET ' . $this->config['charset'] . ' COLLATE utf8mb4_unicode_ci'
        );
    }

    private function runSchemaFile()
    {
        if (! file_exists($this->schemaPath)) {
            throw new \RuntimeException('Database schema file not found: ' . $this->schemaPath);
        }

        $contents = file_get_contents($this->schemaPath);

        if ($contents === false) {
            throw new \RuntimeException('Database schema file could not be read.');
        }

        $statements = preg_split('/;\s*(?:\r?\n|$)/', $contents);

        foreach ($statements as $statement) {
            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            $this->connection()->exec($statement);
        }
    }

    private function prepareAndExecute($sql, array $params)
    {
        $statement = $this->connection()->prepare($sql);

        foreach ($params as $key => $value) {
            $statement->bindValue(is_int($key) ? $key + 1 : ':' . ltrim($key, ':'), $value);
        }

        $statement->execute();

        return $statement;
    }
}
