<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class hook {
    public static array $hook = [];

    public function __construct()
    {
        self::$hook[0] = 0.05;
        self::$hook[0] = 0.05;
    }

    public static function command_hook(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("hook", new hookCommand());
    }
}

class hookCommand extends Command {

    public function __construct() {
        parent::__construct("hook");
        $this->setPermission("server.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 2) {
            hook::$hook[0] = $args[0];
            hook::$hook[1] = $args[1];
        }
    }
}