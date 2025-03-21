<?php

namespace hentai\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use hentai\info;


class hentaiac
{
    public static function command(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new hentaiacCommand());
    }
}

class hentaiacCommand extends Command
{

    public function __construct()
    {
        parent::__construct(
            "hentaiac",
            "discription",
            "/hentaiac",
            ["", ""]
        );
        $config = info::$config;
        $commandPerm = $config['config']['other']['commandperm'];
        $this->setPermission($commandPerm);
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        switch ($args[0]) {
            case "debug":
                if (!isset($args[1])) {
                    return;
                }
                if (isset($args[1])) {
                    if (!isset($args[2])) {
                        if (!$sender instanceof Player) {
                            Server::getInstance()->getLogger()->info("§cFor consoles, use the selector");
                            return;
                        }

                        $data = info::$playerinfo[$sender->getXuid()];
                        $json = json_encode($data, JSON_PRETTY_PRINT);
                        $sender->sendMessage($json);
                    }

                    if (isset($args[2])) {
                        $server = Server::getInstance();
                        $specificPlayer = $server->getPlayerByPrefix($args[2]);
                        if ($specificPlayer == null) {
                            $sender->sendMessage("§cCan't find Player.");
                        } else {
                            $data = info::$playerinfo[$specificPlayer->getXuid()];
                            $json = json_encode($data, JSON_PRETTY_PRINT);
                            $sender->sendMessage("§cCan't find Player.");
                        }
                    }
                }
        }
    }
}