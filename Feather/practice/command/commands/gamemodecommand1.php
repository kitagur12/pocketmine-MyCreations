<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use practice\command\selector;
use pocketmine\Server;
use pocketmine\player\GameMode;

class gamemodecommand1
{
    public static function command_gamemode(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new GamemodeCommand());
    }
}

class GamemodeCommand extends Command
{
    private static GameMode $mode = GameMode::SURVIVAL;

    public function __construct()
    {
        parent::__construct(
            "gamemode",
            "change gamemode"
        );
        $this->setPermission("server.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (count($args) >= 1) {
            if ($args[0] === "c" || $args[0] === "creative") {
                self::$mode = GameMode::CREATIVE;
            } elseif ($args[0] === "s" || $args[0] === "survival") {
                self::$mode = GameMode::SURVIVAL;
            } elseif ($args[0] === "a" || $args[0] === "adventure") {
                self::$mode = GameMode::ADVENTURE;
            } elseif ($args[0] === "spec" || $args[0] === "spectator" || $args[0] === "sp") {
                self::$mode = GameMode::SPECTATOR;
            }

            if (count($args) >= 2) {
                $target = selector::selector($sender, $args[1]);
            } else {
                $target = [$sender];
            }

            if ($target !== null) {
                foreach ($target as $player) {
                    $player->setGamemode(self::$mode);
                }
            } else {
            }
        } else {
        }
    }
}
