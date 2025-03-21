<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\command\selector;
use practice\player\PracticePlayer;
use practice\utils\yaml;

class setrank
{
    public static function command_setrank(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new setrankCommand());
    }
}

class setrankCommand extends Command
{

    public function __construct()
    {
        parent::__construct(
            "setrank",
            "Player Set Rank",
            "/setrank",
        );
        $this->setPermission("server.admin");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (isset($args[1])) {
            $target = selector::selector($sender, $args[0]);
            $xuid = $target->getXuid();
            $config = yaml::getconfig();
            var_dump(array_keys($config["rank"])); 
            if (in_array($args[1], array_keys($config["rank"]))) {
                PracticePlayer::$playerdata[$xuid]["status"]["rank"] = $args[1];
                PracticePlayer::UpdateRank($target);
            }
        }
    }
}
