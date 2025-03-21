<?php

namespace practice\handler\method;

use pocketmine\block\Farmland;
use pocketmine\player\Player;
use practice\utils\PlayerDataManager;

class EntityTrampleFarmlandEventHandler
{
    public static function onEntityTrampleFarmlandEvent($event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $isbuilder = PlayerDataManager::getData($player, "builder");
            if ($isbuilder !== "true") {
                $block = $event->getBlock();
                if ($block instanceof Farmland) {
                    $event->cancel();
                }
            }
        } else {
            $event->cancel();
        }
    }
}
