<?php

namespace practice\handler;

use practice\player\PracticePlayer;

class TitleHandler
{
    public static function sendTitle($player, $message, $islang = true, $type = "other", $fade = -1, $stay = -1, $fadeOut = -1)
    {
        $xuid = $player->getXuid();
        $istype = PracticePlayer::$playerdata[$xuid]["settings"]["title"][$type];
        if ($istype) {
            if ($islang) {
                $message = PracticePlayer::getLangText($player, $message);
            }
            $player->sendTitle($message, "", $fade, $stay, $fadeOut);
        }
    }
    public static function sendsubTitle($player, $message, $islang = true, $type = "other")
    {
        $xuid = $player->getXuid();
        $istype = PracticePlayer::$playerdata[$xuid]["settings"]["title"][$type];
        if ($istype) {
            if ($islang) {
                $message = PracticePlayer::getLangText($player, $message);
            }
            $player->sendSubTitle($message);
        }
    }
}
