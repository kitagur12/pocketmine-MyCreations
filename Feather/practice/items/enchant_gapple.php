<?php

namespace practice\items;

use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use practice\items\cooltime;
use practice\utils\PlayerDataManager;

class enchant_gapple
{
    public static function use($event, $player, $item): void
    {
        if (!cooltime::item($player, $item)) {
            $event->cancel();
        } else {
            $ingame = PlayerDataManager::getData($player, "ingame");
            if ($ingame !== "crystal") {
                $effectManager = $player->getEffects();
                $REGENERATION = new EffectInstance(VanillaEffects::REGENERATION(), 600, 4, false);
                $effectManager->add($REGENERATION);
            }
        }
    }
}
