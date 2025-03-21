<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use practice\form\invformapi;

class sendinvform2 extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {

        invformapi::sendInvform($this->item[0], $this->item[1]);
    }
}
