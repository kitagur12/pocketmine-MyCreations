<?php

namespace practice\handler\method;

use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\practice;
use practice\duel\PlayerDuel;
use practice\practice\practice as PracticeGame;

class BlockPlaceEventHandler
{
    public static function onBlockPlace($event)
    {
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
                        return;
                    } else {
                        $blocklist = explode(",", $config["games"][$type][$game]["blockaccess"]);
                        $blockId = strtoupper(str_replace(" ", "_", $event->getitem()->getVanillaName()));
                        if (!in_array($blockId, $blocklist)) {
                            $event->cancel();
                            return;
                        } else {
                            $player = $event->getPlayer();
                            foreach ($event->getTransaction()->getBlocks() as $blockData) {
                                $block = $blockData[3];
                                $position = $block->getPosition();
                                $level = $position->getWorld();
                            }
                            $breaktime = $config["games"][$type][$game]["breaktime"] ?? 200;
                            practice::CreateTask("practice\utils\\task\BlockDestroyTask", [$position, $level], $breaktime);
                        }
                    }
                } else {
                    $event->cancel();
                    return;
                }
            }
            if ($type == "duel") {
                PlayerDuel::DuelPlaceEvent($event);
            }
            if ($type == "practice") {
                PracticeGame::PracticePlaceHandler($event);
            }
            if ($type == "lobby") {
                $event->cancel();
                return;
            }
        }
        
        if (!$event->isCancelled()) {
            PlayerDataManager::setData($player, "destroy", "true");
        }

    }
}
