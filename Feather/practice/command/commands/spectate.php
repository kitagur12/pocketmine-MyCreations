<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\form\Forms;

class spectate
{
    public static function command_spectate(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("spectate", new spectateCommand());
    }
}

class spectateCommand extends Command
{
    public function __construct()
    {
        parent::__construct("spectate", "spawn spectate", "/spectate", ["spawn", "hub"]);
        $this->setPermission("server.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        Forms::spectate($sender, 1, true);
    }
}
