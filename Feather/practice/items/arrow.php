<?php

namespace practice\items;

use practice\items\cooltime;

class arrow {
    public static function use($event, $item): void
    {
        $entity = $event->getEntity();
        $player = $entity->getOwningEntity();
        if (!cooltime::item($player, $item)) {
            $event->cancel();
        }
    }
}
