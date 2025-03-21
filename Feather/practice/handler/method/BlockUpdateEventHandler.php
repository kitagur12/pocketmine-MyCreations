<?php

namespace practice\handler\method;

use pocketmine\block\Farmland;
use pocketmine\block\Wheat;

class BlockUpdateEventHandler
{
    public static function onBlockUpdateEvent($event)
    {
        $block = $event->getBlock();
        if ($block instanceof Farmland || $block instanceof Wheat) {
            $event->cancel();
        }
    }
}
