<?php

namespace practice\player;

use pocketmine\Server;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\party\Party;
use practice\arena\ArenaManaer;
use practice\arena\DuelManager;
use practice\practice\practice as practicegame;

class PlayerQueue
{

    public static function setup(): void
    {
        $config = yaml::getconfig();
        foreach ($config["games"]["ffa"] as $key => $value) {
            self::registQueue("ffa_$key", 1);
        }
        foreach ($config["games"]["duel"] as $key => $value) {
            self::registQueue("duel_ranked_$key", 2);
            self::registQueue("duel_unranked_$key", 2);
            self::registQueue("party_duel_$key", 2);
            self::registQueue("split_duel_$key", 1);
        }
        foreach ($config["games"]["party"] as $key => $value) {
            self::registQueue("party_party_$key", 2);
        }
        foreach ($config["games"]["practice"] as $key => $value) {
            self::registQueue("practice_$key", 1);
        }
        foreach ($config["games"]["bot"] as $key => $value) {
            self::registQueue("bot_$key", 1);
        }
    }

    private static array $queue;

    public static function registQueue($queue, $number): void
    {
        self::$queue[$queue]["maxplayer"] = $number;
    }

    public static function unregistQueue($queue): void
    {
        unset(self::$queue[$queue]);
    }

    public static function joinQueue($player, $queue): bool
    {
        $gametype = PlayerDataManager::getData($player, "gametype");
        if ($gametype == "lobby") {
            $nowq = PlayerDataManager::getData($player, "queue");
            if ($nowq == "null") {
                if (isset(self::$queue[$queue]["maxplayer"])) {
                    PlayerDataManager::setData($player, "queue", $queue);
                    $xuid = $player->getUniqueId();
                    self::$queue[$queue]["player"][] = $xuid;
                    self::checkQueue($queue);
                    return true;
                } else {
                }

                return false;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function leaveQueue($player): void
    {
        $xuid = $player->getUniqueId();
        $queue = PlayerDataManager::getData($player, "queue");

        if ($queue === "null" || !isset(self::$queue[$queue])) {
            return;
        }
        $key = array_search($xuid, self::$queue[$queue]["player"]);
        if ($key !== false) {
            unset(self::$queue[$queue]["player"][$key]);
            self::$queue[$queue]["player"] = array_values(self::$queue[$queue]["player"]);
        }
        PlayerDataManager::setData($player, "queue", "null");
        if (PlayerDataManager::getData($player, "party") == "true") {
            party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = false;
        }
    }

    public static function checkQueue($queue): void
    {
        $number = self::$queue[$queue]["maxplayer"];
        $players = self::$queue[$queue]["player"];
        if (count($players) >= $number) {
            if (explode("_", $queue)[0] === "duel") {
                self::createDuelPlayers($queue);
            }
            if (explode("_", $queue)[0] === "party") {
                self::createPartyDuelPlayers($queue);
            }
            if (explode("_", $queue)[0] === "split") {
                self::createPartySplitPlayers($queue);
            }
            if (explode("_", $queue)[0] === "practice") {
                self::createPractice($queue);
            }
            if (explode("_", $queue)[0] === "ffa") {
                $onlinePlayers = Server::getInstance()->getOnlinePlayers();
                foreach ($onlinePlayers as $player) {
                    if (in_array($player->getUniqueId(), $players)) {
                        self::completeffaQueue($player);
                    }
                }
            }
        }
    }


    public static function completeffaQueue($player): void
    {
        $queue = PlayerDataManager::getData($player, "queue");
        self::leaveQueue($player);
        if (explode("_", $queue)[0] == "ffa") {
            ArenaManaer::tpArena($player, "ffa", explode("_", $queue)[1]);
        }
        self::leaveQueue($player);
    }

    public static function createPractice($queue): void
    {
        $player = self::$queue[$queue]["player"][0];
        $player = Server::getInstance()->getPlayerByUUID($player);
        $queue = PlayerDataManager::getData($player, "queue");
        PlayerDataManager::setdata($player, "gametype", "practice");
        $data = [
            "player" => $player,
            "game" => explode("_", $queue)[1]
        ];
        practicegame::createPractice($data);
        self::leaveQueue($player);
    }

    private static function createDuelPlayers($queue): void
    {
        $players = self::$queue[$queue]["player"];
        $player1 = Server::getInstance()->getPlayerByUUID($players[0]);
        $player2 = Server::getInstance()->getPlayerByUUID($players[1]);
        $info["ranked"] = explode("_", $queue)[1];
        $info["game"] = explode("_", $queue)[2];
        DuelManager::createPlayerDuel([$player1], [$player2], $info);
    }

    private static function createPartyDuelPlayers($queue): void
    {
        $players = self::$queue[$queue]["player"];
        $player1 = Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["players"];
        $player2 = Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[1]), "party_name")]["players"];
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["induel"] = true;
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[1]), "party_name")]["induel"] = true;
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["split"] = false;
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[1]), "party_name")]["split"] = false;
        $info["ranked"] = false;
        $info["game"] = explode("_", $queue)[2];
        $info["isparty"] = true;
        DuelManager::createPlayerDuel($player1, $player2, $info);
    }

    private static function createPartySplitPlayers($queue): void
    {
        $players = self::$queue[$queue]["player"];
        $player1 = Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["players"];
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["split"] = true;
        Party::$parties[PlayerDataManager::getdata(Server::getInstance()->getPlayerByUUID($players[0]), "party_name")]["induel"] = true;
        $info["ranked"] = false;
        $info["game"] = explode("_", $queue)[2];
        $info["isparty"] = true;
        $info["split"] = true;
        list($player1, $player2) = self::splitRandomly($player1);
        DuelManager::createPlayerDuel($player1, $player2, $info);
    }

    private static function splitRandomly(array $items): array
    {
        shuffle($items);
        $halfSize = intdiv(count($items), 2);

        $group1 = array_slice($items, 0, $halfSize);
        $group2 = array_slice($items, $halfSize);

        return [$group1, $group2];
    }
}
