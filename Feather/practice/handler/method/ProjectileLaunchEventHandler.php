<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use practice\items\arrow;
use pocketmine\item\VanillaItems;

class ProjectileLaunchEventHandler
{
    public static function onProjectileLaunchEvent($event)
    {
        $entity = $event->getEntity();
        $owner = $entity->getOwningEntity();
        if ($owner instanceof Player) {
            $item = VanillaItems::ARROW();
            arrow::use($event, $item);
        }
    }
}
