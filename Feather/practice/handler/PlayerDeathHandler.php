<?php

namespace practice\handler;

use pocketmine\Server;
use pocketmine\entity\Skin;
use practice\arena\ArenaManaer;
use practice\player\PracticePlayer;
use practice\handler\PlayerDamageHandler;
use practice\utils\PlayerDataManager;
use practice\duel\PlayerDuel;
use practice\handler\PlayerKillHandler;
use practice\entity\DeathEntity;
use practice\player\PlayerKits;
use practice\items\cooltime;
use practice\player\scoreboard\Scorehud;
use practice\practice\practice as PracticeGame;

class PlayerDeathHandler
{
    public static function PlayerDeath($player)
    {
        Scorehud::UpdateScore($player);
        $xuid = $player->getXuid();
        $config = PracticePlayer::$playerdata;
        $iscombat = PlayerDamageHandler::isCombat($player);
        $server = Server::getInstance();
        $gametype = PlayerDataManager::getData($player, "gametype");
        $ingame = PlayerDataManager::getData($player, "ingame");
        cooltime::reset($player);
        PracticePlayer::unsetowning($player);
        if ($gametype !== "practice") {
            PlayerKits::getarrow($player, $gametype, $ingame);
            if ($gametype !== "lobby" && PlayerDataManager::getData($player, "spectator") !== "true") {
                foreach ($player->getWorld()->getPlayers() as $players) {
                    self::broadcastDeathAnimation($player, $players);
                }
            }
        } else {
            PracticeGame::$practicedatas[PlayerDataManager::getData($player, "practiceid")]["status"] = PracticeGame::PRACTICE_STANDING;
        }
        if ($gametype == "ffa") {
            if ($iscombat) {
                $killer = $server->getPlayerByPrefix(PlayerDataManager::getData($player, "combatplayer"));
                cooltime::reset($killer);
                PlayerKillHandler::ffaPlayerKill($killer, $player);
            }
            PlayerDataManager::setData($player, "combat_lasthit", "0");
            PlayerDataManager::setData($player, "combatplayer", ".");
            if ($config[$xuid]["settings"]["arena_respawn"] == false) {
                ArenaManaer::joinlobby($player);
            } else {
                $game = PlayerDataManager::getData($player, "ingame");
                $isparty = PlayerDataManager::getData($player, "party");
                if ($isparty == "true") {
                    ArenaManaer::tpArena($player, "ffa", $game, null, true);
                } else {
                    ArenaManaer::tpArena($player, "ffa", $game);
                }
            }
        }
        if ($gametype == "duel" || $gametype == "party") {
            $killer = $server->getPlayerByPrefix(PlayerDataManager::getData($player, "combatplayer"));
            if ($killer !== null) {
                cooltime::reset($killer);
                PlayerKillHandler::DuelPlayerKill($killer, $player);
            }
            PlayerDuel::DuelDeathHandler($player);
        }
    }

    public static function broadcastDeathAnimation($player, $killer): void
    {
        $playerLocation = $player->getLocation();
        $killerLocation = $killer->getLocation();

        if ($playerLocation->getY() > 0) {
            $dymmyskin = $player->getSkin();
            if ($dymmyskin == null) {
                $skinData = str_repeat("\x00", 64 * 32 * 4);
                $dymmyskin = new Skin("0", $skinData);
            }
            $deathEntity = new DeathEntity($playerLocation, $dymmyskin);
            $deathEntity->spawnTo($killer);
            $deathEntity->knockBack($playerLocation->getX() - $killerLocation->getX(), $playerLocation->getZ() - $killerLocation->getZ());
            $deathEntity->kill();
        };
    }
}
