<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use pocketmine\entity\projectile\Arrow;
use practice\entity\FishingHookEntity;
use practice\handler\ChatHandler;
use practice\player\PracticePlayer;
use practice\utils\PlayerDataManager;

class EntityDamageByChildEntityEventHandler
{
    private static array $fishinghook = [];
    public static function onEntityDamageByChildEntityEvent($event): void
    {
        $child = $event->getChild();
        $entity = $event->getEntity();
        $damager = $event->getDamager();
        $game = PlayerDataManager::getdata($damager, "ingame");
        if ($entity instanceof Player && $child instanceof Arrow && !$event->iscancelled() && $entity !== $damager && $game !== "otic") {
            if (round($entity->getHealth() - $event->getFinalDamage(), 1) > 0) {
                ChatHandler::sendMessage($damager, "§l§8»§r §c" . $entity->getName() . " §ris now on §c" . round($entity->getHealth() - $event->getFinalDamage(), 1) . "§r HP!", false, $type = "combatlog");
            }
                PracticePlayer::playSound($damager, "random.orb");
            return;
        }
        if ($entity instanceof Player && $child instanceof FishingHookEntity) {
            $time = microtime(true);
            $oldtime = self::$fishinghook[$entity->getXuid()] ?? 0;
            $delay = $time - $oldtime;
            if ($delay < 0.5) {
                $event->cancel();
                return;
            } else {
                self::$fishinghook[$entity->getXuid()] = microtime(true);
            }
        }
    }
}
