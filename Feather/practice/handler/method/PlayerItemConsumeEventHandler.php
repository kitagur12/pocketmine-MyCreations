<?php

namespace practice\handler\method;

use practice\items\enchant_gapple;
use practice\items\gapple;
use pocketmine\item\VanillaItems;

class PlayerItemConsumeEventHandler
{
    public static function onPlayerItemConsumeEvent($event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->equals(VanillaItems::GOLDEN_APPLE(), false, false)) {
            gapple::use($event, $player, $item);
        }
        if ($item->equals(VanillaItems::ENCHANTED_GOLDEN_APPLE(), false, false)) {
            enchant_gapple::use($event, $player, $item);
        }
    }
}
