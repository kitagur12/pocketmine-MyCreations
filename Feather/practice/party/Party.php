<?php

namespace practice\party;

use pocketmine\player\Player;
use practice\player\PlayerKits;
use practice\utils\PlayerDataManager;
use practice\practice;
use practice\utils\yaml;
use pocketmine\Server;
use practice\arena\ArenaManaer;
use practice\form\invformapi;
use practice\handler\ChatHandler;
use practice\player\PracticePlayer;

class Party
{

    public static array $parties = [];

    public static function createParty(Player $owner, string $pname, $ispublic): void
    {
        if (PlayerDataManager::getdata($owner, "party") == "true") {
            return;
        }
        $name = bin2hex(random_bytes(64));
        PlayerDataManager::setdata($owner, "party", "true");
        PlayerDataManager::setdata($owner, "vote", "none");
        PlayerDataManager::setdata($owner, "party_name", $name);
        PlayerDataManager::setdata($owner, "party_owner", "true");
        PlayerKits::getkit($owner, "lobby", "party");
        $config = yaml::getconfig();
        $maps = $config["map"];
        $ffa = $config["games"]["ffa"];
        self::$parties[$name] = [
            "owner" => $owner,
            "name" => $pname,
            "players" => [$owner],
            "open" => $ispublic,
            "blacklist" => [],
            "duel_request" => true,
            "partyffa" => true,
            "member_invite" => false,
            "member_select" => false,
            "induel" => false,
            "inqueue" => false,
            "split" => false,
            "partygame" => false,
            "game" => "",
            "vote" => [],
            "invite" => [],
            "gamesettings" => [],
        ];
        foreach ($ffa as $gname => $game) {
            $maps = $config["map"][$game["map"]]["map"];
            practice::CreateAsyncTask("practice\utils\\task\worldcreate", [$maps, $name]);
        }
        $duels = $config["games"]["duel"];
        foreach ($duels as $duel => $game) {
            self::$parties[$name]["vote"][$duel] = 0;
            $dueltype = $config["games"]["duel"][$duel]["dueltype"];
            self::$parties[$name][$duel]["hitdelay"] = $config["games"]["duel"][$duel]["hitdelay"];
            self::$parties[$name][$duel]["maxhp"] = 20;
            self::$parties[$name][$duel]["knockback"]["horizontal"] = "default";
            self::$parties[$name][$duel]["knockback"]["vertical"] = "default";
            self::$parties[$name][$duel]["knockback"]["maxkb"] = "default";
            if ($dueltype == "normal") {
            }
            if ($dueltype == "score") {
                self::$parties[$name][$duel]["score"] = $config["games"]["duel"][$duel]["point"];
            }
            if ($dueltype == "bed") {
                self::$parties[$name][$duel]["nobed"] = false;
                self::$parties[$name][$duel]["respawn"] = 5;
            }
            $cooltime = $config["itemcooltime"][$config["games"]["duel"][$duel]["itemcooltime"]];
            foreach ($cooltime as $itemname => $value) {
                if (stripos($itemname, "slot") == false && $itemname !== "priority_item") {
                    self::$parties[$name][$duel]["cooltime"][$itemname] = $value;
                }
            }
        }
    }

    public static function addPlayer(string $partyName, Player $player): void
    {
        $members = self::$parties[$partyName]["players"];
        if (in_array($player, $members)) {
            return;
        }
        if (in_array($player->getName(), self::$parties[$partyName]["blacklist"])) {
            return;
        }
        if (!(self::$parties[$partyName]["open"]) && !(in_array($player->getName(), self::$parties[$partyName]["invite"]))) {
            return;
        }
        if (in_array($player->getName(), self::$parties[$partyName]["invite"])) {
            self::invitePlayer($partyName, $player->getName(), false);
        }
        PlayerKits::getkit($player, "lobby", "party_player");
        PlayerDataManager::setdata($player, "vote", "none");
        PlayerDataManager::setdata($player, "party", "true");
        PlayerDataManager::setdata($player, "party_name", $partyName);
        PlayerDataManager::setdata($player, "party_owner", "false");
        self::$parties[$partyName]["players"][] = $player;
        $players = self::$parties[$partyName]["players"];
        foreach ($players as $playerss) {
            $list = PracticePlayer::getLangText($playerss, "c.party.join");
            $text = "§a" . $player->getName() . " " . $list;
            ChatHandler::sendMessage($playerss, $text, false);
        }
    }

