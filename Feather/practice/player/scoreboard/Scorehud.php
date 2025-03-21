<?php

namespace practice\player\scoreboard;

use practice\player\scoreboard\ScoreboardAPI;
use practice\utils\PlayerDataManager;
use practice\player\PracticePlayer;
use pocketmine\Server;
use practice\utils\yaml;
use practice\utils\task\AlwaysTask;
use practice\handler\PlayerDamageHandler;
use practice\duel\PlayerDuel;

class Scorehud
{
    private static $serverip = "feather.mc.gg - ";
    private static $ip = "";
    private static $line = "§7";
    private static $count = 0;

    public static function UpdateScore($player): void
    {
        $config = yaml::getconfig();
        //var_dump($config);
        self::$ip = self::$serverip . $config["region"];
        $count = AlwaysTask::$count;
        $inQueue = AlwaysTask::$inQueue;
        $server = Server::getInstance();
        self::$count = 0;
        $xuid = $player->getXuid();
        if (PracticePlayer::$playerdata[$player->getXuid()]["settings"]["scoreboard"]["show"]) {
            $showping = PracticePlayer::$playerdata[$player->getXuid()]["settings"]["scoreboard"]["ping"];
            $showdrop = PracticePlayer::$playerdata[$player->getXuid()]["settings"]["scoreboard"]["drop"];
            ScoreboardAPI::sendScoreboard($player);
            ScoreboardAPI::addline($player, "§f§f" . self::$line, true);
            if (PlayerDataManager::getdata($player, "gametype", false) == "lobby") {
                ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                ScoreboardAPI::addline($player, "§fKills: " . yaml::getcolor() . PracticePlayer::$playerdata[$xuid]["status"]["kill"]);
                ScoreboardAPI::addline($player, "§fDeaths: " . yaml::getcolor() . PracticePlayer::$playerdata[$xuid]["status"]["death"]);
                ScoreboardAPI::addline($player, self::random());
                ScoreboardAPI::addline($player, yaml::getcolor() . "§lServer");
                ScoreboardAPI::addline($player, "§fOnline: " . yaml::getcolor() . $count);
                ScoreboardAPI::addline($player, "§fIn Queue: " . yaml::getcolor() . $inQueue);
                if ($showping || $showdrop) {
                    ScoreboardAPI::addline($player, self::random());
                }
                if ($showping) {
                    ScoreboardAPI::addline($player, "§fPing:" . yaml::getcolor() . " " . $player->getNetworkSession()->getPing());
                }
                if ($showdrop) {
                    if (isset(AlwaysTask::$drop[$xuid])) {
                        $drop = AlwaysTask::$drop[$xuid];
                        ScoreboardAPI::addline($player, "§fDrop:" . yaml::getcolor() . " " . $drop);
                    }
                }
            }
            if (PlayerDataManager::getdata($player, "gametype", false) == "ffa") {
                ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                ScoreboardAPI::addline($player, "§fKills: " . yaml::getcolor() . PracticePlayer::$playerdata[$xuid]["status"]["kill"]);
                ScoreboardAPI::addline($player, "§fDeaths: " . yaml::getcolor() . PracticePlayer::$playerdata[$xuid]["status"]["death"]);
                ScoreboardAPI::addline($player, "§fStreak: " . yaml::getcolor() . PracticePlayer::$playerdata[$xuid]["status"]["streak"]);
                ScoreboardAPI::addline($player, self::random());
                ScoreboardAPI::addline($player, yaml::getcolor() . "§lPlaying");
                ScoreboardAPI::addline($player, "§f" . $config["games"]["ffa"][PlayerDataManager::getdata($player, "ingame", false)]["name"] . " FFA");
                $lasthit = PlayerDataManager::getData($player, "combat_lasthit", false);
                $diff = microtime(true) - $lasthit;
                $gametype = PlayerDataManager::getData($player, "gametype", false);
                $game = PlayerDataManager::getData($player, "ingame", false);
                if (isset($config["games"][$gametype][$game]["combattime"])) {
                    $combattime = $config["games"][$gametype][$game]["combattime"];
                } else {
                    $combattime = 10;
                }
                if ($diff < $combattime) {
                    ScoreboardAPI::addline($player, "§fCombat: " . ceil($combattime - $diff));
                }
            }

            if (PlayerDataManager::getdata($player, "gametype", false) == "duel") {
                if (isset($config["games"]["duel"][PlayerDataManager::getdata($player, "ingame", false)])) {
                    $type = "duel";
                } else {
                    $type = "party";
                }
                $team = PlayerDataManager::getdata($player, "team", false);
                $id = PlayerDataManager::getData($player, "duelid", false);
                $data = PlayerDuel::$dueldatas[$id];
                if (PlayerDataManager::getdata($player, "dueltype", false) == "normal") {
                    ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                    ScoreboardAPI::addline($player, "§fPlayer: " . yaml::getcolor() . $player->getName());
                    ScoreboardAPI::addline($player, "§fOpporent: " . yaml::getcolor() . PlayerDataManager::getdata($player, "duelplayer", false));
                    ScoreboardAPI::addline($player, self::random());
                    ScoreboardAPI::addline($player, yaml::getcolor() . "§lPlaying");
                    ScoreboardAPI::addline($player, "§f" . $config["games"][$type][PlayerDataManager::getdata($player, "ingame", false)]["name"] . " Duel");
                    $lasthit = PlayerDataManager::getData($player, "combat_lasthit", false);
                    $lasthit = $lasthit ?? 0;
                    $diff = microtime(true) - $lasthit;
                    $gametype = PlayerDataManager::getData($player, "gametype", false);
                    $game = PlayerDataManager::getData($player, "ingame", false);
                    $config  = yaml::getconfig();
                }

                if (PlayerDataManager::getdata($player, "dueltype", false) == "bed") {
                    ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                    if ($data["red_destroy"] == "false") {
                        $red = "§aO";
                    } else {
                        $red = yaml::getcolor() . "X";
                    }
                    if ($data["blue_destroy"] == "false") {
                        $blue = "§aO";
                    } else {
                        $blue = yaml::getcolor() . "X";
                    }
                    ScoreboardAPI::addline($player, yaml::getcolor() . "Red§f: " . yaml::getcolor() . $red);
                    ScoreboardAPI::addline($player, "§9Blue§f: §9" . $blue);
                    ScoreboardAPI::addline($player, self::random());
                    ScoreboardAPI::addline($player, yaml::getcolor() . "§lPlaying");
                    ScoreboardAPI::addline($player, "§f" . $config["games"][$type][PlayerDataManager::getdata($player, "ingame", false)]["name"] . " Duel");
                    $lasthit = PlayerDataManager::getData($player, "combat_lasthit", false);
                    $lasthit = $lasthit ?? 0;
                    $diff = microtime(true) - $lasthit;
                    $gametype = PlayerDataManager::getData($player, "gametype", false);
                    $game = PlayerDataManager::getData($player, "ingame", false);
                    $config  = yaml::getconfig();
                }

                if (PlayerDataManager::getdata($player, "dueltype", false) == "score") {
                    if (PlayerDataManager::getData($player, "scoretype", false) == "type1") {
                        $redpoint = $data["red_point"];
                        $bluepoint = $data["blue_point"];
                        $maxscore = $data["maxscore"];
                        ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                        $result = "";
                        for ($i = 0; $i < $maxscore; $i++) {
                            if ($i < $redpoint) {
                                $result .= yaml::getcolor() . "O";
                            } else {
                                $result .= "§7O";
                            }
                        }
                        ScoreboardAPI::addline($player, yaml::getcolor() . "Red§r: " . $result);
                        $result = "";
                        for ($i = 0; $i < $maxscore; $i++) {
                            if ($i < $bluepoint) {
                                $result .= "§9O";
                            } else {
                                $result .= "§7O";
                            }
                        }
                        ScoreboardAPI::addline($player, "§9Blue§r: " . $result);
                    }

                    if (PlayerDataManager::getData($player, "scoretype", false) == "type2") {
                        if ($team == "red") {
                            $team = $data["red_point"];
                            $oppot = $data["blue_point"];
                        } else {
                            $team = $data["blue_point"];
                            $oppot = $data["red_point"];
                        }
                        ScoreboardAPI::addline($player, yaml::getcolor() . "§lStatus");
                        ScoreboardAPI::addline($player, "Point§f: " . yaml::getcolor() . $data["red_point"] . " §r| §9" . $data["blue_point"]);
                        $difference = $oppot - $team;
                        if ($difference == 0) {
                            ScoreboardAPI::addLine($player, "Difference§f: §e+0");
                        } elseif ($difference < 0) {
                            ScoreboardAPI::addLine($player, "Difference§f: " . yaml::getcolor() . $difference);
                        } else {
                            ScoreboardAPI::addLine($player, "Difference§f: §a+" . $difference);
                        }
                    }

                    if (PlayerDataManager::getData($player, "scoretype", false) == "hct") {
                    }

                    ScoreboardAPI::addline($player, self::random());
                    ScoreboardAPI::addline($player, yaml::getcolor() . "§lPlaying");
                    ScoreboardAPI::addline($player, "§f" . $config["games"][$type][PlayerDataManager::getdata($player, "ingame", false)]["name"] . " Duel");
                    $lasthit = PlayerDataManager::getData($player, "combat_lasthit", false);
                    $lasthit = $lasthit ?? 0;
                    $diff = microtime(true) - $lasthit;
                    $gametype = PlayerDataManager::getData($player, "gametype", false);
                    $game = PlayerDataManager::getData($player, "ingame", false);
                    $config  = yaml::getconfig();
                }
            }
            $type = PlayerDataManager::getdata($player, "gametype", false);
            if ($type == "ffa" || $type == "duel" || $type == "party") {
                if ($showping || $showdrop) {
                    ScoreboardAPI::addline($player, self::random());
                }
                if ($showping) {
                    $ping = "§fPing: ";
                    $ping = $ping . "§a" . $player->getNetworkSession()->getPing();
                    $ping = $ping . "§f  ";
                    if (PlayerDamageHandler::isCombat($player)) {
                        $attacker = $server->getPlayerByPrefix(PlayerDataManager::getdata($player, "duelplayer", false));
                        if ($attacker !== null) {
                            $ping = $ping . "§c" . $attacker->getNetworkSession()->getPing();
                        } else {
                            $ping = $ping . "§c" . 0;
                        }
                    } else {
                        $ping = $ping . "§c" . 0;
                    }
                    ScoreboardAPI::addline($player, $ping);
                }
                if ($showdrop) {
                    $drop = "§fDrop: ";
                    $dropc = AlwaysTask::$drop[$xuid] ?? "0";
                    $drop = $drop . "§a" . $dropc;
                    $drop = $drop . "§f  ";
                    if (PlayerDamageHandler::isCombat($player)) {
                        $attacker = $server->getPlayerByPrefix(PlayerDataManager::getdata($player, "duelplayer", false));
                        if ($attacker !== null) {
                            $drop = $drop . "§c" . AlwaysTask::$drop[$attacker->getXuid()];
                        } else {
                            $drop = $drop . "§c" . 0;
                        }
                    } else {
                        $drop = $drop . "§c" . 0;
                    }
                    ScoreboardAPI::addline($player, $drop);
                }
            }
            ScoreboardAPI::addline($player, self::$line, true);
            ScoreboardAPI::addline($player, "§7" . self::$ip);
        } else {
            ScoreboardAPI::removeScore($player);
        }
    }

    private static function random(): string
    {
        $codes = str_split("0123456789abcdefghijklmnopqrstuvwxyz");
        $randomText = "";
        for ($i = 0; $i < 2; $i++) {
            $randomText .= "§" . $codes[self::$count];
        }
        self::$count += 1;
        return  $randomText;
    }
}
