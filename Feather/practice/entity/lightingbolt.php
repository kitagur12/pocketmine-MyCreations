<?php

namespace practice\entity;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\world\Position;

class lightingbolt
{
    public static function sendLightning($player, $position, $bright = false): void {
        if ($bright) {
            $position = new Position(0, 999999999999999, 0, $position->getWorld());
        }
        $uniqueId = mt_rand(1000, 999999);
        $runtimeId = mt_rand(1000, 999999);
        $metadataCollection = new EntityMetadataCollection();
        $metadataCollection->setFloat(0, 1.8);
        $packet = new AddActorPacket();
        $packet->actorUniqueId = $uniqueId;
        $packet->actorRuntimeId = $runtimeId;
        $packet->type = "minecraft:lightning_bolt";
        $packet->position = $position;
        $packet->motion = null;
        $packet->pitch = 0.0;
        $packet->yaw = 0.0;
        $packet->headYaw = 0.0;
        $packet->bodyYaw = 0.0;
        $packet->attributes = [];
        $packet->metadata = [];
        $packet->syncedProperties = new PropertySyncData([], []);
        $packet->links = [];
        $player->getNetworkSession()->sendDataPacket($packet);
    }
}
