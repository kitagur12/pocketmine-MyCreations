<?php

namespace hentai\modules;

use DateTime;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class autoclick
{
    private array $cps = [];

    public function check($packet, $player)
    {
        if ($player !== null) {
            if ($packet instanceof PlayerAuthInputPacket) {
                $flags = $packet->getInputFlags();
                if ($flags->get(PlayerAuthInputFlags::MISSED_SWING)) {
                    $this->cps[$player->getXuid()][] = microtime(true);
                }
            }
        }

        if ($packet instanceof InventoryTransactionPacket) {
            $trData = $packet->trData;
            if ($trData instanceof UseItemOnEntityTransactionData) {
                $actionType = $trData->getActionType();
                if ($actionType === 1) {
                    $this->cps[$player->getXuid()][] = microtime(true);
                }
            }
        }

        if (!isset($this->cps[$player->getXuid()])) {
            return;
        }

        foreach ($this->cps[$player->getXuid()] as $key => $time) {
            if ($time + 1000 < microtime(true)) {
                unset($this->cps[$player->getXuid()][$key]);
            }
        }

        //$player->sendMessage(count($this->cps[$player->getXuid()]));
    }
    
    public function update($packet, $player)
    {
    }
}