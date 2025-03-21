<?php

namespace practice\utils\task;

use pocketmine\scheduler\AsyncTask;

class console extends AsyncTask
{
    private string $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function onRun(): void
    {
        print($this->item);
    }
}
