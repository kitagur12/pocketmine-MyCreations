<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\permission\Permission;
use pocketmine\player\Player;

class console {
    public static function command_console(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new consoleCommand());
    }
}

class consoleCommand extends Command {

    public function __construct() {
        parent::__construct(
            "console",
        );
        $this->setPermission("pocketmine.broadcast.admin");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $permissionNode = "server.admin";
        $plugin = Server::getInstance()->getPluginManager()->getPlugin("Feather");
        $attachment = $sender->addAttachment($plugin);
        $attachment->setPermission($permissionNode, true);
        $sender->sendMessage("コンソールにカスタム権限 '$permissionNode' が付与されました！");
    }
}
