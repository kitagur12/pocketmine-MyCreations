<?php

namespace practice\player;

use practice\utils\sql;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;

class PlayerConfig
{

    private static array $defaltdata;

    public static function createdata($xuid): void
    {
        $name = PlayerDataManager::getData($xuid, "name");
        $ip = PlayerDataManager::getData($xuid, "ip");
        $clientid = PlayerDataManager::getData($xuid, "clientid");
        $deviceid = PlayerDataManager::getData($xuid, "deviceid");
        $deviceos = PlayerDataManager::getData($xuid, "deviceos");
        $lang = PlayerDataManager::getData($xuid, "player_language");
        if (is_null($lang)) {
            $lang = "";
        }
        $lang = explode("_", $lang)[0];
        $platform  = PlayerDataManager::getData($xuid, "platform");
        $currenttime = date("Y-m-d H:i:s");
        $data = [
            "client_info" => [
                "name" => $name,
                "ip" => $ip,
                "clientid" => $clientid,
                "deviceid" => $deviceid,
                "language" => $lang,
                "deviceos" => $deviceos,
                "platform" => $platform,
            ],
            "status" => [
                "first_login" => $currenttime,
                "last_login" => $currenttime,
                "login_count" => 0,
                "kill" => 0,
                "death" => 0,
                "rank" => "member",
                "lastvote" => "2000-1-1 00:00:00",
                "lastboost" => "2000-1-1 00:00:00", //discord
                "playerpoint" => "0",
                "streak" => "0",
            ],
            "practice" => [],
            "rate" => [],
        ];
        $defaltplayerdata = yaml::getconfig();
        $duelgame = $defaltplayerdata["games"]["duel"];
        foreach ($duelgame as $duel => $value) {
            $data["rate"][$duel] = 1000;
        }
        $defaltplayerdata = yaml::getconfig();
        $practicegame = $defaltplayerdata["games"]["practice"];
        foreach ($practicegame as $practice => $value) {
            $data["practice"][$practice] = [];
        }
        $addplayerdata["settings"] = $defaltplayerdata["settings"];
        $addplayerdata["cosmetics"] = $defaltplayerdata["cosmetics"];
        $kitdata = $defaltplayerdata["kits"];
        $ids = implode(",", range(0, 41));
        foreach ($kitdata as $kit => $value) {
            $addplayerdata["kits"][$kit] = $ids;
        }
        $data = array_merge($data, $addplayerdata);
        $data["settings"]["lang"] = $lang;
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        $player_info = [
            "xuid" => $xuid,
            "data" => $json,
        ];
        if ($xuid !== "cdd") {
            sql::allsave("player_info", $player_info);
        } else {
            self::$defaltdata = $player_info;
        }
    }

    public static function createDefaltData()
    {
        $xuid = "cdd";
        self::createdata($xuid);
    }

    public static function syncPlayerData(array &$playerData): mixed
    {
        $rawPlayerData = $playerData;
        $playerData = json_decode($playerData["data"], true);
        $serverData = json_decode(self::$defaltdata["data"], true);
        foreach ($serverData as $key => $value) {
            if (is_array($value)) {
                if (!isset($playerData[$key]) || !is_array($playerData[$key])) {
                    $playerData[$key] = [];
                    print("Add_array: $key\n");
                }
                self::syncNestedData($value, $playerData[$key]);
            } else {
                if (!array_key_exists($key, $playerData)) {
                    $playerData[$key] = $value;
                    print("Add_string: $key\n");
                }
            }
        }
        foreach ($playerData as $key => $value) {
            if (!($key == "item" || $key == "practice")) {

                if (!array_key_exists($key, $serverData)) {
                    unset($playerData[$key]);
                    print("Remove: $key\n");
                }
            }
        }
        $playerData = json_encode($playerData, JSON_UNESCAPED_SLASHES);
        $rawPlayerData["data"] = $playerData;

        return $rawPlayerData;
    }

    private static function syncNestedData(array $default, array &$target): void
    {
        foreach ($default as $key => $value) {
            if (is_array($value)) {
                if (!isset($target[$key]) || !is_array($target[$key])) {
                    $target[$key] = [];
                    print("Add_array: $key\n");
                }
                if (!($key == "item" || $key == "practice")) {
                    self::syncNestedData($value, $target[$key]);
                } else {
                    
                    self::syncNestedkitData($value, $target[$key]);
                }
            } else {
                if (!array_key_exists($key, $target)) {
                    if (!($key == "item" || $key == "practice")) {
                        $target[$key] = $value;
                        print("Add_string: $key\n");
                    }
                }
            }
        }
        foreach ($target as $key => $value) {
            if (!array_key_exists($key, $default)) {
                unset($target[$key]);
                print("Remove: $key\n");
            } elseif (is_array($value)) {
                if (!($key == "item" || $key == "practice")) {
                    self::syncNestedData($default[$key], $target[$key]);
                }
            }
        }
    }

    private static function syncNestedkitData(array $default, array &$target): void {
        foreach ($default as $key => $value) {
            if (!isset($target[$key])) {
                $target[$key] = $value;
            }
        }
    }
    
}
