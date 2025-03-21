<?php

namespace practice\handler;

use practice\player\PracticePlayer;

class ActionbarHandler
{
    public static function sendMessage($player, $message)
    {
        $player->sendActionBarMessage($message);
    }
}
