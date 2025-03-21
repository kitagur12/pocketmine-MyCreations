<?php

namespace hentai;

use PDO;
use PDOException;
use hentai\info;

class sql
{

    private static $dbConnection;

    public static function login(): void
    {
        $config = info::$config;
        $host = $config['config']['sql']['host'] ?? null;
        $username = $config['config']['sql']['username'] ?? null;
        $password = $config['config']['sql']['password'] ?? null;
        $database = $config['config']['sql']['schema'] ?? null;
        $port = $config['config']['sql']['port'] ?? null;

        try {
            self::$dbConnection = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
            self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::runsql("use $database");
            self::runsql("CREATE TABLE IF NOT EXISTS ban_info (xuid VARCHAR(16) NOT NULL UNIQUE, name text, ip text, deviceid text, clientID text, reason text, banner text, unban int)");
            self::runsql("CREATE TABLE IF NOT EXISTS regulation_log (name text, staff_name text, time int, action text, reason text, info text)");
            self::runsql("CREATE TABLE IF NOT EXISTS player_info (xuid VARCHAR(16) NOT NULL UNIQUE, name text, ip text, deviceid text, clientID text, SelfSignedId text, SkinId text, SkinResourcePatch text)");
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
                return true;
            }
        } catch (PDOException $e) {
            $logger->error("SQLクエリ実行エラー: §b" . $sql . "§3" . $params2 . "§4\n" . $e->getMessage());
            return null;
        }
    }


    public static function reset(): void
    {
        self::runsql("DROP TABLE IF EXISTS ban_info");
        self::runsql("DROP TABLE IF EXISTS regulation_log");
        self::runsql("CREATE TABLE IF NOT EXISTS ban_info (xuid VARCHAR(16) NOT NULL UNIQUE, name text, ip text, deviceid text, clientID text, reason text, banner text, unban int)");
        self::runsql("CREATE TABLE IF NOT EXISTS regulation_log (name text, staff_name text, time int, action text, reason text, info text)");
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
}

class console
{
    private static $infolog;
    private static $debuglog;
    private static $warnlog;
    private static $errorlog;
    private static $ssqllog;
    private static $gsqllog;

    public function __construct()
    {
        $path = "plugin_data/system/config";/*
        self::$infolog = configdata::load($path, "console_infolog");
        self::$debuglog = configdata::load($path, "console_debuglog");
        self::$warnlog = configdata::load($path, "console_warnlog");
        self::$errorlog = configdata::load($path, "console_errorlog");
        self::$gsqllog = configdata::load($path, "console_gsqllog");
        self::$ssqllog = configdata::load($path, "console_ssqllog");*/
    }

    public static function info($value): void
    {
        //if (self::$infolog == "true") {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§7[§finfo§7] §f" . $value . "\n§r");
        //}
    }

    public static function gsql($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§a[§egetSql§a] §e" . $value . "\n§r");
    }

    public static function ssql($value): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§a[§bsendSql§a] §b" . $value . "\n§r");
    }

    public static function debug($value): void
    {
        //if (self::$debuglog == "true") {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§7[§8debug§7] §7" . $value . "\n§r");
        //}
    }

    public static function warn($value): void
    {
        //if (self::$warnlog == "true") {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§e[warn] " . $value . "\n§r");
        //}
    }

    public static function error($value): void
    {
        //if (self::$errorlog == "true") {
        if (is_array($value)) {
            $value = "(array) " . json_encode($value);
        } else {
            $value = $value;
        }
        self::updatetext("§c[§4error§c] §4" . $value . "\n§r");
        //}
    }

    public static function cls(): void
    {
        $n = 1;
        $result = str_repeat("\n", $n);
        print($result);
    }

    public static function updatetext($value): void
    {
        $colorCodes = [
            '§0' => "\033[38;2;0;0;0m",
            '§1' => "\033[38;2;0;0;170m",
            '§2' => "\033[38;2;0;170;0m",
            '§3' => "\033[38;2;0;170;170m",
            '§4' => "\033[38;2;170;0;0m",
            '§5' => "\033[38;2;170;0;170m",
            '§6' => "\033[38;2;255;170;0m",
            '§7' => "\033[38;2;170;170;170m",
            '§8' => "\033[38;2;85;85;85m",
            '§9' => "\033[38;2;85;85;255m",
            '§a' => "\033[38;2;85;255;85m",
            '§b' => "\033[38;2;85;255;255m",
            '§c' => "\033[38;2;255;85;85m",
            '§d' => "\033[38;2;255;85;255m",
            '§e' => "\033[38;2;255;255;85m",
            '§f' => "\033[0;37m",
            '§r' => "\033[0;37m",
            '§g' => "\033[38;2;221;214;5m",
            '§h' => "\033[38;2;227;213;211m",
            '§i' => "\033[38;2;208;203;203m",
            '§j' => "\033[38;2;75;65;66m",
            '§k' => "\033[3;37m",
            '§l' => "\033[4;37m",
            '§m' => "\033[38;2;158;43;29m",
            '§n' => "\033[38;2;184;112;87m",
            '§p' => "\033[38;2;223;180;56m",
            '§q' => "\033[38;2;40;166;72m",
            '§s' => "\033[38;2;86;191;177m",
            '§t' => "\033[38;2;46;83;130m",
            '§u' => "\033[38;2;160;102;199m"
        ];
        $text = str_replace(array_keys($colorCodes), array_values($colorCodes), $value);
        print($text);
    }
}
