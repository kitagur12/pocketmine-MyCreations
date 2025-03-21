<?php

namespace practice\entity;

use pocketmine\entity\Human;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\entity\Skin;
use function spl_object_id;

class DeathEntity extends Human
{

    public function __construct($location, $skin, $nbt = null)
    {
        $dymmyskin = $skin;
        if ($skin == null) {
            $skinData = str_repeat("\x00", 64 * 32 * 4);
            $dymmyskin = new Skin("0", $skinData);
        }
        $this->skin = $skin ?? $dymmyskin;
        parent::__construct($location, $dymmyskin);
    }
    public function spawnTo(Player $player): void
    {
        $id = spl_object_id($player);
        if (!isset($this->hasSpawned[$id]) && $player->getWorld() === $this->getWorld() && $player->hasReceivedChunk($this->location->getFloorX() >> Chunk::COORD_BIT_SIZE, $this->location->getFloorZ() >> Chunk::COORD_BIT_SIZE)) {
            $this->hasSpawned[$id] = $player;
            $this->sendSpawnPacket($player);
        }
    }

    public function spawnToSpecifyPlayer(Player $player): void {}
}
