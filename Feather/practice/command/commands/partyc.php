<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use practice\form\Forms;
use pocketmine\Server;
use practice\party\party;
use practice\utils\PlayerDataManager;
use practice\command\Selector;
use practice\player\PracticePlayer;
use practice\handler\ChatHandler;
use practice\form\invformapi;
use practice\utils\yaml;
use practice\player\PlayerQueue;
use practice\player\PlayerKits;

class partyc
{
    public static function command_party(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new partyCommand());
    }
}

class partyCommand extends Command
{

    public function __construct()
    {
        parent::__construct(
            "party",
            "Party Command",
            "/party",
            ["party", "p"]
        );
        $this->setPermission("server.command");
    }


    public function execute($sender, string $commandLabel, array $args): void
    {
        if (count($args) >= 1) {
            if (isset($args[0]) && $args[0] === "join") {
                if (isset($args[1])) {
                    $namelist = [];
                    foreach (party::$parties as $id => $party) {
                        $namelist[$party["name"]] = $id;
                    }
                    if (isset($namelist[$args[1]])) {
                        party::addPlayer($namelist[$args[1]], $sender);
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                }
            }

            if (isset($args[0]) && $args[0] === "invite") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true" || party::$parties[PlayerDataManager::getdata($sender, "party_name")]["member_invite"]) {
                        if (isset($args[1])) {
                            $player = Selector::selector($sender, $args[1]);
                            if ($player == null) {
                                $list = PracticePlayer::getLangText($sender, "c.player.undefined");
                                $text = "§c" . $list;
                                ChatHandler::sendMessage($sender, $text, false);
                                PracticePlayer::playSound($sender, "note.bass");
                            } else {
                                Party::invitePlayer(PlayerDataManager::getdata($sender, "party_name"), $player->getName());
                            }
                        } else {
                            $list = PracticePlayer::getLangText($sender, "c.player.useage");
                            $text = "§c" . $list . "/party invite <player>";
                            ChatHandler::sendMessage($sender, $text, false);
                        }
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                    return;
                }
            }

            if (isset($args[0]) && $args[0] === "list") {
                if (count(party::$parties) == 0) {
                    $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.list");
                    $text = "---------§c" . $list . "§r---------\n";
                    foreach (party::$parties as $id => $party) {
                        $text .= "§c" . $party["name"] . "§r: ";
                        $text .= "Member: " . count($party["players"]) . " ";
                        if ($party["open"]) {
                            $opentext = PracticePlayer::getLangText($sender, "c.party.open");
                            $text .= $opentext . "\n";
                        } else {
                            $opentext = PracticePlayer::getLangText($sender, "c.party.close");
                            $text .= $opentext . "§c\n";
                        }
                    }
                    ChatHandler::sendMessage($sender, $text, false);
                }
            }

            if (isset($args[0]) && $args[0] === "info") {
                $id = "a";
                if (isset($args[1])) {
                    $namelist = [];
                    foreach (party::$parties as $id => $party) {
                        $namelist[$party["name"]] = $id;
                    }
                    if (isset($namelist[$args[1]])) {
                        $id = $namelist[$args[1]];
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                        return;
                    }
                } else {
                    if (PlayerDataManager::getdata($sender, "party") == "true") {
                        $id = PlayerDataManager::getdata($sender, "party_name");
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                        return;
                    }
                }

                $ownertext = PracticePlayer::getLangText($sender, "c.party.owner");
                $playerstext = PracticePlayer::getLangText($sender, "c.party.players");
                $blacklisttext = PracticePlayer::getLangText($sender, "c.party.blacklist");
                $list = PracticePlayer::getLangText($sender, "c.party.list");

                $players = [];
                if (isset(party::$parties[$id])) {
                    foreach (party::$parties[$id]["players"] as $player) {
                        $players[] = $player->getName();
                    }
                    $players = implode(", ", $players);
                    $name = party::$parties[$id]["name"];
                    $open = party::$parties[$id]["open"];
                    $owner = party::$parties[$id]["owner"]->getName();
                    $blacklist = party::$parties[$id]["blacklist"];
                    if (count($blacklist) == 0) {
                        $blacklist = "";
                    } else {
                        $blacklist = "§c" . $blacklisttext . "§r: " . implode(", ", $blacklist) . "\n";
                    }

                    $text = "---------§c" . $name . "§r---------\n";
                    $text .= "§c" . $ownertext . "§r: " . $owner . "\n";
                    $text .= "§c" . $playerstext . "§r: " . $players . "\n";
                    $text .= $blacklist;
                    if ($open) {
                        $opentext = PracticePlayer::getLangText($sender, "c.party.open");
                        $text .= $opentext . "\n";
                    } else {
                        $opentext = PracticePlayer::getLangText($sender, "c.party.close");
                        $text .= $opentext . "§c\n";
                    }
                    ChatHandler::sendMessage($sender, $text, false);
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }

            if (isset($args[0]) && $args[0] === "ban") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true") {
                        if (isset($args[1])) {
                            $player = Selector::selector($sender, $args[1]);
                            if ($player == null) {
                                $list = PracticePlayer::getLangText($sender, "c.player.undefined");
                                $text = "§c" . $list;
                                ChatHandler::sendMessage($sender, $text, false);
                                PracticePlayer::playSound($sender, "note.bass");
                            } else {
                                if (PlayerDataManager::getdata($player, "party") == "true" && PlayerDataManager::getdata($player, "party_name") == PlayerDataManager::getdata($sender, "party_name")) {
                                    if (PlayerDataManager::getdata($player, "party_owner") !== "true") {
                                        party::blacklistPlayer(PlayerDataManager::getdata($sender, "party_name"), $player);
                                        $list = PracticePlayer::getLangText($sender, "c.player.process");
                                        $text = "§a" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "random.orb");
                                    } else {
                                        $list = PracticePlayer::getLangText($sender, "c.permission");
                                        $text = "§c" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "note.bass");
                                    }
                                } else {
                                    $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                                    $text = "§c" . $list;
                                    ChatHandler::sendMessage($sender, $text, false);
                                    PracticePlayer::playSound($sender, "note.bass");
                                }
                            }
                        } else {
                            $list = PracticePlayer::getLangText($sender, "c.player.useage");
                            $text = "§c" . $list . "/party ban <player>";
                            ChatHandler::sendMessage($sender, $text, false);
                        }
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.permission");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }

