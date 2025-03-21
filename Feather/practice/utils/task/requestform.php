<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use practice\form\forms;

class requestform extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {
        forms::duel($this->item[0], 5);
    }
}
