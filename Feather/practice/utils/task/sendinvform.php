<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use practice\form\invformapi;
use practice\utils\PlayerDataManager;

class sendinvform extends Task
{
    private $item;

    public function __construct($item)
    {
        $this->item = $item;
    }
    public function onRun(): void
    {
        PlayerDataManager::setData($this->item[0], "formqueue", "true");
        PlayerDataManager::setData($this->item[0], "formqueue_id", $this->item[1]);
    }
}
