<?php

namespace practice\handler\method;

use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\duel\PlayerDuel;
use practice\practice\practice as PracticeGame;

class BlockBreakEventHandler
{
    public static function onBlockBleak($event)
    {
        $event->setDrops([]);
        $player = $event->getPlayer();
        $type = PlayerDataManager::getData($player, "gametype");
        $game = PlayerDataManager::getData($player, "ingame");
        $isbuilder = PlayerDataManager::getData($player, "builder");
        $config = yaml::getconfig();
        if ($isbuilder !== "true") {
            if ($type == "ffa") {
                if (isset($config["games"][$type][$game]["blockaccess"])) {
                    if ($config["games"][$type][$game]["blockaccess"] == false) {
                        $event->cancel();
                    } else {
                        $blocklist = explode(",", $config["games"][$type][$game]["blockaccess"]);
                        if ($event->getblock()->getName() == "Bed Block") {
                            $blockId = "bed";
                        } else {
                            $blockId = str_replace(" ", "_", $event->getblock()->getName());
                            $blockId = strtoupper($blockId);
                        }

                        if (!in_array($blockId, $blocklist)) {
                            $event->cancel();
                        }
                    }
                } else {
                    $event->cancel();
                }
            }
            if ($type == "duel") {
                PlayerDuel::DuelBreakEvent($event);
            }
            if ($type == "practice") {
                PracticeGame::PracticeBreakHandler($event);
            }
            if ($type == "lobby") {
                $event->cancel();
            }
        }
    }
}
