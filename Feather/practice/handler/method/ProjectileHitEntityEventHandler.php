<?php

namespace practice\handler\method;

use pocketmine\entity\projectile\Arrow;
use practice\utils\PlayerDataManager;
use practice\handler\PlayerDeathHandler;
use pocketmine\player\Player;

class ProjectileHitEntityEventHandler
{
    public static function onProjectileHitEntityEvent($event)
    {
        $entity = $event->getEntity();
        $player = $event->getEntityHit();
        $owner = $entity->getOwningEntity();
        if ($entity instanceof Arrow && $event->getEntityHit() instanceof Player && $owner instanceof Player) {
            $game = PlayerDataManager::getdata($owner, "ingame");
            if ($game == "otic" && $player !== $owner) {
                $time = microtime(as_float: true) - 1;
                PlayerDataManager::setData($event->getEntityHit(), "combat_lasthit", "$time");
                PlayerDataManager::setData($owner, "combat_lasthit", "$time");
                PlayerDataManager::setdata($event->getEntityHit(), "combatplayer",$owner->getName());
                PlayerDataManager::setdata($owner, "combatplayer",$event->getEntityHit()->getName());
                PlayerDeathHandler::PlayerDeath($event->getEntityHit());
            }
        }
    }
}
