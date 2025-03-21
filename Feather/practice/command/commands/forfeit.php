<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\utils\PlayerDataManager;
use practice\handler\PlayerDeathHandler;

class forfeit {
    public static function command_forfeit(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new forfeitCommand());
    }
}

class forfeitCommand extends Command {

    public function __construct() {
        parent::__construct(
            "forfeit", 
            "discription",
            "/forfeit",
            ["ff", "suicide"]
        );
        $this->setPermission("server.command");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        PlayerDataManager::setdata($sender, "allive", "false");
        PlayerDeathHandler::PlayerDeath($sender);
    }
}
