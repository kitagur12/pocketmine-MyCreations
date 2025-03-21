<?php

namespace practice\arena;

use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\world\Position;
use pocketmine\Server;
use practice\utils\PlayerDataManager;
use practice\player\PlayerKits;
use practice\practice\practice;
use practice\player\PracticePlayer;
use practice\utils\yaml;
use practice\player\PlayerQueue;
use practice\handler\PlayerDamageHandler;
use pocketmine\player\Player;
use practice\items\cooltime;
use pocketmine\player\GameMode;
use practice\party\Party;
use practice\player\scoreboard\Scorehud;

class ArenaManaer
{
    public static function setup() {}

    public static function joinlobby($player)
    {
        if (!($player instanceof Player)) {
            return;
        }
        if (!PlayerDamageHandler::isCombat($player)) {
            Scorehud::UpdateScore($player);
            PracticePlayer::unsetowning($player);
            cooltime::reset($player);
            if (PlayerDataManager::getData($player, "gametype") == "practice") {
                $practiceid = PlayerDataManager::getdata($player, "practiceid");
                practice::$practicedatas[$practiceid]["status"] = practice::PRACTICE_CLOSED;
            }
            $party = PlayerDataManager::getdata($player, "party");
            PlayerDataManager::setdata($player, "openchest", "false");
            PlayerDataManager::setData($player, "spectator", "false");
            PlayerDataManager::setData($player, "gametype", "lobby");
            PlayerDataManager::setData($player, "kit", "lobby");
            PlayerDataManager::setData($player, "ingame", "lobby");
            PlayerDataManager::setdata($player, "queuegametype", "null");
            PlayerDataManager::setdata($player, "queuegameinfo", "null");
            $player->setGamemode(GameMode::SURVIVAL);
            $player->extinguish();
            $player->setScale(1);
            $player->setHealth(999);
            $player->setMaxHealth(20);
            $player->getHungerManager()->setFood(20);
            $xpManager = $player->getXpManager();
            $xpManager->setXpLevel(0);
            $xpManager->setXpProgress(0.0);
            PracticePlayer::PlayerSettingUpdate($player);
            PlayerQueue::leaveQueue($player);
            PracticePlayer::unsetspectate($player);
            PracticePlayer::UpdateName($player);
            PracticePlayer::resetstatus($player);
            PracticePlayer::setPlayerRain($player, PracticePlayer::$playerdata[$player->getXuid()]["settings"]["rain"]);
            self::tpArena($player, "lobby", "lobby");
            if ($party == "true") {
                PlayerDataManager::setdata($player, "party_ffa", "false");
                $isowner = PlayerDataManager::getdata($player, "party_owner");
                if ($isowner == "true") {
                    PlayerKits::getkit($player, "lobby", "party");
                } else {
                    PlayerKits::getkit($player, "lobby", "party_player");
                }
                Party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = false;
            } else {
                PlayerKits::getkit($player, "lobby", "lobby");
            }
            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                $xuid = $players->getXuid();
                $lobbyHideSetting = PracticePlayer::$playerdata[$xuid]["settings"]["lobby_hide"];
                if ($lobbyHideSetting === true) {
                    $players->hidePlayer($player);
                } else {
                    $players->showPlayer($player);
                }
            }
        }
    }

    public static function lobby($player)
    {
        if (!($player instanceof Player)) {
            return;
        }
        if (!PlayerDamageHandler::isCombat($player)) {
            Scorehud::UpdateScore($player);
            PracticePlayer::unsetowning($player);
            cooltime::reset($player);
            if (PlayerDataManager::getData($player, "gametype") == "practice") {
                $practiceid = PlayerDataManager::getdata($player, "practiceid");
                practice::$practicedatas[$practiceid]["status"] = practice::PRACTICE_CLOSED;
            }
            $party = PlayerDataManager::getdata($player, "party");
            PlayerDataManager::setdata($player, "itemuse", "true");
            PlayerDataManager::setdata($player, "canblock", "true");
            PlayerDataManager::setdata($player, "openchest", "false");
            PlayerDataManager::setData($player, "spectator", "false");
            PlayerDataManager::setData($player, "gametype", "lobby");
            PlayerDataManager::setData($player, "kit", "lobby");
            PlayerDataManager::setData($player, "ingame", "lobby");
            PlayerDataManager::setdata($player, "queuegametype", "null");
            PlayerDataManager::setdata($player, "queuegameinfo", "null");
            if ($party == "true") {
                PlayerDataManager::setdata($player, "party_ffa", "false");
                $isowner = PlayerDataManager::getdata($player, "party_owner");
                if ($isowner == "true") {
                    PlayerKits::getkit($player, "lobby", "party");
                } else {
                    PlayerKits::getkit($player, "lobby", "party_player");
                }
            } else {
                PlayerKits::getkit($player, "lobby", "lobby");
            }
            $player->extinguish();
            $player->setScale(1);
            $player->setHealth(20);
            $player->setMaxHealth(20);
            $player->setHealth(20);
            $player->getHungerManager()->setFood(20);
            $xpManager = $player->getXpManager();
            $xpManager->setXpLevel(0);
            $xpManager->setXpProgress(0.0);
            PracticePlayer::PlayerSettingUpdate($player);
            PlayerQueue::leaveQueue($player);
            PracticePlayer::unsetspectate($player);
            PracticePlayer::UpdateName($player);
            PracticePlayer::resetstatus($player);
            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                $xuid = $players->getXuid();
                $lobbyHideSetting = PracticePlayer::$playerdata[$xuid]["settings"]["lobby_hide"];
                if ($lobbyHideSetting === true) {
                    $players->hidePlayer($player);
                } else {
                    $players->showPlayer($player);
                }
            }
        }
    }

    public static function tpArena($player, $type, $game, $id = null, $party = false)
    {
        if (!($player instanceof Player)) {
            return;
        }
        Scorehud::UpdateScore($player);
        PlayerDataManager::setdata($player, "spawntime", microtime(true));
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            if (PlayerDataManager::getData($players, "spectator") == "true") {
                $player->hidePlayer($players);
            } else {
                $player->showPlayer($players);
            }
        }
        $player->setHealth(999);
        $player->setGamemode(GameMode::SURVIVAL);
        $player->getHungerManager()->setFood(20);
        $config = yaml::getconfig();
        $mapname = $config["games"][$type][$game]["map"];
        $spawnlist = $config["map"][$mapname]["spawn"];
        $map = $config["map"][$mapname]["map"];
        PlayerDataManager::setData($player, "gametype", $type);
        PlayerDataManager::setData($player, "ingame", $game);
        PlayerDataManager::setdata($player, "iscanattack", "true");
        cooltime::reset($player);
        if (is_string($spawnlist[0])) {
            $spawnlist[] = $spawnlist;
        }
        if ($id == null) {
            $spawnKey = array_rand($spawnlist);
            $spawn = $spawnlist[$spawnKey];
        } else {
            $spawn = $spawnlist[$id];
        }
        $world = Server::getInstance()->getWorldManager();
        if ($party) {
            $partyname = PlayerDataManager::getdata($player, "party_name");
            $world = $world->getWorldByName($map . "=" . $partyname);
        } else {
            $world = $world->getWorldByName($map . "=defalt");
        }
        PlayerKits::getkit($player, $type, $game);
        PlayerKits::getarrow($player, $type, $game);
        $Position = new Position($spawn[0], $spawn[1], $spawn[2], $world);
        $to = $Position;
        if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ())) {
            $Position = new Position($to->getFloorX() + 0.5, $to->getFloorY() + 0.5, $to->getFloorZ() + 0.5, $to->getWorld());
        }
        if (count($spawn) == 3) {
            $player->teleport($Position);
        } else {
            $player->teleport($Position, $spawn[3], $spawn[4]);
        }
        PracticePlayer::UpdateName($player);
        PracticePlayer::resetstatus($player);
        if (isset($config["games"][$type][$game]["effect"])) {
            if (is_string($config["games"][$type][$game]["effect"])) {
                $effects = [$config["games"][$type][$game]["effect"]];
            } else {
                $effects = $config["games"][$type][$game]["effect"];
            }
            foreach ($effects as $effect) {
                $effect = explode("-", $effect);
                $p = (int) $effect[1];
                $ieffect = new EffectInstance(VanillaEffects::{$effect[0]}(), 2147483647, $p, false);
                $ieffect->setAmplifier($p);
                $effectManager = $player->getEffects();
                $effectManager->add($ieffect);
            }
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
        }
        PracticePlayer::setPlayerRain($player, PracticePlayer::$playerdata[$player->getXuid()]["settings"]["rain"]);
    }

    public static function Arenatp($player, $type, $game)
    {
        if (!($player instanceof Player)) {
            return;
        }
        PlayerDataManager::setdata($player, "spawntime", microtime(true));
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            if (PlayerDataManager::getData($players, "spectator") == "false") {
                $player->showPlayer($players);
            } else {
                $player->hidePlayer($players);
            }
        }
        $config = yaml::getconfig();
        $mapname = $config["games"][$type][$game]["map"];
        $spawnlist = $config["map"][$mapname]["spawn"];
        $map = $config["map"][$mapname]["map"];
        if (is_string($spawnlist[0])) {
            $spawnlist[] = $spawnlist;
        }
        $spawnKey = array_rand($spawnlist);
        $spawn = $spawnlist[$spawnKey];
        $world = Server::getInstance()->getWorldManager();
        $world = $world->getWorldByName($map . "=defalt");
        $Position = new Position($spawn[0], $spawn[1], $spawn[2], $world);
        $to = $Position;
        if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ())) {
            $Position = new Position($to->getFloorX() + 0.5, $to->getFloorY() + 0.5, $to->getFloorZ() + 0.5, $to->getWorld());
        }
        if (count($spawn) == 3) {
            $player->teleport($Position);
        } else {
            $player->teleport($Position, $spawn[3], $spawn[4]);
        }
        PracticePlayer::UpdateName($player);
        PracticePlayer::resetstatus($player);
        PracticePlayer::setPlayerRain($player, PracticePlayer::$playerdata[$player->getXuid()]["settings"]["rain"]);
    }

    public static function getPlayerArenaConfig($player, $configtype, $isstring): mixed
    {
        if (!($player instanceof Player)) {
            return "a";
        }
        $config = yaml::getconfig();
        $type = PlayerDataManager::getData($player, "gametype", false);
        $game = PlayerDataManager::getData($player, "ingame", false);
        if ($type !== null) {
            $gameconfig = $config["games"][$type][$game];
            if (isset($configtype)) {
                if (isset($config["games"][$type][$game][$configtype])) {
                    $gameconfig = $config["games"][$type][$game][$configtype];
                } else {
                    $gameconfig = "null";
                }

                if ($isstring == true) {
                    $gameconfig = (string)$gameconfig;
                }
            }
            return $gameconfig;
        }
        return "a";
    }
}
