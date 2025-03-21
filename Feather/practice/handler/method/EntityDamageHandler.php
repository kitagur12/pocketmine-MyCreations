<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageEvent;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\handler\PlayerDamageHandler;
use practice\player\knockback;
use practice\party\Party;
use practice\practice\practice as practicegame;

class EntityDamageHandler
{
    public static function damage($event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $gametype = PlayerDataManager::getdata($player, "gametype", false);
            $game = PlayerDataManager::getdata($player, "ingame", false);
            $config = yaml::getconfig();
            if ($gametype !== null) {
                if ($gametype !== "practice") {
                    $damagetype = $config["games"][$gametype][$game]["damagetype"];
                    if ($damagetype == "none") {
                        $event->cancel();
                        return;
                    }
                    if (isset($config["games"][$gametype][$game]["critical"])) {
                        $cancrit = $config["games"][$gametype][$game]["critical"];
                    } else {
                        $cancrit = true;
                    }

                    if (!$cancrit) {
                        if ($event->getModifier(EntityDamageEvent::MODIFIER_CRITICAL) > 0) {
                            knockback::kb($event, true);
                            $newEvent = new EntityDamageEvent(
                                $player,
                                EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                                $event->getBaseDamage(),
                                []
                            );
                            $player->attack($newEvent);
                            $event->cancel();
                        }
                    }

                    if ($event->getCause() === EntityDamageEvent::CAUSE_VOID) {
                        $event->cancel();
                    }
                    if ($event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                        $cooltime = $config["games"][$gametype][$game]["hitdelay"];
                        $isparty = PlayerDataManager::getData($player, "party");
                        if ($isparty == "true") {
                            $partyname = PlayerDataManager::getData($player, "party_name");
                            if (Party::$parties[$partyname]["induel"] && Party::$parties[$partyname]["split"]) {
                                $cooltime = Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["hitdelay"];
                            }
                        }
                        $event->setAttackCooldown($cooltime);
                    }
                    if ($damagetype == "default") {
                        if ($event->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                            $event->cancel();
                        }
                    } else {
                        $apply = false;
                        $types = explode(",", $damagetype);
                        if (in_array("CAUSE_PROJECTILE", $types, true)) {
                            $types[] = "CAUSE_CUSTOM";
                        }
                        foreach ($types as $type) {
                            if ($event->getCause() == EntityDamageEvent::{$type}) {
                                $apply = true;
                            }
                        }
                        if (!$apply) {
                            $event->cancel();
                        }
                    }
                    if ($event->getCause() == EntityDamageEvent::CAUSE_PROJECTILE) {
                        $game = PlayerDataManager::getdata($player, "ingame");
                        if ($game !== "otic") {
                            knockback::kb($event, true);
                        }
                        $newEvent = new EntityDamageEvent(
                            $player,
                            EntityDamageEvent::CAUSE_CUSTOM,
                            $event->getBaseDamage(),
                            []
                        );
                        $player->attack($newEvent);
                        $event->cancel();
                    }
                    if ($event->getCause() == EntityDamageEvent::CAUSE_CUSTOM) {
                        $event->setAttackCooldown(0);
                    }
                    PlayerDamageHandler::hitprocess($event);
                    PlayerDamageHandler::deathcheck($event);
                } else {
                    practicegame::PracticeDamageHandler($event);
                }
            }
        }
    }
}
