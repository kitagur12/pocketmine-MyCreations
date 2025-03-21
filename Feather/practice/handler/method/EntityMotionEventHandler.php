<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use practice\utils\PlayerDataManager;
use pocketmine\math\Vector3;

class EntityMotionEventHandler
{
    public static function onEntityMotionEvent($event)
    {
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $isspec = PlayerDataManager::getData($player, "spectate", false);
            if ($isspec == "true") {
                $event->cancel();
                return;
            }
            $game = PlayerDataManager::getData($player, "ingame", false);
            $crystal = PlayerDataManager::getData($player, "iscrystal", false);
            PlayerDataManager::setData($player, "iscrystal", false);
            $isdown = PlayerDataManager::getData($player, "isdown", false);
            PlayerDataManager::setData($player, "isdown", false);
            if ($isdown) {
                $motion = $event->getVector();
                $player->setMotion(new Vector3(
                    $motion->getX() / 5,
                    0,
                    $motion->getZ() / 5,
                ));
                $event->cancel();
            }
            if ($game == "crystal" && $crystal && PlayerDataManager::getData($player, "crystaldis") < 7 && !$isdown) {
                $motion = $event->getVector();
                if ($event->getVector()->getY() < 0.0001) {
                    $strength = sqrt($motion->getX() ** 2 + $motion->getZ() ** 2);
                    $ykb = $motion->getY() + 1.1 * $strength + 0.01;
                } else {
                    $ykb = $motion->getY() + 0.0001;
                }

                $motion = $event->getVector();
                $player->setMotion(new Vector3(
                    $motion->getX() / 1.65,
                    $ykb,
                    $motion->getZ() / 1.65,
                ));
                $event->cancel();
            } elseif ($game == "crystal" && PlayerDataManager::getData($player, "crystaldis") > 7 && $crystal && !$isdown) {
                $event->cancel();
            }
        }
    }
}
