<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\form\Forms;

class settings
{
    public static function command_settings(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("settings", new settingsCommand());
    }
}

class settingsCommand extends Command
{
    public function __construct()
    {
        parent::__construct("settings", "spawn settings", "/settings", ["spawn", "hub"]);
        $this->setPermission("server.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        Forms::settings($sender, 1, true);
    }
}
