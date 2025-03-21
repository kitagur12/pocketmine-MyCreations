<?php

namespace practice\items;

use practice\items\cooltime;

class splash_potion
{
    public static function use($event, $player, $item): void
    {
        if (!cooltime::item($player, $item)) {
            $event->cancel();
        }
    }
}
