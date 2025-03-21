<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\arena\ArenaManaer;

class lobby {
    public static function command_lobby(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("lobby", new lobbyCommand());
    }
}

class lobbyCommand extends Command {
    public function __construct() {
        parent::__construct("lobby", "spawn lobby", "/lobby", ["spawn", "hub"]);
        $this->setPermission("server.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        ArenaManaer::joinlobby($sender);
    }
}
