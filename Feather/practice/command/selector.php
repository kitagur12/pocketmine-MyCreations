<?php

namespace practice\command;

use pocketmine\Server;
use pocketmine\player\Player;

class Selector {
    public static function selector($sender, string $name): mixed {
        $server = Server::getInstance();
        $targetPlayers = null;
        switch (strtolower($name)) {
            case "@a":
                $targetPlayers = null;
                break;

            case "@p":
                $closestDistance = PHP_INT_MAX;
                $closestPlayer = null;
                foreach ($server->getOnlinePlayers() as $player) {
                    if ($player !== $sender) {
                        $distance = $sender->getPosition()->distance($player->getPosition());
                        if ($distance < $closestDistance) {
                            $closestDistance = $distance;
                            $closestPlayer = $player;
                        }
                    }
                }
                if ($closestPlayer !== null) {
                    $targetPlayers = $closestPlayer;
                }
                break;

            case "@r":
                $onlinePlayers = $server->getOnlinePlayers();
                if (!empty($onlinePlayers)) {
                    $targetPlayers = $onlinePlayers[array_rand($onlinePlayers)];
                }
                break;

            case "@s":
                if ($sender instanceof Player) {
                    $targetPlayers = $sender;
                }
                break;

            default:
                $specificPlayer = $server->getPlayerByPrefix($name);
                if ($specificPlayer !== null) {
                    $targetPlayers = $specificPlayer;
                }
                break;
        }
        
        return $targetPlayers;
    }
}
