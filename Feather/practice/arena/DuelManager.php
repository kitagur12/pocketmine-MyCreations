<?php

namespace practice\arena;

use practice\utils\yaml;
use practice\utils\PlayerDataManager;
use practice\duel\PlayerDuel;
use practice\player\PlayerQueue;
use practice\player\PracticePlayer;
use practice\party\Party;

class DuelManager
{
    public static function createPlayerDuel(array $player1, array $player2,  array $info): void
    {
        $config = yaml::getconfig();
        $players = [];
        foreach ($player1 as $playerss) {
            PracticePlayer::setPlayerRain($playerss, PracticePlayer::$playerdata[$playerss->getXuid()]["settings"]["rain"]);
            $players[] = $playerss;
            PlayerDataManager::setData($playerss, "duelplayer", $player2[0]->getName());
            PlayerDataManager::setdata($playerss, "allive", "true");
            PlayerDataManager::setdata($playerss, "allive2", "true");
            PlayerDataManager::setData($playerss, "team", "red");
            PlayerQueue::leaveQueue($playerss);
        }
        foreach ($player2 as $playerss) {
            PracticePlayer::setPlayerRain($playerss, PracticePlayer::$playerdata[$playerss->getXuid()]["settings"]["rain"]);
            $players[] = $playerss;
            PlayerDataManager::setData($playerss, "duelplayer", $player1[0]->getName());
            PlayerDataManager::setdata($playerss, "allive", "true");
            PlayerDataManager::setdata($playerss, "allive2", "true");
            PlayerDataManager::setData($playerss, "team", "blue");
            PlayerQueue::leaveQueue($playerss);
        }

        if ($info["ranked"] == "ranked") {
            $info["ranked"] = true;
        } else {
            $info["ranked"] = false;
        }
        if (isset($config["games"]["duel"][$info["game"]])) {
            $type = "duel";
        } else {
            $type = "party";
        }
        $dueldata["id"] = bin2hex(random_bytes(64));
        $dueldata["red"] = $player1;
        $dueldata["blue"] = $player2;
        $dueldata["players"] = $players;
        $dueldata["game"] = $info["game"];
        $dueldata["ranked"] = $info["ranked"];
        $dueldata["isparty"] = $info["isparty"] ?? false;
        $dueldata["split"] = $info["split"] ?? false;
        $dueldata["map"] = $config["games"][$type][$info["game"]]["map"];
        $dueldata["mapid"] = $info["mapid"] ?? self::getRandomMapId($config["games"][$type][$info["game"]]["map"]);
        //---------------------------------------------------
        if ($dueldata["split"]) {
            $dueltype = $config["games"][$type][$dueldata["game"]]["dueltype"];
            if ($dueltype == "score") {
                $dueldata["maxscore"] = Party::$parties[PlayerDataManager::getData($player1[0], "party_name")][$dueldata["game"]]["score"];
            }
        }
        if ($dueldata["isparty"]) {
            Party::$parties[PlayerDataManager::getData($player1[0], "party_name")]["inqueue"] = false;
            Party::$parties[PlayerDataManager::getData($player2[0], "party_name")]["inqueue"] = false;
        }
        PlayerDuel::createPlayerDuel($dueldata);
    }

    public static function createBotDuel($player, array $info): void
    {
        $config = yaml::getconfig();
        $duelplayers = [];
    }

    public static function getRandomMapId($map): int
    {
        $config = yaml::getconfig();
        $maps = $config["map"][$map]["map"];
        if (is_string($maps)) {
            $maps = [$maps];
        }
        $count = count($maps) - 1;
        $int = mt_rand(0, $count);
        return $int;
    }
}
