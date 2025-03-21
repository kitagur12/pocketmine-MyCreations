<?php

namespace practice\handler\method;

class PlayerMoveEventHandler
{
    public static array $y = [];
    public static array $d = [];


    public static function onPlayerMoveEvent($event)
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        $old = self::$y[$xuid] ?? 0;
        self::$d[$xuid] = $old - $player->getPosition()->getY();
        self::$y[$xuid] = $player->getPosition()->getY();
    }
}
