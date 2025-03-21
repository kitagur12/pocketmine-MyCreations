<?php

namespace practice\items;

use practice\items\cooltime;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\item\VanillaItems;

class gapplehead
{
    public static function use($player, $item): void
    {
        if (cooltime::item($player, $item)) {
            $goldIngot = VanillaItems::GOLD_INGOT();
            $inventory = $player->getInventory();
            if ($inventory->contains($goldIngot)) {
                $inventory->removeItem($goldIngot->setCount(1));
                $effectManager = $player->getEffects();
                $nightVision = new EffectInstance(VanillaEffects::SPEED(), 180, 0, false);
                $effectManager->add($nightVision);
                $Regeneration = new EffectInstance(VanillaEffects::REGENERATION(), 100, 0, false);
                $Regeneration->setAmplifier(2);
                $effectManager->add($Regeneration);
            }
        }
    }
}
