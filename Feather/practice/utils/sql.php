<?php

namespace practice\utils;

use PDO;
use PDOException;
use practice\utils\console;
use practice\utils\yaml;

class sql
{
    private static $playerdata;
    private static $dbConnection;

    public static function login(): void
    {
        $config = yaml::getconfig();
        $host = $config["database"]["main"]["host"];
        $port = explode(":", $host)[1];
        $username = $config["database"]["main"]["username"];
        $password = $config["database"]["main"]["password"];
        $database = $config["database"]["main"]["schema"];

        try {
            self::$dbConnection = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
            self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::runsql("use $database");
        } catch (PDOException $e) {
        }
    }

    public static function runsql($sql, array $params = [])
    {
        $logger = new Console();
        if (self::$dbConnection === null) {
            $logger->error("データベース接続が初期化されていません。");
            return null;
        }
        if (is_array($params)) {
            $params2 = json_encode($params);
        }
        try {
            $stmt = self::$dbConnection->prepare($sql);
            $stmt->execute($params);
            if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'DESCRIBE') === 0) {
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($result)) {
                    $logger->gsql("SQLクエリの結果が空です。");
                    return null;
                }

                $logger->ssql("sql: " . $sql . "§3" . $params2);
                $logger->gsql($result);
                return $result;
            } else {
                $logger->ssql("sql: " . $sql . "§3" . $params2);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $logger->gsql($result);
                return $result;
            }
        } catch (PDOException $e) {
            $logger->error("SQLクエリ実行エラー: §b" . $sql . "§3" . $params2 . "§4\n" . $e->getMessage());
            return null;
        }
    }




    public static function reset(): void
    {
        self::runsql("DROP TABLE IF EXISTS player_info");
        self::runsql("DROP TABLE IF EXISTS ban_info");
        self::runsql("DROP TABLE IF EXISTS server_log");
        self::runsql("DROP TABLE IF EXISTS regulation_log");
        self::runsql("DROP TABLE IF EXISTS error_log");
        self::runsql("DROP TABLE IF EXISTS server_log");
        self::runsql("CREATE TABLE IF NOT EXISTS player_info (xuid VARCHAR(16) NOT NULL UNIQUE, data text)");
        self::runsql("CREATE TABLE IF NOT EXISTS ban_info (xuid VARCHAR(16) NOT NULL UNIQUE, name text, ip text, deviceid text, clientID text, reason text, banner text, unban TIMESTAMP)");
        self::runsql("CREATE TABLE IF NOT EXISTS server_log (serverid text, content text)");
        self::runsql("CREATE TABLE IF NOT EXISTS regulation_log (xuid VARCHAR(16), staff VARCHAR(16), time TIMESTAMP, action text, reason text, info text)");
        self::runsql("CREATE TABLE IF NOT EXISTS error_log (serverid text, reason text)");
        self::runsql("CREATE TABLE IF NOT EXISTS server_log (xuid VARCHAR(16), content text)");
    }

    public static function save($table, $data, $primaryKey = null): void
    {
        $setClauses = [];
        $parameters = [];
        $insertColumns = [];
        $insertValues = [];
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = :$column";
            $parameters[":$column"] = $value;
            $insertColumns[] = $column;
            $insertValues[] = ":$column";
        }

        $setClause = implode(", ", $setClauses);
        $columns = implode(", ", $insertColumns);
        $values = implode(", ", $insertValues);
        if ($primaryKey) {
            $sql = "INSERT INTO $table ($columns) 
                    VALUES ($values)
                    ON DUPLICATE KEY UPDATE $setClause";
        } else {
            $sql = "INSERT INTO $table ($columns) 
                    VALUES ($values)";
        }

        self::runsql($sql, $parameters);
    }



    public static function add($table, $data): void
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = "INSERT INTO $table (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";

        $parameters = [];
        foreach ($data as $column => $value) {
            $parameters[":$column"] = $value;
        }

        self::runsql($sql, $parameters);
    }


    public static function get($table, $xuid, $column = null)
    {
        $columnQuery = $column === null ? '*' : $column;
        $sql = "SELECT $columnQuery FROM $table WHERE xuid = :xuid";
        $result = self::runsql($sql, [":xuid" => $xuid]);
        if (empty($result)) {
            return null;
        }
        $r = $column !== null && isset($result[0][$column]) ? $result[0][$column] : $result;
        return $r[0];
    }

    public static function search($table, $data, $column = null)
    {
        $logger = new Console();
        $columndata = self::runsql("DESCRIBE $table");
        if ($columndata === false) {
            $logger->error("テーブルのカラム情報を取得できませんでした。SQL: DESCRIBE $table 実行結果: false");
            return null;
        }
        if (is_array($columndata) && !empty($columndata)) {
            $columns = array_map(function ($col) {
                return $col['Field'];
            }, $columndata);
        } else {
            $logger->error("カラム情報が空です。");
            return null;
        }
        $columnsToSearch = is_array($column) ? $column : ($column !== null ? [$column] : $columns);
        $dataToSearch = is_array($data) ? $data : [$data];

        $whereClauses = [];
        $parameters = [];
        $paramIndex = 0;
        foreach ($columnsToSearch as $col) {
            $colClauses = [];
            foreach ($dataToSearch as $value) {
                $param = ":param_{$col}_{$paramIndex}";
                $colClauses[] = "$col LIKE $param";
                $parameters[$param] = "%$value%";
                $paramIndex++;
            }
            $whereClauses[] = '(' . implode(' OR ', $colClauses) . ')';
        }
        $whereClause = implode(' OR ', $whereClauses);
        $sql = "SELECT * FROM $table WHERE $whereClause";
        $result = self::runsql($sql, $parameters);
        return !empty($result) ? $result : null;
    }

    public static function psearch($table, $data, $column = null)
    {
        $logger = new Console();
        $columndata = self::runsql("DESCRIBE $table");
        if ($columndata === false) {
            $logger->error("テーブルのカラム情報を取得できませんでした。SQL: DESCRIBE $table 実行結果: false");
            return null;
        }
        if (is_array($columndata) && !empty($columndata)) {
            $columns = array_map(function ($col) {
                return $col['Field'];
            }, $columndata);
        } else {
            $logger->error("カラム情報が空です。");
            return null;
        }
        $columnsToSearch = is_array($column) ? $column : ($column !== null ? [$column] : $columns);
        $dataToSearch = is_array($data) ? $data : [$data];

        $whereClauses = [];
        $parameters = [];
        $paramIndex = 0;
        foreach ($columnsToSearch as $col) {
            $colClauses = [];
            foreach ($dataToSearch as $value) {
                $param = ":param_{$col}_{$paramIndex}";
                $colClauses[] = "$col = $param";
                $parameters[$param] = "$value";
                $paramIndex++;
            }
            $whereClauses[] = '(' . implode(' OR ', $colClauses) . ')';
        }
        $whereClause = implode(' OR ', $whereClauses);
        $sql = "SELECT * FROM $table WHERE $whereClause";

        $logger->gsql("SQL: $sql");
        $logger->gsql("Parameters: " . json_encode($parameters));
        $result = self::runsql($sql, $parameters);
        return !empty($result) ? $result : null;
    }


    public static function delete(): void {}



    public static function allsave(string $table, array $data): void
    {
        $columns = implode(", ", array_keys($data));
        $values = implode(", ", array_map(function ($value) {
            if ($value === null) {
                return "NULL";
            } elseif (is_bool($value)) {
                return $value ? "true" : "false";
            } elseif (is_numeric($value)) {
                return $value;
            } else {
                return "'" . addslashes($value) . "'";
            }
        }, $data));

        $update = implode(", ", array_map(function ($key) {
            return "$key = VALUES($key)";
        }, array_keys($data)));

        $sql = "INSERT INTO $table ($columns) VALUES ($values) ON DUPLICATE KEY UPDATE $update";

        self::runsql($sql);
    }
}
