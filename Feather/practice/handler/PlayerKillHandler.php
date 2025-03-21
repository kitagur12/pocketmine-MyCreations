<?php

namespace practice\handler;

use pocketmine\item\SplashPotion;
use pocketmine\item\Totem;
use practice\player\PracticePlayer;
use practice\utils\PlayerDataManager;
use practice\handler\ChatHandler;
use practice\utils\yaml;
use practice\entity\lightingbolt;
use practice\player\PlayerKits;
use practice\player\scoreboard\Scorehud;

class PlayerKillHandler
{
    public static function ffaPlayerKill($player, $death)
    {
        Scorehud::UpdateScore($player);
        $xuid = $player->getXuid();
        $dxuid = $death->getXuid();
        $config = PracticePlayer::$playerdata;
        $config[$xuid]["status"]["kill"] = $config[$xuid]["status"]["kill"] + 1;
        $config[$dxuid]["status"]["death"] = $config[$dxuid]["status"]["death"] + 1;
        PlayerDataManager::setData($player, "combat_lasthit", "0", false);
        PlayerDataManager::setData($player, "combatplayer", "dummy", false);
        $killphase = yaml::getcosmeticconfig("killphase");
        $killphase = $killphase["killphase"][$config[$xuid]["cosmetics"]["useing"]["killphase"]];
        $config[$xuid]["status"]["streak"] += 1;
        $config[$dxuid]["status"]["streak"] = 0;
        $message = self::createKillphase($killphase, $player, $death);
        $player->extinguish();
        $player->getHungerManager()->setFood(20);
        $player->setHealth(999);
        ChatHandler::sendMessage($player, $message, false, "killlog");
        ChatHandler::sendMessage($death, $message, false, "killlog");
        PracticePlayer::resetstatus($player);
        if ($config[$xuid]["settings"]["lighting_kill"] == true) {
            lightingbolt::sendLightning($player, $death->getPosition());
            PracticePlayer::playSound($player, "cauldron.explode", 2);
        }
        PracticePlayer::$playerdata = $config;
        $type = PlayerDataManager::getData($player, "gametype");
        $game = PlayerDataManager::getData($player, "ingame");
        PlayerKits::getkit($player, $type, $game);
        PlayerKits::getarrow($player, $type, $game);
    }

    public static function DuelPlayerKill($player, $death)
    {
        $xuid = $player->getXuid();
        $config = PracticePlayer::$playerdata;
        PlayerDataManager::setData($player, "combatplayer", ".");
        if ($config[$xuid]["settings"]["lighting_kill"] == true) {
            lightingbolt::sendLightning($player, $death->getPosition());
            PracticePlayer::playSound($player, "cauldron.explode", 2);
        }
    }

    public static function createKillphase($message, $killer, $death): string
    {
        $isset = false;
        $killern = $killer->getName();
        $deathn = $death->getName();

        if (stripos(PlayerDataManager::getdata($killer, "ingame"), "nodebuff") !== false) {
            $isset = true;
            $inventory = $killer->getInventory();

            $killerpot = 0;
            foreach ($inventory->getContents() as $item) {
                if ($item instanceof SplashPotion) {
                    $killerpot += $item->getCount();
                }
            }

            $inventory = $death->getInventory();
            $deathpot = 0;
            foreach ($inventory->getContents() as $item) {
                if ($item instanceof SplashPotion) {
                    $deathpot += $item->getCount();
                }
            }

            $message = str_replace("<player>", "§c" . $deathn . " §7[§c" . $deathpot . "§7]§r", $message);
            $message = str_replace("<killer>", "§a" . $killern . " §7[§a" . $killerpot . "§7]§r", $message);
        } elseif (PlayerDataManager::getdata($killer, "ingame") == "crystal") {
            $isset = true;
            $inventory = $killer->getInventory();

            $killertotem = 0;
            foreach ($inventory->getContents() as $item) {
                if ($item instanceof Totem) {
                    $killertotem += $item->getCount();
                }
            }

            $inventory = $death->getInventory();
            $deathtotem = 0;
            foreach ($inventory->getContents() as $item) {
                if ($item instanceof Totem) {
                    $deathtotem += $item->getCount();
                }
            }

            $message = str_replace("<player>", "§c" . $deathn . " §7[§c" . $deathtotem . "§7]§r", $message);
            $message = str_replace("<killer>", "§a" . $killern . " §7[§a" . $killertotem . "§7]§r", $message);
        }
        if (PlayerDataManager::getdata($killer, "ingame") == "midfight") {
            $isset = true;

            $killerhealth = $killer->getHealth();

            $message = str_replace("<player>", "§c" . $deathn, $message);
            $message = str_replace("<killer>", "§a" . $killern . " §7[§a" . $killerhealth . "§7]§r", $message);
        }
        if (!$isset) {
            $message = str_replace("<player>", "§c" . $deathn, $message);
            $message = str_replace("<killer>", "§a" . $killern, $message);
        }
        return $message;
    }
}
