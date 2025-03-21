<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\utils\sql;
use practice\utils\console;
use practice\form\formapi;
use practice\practice\practice as practicegame;
use practice\utils\PlayerDataManager;
use practice\player\PracticePlayer;
use practice\duel\PlayerDuel;
use practice\party\Party;

class debug {
    public static function command_debug(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("debug", new debugCommand());
    }
}

class debugCommand extends Command {
    public function __construct() {
        parent::__construct("debug", "Debug command", "/debug <subcommand>");
        $this->setPermission("pocketmine.command.help");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $return = null;
        $logger = new console();
        if (count($args) === 0) {
            $return = "§cnull content";
        } 
    
        if (count($args) >= 1) {
            if (isset($args[0]) && $args[0] === "test") {
                if (isset($args[1]) && $args[1] === "ta") {
                    formapi::setType($sender, "custom_form");
                    formapi::setTitle($sender, "testtitle");
                    formapi::addLabel($sender, "textLabel");
                    formapi::addInput($sender, "testinput", "textplacefolder", "defalttext");
                    formapi::addToggle($sender, "testtoggle", true);
                    formapi::sendForm($sender, 1);
                    $return = "test-ta";
                }
                if (isset($args[1]) && $args[1] === "tb") {

                    $return = "test-tb";
                }
            }
    
            if (isset($args[0]) && $args[0] === "sql") {
                if (isset($args[1]) && $args[1] === "reset") {
                    sql::reset();
                    $return = "§aSQL Resetted!";
                }
            }

            if (isset($args[0]) && $args[0] === "plv") {
                if (isset($args[1]) && $args[1] === "list") {
                    $playerData = PlayerDataManager::getAllData($sender);
                    foreach ($playerData as $key => $value) {
                        if (is_string($value) && strlen($value) > 30) {
                            $value = substr($value, 0, 27) . "...";
                        }
                        $sender->sendMessage("§eKey:§b $key, §aValue:§b $value");
                    }
                    $return = "";
                } 
                if (isset($args[1]) && $args[1] === "set") {
                    $xuid = $sender->getXuid();
                    $config = PracticePlayer::$playerdata;
                    $text = self::arrayToStringRecursive($config[$xuid]);
                    $sender->sendMessage($text);
                }
                if (isset($args[1]) && $args[1] === "duel") {
                    $xuid = $sender->getXuid();
                    $type = PlayerDataManager::getData($xuid, "gametype");
                    if ($type == "duel") {
                        $text = PlayerDuel::$dueldatas[PlayerDataManager::getData($xuid, "duelid")];
                        $text = json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $sender->sendMessage($text);
                    }
                }
                if (isset($args[1]) && $args[1] === "party") {
                    $xuid = $sender->getXuid();
                    $type = PlayerDataManager::getData($xuid, "party");
                    if ($type == "true") {
                        $text = Party::$parties[PlayerDataManager::getData($xuid, "party_name")];
                        $text = json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        $sender->sendMessage($text);
                    }
                }
                if (isset($args[1]) && $args[1] === "practice") {
                    $xuid = $sender->getXuid();
                    $text = practicegame::$practicedatas[PlayerDataManager::getData($xuid, "practiceid")] ?? [];
                    $text = json_encode($text, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $sender->sendMessage($text);
                }
                if (isset($args[1]) && $args[1] === "bot") {
                    $xuid = $sender->getXuid();
                }
            }
        }        
        if ($return == null) {
            
            $logger->info("§cWrong content");
        } else {
            $logger->info($return);
        }
    }

    private static function arrayToStringRecursive(array $array): string {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::arrayToStringRecursive($value);
            } elseif (!is_string($value)) {
                $value = var_export($value, true);
            }
            $result[] = "$key: $value";
        }
        return '{' . implode(', ', $result) . '}';
    }
}
