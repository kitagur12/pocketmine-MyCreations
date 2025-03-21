<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\utils\PlayerDataManager;

class builder {
    public static function command_builder(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new builderCommand());
    }
}

class builderCommand extends Command {

    public function __construct() {
        parent::__construct(
            "builder", 
            "change Builder Mode",
            "/builder",
        );
        $this->setPermission("server.admin");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $isbuilder = PlayerDataManager::getData($sender, "builder");
        if ($isbuilder == "true") {
            PlayerDataManager::setData($sender, "builder", "false");
        } else {
            PlayerDataManager::setData($sender, "builder", "true");
        }
    }
}
