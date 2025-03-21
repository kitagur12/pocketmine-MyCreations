<?php

namespace practice\handler;

use practice\player\PracticePlayer;

class ChatHandler
{
    public static function sendMessage($player, $message, $islang = true, $type = "notification")
    {
        $xuid = $player->getXuid();
        $istype = PracticePlayer::$playerdata[$xuid]["settings"]["chat_setting"][$type];
        if ($istype) {
            if ($islang) {
                $message = PracticePlayer::getLangText($player, $message);
            }
            $player->sendMessage($message);
        }
    }
}
