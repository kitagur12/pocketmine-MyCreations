<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use system\player\PlayerDataManager;
use system\utils\console\console;
use system\player\banplayer;


class unban {
    public static function command_unban(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("unban", new unbanCommand());
    }
}

class unbanCommand extends Command {

    public function __construct() {
        parent::__construct("unban");
        $this->setPermission("server.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 1) {
        banplayer::unban($args[0]);
        }
    }
}