<?php

namespace practice\handler\method;

use pocketmine\entity\object\EndCrystal;

class EntitySpawnEventHandler
{
    public static array $time = [];

    public static function onEntitySpawnEvent($event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof EndCrystal) {
            self::$time["crystal"][$entity->getId()] = 0;
        }
    }
}
