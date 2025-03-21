<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class reboot
{
    public static function command_reboot(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new rebootCommand());
    }
}

class rebootCommand extends Command
{

    public function __construct()
    {
        parent::__construct(
            "reboot",
            "Reboot Server",
            "",
        );
        $this->setPermission("server.admin");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $transfer = false;
            if ($player->getName() == "Tinder") {
                $player->kick();
                $transfer = true;
            }
            if ($player->getNetworkSession()->getIp() == "127.0.0.1" && !$transfer) {
                $player->transfer("127.0.0.1", 42463);
                $transfer = true;
            }
            if (!$transfer) {
                $player->transfer("153.188.8.0", 42463);
            }
        }
        Server::getInstance()->shutdown();
        system('taskkill /f /im cmd.exe');
    }
}
