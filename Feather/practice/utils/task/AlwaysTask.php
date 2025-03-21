<?php

namespace practice\utils\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use practice\handler\ActionbarHandler;
use practice\player\PracticePlayer;
use practice\player\scoreboard\Scorehud;
use practice\utils\PlayerDataManager;
use practice\handler\method\DataPacketReceiveEventHandler;
use practice\items\cooltime;
use practice\utils\yaml;
use practice\player\scoreboard\ScoreTag;
use practice\handler\PlayerDamageHandler;
use practice\duel\PlayerDuel;
use practice\player\PlayerKits;
use practice\handler\method\EntitySpawnEventHandler;
use practice\party\Party;
use practice\practice\practice;

class AlwaysTask extends Task
{
    public static array $drop1 = [];
    public static array $drop = [];
    public static array $data = [];
    public static int $count = 0;
    public static int $inQueue = 0;
    public static int $inGamme = 0;
    public static int $tick = 0;

    public function onRun(): void
    {
        $count = 0;
        $inQueue = 0;
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $count = $count + 1;
            if (PlayerDataManager::getData($player, "queue", false) !== "null") {
                $inQueue += 1;
            }
            self::$inQueue = $inQueue;
            if (PlayerDataManager::getData($player, "queue", false) !== "null") {
                $inQueue += 1;
            }
        }
        PlayerDuel::dueltick();
        practice::practicetick();
        $crystals = EntitySpawnEventHandler::$time["crystal"] ?? [];
        foreach ($crystals as $id => $time) {
            if ($time > 200) {
                foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
                    $entity = $world->getEntity($id);
                    if ($entity !== null) {
                        $entity->kill();
                    }
                }
            }
            EntitySpawnEventHandler::$time["crystal"][$id] += 1;
        }
        self::$tick += 1;
        if (is_float(self::$tick / 20)) {
            $score = false;
        } else {
            $score = true;
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($score) {
                Scorehud::UpdateScore($player);
            }
            $xuid = $player->getXuid();
            $time = microtime(true);
            $cooltime = cooltime::$cooltime[$xuid]["arrow"] ?? 0;
            $delay = $cooltime - $time;
            if ($delay < -5) {
                $arrowcool = PlayerDataManager::getdata($player, "arrowcool", false);
                if ($arrowcool !== "false") {
                    PlayerDataManager::setdata($player, "arrowcool", "false", false);
                    $gametype = PlayerDataManager::getdata($player, "gametype");
                    $game = PlayerDataManager::getdata($player, "ingame");
                    PlayerKits::getarrow($player, $gametype, $game);
                }
            } else {
                PlayerDataManager::setdata($player, "arrowcool", "true", false);
            }

            if (PlayerDataManager::getdata($player, "connected", false) == "true") {
                self::$drop1[$xuid]["count"] = self::$drop1[$xuid]["count"] + 1;
                PlayerDataManager::setdata($player, "connected", "false", false);
            }
            if (isset(self::$drop1[$xuid]["time"])) {
                self::$drop1[$xuid]["time"] = self::$drop1[$xuid]["time"] + 1;
                if (self::$drop1[$xuid]["time"] == 20) {
                    self::$drop1[$xuid]["time"] = 0;
                    self::$drop[$xuid] = 20 - self::$drop1[$xuid]["count"];
                    self::$drop1[$xuid]["count"] = 0;
                }
            } else {
                self::$drop1[$xuid]["time"] = 0;
                self::$drop1[$xuid]["count"] = 0;
            }
            self::UpdateXp($player);
            if (PlayerDataManager::getdata($player, "joined", false) == "true") {
                if ($isreach = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["alwaysshow"]) {
                    $iscps = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["cpscount"];
                    $isping = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["ping"];
                    $iscombo = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["combo"];
                    $isreach = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["reach"];
                    DataPacketReceiveEventHandler::removeOldClicks($player);
                    $cps = DataPacketReceiveEventHandler::getCPS($player);
                    if (!isset(self::$data[$xuid])) {
                        self::$data[$xuid]["combo"] = 0;
                        self::$data[$xuid]["reach"] = 0;
                        self::$data[$xuid]["time"] = 0;
                    }
                    $time = microtime(true) - self::$data[$xuid]["time"];
                    if ($time > 4) {
                        self::$data[$xuid]["combo"] = 0;
                        self::$data[$xuid]["reach"] = 0;
                    } else {
                        $attack = Server::getInstance()->getPlayerByPrefix(PlayerDataManager::getData($player, "combatplayer", false));
                        if ($attack !== null) {
                            if (self::$data[$attack->getXuid()]["onground"] > 8) {
                                self::$data[$xuid]["combo"] = 0;
                            }
                        }
                    }
                    if (!isset(self::$data[$xuid]["onground"])) {
                        self::$data[$xuid]["onground"] = 0;
                    }
                    if ($player->isOnGround()) {
                        self::$data[$xuid]["onground"] += 1;
                    } else {
                        self::$data[$xuid]["onground"] = 0;
                    }
                    $combo = self::$data[$xuid]["combo"] ?? 0;
                    $reach = self::$data[$xuid]["reach"] ?? 0;
                    $message = "";
                    if ($iscps) {
                        $message = yaml::getcolor() . "Cps§7 : " . $cps;
                        if ($iscombo || $isreach || $isping) {
                            $message = $message . " | ";
                        }
                    }
                    if ($iscombo) {
                        $message = $message . yaml::getcolor() . "Combo§7 : " . $combo;
                        if ($isreach || $isping) {
                            $message = $message . " | ";
                        }
                    }
                    if ($isreach) {
                        $message = $message . yaml::getcolor() . "Reach§7 : " . $reach;
                        if ($isping) {
                            $message = $message . " | ";
                        }
                    }
                    if ($isping) {
                        $message = $message . yaml::getcolor() . "Ping§7 : " . $player->getNetworkSession()->getPing();
                    }
                    ActionbarHandler::sendMessage($player, $message);
                }
                $nping = PracticePlayer::$playerdata[$xuid]["settings"]["scoreboard"]["nocombat_ping"];
                $ndevice = PracticePlayer::$playerdata[$xuid]["settings"]["scoreboard"]["nocombat_device"];
                $ndrop = PracticePlayer::$playerdata[$xuid]["settings"]["scoreboard"]["nocombat_drop"];
                $show = PracticePlayer::$playerdata[$xuid]["settings"]["scoreboard"]["show"];
                $playerpos = $player->getPosition();
                $show = true;
                if (PlayerDamageHandler::isCombat($player) && $show) {
                    $show = false;
                }
                foreach ($player->getWorld()->getPlayers() as $playerss) {
                    if ($show) {
                        $di = $playerss->getPosition()->distance($playerpos);
                        $message = "";
                        if ($di < 20) {
                            if ($nping) {
                                $ping = $playerss->getNetworkSession()->getPing();
                                $message .= yaml::getcolor() . "Ping§7 : " . $ping;
                            }
                            if ($ndrop) {
                                $pxuid = $playerss->getXuid();
                                if (isset(self::$drop[$pxuid])) {
                                    $drop = self::$drop[$pxuid];
                                } else {
                                    $drop = 0;
                                }
                                $message .= ($message !== "" ? " | " : "") . yaml::getcolor() . "Drop§7 : " . $drop;
                            }
                            if ($ndevice) {
                                $device = PlayerDataManager::getdata($playerss, "deviceos", false);
                                $message .= ($message !== "" ? " | " : "") . yaml::getcolor() . "Device§7 : " . $device;
                            }
                            ScoreTag::setScoreTag($player, [$playerss], $message);
                        }
                    }
                }
                if (PlayerDataManager::getdata($player, "gametype", false) == "ffa") {
                    if (PlayerDamageHandler::isCombat($player)) {
                        Scorehud::UpdateScore($player);
                        $attacker = Server::getInstance()->getPlayerByPrefix(PlayerDataManager::getdata($player, "combatplayer", false));
                        self::combatplayerscoretag($player, $attacker);
                    }
                }
                if (PlayerDataManager::getdata($player, "gametype", false) == "duel") {
                    $duelid = PlayerDataManager::getdata($player, "duelid", false);
                    $team = PlayerDataManager::getdata($player, "team", false);
                    if ($team == "red") {
                        $targets = PlayerDuel::$dueldatas[$duelid]["blue"];
                    } else {
                        $targets = PlayerDuel::$dueldatas[$duelid]["red"];
                    }
                    foreach ($targets as $target) {
                        self::combatplayerscoretag($player, $target);
                    }
                }
                ScoreTag::setScoreTag($player, [$player], "PVP client is god");
            }
        }
    }

    private static function UpdateXp($player)
    {
        $xuid = $player->getXuid();
        $config = yaml::getconfig();
        $gametype = PlayerDataManager::getdata($player, "gametype", false);
        if ($gametype !== "lobby") {
            if ($gametype !== "practice") {
                $game = PlayerDataManager::getdata($player, "ingame", false);
                $cooltime = cooltime::$cooltime;
                $time = microtime(true);
                $cooltimekit = $config["games"][$gametype][$game]["itemcooltime"];
                $cooltimedata = $config["itemcooltime"][$cooltimekit];
                $priority = $config["itemcooltime"][$cooltimekit]["priority_item"];
                $xpManager = $player->getXpManager();
                $level = 0;
                $prog = 0.0;
                if ($prog == 0.0 && isset($cooltime[$xuid])) {
                    foreach ($cooltime[$xuid] as $item => $itemtime) {
                        if (isset($cooltimedata[$item])) {
                            $delay = $itemtime - $time;
                            $cooltime = $cooltimedata[$item];
                            if (PlayerDataManager::getData($player, "party", false) == "true") {
                                $partyid = PlayerDataManager::getData($player, "party_name", false);
                                if (Party::$parties[$partyid]["induel"] && Party::$parties[$partyid]["split"]) {
                                    $cooltime = Party::$parties[$partyid][$game]["cooltime"][$item];
                                }
                            }
                            $cooltime2 = 0 - $cooltime;
                            if (!($delay < $cooltime2)) { {
                                    $level = (int) ceil($cooltime + $delay);
                                    $prog = 1 - $delay / $cooltime2;
                                }
                            }
                        }
                    }
                    if (isset($cooltime[$xuid][$priority]) && isset($cooltimedata[$priority])) {
                        $delay = $cooltime[$xuid][$priority] - $time;
                        $cooltime = $cooltimedata[$priority];
                        if (PlayerDataManager::getData($player, "party", false) == "true") {
                            $partyid = PlayerDataManager::getData($player, "party_name", false);
                            if (Party::$parties[$partyid]["induel"] && Party::$parties[$partyid]["split"]) {
                                $cooltime = Party::$parties[$partyid][$game]["cooltime"][$priority];
                            }
                        }
                        $cooltime2 = 0 - $cooltime;
                        if (!($delay < $cooltime2)) {
                            $level = (int) ceil($cooltime + $delay);
                            $prog = 1 - $delay / $cooltime2;
                        }
                    }
                    $xpManager->setXpLevel($level);
                    $xpManager->setXpProgress($prog);
                }
            }
        }
    }

    private static function combatplayerscoretag($player, $attackers)
    {
        if ($attackers instanceof \pocketmine\player\Player) {
            $attackers = [$attackers];
        }
        $pxuid = $player->getXuid();
        $icps = PracticePlayer::$playerdata[$pxuid]["settings"]["scoreboard"]["incombat_cps"];
        $iping = PracticePlayer::$playerdata[$pxuid]["settings"]["scoreboard"]["incombat_ping"];
        $idrop = PracticePlayer::$playerdata[$pxuid]["settings"]["scoreboard"]["incombat_drop"];
        foreach ($attackers as $attacker) {
            $xuid = $attacker->getXuid();
            $cps = DataPacketReceiveEventHandler::getCPS($attacker);
            $message = "";
            if ($attacker->isOnline()) {
                if ($iping) {
                    $ping = $attacker->getNetworkSession()->getPing();
                    $message .= "§ePing§7 : " . $ping;
                }
                if ($idrop) {
                    $drop = self::$drop[$xuid];
                    $message .= ($message !== "" ? " | " : "") . "§eDrop§7 : " . $drop;
                }
                if ($icps) {
                    $message .= ($message !== "" ? " | " : "") . "§eCPS§7 : " . $cps;
                }
                ScoreTag::setScoreTag($player, [$attacker], $message);
            }
        }
    }
}
