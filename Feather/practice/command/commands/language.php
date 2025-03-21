<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\player\PracticePlayer;

class language {
    public static function command_language(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new languageCommand());
    }
}

class languageCommand extends Command {

    public function __construct() {
        parent::__construct(
            "language", 
            "Change Server Language",
        );
        $this->setPermission("server.command");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (isset($args[0])) {
            if (PracticePlayer::cheangeLanguage($sender, $args[0])) {
                PracticePlayer::sendMessage($sender, "player.changelanguage", $args);
            } else {
                PracticePlayer::sendMessage($sender, "dummmy_text1", $args);
            }
            

        } else {
            $sender->sendMessage("§c言語を入力してください");
        }
    }
}
