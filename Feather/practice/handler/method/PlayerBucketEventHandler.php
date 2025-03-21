<?php

namespace practice\handler\method;

use practice\practice;

class PlayerBucketEventHandler
{
    public static function onPlayerBucketEvent($event)
    {
        $player = $event->getPlayer();
        $bucket = $event->getBucket();
        if ($bucket->getVanillaName() == "Water Bucket") {
            $position = $event->getBlockClicked()->getPosition();
            $world = $position->getWorld();
            practice::CreateTask("practice\utils\\task\BlockDestroyTask",[$position,$world], 200);
        }
        if ($bucket->getVanillaName() == "Lava Bucket") {
            $position = $event->getBlockClicked()->getPosition();
            $world = $position->getWorld();
            practice::CreateTask("practice\utils\\task\BlockDestroyTask",[$position,$world], 200);
        }
    }
}
