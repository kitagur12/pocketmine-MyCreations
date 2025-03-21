<?php

namespace practice\handler;

use pocketmine\network\mcpe\protocol\SetTimePacket;
use practice\utils\PlayerDataManager;

class setTimeHandler
{
    public static function setTime($player, $time)
    {
        $packet = SetTimePacket::create(
            $time
        );
        PlayerDataManager::setData($player, "changetime", "true");
        $player->getNetworkSession()->sendDataPacket($packet);
    }
}
