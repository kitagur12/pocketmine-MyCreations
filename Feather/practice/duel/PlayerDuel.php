<?php

namespace practice\duel;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\block\Bed;
use pocketmine\Server;
use practice\utils\yaml;
use practice\utils\PlayerDataManager;
use practice\player\PlayerKits;
use practice\arena\WorldManaer;
use practice\player\PracticePlayer;
use practice\handler\TitleHandler;
use practice\handler\ChatHandler;
use practice\arena\ArenaManaer;
use practice\handler\PlayerDeathHandler;
use practice\party\Party;
use pocketmine\block\VanillaBlocks;
use practice\player\scoreboard\Scorehud;

class PlayerDuel
{
    public static array $dueldatas = [];

    const DUEL_SETUP = 0;
    const DUEL_READY = 1;
    const DUEL_PLAYING = 2;
    const DUEL_FINISHED = 3;
    const DUEL_ENDED = 4;
    const DUEL_STOPPED = 5;

    const MAX_DURATION_SECONDS = 1200;

    public static function createPlayerDuel(array $dueldata): void
    {
        $config = yaml::getconfig();
        if (isset($config["games"]["duel"][$dueldata["game"]])) {
            $type = "duel";
        } else {
            $type = "party";
        }
        $dueltype = $config["games"][$type][$dueldata["game"]]["dueltype"];
        self::$dueldatas[$dueldata["id"]]["status"] = self::DUEL_SETUP;
        self::$dueldatas[$dueldata["id"]]["red"] = $dueldata["red"];
        self::$dueldatas[$dueldata["id"]]["blue"] = $dueldata["blue"];
        self::$dueldatas[$dueldata["id"]]["players"] = $dueldata["players"];
        self::$dueldatas[$dueldata["id"]]["game"] = $dueldata["game"];
        self::$dueldatas[$dueldata["id"]]["ranked"] = $dueldata["ranked"];
        self::$dueldatas[$dueldata["id"]]["isparty"] = $dueldata["isparty"];
        self::$dueldatas[$dueldata["id"]]["split"] = $dueldata["split"];
        self::$dueldatas[$dueldata["id"]]["tick"] = -1;
        self::$dueldatas[$dueldata["id"]]["map"] = $dueldata["map"];
        self::$dueldatas[$dueldata["id"]]["mapid"] = $dueldata["mapid"];
        self::$dueldatas[$dueldata["id"]]["eb"] = true;
        self::$dueldatas[$dueldata["id"]]["dueltype"] = $dueltype;
        self::$dueldatas[$dueldata["id"]]["countinfo"] = "start";
        self::$dueldatas[$dueldata["id"]]["world"] = false;
        self::$dueldatas[$dueldata["id"]]["maxhealth"] = $dueldata["maxhealth"] ?? 20;
        self::$dueldatas[$dueldata["id"]]["resetblock"] = $config["games"][$type][$dueldata["game"]]["resetblock"] ?? false;
        $defaultdelay = $config["games"][$type][$dueldata["game"]]["hitdelay"];
        self::$dueldatas[$dueldata["id"]]["hitdelay"] = $dueldata["hitdelay"] ?? $defaultdelay;
        if ($dueltype == "score") {
            $maxpoint = $config["games"][$type][$dueldata["game"]]["point"];
            self::$dueldatas[$dueldata["id"]]["maxscore"] = $maxpoint;
            if (self::$dueldatas[$dueldata["id"]]["split"]) {
                self::$dueldatas[$dueldata["id"]]["maxscore"] = $dueldata["maxscore"] ?? $maxpoint;
            }
            $mapname = $config["map"][$dueldata["map"]]["map"][$dueldata["mapid"]];
            self::$dueldatas[$dueldata["id"]]["red_point"] = 0;
            self::$dueldatas[$dueldata["id"]]["blue_point"] = 0;
            $subdueltype = $config["games"][$type][$dueldata["game"]]["subtype"];
            self::$dueldatas[$dueldata["id"]]["subtype"] = $subdueltype;
            $scoretype = $config["games"][$type][$dueldata["game"]]["scoretype"];
            self::$dueldatas[$dueldata["id"]]["scoretype"] = $scoretype;
            if ($subdueltype == "score") {
                $blockrange = $config["map"][$dueldata["map"]][$mapname][2];
                self::$dueldatas[$dueldata["id"]]["blockrange"] = $blockrange;
                $red_score = $config["map"][$dueldata["map"]][$mapname][3];
                self::$dueldatas[$dueldata["id"]]["red_score"] = $red_score;
                $blue_score = $config["map"][$dueldata["map"]][$mapname][4];
                self::$dueldatas[$dueldata["id"]]["blue_score"] = $blue_score;
            }
            if ($subdueltype == "kill") {
                if (isset($config["map"][$dueldata["map"]][$mapname][2])) {
                    $blockrange = $config["map"][$dueldata["map"]][$mapname][2];
                    self::$dueldatas[$dueldata["id"]]["blockrange"] = $blockrange;
                }
            }
            $respawn = $config["games"][$type][$dueldata["game"]]["respawn"] ?? false;
            self::$dueldatas[$dueldata["id"]]["respawn"] = $respawn;
        }

        if ($dueltype == "bed") {
            $mapname = $config["map"][$dueldata["map"]]["map"][$dueldata["mapid"]];
            $blockrange = $config["map"][$dueldata["map"]][$mapname][2];
            self::$dueldatas[$dueldata["id"]]["blockrange"] = $blockrange;
            self::$dueldatas[$dueldata["id"]]["red_destroy"] = "false";
            self::$dueldatas[$dueldata["id"]]["blue_destroy"] = "false";
            self::$dueldatas[$dueldata["id"]]["respawn"] = $dueldata["respawn"] ?? 5;
        }
    }

