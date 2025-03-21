<?php

namespace practice\duel;

use practice\utils\yaml;
use practice\arena\DuelManager;
use practice\party\Party;

class Request
{
    public static array $requestdata = [];

    public static function createRequest($sender, $recipient, $game): void
    {
        $sxuid = $sender->getXuid();
        $name = $sender->getName();
        $rxuid = $recipient->getXuid();
        $time = time();
        self::$requestdata[$rxuid][$name]["time"] = $time;
        self::$requestdata[$rxuid][$name]["game"] = $game;
    }

    public static function checkRequest($player)
    {
        $xuid = $player->getXuid();
        $data = self::$requestdata[$xuid] ?? [];
        $time = time();
        foreach ($data as $name => $item) {
            if ( $time - $item["time"] > 300) {
                unset(self::$requestdata[$xuid][$name]);
            }
        }
        return self::$requestdata[$xuid] ?? [];
    }

    public static function acceptRequest($player, $sender)
    {
        $config = yaml::getconfig();
        $gamemode = $config["games"]["duel"];
        $id = [];
        foreach ($gamemode as $game => $value) {
            $id[] = $game;
        }
        $game = self::$requestdata[$player->getXuid()][$sender->getName()]["game"];
        unset(self::$requestdata[$player->getXuid()][$sender->getName()]);
        $info["ranked"] = "unranked";
        $info["game"] = $id[$game];
        DuelManager::createPlayerDuel([$player], [$sender], $info);
    }

    public static function partycreateRequest($sender, $recipient, $game): void
    {
        $time = time();
        self::$requestdata[$recipient][$sender]["time"] = $time;
        self::$requestdata[$recipient][$sender]["game"] = $game;
    }

    public static function partycheckRequest($partyid)
    {
        $data = self::$requestdata[$partyid] ?? [];
        $time = time();
        foreach ($data as $name => $item) {
            if ( $time - $item["time"] > 300) {
                unset(self::$requestdata[$partyid][$name]);
            }
        }
        return self::$requestdata[$partyid] ?? [];
    }

    public static function partyacceptRequest($partyid, $sender)
    {
        $config = yaml::getconfig();
        $id = [];
        $gamemode = $config["games"]["duel"];
        foreach ($gamemode as $game => $value) {
            $id[] = $game;
        }
        $gamemode = $config["games"]["party"];
        foreach ($gamemode as $game => $value) {
            $id[] = $game;
        }
        $game = self::$requestdata[$partyid][$sender]["game"];
        unset(self::$requestdata[$partyid][$sender]);
        $info["ranked"] = "unranked";
        $info["game"] = $game;
        DuelManager::createPlayerDuel(Party::$parties[$partyid]["players"], Party::$parties[$sender]["players"], $info);
    }
}
