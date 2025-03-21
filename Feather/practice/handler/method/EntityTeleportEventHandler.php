<?php

namespace practice\handler\method;

use pocketmine\world\Position;
use practice\utils\PlayerDataManager;

class EntityTeleportEventHandler
{
    public static function onEntityTeleport($event)
    {/*
        $player = $event->getEntity();
        $ispearl = PlayerDataManager::getData($player,"tp_ender_pearl");
        if ($ispearl !== "true") {
            $to =  $event->getTo();
            if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ()) && true == false) {
            $newPosition = new Position($to->getFloorX() + 0.5, $to->getFloorY() + 0.5, $to->getFloorZ() + 0.5, $to->getWorld());
            $event->setTo($newPosition);
            }
        }*/
        
    }
}