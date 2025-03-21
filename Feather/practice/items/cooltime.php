<?php

namespace practice\items;

use practice\utils\yaml;
use practice\utils\PlayerDataManager;
use practice\party\Party;

class cooltime
{
    public static array $cooltime = [];
    public static function item($player, $item): bool
    {
        $config = yaml::getconfig();
        $gametype = PlayerDataManager::getdata($player, "gametype");
        if ($gametype !== "practice") {
            $game = PlayerDataManager::getdata($player, "ingame");
            $itemlist = [
                "Ender Pearl" => "ender_pearl",
                "Snowball" => "snowball",
                "Steak" => "steak",
                "Golden Apple" => "gapple",
                "Enchanted Golden Apple" => "enchant_gapple",
                "Gold Ingot" => "gapple_head",
                "Arrow" => "arrow",
                "Potion" => "potion",
                "Splash Potion" => "splash_potion",
                "Totem" => "totem",
            ];
            if (PlayerDataManager::getdata($player, "itemuse") !== "false") {
                if (in_array($item->getVanillaName(), array_keys($itemlist))) {
                    if (isset($config["games"][$gametype][$game]["itemcooltime"])) {
                        $cooltimekit = $config["games"][$gametype][$game]["itemcooltime"];
                        $cooltimedata = $config["itemcooltime"][$cooltimekit];
                        if (isset($cooltimedata[$itemlist[$item->getVanillaName()]])) {
                            $xuid = $player->getXuid();
                            $item = $itemlist[$item->getVanillaName()];
                            $time = microtime(true);
                            if (isset(self::$cooltime[$xuid][$item])) {
                                $itemc = self::$cooltime[$xuid][$item];
                                $cooltime = $cooltimedata[$item];
                                if (PlayerDataManager::getData($player, "party") == "true") {
                                    $partyid = PlayerDataManager::getData($player, "party_name");
                                    if (Party::$parties[$partyid]["induel"] && Party::$parties[$partyid]["split"]) {
                                        $cooltime = Party::$parties[$partyid][$game]["cooltime"][$item];
                                    }
                                }
                                $delay = $itemc - $time;
                                $cooltime = 0 - $cooltime;
                                if ($delay < $cooltime) {
                                    self::$cooltime[$xuid][$item] = $time;
                                    return true;
                                } else {
                                    return false;
                                }
                            } else {
                                self::$cooltime[$xuid][$item] = $time;
                                return true;
                            }
                        } else {
                            return true;
                        }
                    }
                }
            }
        }
        return true;
    }

    public static function reset($player)
    {
        $xuid = $player->getXuid();
        if (isset(self::$cooltime[$xuid])) {
            foreach (self::$cooltime[$xuid] as $key => $value) {
                self::$cooltime[$xuid][$key] = 0;
            }
        }
    }
}
