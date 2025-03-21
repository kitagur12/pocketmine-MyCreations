<?php

namespace practice\handler\method;

use pocketmine\player\Player;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\inventory\PlayerInventory;
use practice\utils\PlayerDataManager;
use practice\form\ReciveFormEvent;

class InventoryTransactionEventHandler
{
    public static function onInventoryTransactionEvent($event)
    {
        $player = $event->getTransaction()->getSource();
        if ($player instanceof Player) {
            $isuse = PlayerDataManager::getdata($player, "useinv");
            foreach ($event->getTransaction()->getActions() as $action) {
                if ($action instanceof TakeStackRequestAction) {
                    $inventory = $action->getInventory();
                    if ($inventory instanceof PlayerInventory) {
                        if ($isuse == "true") {
                            $event->cancel();
                        }
                    }
                }
            }
        }
    }
}
