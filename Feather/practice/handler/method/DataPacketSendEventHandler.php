<?php

namespace practice\handler\method;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use practice\utils\PlayerDataManager;
use practice\command\Completion;
use practice\player\PracticePlayer;

class DataPacketSendEventHandler
{
    public static $oldBlocksFullId = [];
    public static function onPacketSend($event)
    {
        $packets = $event->getPackets();
        $players = $event->getTargets();
        foreach ($packets as $packet) {
            if ($packet instanceof AvailableCommandsPacket) {
                Completion::onSend($event);
            }

            if ($packet instanceof MovePlayerPacket) {
                foreach ($players as $player) {
                    $playerInstance = $player->getPlayer();
                    $isspec = PlayerDataManager::getData($playerInstance, "blocktp", false);
                    if ($isspec == "true") {
                        $event->cancel();
                    }
                }
            }

            if ($packet instanceof SetTimePacket) {
                foreach ($players as $player) {
                    $playerInstance = $player->getPlayer();
                    $packet->time = PracticePlayer::$playerdata[$playerInstance->getXuid()]["settings"]["time"];
                }
            }
            if ($packet instanceof PlayerListPacket) {
                foreach ($packet->entries as $entry) {
                    if ($entry instanceof PlayerListEntry) {
                        $entry->xboxUserId = "0";
                    }
                }
            }

            if ($packet instanceof AnimatePacket && $packet->action === 4 && $packet->float == 0) {
                foreach ($players as $player) {
                    $playerInstance = $player->getPlayer();
                    $xuid = $playerInstance->getXuid();
                    if (PracticePlayer::$playerdata[$xuid]["settings"]["crit_particle"] == 1) {
                        $packet->float = 1;
                    }
                }
            }
        }
    }
}
