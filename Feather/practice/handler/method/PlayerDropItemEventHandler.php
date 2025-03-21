<?php

namespace practice\handler\method;


use practice\arena\ArenaManaer;

class PlayerDropItemEventHandler
{
    public static function onDropItem($event)
    {
        $player = $event->getPlayer();
        
        $config = ArenaManaer::getPlayerArenaConfig($player, "itemdrop", true);
        if ($config !== "1") {
            $event->cancel();
        }
    }
}
