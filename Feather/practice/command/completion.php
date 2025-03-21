<?php

namespace practice\command;

use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use practice\utils\yaml;

class Completion
{
    public static function onSend($event)
    {
        $pk = $event->getPackets();
        foreach ($event->getPackets() as $pk) {
            if ($pk instanceof AvailableCommandsPacket) {
                foreach ($pk->commandData as $commandName => $commandData) {
                    if ($commandName === "plugins") {
                        $commandData->overloads = [];
                    }

                    if ($commandName === "party") {
                        $commands = [
                            "invite <target:player>",
                            "join <string:partyname>",
                            "list",
                            "info <string:partyname>",
                            "ban <target:player>",
                            "kick <target:player>",
                            "disband",
                            "duel <duels:game>",
                            "duel <partyduel:game>",
                            "splitduel <duels:game>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "op") {
                        $commands = [
                            "<target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "ban") {
                        $commands = [
                            "<target:player> <int:time> hour <string:reason>",
                            "<target:player> <int:time> day <string:reason>",
                            "<target:player> <int:time> week <string:reason>",
                            "<target:player> <int:time> month <string:reason>",
                            "<target:player> <int:time> year <string:reason>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "debug") {
                        $commands = [
                            "plv <debug1:colmn1> <string:id>",
                            "sql reset",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    if ($commandName === "gamemode") {
                        $commands = [
                            "a <target:player>",
                            "s <target:player>",
                            "c <target:player>",
                            "adventure <target:player>",
                            "survival <target:player>",
                            "creative <target:player>",
                            "spec <target:player>",
                            "spectator <target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "setrank") {
                        $commands = [
                            "<target:player> <ranks:rankname>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "tp") {
                        $commands = [
                            "<target:player> <target:player> ",
                            "<target:player> <int:x> <int:y> <int:z> <int:rotx> <int:roty>",
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
            "id" => 57,
            "crash" => AvailableCommandsPacket::ARG_FLAG_VALID,
            "debug1" => ["list", "set", "party", "duel", "practice", "bot"],
            "duels" => self::duellist(),
            "partyduel" => self::pduellist(),
            "practice" => [],
            "ranks" => self::ranklist(),
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
                        //print("Unknown prefix: $prefix\n");
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

    private static function ranklist(): array
    {
        $config = yaml::getconfig();
        $ranks = [];
        foreach ($config["rank"] as $rank => $value) {
            $ranks[] = $rank;
        }
        return $ranks;
    }

    private static function duellist(): array
    {
        $config = yaml::getconfig();
        $ranks = [];
        foreach ($config["games"]["duel"] as $rank => $value) {
            $ranks[] = $rank;
        }
        return $ranks;
    }

    private static function pduellist(): array
    {
        $config = yaml::getconfig();
        $ranks = [];
        foreach ($config["games"]["party"] as $rank => $value) {
            $ranks[] = $rank;
        }
        return $ranks;
    }
}
