<?php

namespace practice\items;

use practice\items\cooltime;

class enderpearl
{
    public static function use($event, $player, $item): void
    {
        if (!cooltime::item($player, $item)) {
            $event->cancel();
        }
    }
}
