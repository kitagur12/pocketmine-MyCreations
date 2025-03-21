<?php

namespace practice\handler\method;

use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\player\PracticePlayer;
use pocketmine\Server;
use practice\handler\ChatHandler;

class PlayerChatEventHandler
{

    public static function onPlayerChatEvent($event)
    {
        $player = $event->getPlayer();
        $message = $event->getMessage();
        $xuid = $player->getXuid();
        $rank = PracticePlayer::$playerdata[$xuid]["status"]["rank"];
        $event->cancel();
        if ($rank == "mute") {
            $PlayerList = [];
            foreach (Server::getInstance()->getOnlinePlayers() as $pplayer) {
                $permissions = $pplayer->getEffectivePermissions();
                foreach ($permissions as $permission) {
                    if ($permission->getPermission() == "server.admin") {
                        $PlayerList[] = $pplayer;
                    }
                }
            }
            $PlayerList[] = $player;
        } else {
            $PlayerList = Server::getInstance()->getOnlinePlayers();
        }
        $config = yaml::getconfig();
        $rank = PracticePlayer::$playerdata[$xuid]["status"]["rank"];
        $name = PlayerDataManager::getdata($player, "name");
        $showrank = $config["rank"][$rank]["show"];
        $rankcolor = $config["rank"][$rank]["color"];
        if ($rank == "member") {
            $showrank = $rankcolor . $name;
        } else {
            $showrank = "§7[" . $rankcolor . $showrank . "§r§7] " . $rankcolor . $name;
        }
        $messageformat = $config["chatformat"];
        $message = str_replace("§", "*", $message);
        $messageformat = str_replace("<message>", $message, $messageformat);
        $messageformat = str_replace("<player>", $showrank, $messageformat);
        foreach ($PlayerList as $players) {
            ChatHandler::sendMessage($players, $messageformat, false, "chat");
        }
    }
}
