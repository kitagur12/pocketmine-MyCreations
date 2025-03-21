<?php

namespace practice\handler\method;

use practice\utils\PlayerDataManager;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\BlockTypeIds;

class PlayerInteractEventHandler
{

    private const FORM_BLOCK_IDS = [
        BlockTypeIds::CHEST,
        BlockTypeIds::TRAPPED_CHEST,
        BlockTypeIds::ENDER_CHEST,
        BlockTypeIds::ENCHANTING_TABLE,
        BlockTypeIds::BEACON,
        BlockTypeIds::FURNACE,
        BlockTypeIds::SMOKER,
        BlockTypeIds::BLAST_FURNACE,
        BlockTypeIds::CARTOGRAPHY_TABLE,
        BlockTypeIds::SHULKER_BOX,
        BlockTypeIds::STONECUTTER,
        BlockTypeIds::LOOM,
        BlockTypeIds::CRAFTING_TABLE,
        BlockTypeIds::BARREL,
        BlockTypeIds::ANVIL,
        BlockTypeIds::SMITHING_TABLE,
        BlockTypeIds::BED,
        BlockTypeIds::FIRE,
        BlockTypeIds::SOUL_FIRE,
        BlockTypeIds::CAMPFIRE,
        BlockTypeIds::SOUL_CAMPFIRE,
        BlockTypeIds::ACACIA_SIGN,
        BlockTypeIds::ACACIA_WALL_SIGN,
        BlockTypeIds::BIRCH_SIGN,
        BlockTypeIds::BIRCH_WALL_SIGN,
        BlockTypeIds::CHERRY_SIGN,
        BlockTypeIds::CHERRY_WALL_SIGN,
        BlockTypeIds::CRIMSON_SIGN,
        BlockTypeIds::CRIMSON_WALL_SIGN,
        BlockTypeIds::DARK_OAK_SIGN,
        BlockTypeIds::DARK_OAK_WALL_SIGN,
        BlockTypeIds::JUNGLE_SIGN,
        BlockTypeIds::JUNGLE_WALL_SIGN,
        BlockTypeIds::MANGROVE_SIGN,
        BlockTypeIds::MANGROVE_WALL_SIGN,
        BlockTypeIds::OAK_SIGN,
        BlockTypeIds::OAK_WALL_SIGN,
        BlockTypeIds::SPRUCE_SIGN,
        BlockTypeIds::SPRUCE_WALL_SIGN,
        BlockTypeIds::WARPED_SIGN,
        BlockTypeIds::WARPED_WALL_SIGN,
    ];

    public static function onPlayerInteractEvent(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $isbuilder = PlayerDataManager::getData($player, "builder", false);

        if (PlayerDataManager::getData($player, "spectator") == "true") {
            $player->continueBreakBlock($player->getPosition(), 0);
            return;
        }

        if ($block->getTypeId() == BlockTypeIds::CHEST) {
            if (PlayerDataManager::getData($player, "openchest") !== "true") {
                $event->cancel();
            } else {
                return;
            }
        }
        
        if (($isbuilder !== "true") &&
            ($block instanceof \pocketmine\block\TrapDoor ||
                $block instanceof \pocketmine\block\FenceGate ||
                $block instanceof \pocketmine\block\Door ||
                $block instanceof \pocketmine\block\Button ||
                $block instanceof \pocketmine\block\PressurePlate)
        ) {
            $event->cancel();
        }
        if (in_array($block->getTypeId(), self::FORM_BLOCK_IDS, true)) {
            $event->cancel();
        }
    }
}