            if (isset($args[0]) && $args[0] === "kick") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true") {
                        if (isset($args[1])) {
                            $player = Selector::selector($sender, $args[1]);
                            if ($player == null) {
                                $list = PracticePlayer::getLangText($sender, "c.player.undefined");
                                $text = "§c" . $list;
                                ChatHandler::sendMessage($sender, $text, false);
                                PracticePlayer::playSound($sender, "note.bass");
                            } else {
                                if (PlayerDataManager::getdata($player, "party") == "true" && PlayerDataManager::getdata($player, "party_name") == PlayerDataManager::getdata($sender, "party_name")) {
                                    if (PlayerDataManager::getdata($player, "party_owner") !== "true") {
                                        party::removePlayer(PlayerDataManager::getdata($sender, "party_name"), $player);
                                        $list = PracticePlayer::getLangText($sender, "c.player.process");
                                        $text = "§a" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "random.orb");
                                    } else {
                                        $list = PracticePlayer::getLangText($sender, "c.permission");
                                        $text = "§c" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "note.bass");
                                    }
                                } else {
                                    $list = PracticePlayer::getLangText($sender, "c.party.list.undefined");
                                    $text = "§c" . $list;
                                    ChatHandler::sendMessage($sender, $text, false);
                                    PracticePlayer::playSound($sender, "note.bass");
                                }
                            }
                        } else {
                            $list = PracticePlayer::getLangText($sender, "c.player.useage");
                            $text = "§c" . $list . "/party kick <player>";
                            ChatHandler::sendMessage($sender, $text, false);
                        }
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.permission");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }

            if (isset($args[0]) && $args[0] === "disband") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true") {
                        party::destroyParty(PlayerDataManager::getdata($sender, "party_name"));
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.permission");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }

            if (isset($args[0]) && $args[0] === "duel") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true") {
                        if (Party::$parties[PlayerDataManager::getData($sender, "party_name")]["induel"] == false) {
                            if (isset($args[1])) {
                                $gamelist = [];
                                $config = yaml::getconfig();
                                $gamemode = $config["games"]["duel"];
                                foreach ($gamemode as $game => $value) {
                                    $gamelist[] = $game;
                                }
                                $gamemode = $config["games"]["party"];
                                foreach ($gamemode as $game => $value) {
                                    $gamelist[] = $game;
                                }
                                if (in_array($args[1], $gamelist)) {
                                    if (Party::$parties[PlayerDataManager::getData($sender, "party_name")]["inqueue"] == false) {
                                        PlayerKits::getkit($sender, "lobby", "queue");
                                        if (in_array($args[1], array_keys($config["games"]["party"]))) {
                                            $bool = PlayerQueue::joinQueue($sender, "party_party_" . $args[1]);
                                        } else {
                                            $bool = PlayerQueue::joinQueue($sender, "party_duel_" . $args[1]);
                                        }
                                        if (!$bool) {
                                            $queue = PlayerDataManager::getData($sender, "queue");
                                            if ($queue == "null") {
                                                Party::$parties[PlayerDataManager::getData($sender, "party_name")]["inqueue"] = false;
                                                PlayerKits::getkit($sender, "lobby", "party");
                                            }
                                        } else {
                                            Party::$parties[PlayerDataManager::getData($sender, "party_name")]["partygame"] = false;
                                            Party::$parties[PlayerDataManager::getData($sender, "party_name")]["inqueue"] = true;
                                            Party::$parties[PlayerDataManager::getData($sender, "party_name")]["game"] = $args[1];
                                        }
                                        invformapi::closeInvform($sender);
                                    } else {
                                        $list = PracticePlayer::getLangText($sender, "c.duel.already");
                                        $text = "§c" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "note.bass");
                                    }
                                } else {
                                    $list = PracticePlayer::getLangText($sender, "c.duel.undefined");
                                    $text = "§c" . $list;
                                    ChatHandler::sendMessage($sender, $text, false);
                                    PracticePlayer::playSound($sender, "note.bass");
                                }
                            } else {
                                Forms::party($sender, 6, true);
                            }
                        } else {
                            $list = PracticePlayer::getLangText($sender, "c.duel.use");
                            $text = "§c" . $list;
                            ChatHandler::sendMessage($sender, $text, false);
                            PracticePlayer::playSound($sender, "note.bass");
                        }
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.permission");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }

            if (isset($args[0]) && $args[0] === "splitduel") {
                if (PlayerDataManager::getdata($sender, "party") == "true") {
                    if (PlayerDataManager::getdata($sender, "party_owner") == "true" || Party::$parties[PlayerDataManager::getData($sender, "party_name")]["member_select"] == true) {
                        if (Party::$parties[PlayerDataManager::getData($sender, "party_name")]["induel"] == false) {
                            if (isset($args[1])) {
                                $partyname = PlayerDataManager::getData($sender, "party_name");
                                $gamelist = [];
                                $config = yaml::getconfig();
                                $gamemode = $config["games"]["duel"];
                                foreach ($gamemode as $game => $value) {
                                    $gamelist[] = $game;
                                }
                                if (in_array($args[1], $gamelist)) {
                                    $config = yaml::getconfig();
                                    $playercount = count(Party::$parties[$partyname]["players"]);
                                    if ($playercount !== 1) {
                                        $bool = PlayerQueue::joinQueue($sender, "split_duel_" . $args[1]);
                                        Party::resetvote($partyname);
                                        if (!$bool) {
                                            $queue = PlayerDataManager::getData($sender, "queue");
                                            if ($queue == "null") {
                                                PlayerKits::getkit($sender, "lobby", "party");
                                            }
                                        }
                                    } else {
                                        $list = PracticePlayer::getLangText($sender, "c.party.split.playercount");
                                        $text = "§c" . $list;
                                        ChatHandler::sendMessage($sender, $text, false);
                                        PracticePlayer::playSound($sender, "note.bass");
                                    }
                                } else {
                                    $list = PracticePlayer::getLangText($sender, "c.duel.undefined");
                                    $text = "§c" . $list;
                                    ChatHandler::sendMessage($sender, $text, false);
                                    PracticePlayer::playSound($sender, "note.bass");
                                }
                            } else {
                                Forms::party($sender, 7, true);
                            }
                        } else {
                            $list = PracticePlayer::getLangText($sender, "c.duel.use");
                            $text = "§c" . $list;
                            ChatHandler::sendMessage($sender, $text, false);
                            PracticePlayer::playSound($sender, "note.bass");
                        }
                    } else {
                        $list = PracticePlayer::getLangText($sender, "c.permission");
                        $text = "§c" . $list;
                        ChatHandler::sendMessage($sender, $text, false);
                        PracticePlayer::playSound($sender, "note.bass");
                    }
                } else {
                    $list = PracticePlayer::getLangText($sender, "c.party.nojoin");
                    $text = "§c" . $list;
                    ChatHandler::sendMessage($sender, $text, false);
                    PracticePlayer::playSound($sender, "note.bass");
                }
            }
        }
    }
}
