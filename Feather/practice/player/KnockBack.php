<?php

namespace practice\player;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\party\Party;

class knockback
{
    public static function kb($event, $bypass = false): void
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            if (!$event->iscancelled() || $bypass) {
                $event->setKnockBack(0);
                $damager = $event->getDamager();
                $player = $event->getEntity();
                if ($player instanceof Player && $damager instanceof Player) {
                    if (PlayerDataManager::getData($player, "spectator") == "true") {
                        $event->cancel();
                        return;
                    }
                    PlayerDataManager::setData($player, "iscrystal", false);
                    $config = yaml::getconfig();
                    $kbconfig = $config["knockback"][$config["games"][PlayerDataManager::getData($player, "gametype")][PlayerDataManager::getData($player, "ingame")]["knockback"]];
                    $horizontalKb = $kbconfig["horizontal"];
                    $verticalKb = $kbconfig["vertical"];
                    $horizontallimiter = $kbconfig["horizontallimit"];
                    $verticalLimiter = $kbconfig["verticalLimit"];
                    $horizontalLimit = $kbconfig["horizontalLimitkb"];
                    $verticalLimit = $kbconfig["verticalLimitkb"];
                    $isparty = PlayerDataManager::getData($player, "party");
                    if ($isparty == "true") {
                        $partyname = PlayerDataManager::getData($player, "party_name");
                        if (Party::$parties[$partyname]["induel"] && Party::$parties[$partyname]["split"]) {
                            if (Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["horizontal"] !== "default") {
                                $horizontalKb = Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["horizontal"];
                            }
                            if (Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["vertical"] !== "default") {
                                $verticalKb = Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["vertical"];
                            }
                            if (Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["maxkb"] !== "default") {
                                $horizontalLimit = Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["maxkb"];
                                $verticalLimit = Party::$parties[$partyname][PlayerDataManager::getData($player, "ingame")]["knockback"]["maxkb"];
                            }
                        }
                    }
                    if ($horizontallimiter == false) {
                        $horizontallimiter = 0.5;
                    }
                    if ($verticalLimiter == false) {
                        $verticalLimiter = 0.5;
                    }
                    if ($horizontalLimit == false) {
                        $horizontalLimit = 100;
                    }
                    if ($verticalLimit == false) {
                        $verticalLimit = 100;
                    }
                    $enchantmentLevel = 1;
                    $item = $damager->getInventory()->getItemInHand();
                    $enchantments = $item->getEnchantments();
                    foreach ($enchantments as $enchantment) {
                        if ($enchantment->getType()->getName()->getText() == "enchantment.knockback") {
                            $enchantmentLevel += $enchantment->getLevel() * 2;
                        }
                    }
                    $x = $player->getPosition()->getX() - $damager->getPosition()->getX();
                    $z = $player->getPosition()->getZ() - $damager->getPosition()->getZ();
                    $f = sqrt($x * $x + $z * $z);
                    if ($f > 0) {
                        $x /= $f;
                        $z /= $f;
                        if ($bypass) {
                            $p = 1;
                        } else {
                            $p = 2;
                        }
                    } else {
                        $p = 1;
                    }

                    if ($f > $horizontalLimit) {
                        $horizontalKb *= $horizontalLimit;
                    }
                    if ($player->getPosition()->getY() - $damager->getPosition()->getY() > $verticalLimit) {
                        $verticalKb *= $verticalLimiter;
                    }
                    $motion = $player->getMotion();
                    $motion->x = $x * $horizontalKb * $enchantmentLevel * $p;
                    $motion->y = $verticalKb * $p;
                    $motion->z = $z * $horizontalKb * $enchantmentLevel * $p;
                    $player->setMotion($motion);
                }
            }
        }
    }
}
