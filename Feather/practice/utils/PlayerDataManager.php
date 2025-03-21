<?php

namespace practice\utils;

use practice\utils\console;

class PlayerDataManager
{
    public static array $playerData = [];

    public static function setData($player, string $key, $value, $isshow = true): void
    {
        $logger = new console();
        $uuid = self::uuid($player);
        if (!isset(self::$playerData[$uuid])) {
            self::$playerData[$uuid] = [];
        }
        self::$playerData[$uuid][$key] = $value;
        if ($isshow) {
            $logger->splv("$uuid > $key:$value");
        }
    }

    public static function getData($player, string $key, $isshow = true)
    {
        $uuid = self::uuid($player);
        $return = self::$playerData[$uuid][$key] ?? "null";
        if ($isshow) {
            $logger = new console();
            $logger->gplv("$uuid > " . $key . ":" . $return);
        }
        return $return;
    }

    public static function removePlayerData($player): void
    {
        $uuid = self::uuid($player);
        unset(self::$playerData[$uuid]);
    }

    public static function hasData($player, string $key): bool
    {
        $uuid = self::uuid($player);
        return isset(self::$playerData[$uuid][$key]);
    }

    public static function uuid($player): string
    {
        if (is_string($player)) {
            return $player;
        } else {
            $uuid = $player->getxuid();
            if ($uuid == "") {
                $uuid = "0000000000000000";
            }
            return $uuid;
        }
    }

    public static function getAllData($player): array
    {
        $uuid = self::uuid($player);
        return self::$playerData[$uuid] ?? [];
    }
}
