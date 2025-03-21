<?php

namespace practice\items;

use practice\items\cooltime;
use practice\utils\PlayerDataManager;

class gapple
{
    public static function use($event, $player, $item): void
    {
        if (!cooltime::item($player, $item)) {
            $event->cancel();
        } else {
            if (PlayerDataManager::getdata($player, "gametype", false) == "duel"
            && PlayerDataManager::getdata($player, "dueltype", false) == "score"
            && $player->getHealth() < 20){
                $player->setHealth(999);
            }
        }
    }
}
