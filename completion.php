<?php

namespace practice\command;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;

class Completion {
    public static function onSend($event) {
        $pk = $event->getPackets();
        foreach ($event->getPackets() as $pk) {
            if ($pk instanceof AvailableCommandsPacket) {
                foreach ($pk->commandData as $commandName => $commandData) {
                    if ($commandName === "testcommand") {
                        $commands = [
                            "command1" => "test conpletion <target:player> <string:key> <value:key> <op:opppp-ai-> <float:iguw> <int:key> <json:key> <command:key> <filepath:key> <rawtext:key> <message:key> <block_states:key>",
                            "command2" => "bbb <target:player> ccc §l§bO§aM§cG§r  <string:aaaaaa>",
                            "command3" => "test conpletion2 <float:iguw> <int:key> <json:key> <command:key> <filepath:key> <rawtext:key> <message:key> <block_states:key>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "plugins") {
                        $commandData->overloads = [];
                    }

                    if ($commandName === "op") {
                        $commands = [
                            "command1" => "<target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    
                    if ($commandName === "ban") {
                        $commands = [
                            "command1" => "<target:player> <int:time> hour <string:reason>",
                            "command2" => "<target:player> <int:time> day <string:reason>",
                            "command3" => "<target:player> <int:time> week <string:reason>",
                            "command4" => "<target:player> <int:time> month <string:reason>",
                            "command5" => "<target:player> <int:time> year <string:reason>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "debug") {
                        $commands = [
                            "command1" => "plv list <target:player> <string:key>",
                            "command2" => "plv set <target:player> <string:key> <string:value>",
                            "command3" => "plv seta",
                            "command4" => "sql reset",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    if ($commandName === "gamemode") {
                        $commands = [
                            "command1" => "a <target:player>",
                            "command2" => "s <target:player>",
                            "command3" => "c <target:player>",
                            "command4" => "adventure <target:player>",
                            "command5" => "survival <target:player>",
                            "command6" => "creative <target:player>",
                            "command7" => "spec <target:player>",
                            "command8" => "spectator <target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "setrank") {
                        $commands = [
                            "command1" => "<target:player> <string:rankname>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "tp") {
                        $commands = [
                            "command1" => "<target:player> <target:player> ",
                            "command2" => "<target:player> <int:x> <int:y> <int:z> <int:rotx> <int:roty>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                }
            }
        }
        $event->setPackets([$pk]);
    }

    private static function subcommand(array $commands): array
    {
        $overloads = [];
        $typeMapping = [
            "target" => AvailableCommandsPacket::ARG_TYPE_TARGET,
            "string" => AvailableCommandsPacket::ARG_TYPE_STRING,
            "int" => AvailableCommandsPacket::ARG_TYPE_INT,
            "float" => AvailableCommandsPacket::ARG_TYPE_FLOAT,
            "value" => AvailableCommandsPacket::ARG_TYPE_VALUE,
            "op" => AvailableCommandsPacket::ARG_TYPE_OPERATOR,
            "rawtext" => AvailableCommandsPacket::ARG_TYPE_RAWTEXT,
            "json" => AvailableCommandsPacket::ARG_TYPE_JSON,
            "block_states" => AvailableCommandsPacket::ARG_TYPE_BLOCK_STATES,
            "command" => AvailableCommandsPacket::ARG_TYPE_COMMAND,
            "filepath" => AvailableCommandsPacket::ARG_TYPE_FILEPATH,
            "message" => AvailableCommandsPacket::ARG_TYPE_MESSAGE,
            "crash" => AvailableCommandsPacket::ARG_FLAG_VALID,
            "party" => ["party", "info"],
            "modulename" => ["autoclick(a)", "autoclick(b)", "fly(a)", "fly(b)"],
            "unit" => ["minute", "hour", "day", "week", "month", "year"],
        ];

        foreach ($commands as $command) {
            $commandParts = explode(" ", $command);
            $params = [];
            foreach ($commandParts as $part) {
                if (preg_match('/^<([^:>]+):([^>]+)>$/', $part, $matches)) {
                    [$full, $prefix, $name] = $matches;
                    if (isset($typeMapping[$prefix])) {
                    if (!is_int($typeMapping[$prefix])) {
                        $sb = CommandParameter::enum($name, new CommandEnum("$prefix", $typeMapping[$prefix]), 0);
                    } elseif (isset($typeMapping[$prefix])) {
                        $sb = CommandParameter::standard($name, $typeMapping[$prefix], 0, true);
                    }
                } else {
                    print("Unknown prefix: $prefix\n");
                }
                } else {
                    $enum = new CommandEnum($part, [$part]);
                    $sb = CommandParameter::enum($part, $enum, 0);
                }
                $params[] = $sb;
            }
            $overloads[] = new CommandOverload(false, $params);
        }

        return $overloads;
    }
}
