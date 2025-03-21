<?php

namespace practice\player\scoreboard;

use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use practice\utils\yaml;

class ScoreboardAPI
{

    public static array $scoreboards = [];
    private static $line = 0;

    public static function sendScoreboard($player): void
    {
        self::removeScore($player);
        $displaySlot = SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR;
        $objectiveName = "dummy";
        $displayName = "§l" . yaml::getcolor() . "Feather";
        $criteriaName = "dummy";

        $sortOrder = SetDisplayObjectivePacket::SORT_ORDER_ASCENDING;
        $packet = SetDisplayObjectivePacket::create($displaySlot, $objectiveName, $displayName, $criteriaName, $sortOrder);
        $player->getNetworkSession()->sendDataPacket($packet);
        self::$line = 0;
    }

    public static function addline($player, $message, $line = false): void
    {
        $entry = new ScorePacketEntry;
        $entry->objectiveName = "dummy";
        $entry->type = 3;
        if ($line) {
            $entry->customName = $message;
        } else {
            $entry->customName = " " . $message;
        }
        $entry->score = self::$line;
        $entry->scoreboardId = self::$line;

        $pk = new SetScorePacket();
        $pk->type = $pk::TYPE_CHANGE;
        $pk->entries[] = $entry;
        $player->getNetworkSession()->sendDataPacket($pk);
        self::$line = self::$line + 1;
    }

    public static function removeScore($player): void{
        $objectiveName = "dummy";
        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = $objectiveName;
        $player->getNetworkSession()->sendDataPacket($pk);
        unset(self::$scoreboards[strtolower($player->getName())]);
    }
}
