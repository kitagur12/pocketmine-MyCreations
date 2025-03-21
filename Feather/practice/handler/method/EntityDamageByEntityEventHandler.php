<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\object\EndCrystal;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use pocketmine\entity\Human;
use practice\utils\PlayerDataManager;

class EntityDamageByEntityEventHandler
{
    private static array $crystalAttackers = [];

    public static function onEntityDamageByEntityEvent($event): void
    {
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        if ($damager instanceof Player) {
            if (PlayerDataManager::getData($damager, "spectator") == "true") {
                $event->cancel();
                return;
            }
        }
        if ($entity instanceof Human && !($entity instanceof Player)) {
            $event->setKnockBack(0);
            $event->setModifier(0, 1);
            $event->setBaseDamage(0);
            $entity->setHealth(999);
            return;
        }
        if ($entity instanceof EndCrystal && $damager instanceof EndCrystal) {
            $entity->kill();
            $event->cancel();
            return;
        }
        if ($entity instanceof Player && $damager instanceof Player) {
            if (PlayerDataManager::getData($entity, "joined", false) === "true") {
                PlayerDataManager::setData($entity, "lasthitplayer", $damager->getName(), false);
            }
            if ($event->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0) {
                $event->cancel();
            }
            if (PlayerDataManager::getData($entity, "ingame", false) == "crystal") {
                $event->setBaseDamage($event->getBaseDamage() * 0.88);
            }
            return;
        }
        if ($entity instanceof Player && $damager instanceof EndCrystal) {
            $vector1 = $entity->getPosition()->asVector3();
            $vector2 = $damager->getPosition()->asVector3();
            $dx = $vector2->getX() - $vector1->getX();
            $dy = $vector2->getY() - $vector1->getY();
            $dz = $vector2->getZ() - $vector1->getZ();
            $direction = new Vector3($dx, $dy, $dz);
            $direction = $direction->normalize();
            $distanceBetweenPlayers = $vector1->distance($vector2);
            PlayerDataManager::setData($entity, "crystaldis", $distanceBetweenPlayers);
            PlayerDataManager::setData($entity, "iscrystal", true);
            if ($distanceBetweenPlayers > 7) {
                $event->cancel();
                return;
            }
            $entityPosition = $damager->getPosition();
            $belowPosition = $entityPosition->subtract(0, 1, 0);
            $block = $damager->getWorld()->getBlock($belowPosition);

            if ($entity->getPosition()->getY() < $damager->getPosition()->add(0, -0.9, 0)->getY() && !($block instanceof Air)) {
                PlayerDataManager::setData($entity, "isdown", true);
                $newEvent = new EntityDamageEvent(
                    $entity,
                    1,
                    $event->getBaseDamage() * 0.06,
                    []
                );
                $entity->attack($newEvent);
            } else {
                PlayerDataManager::setData($entity, "isdown", false);
                $isblock = self::checkBlockBetweenPlayers($entity, $damager);
                if ($isblock) {
                    $newEvent = new EntityDamageEvent(
                        $entity,
                        1,
                        $event->getBaseDamage() * 0.06,
                        []
                    );
                } else {
                    $newEvent = new EntityDamageEvent(
                        $entity,
                        1,
                        $event->getBaseDamage() * 0.45,
                        []
                    );
                }
            }
            $entity->attack($newEvent);
            $event->cancel();
            $crystalId = $damager->getId();
            if (isset(self::$crystalAttackers["id:" . $crystalId])) {
                $attackerName = self::$crystalAttackers["id:" . $crystalId];
                if ($attackerName !== $entity->getName()) {
                    $time = microtime(true);
                    PlayerDataManager::setData($entity, "lasthitplayer", $attackerName);
                    PlayerDataManager::setData($entity, "combat_lasthit", (string)$time);
                    PlayerDataManager::setData($entity, "combatplayer", $attackerName);
                }
                return;
            }
        }
        if ($entity instanceof EndCrystal && $damager instanceof Player) {
            $crystalId = $entity->getId();
            self::$crystalAttackers["id:" . $crystalId] = $damager->getName();
            return;
        }
    }

    private static function checkBlockBetweenPlayers($player1, $player2, int $distance = 10): bool
    {
        $vector1 = $player1->getPosition()->asVector3();
        $vector2 = $player2->getPosition()->asVector3()->add(0, 0, 0);
        $dx = $vector2->getX() - $vector1->getX();
        $dy = $vector2->getY() - $vector1->getY();
        $dz = $vector2->getZ() - $vector1->getZ();
        $direction = new Vector3($dx, $dy, $dz);
        $direction = $direction->normalize();
        $distanceBetweenPlayers = $vector1->distance($vector2) * 2;
        for ($i = 0; $i < $distanceBetweenPlayers; $i++) {
            $checkPos = new Vector3(
                $vector1->getX() + $direction->getX() * $i / 2,
                $vector1->getY() + $direction->getY() * $i / 2,
                $vector1->getZ() + $direction->getZ() * $i / 2
            );
            if (!($player1->getWorld()->getBlock($checkPos) instanceof Air)) {
                return true;
            }
        }
        return false;
    }
}
