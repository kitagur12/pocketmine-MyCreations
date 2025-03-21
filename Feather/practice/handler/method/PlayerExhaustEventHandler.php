<?php

namespace practice\handler\method;


use practice\arena\ArenaManaer;

class PlayerExhaustEventHandler
{
    public static function onPlayerExhaust($event)
    {
        $player = $event->getPlayer();
        
        $config = ArenaManaer::getPlayerArenaConfig($player, "exhaust", true);
        if ($config !== "1") {
            $event->cancel();
        }
    }
}
