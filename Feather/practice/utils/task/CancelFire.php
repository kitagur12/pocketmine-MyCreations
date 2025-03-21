<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;

class CancelFire extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {
        $this->item->setFireTicks(0);
        
    }
}
