<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use system\utils\console\console;
use pocketmine\lang\Translatable;

class help {
    public static function command_help(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("help", new helpCommand());
    }
}

class helpCommand extends Command {

    public function __construct() {
        parent::__construct(
            "help",
            "all command information",
            "/help",
            ["?"]
        );
        $this->setPermission("server.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $logger = new console();
        self::listAllCommands();
    }

    public static function listAllCommands(): void {
        $commandMap = Server::getInstance()->getCommandMap();
        $logger = new console();
        $commands = $commandMap->getCommands();
        $displayedCommands = [];

        foreach ($commands as $command) {
            if ($command instanceof Command) {
                $commandName = $command->getName();
                
                if (in_array($commandName, $displayedCommands)) {
                    continue;
                }

                if ($commandName instanceof Translatable) {
                    $commandName = Server::getInstance()->getLanguage()->translate($commandName);
                }

                $description = $command->getDescription();
                if ($description instanceof Translatable) {
                    $description = Server::getInstance()->getLanguage()->translate($description);
                }

                $aliases = $command->getAliases();
                $aliasString = implode(", ", $aliases);
                $logger->info("Command: $commandName - Description: $description - Aliases: $aliasString");
                $displayedCommands[] = $commandName;
            }
        }
    }
}