    public static function removePlayer(string $partyName, $player, string $reason = ""): void
    {
        if (!isset(self::$parties[$partyName])) {
            return;
        }
        $party = self::$parties[$partyName];
        PlayerDataManager::setdata($player, "party", "false");
        PlayerDataManager::setdata($player, "party_owner", "false");
        $playerss = $party["players"];
        $playersss = [];
        foreach ($playerss as $players) {
            if ($players === $player) {
            } else {
                $playersss[] = $players;
            }
        }
        if ($party["owner"] === $player) {
            $newOwner = array_rand($party["players"]);
            $party["owner"] = $newOwner;
        }
        $players = self::$parties[$partyName]["players"];
        foreach ($players as $playerss) {
            $list = PracticePlayer::getLangText($playerss, "c.party.leave");
            $text = "§c" . $player->getName() . " " . $list;
            ChatHandler::sendMessage($playerss, $text, false);
        }
        $isffa = PlayerDataManager::getdata($player, "party_ffa");
        if ($isffa == "true") {
            ArenaManaer::joinlobby($player);
        } else {
            ArenaManaer::lobby($player);
        }
        unset($party["players"]);
        self::$parties[$partyName]["players"] = $playersss;
        invformapi::closeInvform($player);
    }

    public static function invitePlayer(string $partyName, string $name, $invate = true)
    {
        if ($invate) {
            self::$parties[$partyName]["invite"][] = $name;
        } else {
            self::$parties[$partyName]["invite"] = array_values(array_filter(
                self::$parties[$partyName]["invite"],
                function ($inviteName) use ($name) {
                    return $inviteName !== $name;
                }
            ));
        }
    }

    public static function blacklistPlayer(string $partyName, Player $player): void
    {
        if (PlayerDataManager::getdata($player, "party_owner") == "true") {
            return;
        }
        self::$parties[$partyName]["blacklist"][] = $player->getName();
        self::removePlayer($partyName, $player);
    }

    public static function resetvote(string $partyName)
    {
        $config = yaml::getconfig();
        $duels = $config["games"]["duel"];
        foreach ($duels as $duel => $game) {
            self::$parties[$partyName]["vote"][$duel] = 0;
        }
        if (isset(self::$parties[$partyName]["players"])) {
            $players = self::$parties[$partyName]["players"];
            foreach ($players as $player) {
                PlayerDataManager::setdata($player, "vote", "none");
            }
        }
    }

    public static function vote(string $partyName, Player $player, $game): void
    {
        $votedgame = PlayerDataManager::getdata($player, "vote");
        if ($votedgame == "none") {
            self::$parties[$partyName]["vote"][$game] += 1;
        } else {
            self::$parties[$partyName]["vote"][$votedgame] -= 1;
            self::$parties[$partyName]["vote"][$game] += 1;
        }
        PlayerDataManager::setdata($player, "vote", $game);
    }

    public static function isPlayerInParty(string $partyName, Player $player): bool
    {
        return isset(self::$parties[$partyName]) && isset(self::$parties[$partyName]["players"][$player]);
    }

    public static function PromotePlayer(string $partyName, Player $player)
    {
        $oldOwner = self::$parties[$partyName]["owner"];
        self::$parties[$partyName]["owner"] = $player;
        PlayerDataManager::setdata($oldOwner, "party_owner", "false");
        PlayerDataManager::setdata($player, "party_owner", "true");
        $gametype = PlayerDataManager::getdata($oldOwner, "gametype");
        if ($gametype == "lobby") {
            ArenaManaer::lobby($oldOwner);
        }
        $gametype = PlayerDataManager::getdata($player, "gametype");
        if ($gametype == "lobby") {
            ArenaManaer::lobby($player);
        }
    }

    public static function getPlayers(string $partyName): array
    {
        return self::$parties[$partyName]["players"] ?? [];
    }

    public static function destroyParty(string $partyName): void
    {
        $config = yaml::getconfig();
        $maps = $config["map"];
        $ffa = $config["games"]["ffa"];
        foreach ($ffa as $game) {
            $maps = $config["map"][$game["map"]]["map"];
            $worldManager = Server::getInstance()->getWorldManager();
            $worldName = $maps . "=" . $partyName;
            if ($worldManager->isWorldLoaded($worldName)) {
                $worldManager->unloadWorld($worldManager->getWorldByName($worldName));
            }
            practice::CreateAsyncTask("practice\utils\\task\worlddelete", [$maps, $partyName]);
        }
        $players = self::$parties[$partyName]["players"];
        foreach ($players as $playerss) {
            $list = PracticePlayer::getLangText($playerss, "c.party.leave");
            $text = "§c" . $list;
            ChatHandler::sendMessage($playerss, $text, false);
        }
        if (isset(self::$parties[$partyName]["players"])) {
            $players = self::$parties[$partyName]["players"];
            foreach ($players as $player) {
                self::removePlayer($partyName, $player);
            }
            if (isset(self::$parties[$partyName])) {
                unset(self::$parties[$partyName]);
            }
        }
    }
}
