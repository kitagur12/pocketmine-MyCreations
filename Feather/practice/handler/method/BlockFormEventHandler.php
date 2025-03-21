<?php

namespace practice\handler\method;

use practice\practice;

class BlockFormEventHandler
{
    public static function onBlockFormEvent($event)
    {
        $block = $event->getBlock();
        $position = $block->getPosition();
        $world = $position->getWorld();
        practice::CreateTask("practice\utils\\task\BlockDestroyTask", [$position, $world], 200);
    }
}
