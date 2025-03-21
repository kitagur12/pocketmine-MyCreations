<?php

namespace practice\handler;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\handler\PlayerDeathHandler;
use pocketmine\Server;
use practice\player\PracticePlayer;
use pocketmine\player\Player;
use practice\player\knockback;
use practice\duel\PlayerDuel;
use practice\utils\task\AlwaysTask;

class PlayerDamageHandler
{

    public static function hitprocess($event): void
    {
        $player = $event->getEntity();
        $xuid = $player->getXuid();
        $server = Server::getInstance();
        if ($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK || $event->getCause() == EntityDamageEvent::CAUSE_PROJECTILE) {
            $attacker = $server->getPlayerExact((string) PlayerDataManager::getdata($player, "lasthitplayer"));
            if ($attacker !== null) {
                $attacker = $server->getPlayerByPrefix(PlayerDataManager::getdata($player, "lasthitplayer"));
                if (PlayerDataManager::getdata($player, "gametype") == "ffa") {
                    $config = yaml::getconfig();
                    $gametype = PlayerDataManager::getData($player, "gametype");
                    $game = PlayerDataManager::getData($player, "ingame");
                    if (isset($config["games"][$gametype][$game]["combat"])) {
                        if ($config["games"][$gametype][$game]["combat"] == "multiple") {
                            $singlecombat = false;
                        } else {
                            $singlecombat = true;
                        }
                    } else {
                        $singlecombat = true;
                    }
                    $time = microtime(as_float: true);
                    $bool = false;
                    if (((microtime(true) - PlayerDataManager::getdata($player, "spawntime")) < PracticePlayer::$playerdata[$xuid]["settings"]["spawn_protection"]) || ((microtime(true) - PlayerDataManager::getdata($attacker, "spawntime")) < PracticePlayer::$playerdata[$attacker->getXuid()]["settings"]["spawn_protection"])) {
                        $event->cancel();
                        return;
                    }

                    if ($player !== $attacker) {
                        if ($singlecombat) {
                            $playercombat = self::isCombat($player);
                            $attackercombat = self::isCombat($attacker);
                            if (!$playercombat && !$attackercombat) {
                                $bool = true;
                                PlayerDataManager::setData($player, "combat_lasthit", "$time");
                                PlayerDataManager::setData($attacker, "combat_lasthit", "$time");
                                $dan = $attacker->getName();
                                $atn = $player->getName();
                                PlayerDataManager::setData($player, "combatplayer", "$dan");
                                PlayerDataManager::setData($attacker, "combatplayer", "$atn");
                            } else {
                                $atn = PlayerDataManager::getData($player, "combatplayer");
                                $dan = PlayerDataManager::getData($attacker, "combatplayer");
                                if ($dan == $player->getName() || $atn == $attacker->getName()) {
                                    $bool = true;
                                    PlayerDataManager::setData($player, "combat_lasthit", "$time");
                                    PlayerDataManager::setData($attacker, "combat_lasthit", "$time");
                                }
                            }
                        } else {
                            $dan = $attacker->getName();
                            $atn = $player->getName();
                            PlayerDataManager::setData($player, "combatplayer", "$dan");
                            PlayerDataManager::setData($attacker, "combatplayer", "$atn");
                            PlayerDataManager::setData($player, "combat_lasthit", "$time");
                            PlayerDataManager::setData($attacker, "combat_lasthit", "$time");
                            $bool = true;
                        }
                    }
                    if (!$bool) {
                        $event->cancel();
                    }
                } elseif (PlayerDataManager::getdata($player, "gametype") == "duel" || PlayerDataManager::getdata($player, "gametype") == "party") {
                    $attackerteam = PlayerDataManager::getdata($attacker, "team");
                    $playerteam = PlayerDataManager::getdata($player, "team");
                    if ($attackerteam == $playerteam) {
                        $event->cancel();
                    } else {
                        PlayerDuel::DuelattackHandler($event);
                        PlayerDataManager::setData($player, "combatplayer", $attacker->getName());
                        if (PlayerDataManager::getdata($attacker, "iscanattack") == "false" || PlayerDataManager::getdata($player, "iscanattack") == "false") {
                            $event->cancel();
                        }
                    }
                } elseif (PlayerDataManager::getdata($player, "dueltype") == "endduel") {
                    $event->cancel();
                } elseif (PlayerDataManager::getdata($player, "gametype") == "duel") {
                }

                var_dump(PlayerDataManager::getdata($player, "gametype"));
                knockback::kb($event);
                $xuid = $player->getXuid();
                $attack = $server->getPlayerByPrefix(PlayerDataManager::getData($player, "combatplayer"));
                if ($attack !== null) {
                    if (PracticePlayer::$playerdata[$attack->getXuid()]["settings"]["crit_particle"] == 4) {
                        $attack->getNetworkSession()->sendDataPacket(AnimatePacket::boatHack($player->getId(), 4, 1));
                    }
                    if ($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                        AlwaysTask::$data[$attack->getXuid()]["combo"] = AlwaysTask::$data[$attack->getXuid()]["combo"] ?? 0;
                        AlwaysTask::$data[$attack->getXuid()]["combo"] += 1;
                        AlwaysTask::$data[$attack->getXuid()]["reach"] = round($player->getPosition()->distance($attack->getPosition()), 2);
                        AlwaysTask::$data[$attack->getXuid()]["time"] = microtime(true);
                    }
                }
            }
        }
    }


    public static function deathcheck($event): bool
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            if (!$event->isCancelled()) {
                if ($player->getHealth() - $event->getFinalDamage() <= 0) {
                    $event->cancel();
                    PlayerDeathHandler::PlayerDeath($player);
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function isCombat($player): bool
    {
        if ($player instanceof Player) {
            if (PlayerDataManager::getdata($player, "gametype", false) == "ffa") {
                $config = yaml::getconfig();
                $gametype = PlayerDataManager::getData($player, "gametype", false);
                $game = PlayerDataManager::getData($player, "ingame", false);
                if (isset($config["games"][$gametype][$game]["combattime"])) {
                    $combattime = $config["games"][$gametype][$game]["combattime"];
                } else {
                    $combattime = 10;
                }
                $lasthit = PlayerDataManager::getData($player, "combat_lasthit", false);
                $diff = microtime(true) - $lasthit;
                if ($diff < $combattime && (Server::getInstance()->getPlayerByPrefix(PlayerDataManager::getdata($player, "combatplayer", false) ?? "a") !== null)) {
                    return true;
                } else {
                    return false;
                }
            } elseif (PlayerDataManager::getdata($player, "gametype", false) == "duel" || PlayerDataManager::getdata($player, "gametype", false) == "party") {
                return true;
            } else {
                return false;
            }
        }
    }
}
