<?php

namespace practice\player\scoreboard;

use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;

class ScoreTag
{
    public static function setScoreTag($player, $player2, $scoreTag): void
    {
        $metadata = [
            84 => new StringMetadataProperty($scoreTag)
        ];
        $syncedProperties = new PropertySyncData([], []);
        foreach ($player2 as $players) {
            $packet = SetActorDataPacket::create(
                $players->getId(),
                $metadata,
                $syncedProperties,
                0
            );
            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }
}
