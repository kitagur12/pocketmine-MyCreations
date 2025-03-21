<?php

namespace hentai\modules;

use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use hentai\info;

class invmove
{
    public function check($packet, $player)
    {
        $xuid = $player->getXuid();
        if ($packet instanceof PlayerAuthInputPacket) {
            $flags = $packet->getInputFlags();
            //var_dump($packet);
            $ping = $player->getNetworkSession()->getPing();
            $isinvopen = info::$playerinfo[$xuid]["invmove"]["open_inv"] ?? false;
            //var_dump($isinvopen);
            if ($isinvopen == false) {
                return;
            }

            $pinglag = info::$config["config"]["checks"]["inventoryMove"]["pinglag"];
            $multiplier = info::$config["config"]["checks"]["inventoryMove"]["multiplier"];
            $range = info::$config["config"]["checks"]["inventoryMove"]["range"];

            if (info::$playerinfo[$xuid]["invmove"]["open_invtime"] + round($ping * ($multiplier + ($ping / $range))) + $pinglag < round(microtime(true) * 1000)) {
                $tempscore = info::$config["config"]["checks"]["inventoryMove"]["modulelv"];
                /*
                foreach ($checklist as $input) {
                    if ($flags->get($input) || $packet->getCameraOrientation() !== info::$playerinfo[$xuid]["invmove"]["Camera"]) {
                        var_dump($input);
                        info::$playerinfo[$xuid]["invmove"]["Camera"] = $packet->getCameraOrientation();
                        info::$playerinfo[$xuid]["invmove"]["module_score"] += $tempscore;
                        if (info::$playerinfo[$xuid]["invmove"]["module_score"] > 100) {
                            $lv = info::$config["config"]["checks"]["inventoryMove"]["lv"];
                            info::$playerinfo[$xuid]["global"]["check_score"] += $lv;
                            info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] = info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] ?? 0;
                            info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] += $lv;
                        }
                    }
                }
                    */
            }
        }

        if ($packet instanceof ContainerClosePacket) {
            info::$playerinfo[$xuid]["invmove"]["open_inv"] = false;
        }
    }

    public function update($packet, $player)
    {
        $xuid = $player->getXuid();
        if ($packet instanceof ContainerOpenPacket) {
            info::$playerinfo[$xuid]["invmove"]["open_inv"] = true;
            info::$playerinfo[$xuid]["invmove"]["Camera"] = false;
            info::$playerinfo[$xuid]["invmove"]["opened_inv"] = false;
            info::$playerinfo[$xuid]["invmove"]["module_score"] = 0;
            info::$playerinfo[$xuid]["invmove"]["open_invtime"] = round(microtime(true) * 1000);
            //var_dump(info::$playerinfo[$xuid]["invmove"]["open_invtime"]);
        }
    }
}