    public static function dueltick(): void
    {
        foreach (self::$dueldatas as $id => $data) {
            $config = yaml::getconfig();
            $map = $data["map"];
            $mapid = $data["mapid"];
            $maps = $config["map"][$map]["map"];
            $mapname = $maps[$mapid];
            if (isset($config["games"]["duel"][self::$dueldatas[$id]["game"]])) {
                $type = "duel";
            } else {
                $type = "party";
            }
            $data["tick"] += 1;
            if ($data["status"] == self::DUEL_SETUP) {
                $data["counttick"] = -1;
                $game = $data["game"];
                $data["starttime"] = microtime(true);
                $iplist = [];
                $iseb = false;
                if (self::$dueldatas[$id]["world"] === false) {
                    self::$dueldatas[$id]["world"] = "aaaaaaaaaa";
                    WorldManaer::cloneDuelWorld($mapname, $id);
                    foreach ($data["players"] as $player) {
                        PracticePlayer::UpdateName($player);
                        PracticePlayer::resetstatus($player);
                        if ($type == "duel") {
                            PlayerDataManager::setdata($player, "gametype", "duel");
                        } else {
                            PlayerDataManager::setdata($player, "gametype", "party");
                        }
                        PlayerDataManager::setdata($player, "ingame", $game);
                        PlayerDataManager::setData($player, "isduel", "true");
                        PlayerDataManager::setData($player, "dueltype", $data["dueltype"]);
                        if ($data["dueltype"] == "score") PlayerDataManager::setData($player, "subtype", $data["subtype"]);
                        if ($data["dueltype"] == "score") PlayerDataManager::setData($player, "scoretype", $data["scoretype"]);
                        PlayerDataManager::setData($player, "duelid", $id);
                        PlayerDataManager::setdata($player, "iscanattack", "false");
                        PlayerDataManager::setdata($player, "canblock", "false");
                        PlayerDataManager::setdata($player, "deathtick", -1);
                        $ip = $player->getNetworkSession()->getIp();
                        if (in_array($ip, $iplist)) {
                            $iseb = true;
                        } else {
                            $iplist[] = $ip;
                        }
                    }
                    if ($iseb && $data["ranked"] == true) {
                        self::$dueldatas[$id]["eb"] = false;
                        foreach ($data["players"] as $players) {
                            ChatHandler::sendMessage($players, "duel.dissableelo", true, "notification");
                        }
                    }

                    if (isset($config["games"][$type][$game]["effect"])) {
                        $effectManager = $player->getEffects();
                        if (is_string($config["games"][$type][$game]["effect"])) {
                            $effects = [$config["games"][$type][$game]["effect"]];
                        } else {
                            $effects = $config["games"][$type][$game]["effect"];
                        }
                    }
                }
                if (self::$dueldatas[$id]["world"] === "bbbbbbbbbb") {
                    self::teleportPlayers($data);
                    $maxhp = 20;
                    if (self::$dueldatas[$id]["isparty"] && self::$dueldatas[$id]["split"]) {
                        $maxhp = Party::$parties[PlayerDataManager::getData(self::$dueldatas[$id]["players"][0], "party_name")][self::$dueldatas[$id]["game"]]["maxhp"];
                    }
                    foreach ($data["players"] as $player) {
                        if ($player->isOnline()) {
                            $player->setMaxHealth($maxhp);
                            PlayerKits::getkit($player, $type, $game);
                            PlayerKits::getarrow($player, $type, $game);
                            if (isset($config["games"][$type][$game]["effect"])) {
                                if (is_string($config["games"][$type][$game]["effect"])) {
                                    $effects = [$config["games"][$type][$game]["effect"]];
                                } else {
                                    $effects = $config["games"][$type][$game]["effect"];
                                }
                                foreach ($effects as $effect) {
                                    $effect = explode("-", $effect);
                                    $p = (int) $effect[1];
                                    $ieffect = new EffectInstance(VanillaEffects::{$effect[0]}(), 2147483647, $p, false);
                                    $ieffect->setAmplifier($p);
                                    $effectManager = $player->getEffects();
                                    $effectManager->add($ieffect);
                                }
                            }
                        }
                    }
                    $data["status"] = self::DUEL_READY;
                }
            }

            if ($data["status"] == self::DUEL_READY) {
                $data["counttick"] += 1;
                if ($data["counttick"] == 10) {
                    foreach ($data["players"] as $player) {
                        if ($player->isOnline()) {
                            $player->setHealth(999);
                        }
                    }
                }
                if (!is_float($data["counttick"] / 20)) {
                    if ($data["counttick"] == 0) {
                        if ($data["resetblock"]) {
                            self::resetblock($id);
                        }
                        foreach ($data["players"] as $player) {
                            if ($player->isOnline()) {
                                $pallive2 = PlayerDataManager::getdata($player, "allive", false);
                                PlayerDataManager::setdata($player, "allive2", "true");
                                PlayerDataManager::setdata($player, "canblock", "false");
                                if ($data["countinfo"] == "start") {
                                    $player->setNoClientPredictions(true);
                                    TitleHandler::sendSubTitle($player, "§75.....", false, "countdown");
                                    TitleHandler::sendTitle($player, "duel.matched", true, "countdown", 5, 100, 0);
                                    PlayerDataManager::setdata($player, "itemuse", "false");
                                    PracticePlayer::playSound($player, "random.click");
                                    PracticePlayer::unsetspectate($player);
                                }
                                if ($data["countinfo"] == "scored") {
                                    if ($pallive2 == "true") {
                                        $game = PlayerDataManager::getdata($player, "ingame");
                                        PlayerKits::getkit($player, $type, $game);
                                        PlayerKits::getarrow($player, $type, $game);
                                        self::teleportPlayer($player);
                                        $player->setNoClientPredictions(true);
                                        $title = PracticePlayer::getLangText($player, "duel.scored");
                                        if ($data["countinfo_other"]["scored_team"] == "red") {
                                            $title = "§c" . $title;
                                        } else {
                                            $title = "§9" . $title;
                                        }
                                        $title = str_replace("<player>", $data["countinfo_other"]["scored_player"], $title);
                                        TitleHandler::sendSubTitle($player, "§75.....", false, "countdown");
                                        TitleHandler::sendTitle($player, $title, false, "countdown", 5, 100, 0);
                                        PlayerDataManager::setdata($player, "itemuse", "false");
                                        PracticePlayer::playSound($player, "random.click");
                                        $player->getHungerManager()->setFood(20);
                                        $game = PlayerDataManager::getdata($player, "ingame");
                                        PlayerKits::getkit($player, $type, $game);
                                        PlayerKits::getarrow($player, $type, $game);
                                        self::teleportPlayer($player);
                                        PracticePlayer::unsetspectate($player);
                                    }
                                }
                            }
                        }
                    } elseif ($data["counttick"] == 100) {
                        foreach ($data["players"] as $player) {
                            if ($player->isOnline()) {
                                $player->setHealth(999);
                                $pallive2 = PlayerDataManager::getdata($player, "allive", false);
                                if ($pallive2 == "true") {
                                    $player->setNoClientPredictions(false);
                                    TitleHandler::sendSubTitle($player, "duel.startsub", true, "countdown");
                                    TitleHandler::sendTitle($player, "duel.start", true, "countdown", 5, 20, 10);
                                    PlayerDataManager::setdata($player, "allive2", "true");
                                    PlayerDataManager::setdata($player, "itemuse", "true");
                                    PlayerDataManager::setdata($player, "canblock", "true");
                                    PlayerDataManager::setdata($player, "iscanattack", "true");
                                    PracticePlayer::playSound($player, "random.orb");
                                }
                            }
                        }
                        $data["status"] = self::DUEL_PLAYING;
                    } else {
                        foreach ($data["players"] as $player) {
                            if ($player->isOnline()) {
                                $player->setHealth(999);
                                $count = 5 - $data["counttick"] / 20;
                                if ($count !== 4) {
                                    TitleHandler::sendSubTitle($player, "duel.starting", true, "countdown");
                                    $ten = str_repeat(".", $count);
                                    TitleHandler::sendTitle($player, "§c" . $count . $ten, false, "countdown", 5, 100, 0);
                                    PracticePlayer::playSound($player, "random.click");
                                }
                            }
                        }
                    }
                }
            }

            if ($data["status"] == self::DUEL_PLAYING) {
                $blueallive = 0;
                $redallive = 0;
                $blueallive2 = 0;
                $redallive2 = 0;
                if (is_float($data["counttick"] / 20)) {
                    $score = false;
                } else {
                    $score = true;
                }

                foreach ($data["players"] as $player) {
                    if ($score) {
                        Scorehud::UpdateScore($player);
                    }
                    if (PlayerDataManager::getdata($player, "allive", false) == "true") {
                        if (PlayerDataManager::getdata($player, "team", false) == "red") {
                            $redallive += 1;
                        } else {
                            $blueallive += 1;
                        }
                        if (PlayerDataManager::getdata($player, "allive2", false) == "true") {
                            if (PlayerDataManager::getdata($player, "team", false) == "red") {
                                $redallive2 += 1;
                            } else {
                                $blueallive2 += 1;
                            }
                        }
                    }
                    $deathheight = $config["games"][$type][$data["game"]]["deathheight"];
                    $y = $player->getPosition()->getY();
                    $deathtick = PlayerDataManager::getdata($player, "deathtick", false);
                    if ($y < $deathheight && $deathtick == -1) {
                        PlayerDeathHandler::PlayerDeath($player);
                    }
                    if ($data["dueltype"] == "bed") {
                        if ($player->isOnline()) {
                            $deathtick = PlayerDataManager::getdata($player, "deathtick", false);
                            if ($deathtick > 0) {
                                PlayerDataManager::setdata($player, "deathtick", $deathtick - 1, false);
                                $timer = $deathtick / 20;
                                if (is_int($timer)) {
                                    TitleHandler::sendSubTitle($player, "duel.respawning", true, "countdown");
                                    $count = $timer;
                                    $ten = str_repeat(".", $count);
                                    TitleHandler::sendTitle($player, "§c" . $count . $ten, false, "countdown", 5, 100, 0);
                                    PracticePlayer::playSound($player, "random.click");
                                }
                            }
                            if ($deathtick == 0) {
                                foreach ($player->getWorld()->getPlayers() as $playerss) {
                                    $playerss->showPlayer($player);
                                }
                                $player->setAllowFlight(false);
                                $player->setFlying(false);
                                TitleHandler::sendSubTitle($player, "", false, "countdown");
                                TitleHandler::sendTitle($player, "duel.respawned", true, "countdown", 5, 20, 10);
                                PlayerDataManager::setdata($player, "deathtick", -1);
                                PlayerDataManager::setdata($player, "iscanattack", "true");
                                PlayerDataManager::setData($player, "canblock", "true");
                                PlayerKits::getkit($player, $type, $data["game"]);
                                PlayerKits::getarrow($player, $type, $data["game"]);
                                self::teleportPlayer($player);
                            }
                        }
                    }

                    if ($data["dueltype"] == "score") {
                        if ($data["subtype"] == "score") {
                            $redpos = $data["red_score"];
                            $pos1 = new Vector3($redpos[0], $redpos[1], $redpos[2]);
                            $pos2 = new Vector3($redpos[3], $redpos[4], $redpos[5]);;
                            $redscore = self::isPlayerInscore($player, $pos1, $pos2);

                            $bluepos = $data["blue_score"];
                            $pos1 = new Vector3($bluepos[0], $bluepos[1], $bluepos[2]);
                            $pos2 = new Vector3($bluepos[3], $bluepos[4], $bluepos[5]);;
                            $bluescore = self::isPlayerInscore($player, $pos1, $pos2);

                            if ($redscore) {
                                if ("red" != PlayerDataManager::getdata($player, "team")) {
                                    $data["countinfo"] = "scored";
                                    $data["countinfo_other"]["scored_team"] = PlayerDataManager::getdata($player, "team");
                                    $data["countinfo_other"]["scored_player"] = $player->getName();
                                    $data["counttick"] = -1;
                                    $data["red_point"] += 1;
                                    $data["status"] = self::DUEL_READY;
                                } else {
                                    PlayerDeathHandler::PlayerDeath($player);
                                }
                            } elseif ($bluescore) {
                                if ("blue" != PlayerDataManager::getdata($player, "team")) {
                                    $data["countinfo"] = "scored";
                                    $data["countinfo_other"]["scored_team"] = PlayerDataManager::getdata($player, "team");
                                    $data["countinfo_other"]["scored_player"] = $player->getName();
                                    $data["counttick"] = -1;
                                    $data["blue_point"] += 1;
                                    $data["status"] = self::DUEL_READY;
                                } else {
                                    PlayerDeathHandler::PlayerDeath($player);
                                }
                            }
                        }

                        if ($data["subtype"] == "kill") {
                        }
                    }
                }

                if ($data["dueltype"] == "score") {
                    if ($data["subtype"] == "kill") {
                        if ($redallive2 == 0 || $blueallive2 == 0) {
                            foreach ($data["players"] as $players) {
                                PlayerDataManager::setdata($players, "allive2", "true");
                            }
                            $data["countinfo"] = "scored";
                            $data["counttick"] = -1;
                            if ($redallive2 == 0) {
                                $data["red_point"] += 1;
                                $data["countinfo_other"]["scored_team"] = "red";
                                $data["countinfo_other"]["scored_player"] = "Red Team";
                            } else {
                                $data["blue_point"] += 1;
                                $data["countinfo_other"]["scored_team"] = "blue";
                                $data["countinfo_other"]["scored_player"] = "Blue Team";
                            }
                            $data["status"] = self::DUEL_READY;
                        }

                        if ($data["red_point"] == $data["maxscore"] || $data["blue_point"] == $data["maxscore"]) {
                            $data["counttick"] = -1;
                            $data["status"] = self::DUEL_FINISHED;
                            $data["endreason"] = "decided";
                            if ($data["red_point"] == $data["maxscore"]) {
                                $data["winner"] = "blue";
                                $data["loser"] = "red";
                            } else {
                                $data["winner"] = "red";
                                $data["loser"] = "blue";
                            }
                        }
                    }
                    if ($data["subtype"] == "hit") {
                        if ($data["red_point"] == $data["maxscore"] || $data["blue_point"] == $data["maxscore"]) {
                            $data["counttick"] = -1;
                            $data["status"] = self::DUEL_FINISHED;
                            $data["endreason"] = "decided";
                            if ($data["red_point"] == $data["maxscore"]) {
                                $data["winner"] = "blue";
                                $data["loser"] = "red";
                            } else {
                                $data["winner"] = "red";
                                $data["loser"] = "blue";
                            }
                        }
                    }
                    if ($data["subtype"] == "score") {
                        if ($data["red_point"] == $data["maxscore"] || $data["blue_point"] == $data["maxscore"]) {
                            $data["counttick"] = -1;
                            $data["status"] = self::DUEL_FINISHED;
                            $data["endreason"] = "decided";
                            if ($data["red_point"] == $data["maxscore"]) {
                                $data["winner"] = "blue";
                                $data["loser"] = "red";
                            } else {
                                $data["winner"] = "red";
                                $data["loser"] = "blue";
                            }
                        }
                    }
                }

                if ($redallive == 0 || $blueallive == 0) {
                    $data["endreason"] = "decided";
                    if ($redallive == 0) {
                        $data["winner"] = "blue";
                        $data["loser"] = "red";
                    } else {
                        $data["winner"] = "red";
                        $data["loser"] = "blue";
                    }
                    $data["counttick"] = -1;
                    $data["status"] = self::DUEL_FINISHED;
                }

                if ($data["tick"] == 12000) {
                    $data["endreason"] = "timeout";
                    $data["counttick"] = -1;
                    $data["status"] = self::DUEL_FINISHED;
                }
            }
            if ($data["status"] == self::DUEL_FINISHED) {
                $data["counttick"] += 1;
                if ($data["counttick"] == 0) {
                    foreach ($data["players"] as $player) {
                        if ($player->isOnline()) {
                            $player->setHealth(999);
                            $player->setAllowFlight(true);
                            $player->setFlying(true);
                            PlayerDataManager::setdata($player, "canblock", "false");
                            PlayerDataManager::setdata($player, "iscanattack", "false");
                            PlayerKits::getkit($player, "lobby", "void");
                        }
                    }
                    if ($data["endreason"] == "decided") {
                        if ($data["winner"] == "red") {
                            foreach ($data["red"] as $player) {
                                if ($player->isOnline()) {
                                    TitleHandler::sendSubTitle($player, "duel.winsub", true, "countdown");
                                    TitleHandler::sendTitle($player, "duel.win", true, "countdown", 10, 60, 20);
                                }
                            }
                            foreach ($data["blue"] as $player) {
                                if ($player->isOnline()) {
                                    TitleHandler::sendSubTitle($player, "duel.losesub", true, "countdown");
                                    TitleHandler::sendTitle($player, "duel.lose", true, "countdown", 10, 60, 20);
                                    PracticePlayer::playSound($player, "mob.wither.death");
                                }
                            }
                        } else {
                            foreach ($data["red"] as $player) {
                                if ($player->isOnline()) {
                                    TitleHandler::sendSubTitle($player, "duel.losesub", true, "countdown");
                                    TitleHandler::sendTitle($player, "duel.lose", true, "countdown", 10, 60, 20);
                                    PracticePlayer::playSound($player, "mob.wither.death");
                                }
                            }
                            foreach ($data["blue"] as $player) {
                                if ($player->isOnline()) {
                                    TitleHandler::sendSubTitle($player, "duel.winsub", true, "countdown");
                                    TitleHandler::sendTitle($player, "duel.win", true, "countdown", 10, 60, 20);
                                }
                            }
                        }
                    }
                    if ($data["endreason"] == "timeout") {
                        foreach ($data["players"] as $player) {
                            TitleHandler::sendSubTitle($player, "duel.drawsub", true, "countdown");
                            TitleHandler::sendTitle($player, "duel.draw", true, "countdown", 10, 60, 20);
                        }
                    }
                }
                if ($data["counttick"] == 40) {
                    if ($data["ranked"] == true && $data["eb"] == true && $data["endreason"] == "decided") {
                        foreach ($data["players"] as $player) {
                            if ($player->isOnline()) {
                                $winnerelo = PracticePlayer::$playerdata[$data[$data["winner"]][0]->getXuid()]["rate"][$data["game"]];
                                $loserelo = PracticePlayer::$playerdata[$data[$data["loser"]][0]->getXuid()]["rate"][$data["game"]];
                                $elo = self::ratecalc($winnerelo, $loserelo);
                                $winnerelo += $elo;
                                $loserelo -= $elo;
                                PracticePlayer::$playerdata[$data[$data["winner"]][0]->getXuid()]["rate"][$data["game"]] = $winnerelo;
                                PracticePlayer::$playerdata[$data[$data["loser"]][0]->getXuid()]["rate"][$data["game"]] = $loserelo;
                                PracticePlayer::playSound($player, "random.orb");
                                ChatHandler::sendMessage($player, "試合結果\n§a勝者§7: $winnerelo\n§c敗者§7: $loserelo\nelo: $elo", false, "notification");
                            }
                        }
                    }
                }
                if ($data["counttick"] == 100) {
                    $data["counttick"] = -1;
                    $data["status"] = self::DUEL_ENDED;
                }
            }

            if ($data["status"] == self::DUEL_ENDED) {
                foreach ($data["players"] as $player) {
                    if ($player->isOnline()) {
                        PlayerDataManager::setData($player, "team", "null");
                        PlayerDataManager::setdata($player, "gametype", "lobby"); //joinlobbyのiscombat対策
                        PlayerDataManager::setdata($player, "itemuse", "true");
                        ArenaManaer::joinlobby($player);
                        $player->setNoClientPredictions(false);
                        if (PlayerDataManager::getData($player, "party") == "true") {
                            Party::$parties[PlayerDataManager::getData($player, "party_name")]["induel"] = false;
                        }
                    }
                }
                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                    if (PlayerDataManager::getdata($players, "spectator") == "true" && PlayerDataManager::getdata($players, "specid") == $id) {
                        ArenaManaer::joinlobby($players);
                    }
                }
                WorldManaer::deleteDuelWorld($mapname, $id);
                unset(self::$dueldatas[$id]);
            } else {
                self::$dueldatas[$id] = $data;
            }
        }
    }

    public static function teleportPlayers($data): void
    {
        $player = $data["players"][0];
        $id = PlayerDataManager::getdata($player, "duelid");
        if (self::$dueldatas[$id]["world"] == false) {
            self::$dueldatas[$id]["world"] = "0";
            foreach ($data["players"] as $player) {
                $config = yaml::getconfig();
                $map = $data["map"];
                $mapid = $data["mapid"];
                $maps = $config["map"][$map]["map"];
                $mapname = $maps[$mapid];
                $spawnlist = $config["map"][$map][$mapname];
                $team = PlayerDataManager::getdata($player, "team");
                if ($team == "red") {
                    $spawn = $spawnlist[0];
                } else {
                    $spawn = $spawnlist[1];
                }
            }
        }
        if (self::$dueldatas[$id]["world"] == true) {
            foreach ($data["players"] as $player) {
                $config = yaml::getconfig();
                $map = $data["map"];
                $mapid = $data["mapid"];
                $maps = $config["map"][$map]["map"];
                $mapname = $maps[$mapid];
                $spawnlist = $config["map"][$map][$mapname];
                $team = PlayerDataManager::getdata($player, "team");
                if ($team == "red") {
                    $spawn = $spawnlist[0];
                } else {
                    $spawn = $spawnlist[1];
                }
                $world = WorldManaer::getWorldByExactName($mapname . "=" . $id);
                $Position = new Position($spawn[0], $spawn[1], $spawn[2], $world, $spawn[3], $spawn[4]);
                $to =  $Position;
                if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ())) {
                    $Position = new Position($to->getFloorX() + 0.5, $to->getFloorY() + 0.5, $to->getFloorZ() + 0.5, $to->getWorld());
                }
                if ($player->isOnline()) {
                    if (count($spawn) == 3) {
                        $player->teleport($Position);
                    } else {
                        $player->teleport($Position, $spawn[3], $spawn[4]);
                    }
                }
            }
        }
    }

    public static function teleportPlayer($player): void
    {
        $config = yaml::getconfig();
        $team = PlayerDataManager::getdata($player, "team");
        $id = PlayerDataManager::getdata($player, "duelid");
        $data = self::$dueldatas[$id];
        $map = $data["map"];
        $mapid = $data["mapid"];
        $maps = $config["map"][$map]["map"];
        $mapname = $maps[$mapid];
        $spawnlist = $config["map"][$map][$mapname];
        $team = PlayerDataManager::getdata($player, "team");
        $id = PlayerDataManager::getdata($player, "duelid");
        if ($team == "red") {
            $spawn = $spawnlist[0];
        } else {
            $spawn = $spawnlist[1];
        }
        $world = WorldManaer::getWorldByExactName($mapname . "=" . $id);
        $Position = new Position($spawn[0], $spawn[1], $spawn[2], $world, $spawn[3], $spawn[4]);
        $to =  $Position;
        if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ())) {
            $Position = new Position($to->getFloorX() + 0.5, $to->getFloorY() + 0.5, $to->getFloorZ() + 0.5, $to->getWorld());
        }
        if (count($spawn) == 3) {
            $player->teleport($Position);
        } else {
            $player->teleport($Position, $spawn[3], $spawn[4]);
        }
    }

    public static function ratecalc($winner, $loser): int
    {
        $E_win = 1 / (1 + pow(10, ($loser - $winner) / 400));
        $rate = 32 * (1 - $E_win);
        $rate = round($rate);
        return $rate;
    }

    public static function getDuelData($id)
    {
        return  self::$dueldatas[$id];
    }

    public static function isPlayerInscore($player, Vector3 $pos1, Vector3 $pos2): bool
    {
        $minX = min($pos1->getX(), $pos2->getX()) - 1;
        $maxX = max($pos1->getX(), $pos2->getX()) + 1;

        $minY = min($pos1->getY(), $pos2->getY());
        $maxY = max($pos1->getY(), $pos2->getY());

        $minZ = min($pos1->getZ(), $pos2->getZ()) - 1;
        $maxZ = max($pos1->getZ(), $pos2->getZ()) + 1;
        $playerPos = $player->getPosition();
        return (
            $playerPos->getX() >= $minX && $playerPos->getX() <= $maxX &&
            $playerPos->getY() >= $minY && $playerPos->getY() <= $maxY &&
            $playerPos->getZ() >= $minZ && $playerPos->getZ() <= $maxZ
        );
    }

    public static function DuelDeathHandler($player)
    {

        if (PlayerDataManager::getdata($player, "allive") == "false") {
            $player->setNoClientPredictions(false);
        }
        $type = PlayerDataManager::getData($player, "gametype");
        if (PlayerDataManager::getdata($player, "dueltype") == "normal") {
            $player->setHealth(999);
            $player->getHungerManager()->setFood(20);
            PracticePlayer::setspectate($player);
            $player->setFlying(true);
            PlayerKits::getkit($player, "lobby", "void");
            PlayerDataManager::setData($player, "duelstatus", "death");
            PlayerDataManager::setdata($player, "iscanattack", "false");
            PlayerDataManager::setdata($player, "allive", "false");
        }
        if (PlayerDataManager::getdata($player, "dueltype") == "score") {
            if (PlayerDataManager::getdata($player, "allive") == "true") {
                if (PlayerDataManager::getdata($player, "subtype") == "kill") {
                    $player->setHealth(999);
                    $player->getHungerManager()->setFood(20);
                    PracticePlayer::setspectate($player);
                    $player->setFlying(true);
                    PlayerKits::getkit($player, "lobby", "void");
                    PlayerDataManager::setData($player, "duelstatus", "death");
                    PlayerDataManager::setdata($player, "iscanattack", "false");
                    PlayerDataManager::setdata($player, "allive2", "false");
                } else {
                    PlayerDataManager::setdata($player, "allive2", "false");
                    $player->setHealth(999);
                    $player->getHungerManager()->setFood(20);
                    $game = PlayerDataManager::getdata($player, "ingame");
                    PlayerKits::getkit($player, $type, $game);
                    PlayerKits::getarrow($player, $type, $game);
                    self::teleportPlayer($player);
                }
            } else {
                $player->setHealth(999);
                $player->getHungerManager()->setFood(20);
                PracticePlayer::setspectate($player);
                $player->setFlying(true);
                PlayerKits::getkit($player, "lobby", "void");
                PlayerDataManager::setData($player, "duelstatus", "death");
                PlayerDataManager::setdata($player, "iscanattack", "false");
            }
        }
        if (PlayerDataManager::getdata($player, "dueltype") == "bed") {
            foreach ($player->getWorld()->getPlayers() as $playerss) {
                $playerss->hidePlayer($player);
            }
            $player->setHealth(999);
            $player->getHungerManager()->setFood(20);
            $player->setAllowFlight(true);
            $player->setFlying(true);
            PlayerKits::getkit($player, "lobby", "void");
            PlayerDataManager::setdata($player, "iscanattack", "false");
            PlayerDataManager::setData($player, "canblock", "false");
            $id = PlayerDataManager::getData($player, "duelid");
            $team = PlayerDataManager::getData($player, "team");
            PlayerDataManager::setdata($player, "deathtick", 100);
            $idname = $team . "_destroy";
            if (self::$dueldatas[$id][$idname] == "true") {
                PlayerDataManager::setdata($player, "allive", "false");

                if ($team == "red") {
                    self::$dueldatas[$id]["winner"] = "blue";
                    self::$dueldatas[$id]["loser"] = "red";
                } else {
                    self::$dueldatas[$id]["winner"] = "red";
                    self::$dueldatas[$id]["loser"] = "blue";
                }
                self::$dueldatas[$id]["endreason"] = "decided";
                self::$dueldatas[$id]["counttick"] = -1;
                self::$dueldatas[$id]["status"] = self::DUEL_FINISHED;
            }
        }
        PlayerDataManager::setData($player, "combatplayer", ".");
    }

    public static function iscanBlockPlace(Vector3 $locate, $id, $bypass = false): bool
    {
        $pos1 = new Vector3(self::$dueldatas[$id]["blockrange"][0], self::$dueldatas[$id]["blockrange"][1], self::$dueldatas[$id]["blockrange"][2]);
        $pos2 = new Vector3(self::$dueldatas[$id]["blockrange"][3], self::$dueldatas[$id]["blockrange"][4], self::$dueldatas[$id]["blockrange"][5]);
        $isInRedRange = false;
        $isInBlueRange = false;
        $gametype = self::$dueldatas[$id]["dueltype"];
        if ($gametype == "score") {
            $gamesubtype = self::$dueldatas[$id]["subtype"];
            if ($gamesubtype == "score") {
                $redpos1 = new Vector3(self::$dueldatas[$id]["red_score"][0], self::$dueldatas[$id]["red_score"][1], self::$dueldatas[$id]["red_score"][2]);
                $redpos2 = new Vector3(self::$dueldatas[$id]["red_score"][3], self::$dueldatas[$id]["red_score"][4], self::$dueldatas[$id]["red_score"][5]);

                $bluepos1 = new Vector3(self::$dueldatas[$id]["blue_score"][0], self::$dueldatas[$id]["blue_score"][1], self::$dueldatas[$id]["blue_score"][2]);
                $bluepos2 = new Vector3(self::$dueldatas[$id]["blue_score"][3], self::$dueldatas[$id]["blue_score"][4], self::$dueldatas[$id]["blue_score"][5]);

                $redMinX = min($redpos1->getX(), $redpos2->getX());
                $redMaxX = max($redpos1->getX(), $redpos2->getX());
                $redMinY = min($redpos1->getY(), $redpos2->getY());
                $redMaxY = max($redpos1->getY(), $redpos2->getY()) + 5;
                $redMinZ = min($redpos1->getZ(), $redpos2->getZ());
                $redMaxZ = max($redpos1->getZ(), $redpos2->getZ());

                $isInRedRange = (
                    $locate->getX() >= $redMinX && $locate->getX() <= $redMaxX &&
                    $locate->getY() >= $redMinY && $locate->getY() <= $redMaxY &&
                    $locate->getZ() >= $redMinZ && $locate->getZ() <= $redMaxZ
                );

                $blueMinX = min($bluepos1->getX(), $bluepos2->getX());
                $blueMaxX = max($bluepos1->getX(), $bluepos2->getX());
                $blueMinY = min($bluepos1->getY(), $bluepos2->getY());
                $blueMaxY = max($bluepos1->getY(), $bluepos2->getY()) + 5;
                $blueMinZ = min($bluepos1->getZ(), $bluepos2->getZ());
                $blueMaxZ = max($bluepos1->getZ(), $bluepos2->getZ());

                $isInBlueRange = (
                    $locate->getX() >= $blueMinX && $locate->getX() <= $blueMaxX &&
                    $locate->getY() >= $blueMinY && $locate->getY() <= $blueMaxY &&
                    $locate->getZ() >= $blueMinZ && $locate->getZ() <= $blueMaxZ
                );
            }
        }

        $minX = min($pos1->getX(), $pos2->getX()) - 1;
        $maxX = max($pos1->getX(), $pos2->getX()) + 1;
        $minY = min($pos1->getY(), $pos2->getY());
        $maxY = max($pos1->getY(), $pos2->getY());
        $minZ = min($pos1->getZ(), $pos2->getZ()) - 1;
        $maxZ = max($pos1->getZ(), $pos2->getZ()) + 1;

        $isInMainRange = (
            $locate->getX() >= $minX && $locate->getX() <= $maxX &&
            $locate->getY() >= $minY && $locate->getY() <= $maxY &&
            $locate->getZ() >= $minZ && $locate->getZ() <= $maxZ
        );



        return $isInMainRange && !$isInRedRange && !$isInBlueRange;
    }


    public static function DuelPlaceEvent($event)
    {
        $player = $event->getPlayer();
        $type = PlayerDataManager::getData($player, "gametype");
        $game = PlayerDataManager::getData($player, "ingame");
        $id = PlayerDataManager::getData($player, "duelid");
        $config = yaml::getconfig();
        if (isset($config["games"][$type][$game]["blockaccess"])) {
            if ($config["games"][$type][$game]["blockaccess"] == false) {
                $event->cancel();
                return;
            } else {
                $blocklist = explode(",", $config["games"][$type][$game]["blockaccess"]);
                $blockId = strtoupper(str_replace(" ", "_", $event->getitem()->getVanillaName()));
                if (!in_array($blockId, $blocklist)) {
                    $event->cancel();
                    return;
                } else {
                    foreach ($event->getTransaction()->getBlocks() as $blockData) {
                        $block = $blockData[3];
                        $position = $block->getPosition();
                    }
                    if (!self::iscanBlockPlace($position, $id)) {
                        $event->cancel();
                        return;
                    }
                }
            }
        } else {
            $event->cancel();
            return;
        }
        $canblock = PlayerDataManager::getData($player, "canblock");
        if ($canblock !== "true") {
            $event->cancel();
            return;
        }
        $positionString = $position->getX() . ':' . $position->getY() . ':' . $position->getZ();
        self::$dueldatas[$id]["blockdata"][$positionString] = true;
    }

    public static function DuelBreakEvent($event)
    {
        $player = $event->getPlayer();
        $type = PlayerDataManager::getData($player, "gametype");
        $game = PlayerDataManager::getData($player, "ingame");
        $id = PlayerDataManager::getData($player, "duelid");
        $isbuilder = PlayerDataManager::getData($player, "builder");
        $dueltype = PlayerDataManager::getData($player, "dueltype");
        $config = yaml::getconfig();
        if ($isbuilder !== "true") {
            if (isset($config["games"][$type][$game]["blockaccess"])) {
                if ($config["games"][$type][$game]["blockaccess"] == false) {
                    $event->cancel();
                    return;
                } else {
                    $blocklist = explode(",", $config["games"][$type][$game]["blockaccess"]);
                    if ($event->getblock()->getName() == "Bed Block") {
                        $blockId = "BED";
                    } else {
                        $blockId = str_replace(" ", "_", $event->getblock()->getName());
                        $blockId = strtoupper($blockId);
                    }
                    if (!in_array($blockId, $blocklist)) {
                        $event->cancel();
                        return;
                    }
                }
            } else {
                $event->cancel();
                return;
            }
            $canblock = PlayerDataManager::getData($player, "canblock");
            if ($canblock !== "true") {
                $event->cancel();
                return;
            }
            $position = $event->getblock()->getPosition();
            $positionString = $position->getX() . ':' . $position->getY() . ':' . $position->getZ();
            if ($dueltype == "bed") {
                $idlist = ["OAK_PLANKS", "END_STONE", "BED"];
            } else {
                $idlist = [];
            }
            if (!isset(self::$dueldatas[$id]["blockdata"][$positionString]) && !in_array($blockId, $idlist)) {
                $event->cancel();
                return;
            }
            unset(self::$dueldatas[$id]["blockdata"][$positionString]);
            $block = $event->getblock();
            if ($block instanceof Bed) {
                $color = $block->getColor()->getDisplayName();
                $pcolor = PlayerDataManager::getData($player, "team");
                if (strcasecmp($color, $pcolor) == 0) {
                    $event->cancel();
                    return;
                }
                if ($color === "Red") {
                    self::$dueldatas[$id]["red_destroy"] = "true";
                    foreach (self::$dueldatas[$id]["players"] as $players) {
                        TitleHandler::sendTitle($players, "duel.bed.break.red", true, "countdown", 5, 40, 10);
                    }
                } else {
                    self::$dueldatas[$id]["blue_destroy"] = "true";
                    foreach (self::$dueldatas[$id]["players"] as $players) {
                        TitleHandler::sendTitle($players, "duel.bed.break.blue", true, "countdown", 5, 40, 10);
                    }
                }
                foreach (self::$dueldatas[$id]["players"] as $players) {
                    PracticePlayer::playSound($players, "cauldron.explode", 2);
                }
            }
        }
    }

    public static function DuelattackHandler($event)
    {
        if ($event instanceof EntityDamageByEntityEvent) {
            $player = $event->getEntity();
            $attacker = $event->getDamager();
            if ($player instanceof Player && $attacker instanceof Player) {
                if (PlayerDataManager::getdata($attacker, "iscanattack") == "false" || PlayerDataManager::getdata($player, "iscanattack") == "false") {
                    $event->cancel();
                }
                if (PlayerDataManager::getdata($player, "dueltype", false) == "score" && PlayerDataManager::getData($player, "subtype", false) == "hit") {
                    if (!$event->iscancelled()) {
                        $id = PlayerDataManager::getData($player, "duelid", false);
                        $team = PlayerDataManager::getData($player, "team", false);
                        self::$dueldatas[$id][$team . "_point"] += 1;
                        $event->setBaseDamage(0.01);
                        $player->setHealth(999);
                        if ($event->getModifier(7) !== (float) 0) {
                            $event->setModifier(0.01, 7);
                        }
                    }
                }
            }
        }
    }

    public static function resetblock($id)
    {
        $datas = self::$dueldatas[$id]["blockdata"] ?? [];
        $world = self::$dueldatas[$id]["red"][0]->getWorld();
        foreach ($datas as $data => $value) {
            $parts = explode(":", $data);
            $pos = new Vector3($parts[0], $parts[1], $parts[2]);
            $block = vanillablocks::AIR();
            $world->setBlock($pos, $block);
        }
    }
}
