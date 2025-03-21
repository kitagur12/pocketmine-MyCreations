<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use practice\form\formapi;

class sendform extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {
        $item = $this->item;
        formapi::sendForm($item[0], $item[1]);
    }
}
