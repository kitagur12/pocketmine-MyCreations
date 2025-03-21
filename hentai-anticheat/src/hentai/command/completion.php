<?php

namespace hentai\command;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;

class Completion implements Listener
{
    public function onSend(DataPacketSendEvent $event)
    {
        foreach ($event->getPackets() as $pk) {
            if ($pk instanceof AvailableCommandsPacket) {
                foreach ($pk->commandData as $commandName => $commandData) {
                    if ($commandName === "hentaiac") {
                        $commands = [
                            "debug <target:player>"
                        ];
                        $result = Completion::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                }
                $event->setPackets([$pk]);
            }
        }
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