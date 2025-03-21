<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\particle\BlockBreakParticle;
use practice\utils\PlayerDataManager;

class BlockDestroyTask extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {
        $position = $this->item[0];
        $level = $this->item[1];
        $level->addParticle($position->add(0.5, 0.5, 0.5), new BlockBreakParticle($level->getBlock($position)));
        foreach ($level->getPlayers() as $player) {
            PlayerDataManager::setData($player, "destroy", "true");
        }
        $level->setBlock($position, VanillaBlocks::AIR());
    }
}
