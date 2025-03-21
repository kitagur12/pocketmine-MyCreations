<?php

namespace practice\form;

use pocketmine\Server;
use practice\practice;
use practice\form\formapi;
use practice\form\invformapi;
use practice\player\PracticePlayer;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;
use practice\duel\Request;
use practice\duel\PlayerDuel;
use practice\party\Party;
use practice\practice\practice as PracticeGame;

class Forms
{
    public static array $playerlist = [];
    public static array $requestdata = [];
    public static array $joinlist = [];
    public static array $memberlist = [];

    public static function joinffa($player)
    {
        $config = yaml::getconfig();
        $gamemode = $config["games"]["ffa"];
        $players = Server::getInstance()->getOnlinePlayers();
        $gameCounts = [];
        foreach ($gamemode as $game => $value) {
            $gameCounts[$game] = 0;
        }
        foreach ($players as $playerss) {
            $gametype = PlayerDataManager::getdata($playerss, "gametype");
            $game = PlayerDataManager::getdata($playerss, "ingame");
            $party = PlayerDataManager::getdata($playerss, "party");
            if ($gametype == "ffa" && $party !== "true") {
                $gameCounts[$game] += 1;
            }
        }
        invformapi::resetInventory($player);
        for ($i = 0; $i < 27; $i++) {
            invformapi::setItem($player, $i, -657, 1);
            invformapi::setName($player, $i, "");
        }
        invformapi::setInventoryName($player, yaml::getcolor() . "Free For All");
        foreach ($gamemode as $game => $value) {
            $meta = $value["meta"] ?? 0;
            invformapi::setItem($player, $value["slot"], $value["icon"], 1);
            invformapi::setMeta($player, $value["slot"], $meta);
            invformapi::setName($player, $value["slot"], $value["name"]);
            invformapi::setLore($player, $value["slot"], "§rPlayer: " . yaml::getcolor() . $gameCounts[$game]);
        }
        invformapi::sendInvform($player, 1);
    }
    public static function duel($player, $id, $open = false)
    {
        $isinv = true;
        $config = yaml::getconfig();
        $data = PracticePlayer::$playerdata;
        $gamemode = $config["games"]["duel"];
        $players = Server::getInstance()->getOnlinePlayers();
        invformapi::resetInventory($player);
        for ($i = 0; $i < 27; $i++) {
            invformapi::setItem($player, $i, -657, 1);
            invformapi::setName($player, $i, "");
        }
        $xuid = $player->getXuid();
        if ($id == 1) {
            $FormName = PracticePlayer::getLangText($player, "form.duel.name");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $gameCounts = 0;
            }
            foreach ($players as $playerss) {
                $gametype = PlayerDataManager::getdata($playerss, "queuegametype");
                $gameinfo = PlayerDataManager::getdata($playerss, "queuegameinfo");
                if ($gametype == "duelqueue" && str_starts_with($gameinfo, "unranked_")) {
                    $gameCounts += 1;
                }
            }
            $ItemName = PracticePlayer::getLangText($player, "form.duel.unranked");
            invformapi::setItem($player, 10, 438, 1);
            invformapi::setName($player, 10, $ItemName);
            invformapi::setLore($player, 10, "Player: " . yaml::getcolor() . $gameCounts);

            foreach ($gamemode as $game => $value) {
                $gameCounts = 0;
            }
            foreach ($players as $playerss) {
                $gametype = PlayerDataManager::getdata($playerss, "queuegametype");
                $gameinfo = PlayerDataManager::getdata($playerss, "queuegameinfo");
                if ($gametype == "duelqueue" && str_starts_with($gameinfo, "ranked_")) {
                    $gameCounts += 1;
                }
            }
            $ItemName = PracticePlayer::getLangText($player, "form.duel.ranked");
            invformapi::setItem($player, 11, 423, 1);
            invformapi::setName($player, 11, $ItemName);
            invformapi::setLore($player, 11, "Player: " . yaml::getcolor() . $gameCounts);
            $ItemName = PracticePlayer::getLangText($player, "form.duel.bot");
            invformapi::setItem($player, 12, -967, 1);
            invformapi::setName($player, 12, $ItemName);
            $ItemName = PracticePlayer::getLangText($player, "form.duel.request");
            invformapi::setItem($player, 14, 412, 1);
            invformapi::setName($player, 14, $ItemName);
            $ItemName = PracticePlayer::getLangText($player, "form.duel.accept");
            invformapi::setItem($player, 15, 453, 1);
            invformapi::setName($player, 15, $ItemName);
            $ItemName = PracticePlayer::getLangText($player, "form.duel.recent");
            invformapi::setItem($player, 16, 429, 1);
            invformapi::setName($player, 16, $ItemName);
            $formid = 200;
        }
        if ($id == 2) { //unranked
            $FormName = PracticePlayer::getLangText($player, "form.duel.unranked");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
                foreach ($players as $playerss) {
                    $gametype = PlayerDataManager::getdata($playerss, "queuegametype");
                    $gameinfo = PlayerDataManager::getdata($playerss, "queuegameinfo");
                    if ($gametype == "duelqueue" && $gameinfo == "unranked_" . $game) {
                        $gameCounts[$game] += 1;
                    }
                }
            }
            $returntext = PracticePlayer::getLangText($player, "general.return");
            invformapi::setItem($player, 4, 398, 1);
            invformapi::setName($player, 4, $returntext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
                invformapi::setLore($player, $value["slot"], "\n" . yaml::getcolor() . "Queue: §l§f" . $gameCounts[$game] . "\n§rPlaying: " . "0");
                if ($gameCounts[$game] !== 0) {
                    invformapi::setEnchant($player, $value["slot"], 17, 1);
                }
            }
            $formid = 202;
        }
        if ($id == 3) { //ranked
            $FormName = PracticePlayer::getLangText($player, "form.duel.unranked");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
                foreach ($players as $playerss) {
                    $gametype = PlayerDataManager::getdata($playerss, "queuegametype");
                    $gameinfo = PlayerDataManager::getdata($playerss, "queuegameinfo");
                    if ($gametype == "duelqueue" && $gameinfo == "ranked_" . $game) {
                        $gameCounts[$game] += 1;
                    }
                }
            }
            $returntext = PracticePlayer::getLangText($player, "general.return");
            invformapi::setItem($player, 4, 398, 1);
            invformapi::setName($player, 4, $returntext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
                invformapi::setLore($player, $value["slot"], "\n§rRate: " . yaml::getcolor() . $data[$player->getXuid()]["rate"][$game] . "\n" . yaml::getcolor() . "Queue: §l§f" . $gameCounts[$game] . "\n§rPlaying: " . "0");
                if ($gameCounts[$game] !== 0) {
                    invformapi::setEnchant($player, $value["slot"], 17, 1);
                }
            }
            $formid = 203;
        }

        if ($id == 5) { //request
            $isinv = false;
            formapi::setType($player, "custom_form");
            formapi::setTitle($player, "form.duel.request");
            formapi::setContent($player, "");

            $players = array_values(Server::getInstance()->getOnlinePlayers());
            $playerlist = [];

            foreach ($players as $playera) {
                $playerlist[] = $playera->getName();
            }
            $playerName = $player->getName();
            $playerlist = array_values(array_filter($playerlist, fn($name) => $name !== $playerName));

            $gamelist = [];
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            foreach ($gamemode as $game) {
                $gamelist[] = $game["name"];
            }

            if (count($playerlist) == 0) {
                formapi::addLabel($player, "form.duel.request.none");
            } else {
                formapi::addDropDown($player, "form.duel.request.select", $playerlist);
                formapi::addDropDown($player, "form.duel.content", $gamelist);
            }
            self::$playerlist[$xuid] = $playerlist;

            formapi::sendForm($player, 205);
            PlayerDataManager::setData($player, "menuid", 205);
        }

        if ($id == 6) { //accept
            self::$requestdata[$xuid] = Request::checkRequest($player);
            $time = time();
            $count = -1;
            foreach (self::$requestdata[$xuid] as $name => $value) {
                $count += 1;
                $time = $time - $value["time"];
                $gamemode = $config["games"]["duel"];
                foreach ($gamemode as $game) {
                    $gamelist[] = $game["name"];
                }
                invformapi::setItem($player, $count, -967, 1);
                invformapi::setName($player, $count, $name);
                invformapi::setLore($player, $count, "\n§rGame: " . yaml::getcolor() . $gamelist[$value["game"]] . "\n§rRemaining: §r" . 300 - $time . "§rs");
            }
            $formid = 206;
        }
        if ($isinv) {
            if ($open) {
                invformapi::sendInvform($player, $formid);
            } else {
                invformapi::UpdateInvform($player, $formid);
            }
        }
    }

    public static function practice($player, $id, $open = false)
    {
        $config = yaml::getconfig();
        $isinv = true;
        $ingame = PlayerDataManager::getData($player, "ingame");
        $practiceid = PlayerDataManager::getdata($player, "practiceid");
        if ($id == 1) {
            $config = yaml::getconfig();
            $gamemode = $config["games"]["practice"];
            invformapi::resetInventory($player);
            $soon = PracticePlayer::getLangText($player, "general.soon");
            for ($i = 0; $i < 27; $i++) {
                invformapi::setItem($player, $i, -657, 1);
                invformapi::setName($player, $i, "");
            }
            $FormName = PracticePlayer::getLangText($player, "form.practice.name");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
            }
            $formid = 301;
        }
        if ($id == 2) { //start
            PracticeGame::$practicedatas[$practiceid]["tick"] = -1;
            PracticeGame::$practicedatas[$practiceid]["status"] = PracticeGame::PRACTICE_READY;
            $isinv = false;
            $formid = 302;
        }
        if ($id == 3) { //record
            invformapi::resetInventory($player);
            for ($i = 0; $i < 27; $i++) {
                invformapi::setItem($player, $i, -657, 1);
                invformapi::setName($player, $i, "§8" . "");
            }
            $FormName = PracticePlayer::getLangText($player, "form.practice.record");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $formid = 303;
            $savedata = PracticePlayer::$playerdata[$player->getXuid()]["practice"][$ingame];
            foreach($savedata as $slot => $data) {
                $slot = (int) $slot;
                invformapi::setItem($player, $slot, -655, 1);
                invformapi::setName($player, $slot, "Config: $slot\n\n" . self::arrayToYaml(json_decode($data, true)));
            };
        }
        if ($id == 4) { //settings
            invformapi::resetInventory($player);
            for ($i = 0; $i < 27; $i++) {
                invformapi::setItem($player, $i, -657, 1);
                invformapi::setName($player, $i, "§8" . "");
            }
            $FormName = PracticePlayer::getLangText($player, "form.practice.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $formid = 304;



            $itemName = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 0, 422, 1);
            invformapi::setName($player, 0, $itemName . "\n§c" . $itemName);
            $itemName = PracticePlayer::getLangText($player, "form.practice.save");
            invformapi::setItem($player, 9, 546, 1);
            invformapi::setName($player, 9, $itemName . "\n§a" . $itemName);
            $itemName = PracticePlayer::getLangText($player, "form.spectate.name");
            if (PracticeGame::$practicedatas[$practiceid]["spectate"] == true) {
                $text = "\n§a" . PracticePlayer::getLangText($player, "general.enable");
                invformapi::setItem($player, 18, 431, 1);
            } else {
                $text = "\n§c" . PracticePlayer::getLangText($player, "general.disable");
                invformapi::setItem($player, 18, 433, 1);
            }
            invformapi::setLore($player, 18, $text);
            invformapi::setName($player, 18, $itemName);

            if ($ingame == "clutch") {
                $verticalkb = PracticeGame::$practicedatas[$practiceid]["settings"]["verticalkb"];
                $horizontalkb = PracticeGame::$practicedatas[$practiceid]["settings"]["horizontalkb"];
                $hitcount = PracticeGame::$practicedatas[$practiceid]["settings"]["hitcount"];
                $hitdelay = PracticeGame::$practicedatas[$practiceid]["settings"]["hitdelay"];
                $hitcooltime = PracticeGame::$practicedatas[$practiceid]["settings"]["hitcooltime"];
                $attackrandomise = PracticeGame::$practicedatas[$practiceid]["settings"]["attackrandomise"];
                $hitrandomise = PracticeGame::$practicedatas[$practiceid]["settings"]["hitrandomise"];
                $directionrandomise = PracticeGame::$practicedatas[$practiceid]["settings"]["directionrandomise"];

                $itemName = PracticePlayer::getLangText($player, "practice.horizontalkb");
                invformapi::setItem($player, 10, 352, 1);
                invformapi::setName($player, 10, $itemName . "\n" . yaml::getcolor() . $horizontalkb);
                invformapi::setItem($player, 1, -671, 1);
                invformapi::setName($player, 1, "§c-0.05");
                invformapi::setItem($player, 19, -655, 1);
                invformapi::setName($player, 19, "§a+0.05");
                $itemName = PracticePlayer::getLangText($player, "practice.verticalkb");
                invformapi::setItem($player, 11, 352, 1);
                invformapi::setName($player, 11, $itemName . "\n" . yaml::getcolor() . $verticalkb);
                invformapi::setItem($player, 2, -671, 1);
                invformapi::setName($player, 2, "§r§c-0.05");
                invformapi::setItem($player, 20, -655, 1);
                invformapi::setName($player, 20, "§r§a+0.05");
                $itemName = PracticePlayer::getLangText($player, "practice.hitcount");
                invformapi::setItem($player, 12, 399, 1);
                invformapi::setName($player, 12, $itemName . "\n" . yaml::getcolor() . $hitcount);
                invformapi::setItem($player, 3, -671, 1);
                invformapi::setName($player, 3, "§c-1");
                invformapi::setItem($player, 21, -655, 1);
                invformapi::setName($player, 21, "§a+1");
                $itemName = PracticePlayer::getLangText($player, "practice.hitdelay");
                invformapi::setItem($player, 13, 345, 1);
                invformapi::setName($player, 13, $itemName . "\n" . yaml::getcolor() . $hitdelay);
                invformapi::setItem($player, 4, -671, 1);
                invformapi::setName($player, 4, "§r§c-1");
                invformapi::setItem($player, 22, -655, 1);
                invformapi::setName($player, 22, "§r§a+1");
                $itemName = PracticePlayer::getLangText($player, "practice.hitcooltime");
                invformapi::setItem($player, 14, 633, 1);
                invformapi::setName($player, 14, $itemName . "\n" . yaml::getcolor() . $hitcooltime);
                invformapi::setItem($player, 5, -671, 1);
                invformapi::setName($player, 5, "§r§r§c-1");
                invformapi::setItem($player, 23, -655, 1);
                invformapi::setName($player, 23, "§r§r§a+1");
                $itemName = PracticePlayer::getLangText($player, "practice.attackrandomise");
                invformapi::setItem($player, 15, 640, 1);
                invformapi::setName($player, 15, $itemName . "\n" . yaml::getcolor() . $attackrandomise);
                invformapi::setItem($player, 6, -671, 1);
                invformapi::setName($player, 6, "§r§r§r§c-1");
                invformapi::setItem($player, 24, -655, 1);
                invformapi::setName($player, 24, "§r§r§r§a+1");
                $itemName = PracticePlayer::getLangText($player, "practice.hitrandomise");
                invformapi::setItem($player, 16, 7, 1);
                invformapi::setName($player, 16, $itemName . "\n" . yaml::getcolor() . $hitrandomise);
                invformapi::setItem($player, 7, -671, 1);
                invformapi::setName($player, 7, "§r§r§r§r§c-1");
                invformapi::setItem($player, 25, -655, 1);
                invformapi::setName($player, 25, "§r§r§r§r§a+1");
                $itemName = PracticePlayer::getLangText($player, "practice.directionrandomise");
                invformapi::setItem($player, 17, 684, 1);
                invformapi::setName($player, 17, $itemName . "\n" . yaml::getcolor() . $directionrandomise);
                invformapi::setItem($player, 8, -671, 1);
                invformapi::setName($player, 8, "§c-10");
                invformapi::setItem($player, 26, -655, 1);
                invformapi::setName($player, 26, "§a+10");
            }
        }
        if ($id == 5) { //end
            $isinv = false;
            $practiceid = PlayerDataManager::getdata($player, "practiceid");
            PracticeGame::$practicedatas[$practiceid]["tick"] = -1;
            PracticeGame::$practicedatas[$practiceid]["status"] = PracticeGame::PRACTICE_STANDING;
        }
        if ($isinv) {
            if ($open) {
                invformapi::sendInvform($player, $formid);
            } else {
                invformapi::UpdateInvform($player, $formid);
            }
        }
    }

    public static function party($player, $id, $open = false)
    {
        $isinv = true;
        $config = yaml::getconfig();
        $xuid = $player->getXuid();
        $gamemode = $config["games"]["duel"];
        $players = Server::getInstance()->getOnlinePlayers();
        $partyname = PlayerDataManager::getdata($player, "party_name");
        invformapi::resetInventory($player);
        for ($i = 0; $i < 27; $i++) {
            invformapi::setItem($player, $i, -657, 1);
            invformapi::setName($player, $i, "");
        }
        $FormName = PracticePlayer::getLangText($player, "form.party.name");
        invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
        if ($id == 1) {
            invformapi::setInvtype($player, "HOPPER", 8);
            $createitem = PracticePlayer::getLangText($player, "form.party.create");
            invformapi::setItem($player, 0, 554, 1);
            invformapi::setName($player, 0, $createitem);
            $joinitem = PracticePlayer::getLangText($player, "form.party.join");
            invformapi::setItem($player, 1, 328, 1);
            invformapi::setName($player, 1, $joinitem);
            $inviteitem = PracticePlayer::getLangText($player, "form.party.invite");
            invformapi::setItem($player, 2, 549, 1);
            invformapi::setName($player, 2, $inviteitem);
            $listitem = PracticePlayer::getLangText($player, "form.party.list");
            invformapi::setItem($player, 4, 585, 1);
            invformapi::setName($player, 4, $listitem);
            $formid = 400;
        }

        if ($id == 2) { //public or private
            invformapi::setInvtype($player, "HOPPER", 8);
            $publicitem = PracticePlayer::getLangText($player, "form.party.creaye.public");
            invformapi::setItem($player, 1, 144, 1);
            invformapi::setName($player, 1, $publicitem);
            $privateitem = PracticePlayer::getLangText($player, "form.party.creaye.private");
            invformapi::setItem($player, 3, -965, 1);
            invformapi::setName($player, 3, $privateitem);
            $formid = 401;
        }
        if ($id == 3) { //join party
            invformapi::setInvtype($player, "HOPPER", 8);
            $formid = 403;
        }
        if ($id == 4) { //invited party
            invformapi::setInvtype($player, "HOPPER", 8);
            $formid = 404;
        }
        if ($id == 5) { //party list
            invformapi::setInvtype($player, "HOPPER", 8);
            $formid = 405;
        }
        if ($id == 6) { //party duel
            $parties = Party::$parties;
            $gameCounts = [];
            $gamePlaying = [];
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
                $gamePlaying[$game] = 0;
                foreach ($parties as $party) {
                    $data = $party["inqueue"] ?? false;
                    //var_dump($data);
                    if ($data && $party["game"] == $game) {
                        $gameCounts[$game] += 1;
                    }
                    if ($party["induel"] && $party["game"] == $game) {
                        $gamePlaying[$game] += 1;
                    }
                }
            }

            $requesttext = PracticePlayer::getLangText($player, "party.game.party");
            invformapi::setItem($player, 4, 551, 1);
            invformapi::setName($player, 4, $requesttext);
            $requesttext = PracticePlayer::getLangText($player, "party.game.duel");
            invformapi::setItem($player, 8, 412, 1);
            invformapi::setName($player, 8, $requesttext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
                invformapi::setLore($player, $value["slot"], "\n" . yaml::getcolor() . "Queue: §l§f" . $gameCounts[$game] . "\n§rPlaying: " . "0");
                if ($gameCounts[$game] !== 0) {
                    invformapi::setEnchant($player, $value["slot"], 17, 1);
                }
            }
            $formid = 406;
        }
        if ($id == 7) { //split party duel
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
            }
            $votetext = PracticePlayer::getLangText($player, "party.vote.game");
            invformapi::setItem($player, 3, 412, 1);
            invformapi::setName($player, 3, $votetext);
            $randomtext = PracticePlayer::getLangText($player, "party.random.game");
            invformapi::setItem($player, 5, 385, 1);
            invformapi::setName($player, 5, $randomtext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                $vote = Party::$parties[$partyname]["vote"][$game];
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
                invformapi::setLore($player, $value["slot"], "\n" . yaml::getcolor() . "Vote: §l§f" . $vote);
            }
            $formid = 406;
            $formid = 407;
        }
        if ($id == 8) { //party ffa
            $config = yaml::getconfig();
            $gamemode = $config["games"]["ffa"];
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
            }
            invformapi::sendInvform($player, 1);
            $formid = 408;
        }
        if ($id == 9) { //party vote
            $randomtext = PracticePlayer::getLangText($player, "party.random");
            invformapi::setItem($player, 4, 412, 1);
            invformapi::setName($player, 4, $randomtext);
            $votedgame = PlayerDataManager::getdata($player, "vote");
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                $vote = Party::$parties[$partyname]["vote"][$game];
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setLore($player, $value["slot"], "\n" . yaml::getcolor() . "Vote: §l§f" . $vote);
                if ($votedgame == $game) {
                    invformapi::setEnchant($player, $value["slot"], 17, 1);
                }
            }
            $formid = 409;
        }
        if ($id == 10) { //party settings
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $invitetext = PracticePlayer::getLangText($player, "party.settings.invite");
            invformapi::setItem($player, 11, 412, 1);
            invformapi::setName($player, 11, $invitetext);
            $promotetext = PracticePlayer::getLangText($player, "party.settings.promote");
            invformapi::setItem($player, 12, 601, 1);
            invformapi::setName($player, 12, $promotetext);
            $kicktext = PracticePlayer::getLangText($player, "party.settings.kick");
            invformapi::setItem($player, 13, 388, 1);
            invformapi::setName($player, 13, $kicktext);
            $bantext = PracticePlayer::getLangText($player, "party.settings.ban");
            invformapi::setItem($player, 14, 101, 1);
            invformapi::setName($player, 14, $bantext);
            $nametext = PracticePlayer::getLangText($player, "party.settings.disband");
            invformapi::setItem($player, 15, 422, 1);
            invformapi::setName($player, 15, yaml::getcolor() . "" . $nametext);
            $opentext = PracticePlayer::getLangText($player, "party.settings.open");
            self::toggleitem($player, 20, Party::$parties[$partyname]["open"]);
            invformapi::setName($player, 20, $opentext);
            $ffatext = PracticePlayer::getLangText($player, "party.settings.partyffa");
            self::toggleitem($player, 21, Party::$parties[$partyname]["partyffa"]);
            invformapi::setName($player, 21, $ffatext);
            $invitetext = PracticePlayer::getLangText($player, "party.settings.membrt_invite");
            self::toggleitem($player, 22, Party::$parties[$partyname]["member_invite"]);
            invformapi::setName($player, 22, $invitetext);
            $selecttext = PracticePlayer::getLangText($player, "party.settings.select");
            self::toggleitem($player, 23, Party::$parties[$partyname]["member_select"]);
            invformapi::setName($player, 23, $selecttext);
            $gametext = PracticePlayer::getLangText($player, "party.settings.game");
            invformapi::setItem($player, 24, 554, 1);
            invformapi::setName($player, 24, $gametext);
            $formid = 410;
        }

        if ($id == 11) { //party join
            invformapi::closeInvform($player);
            $formid = 411;
            $FormName = PracticePlayer::getLangText($player, "party.join");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $i = 0;
            self::$joinlist[$xuid] = [];
            foreach (Party::$parties as $partyname => $party) {
                //var_dump($party); //ここで過去に原因不明のクラッシュしたため下の行で?? falseで応急措置しているが根本的な原因が不明のままなので気が向いたり誰かがここ見たら修正お願い
                $data = $party["open"] ?? false;

                if ($data == true) {
                    invformapi::setItem($player, $i, -967, 1);
                    invformapi::setName($player, $i, Party::$parties[$partyname]["name"]);
                    self::$joinlist[$xuid][$i] = $partyname;
                    $i += 1;
                }
            }
            practice::CreateTask("practice\utils\\task\\sendinvform", [$player, $formid], 0);
        }
        if ($id == 12) { //party invite
            $formid = 412;
            $parties = Party::$parties;
            $i = 0;
            self::$joinlist[$xuid] = [];
            foreach ($parties as $partyname => $party) {
                if (in_array($player->getName(), $party["invite"])) {
                    $playercount = 0;
                    foreach ($party["players"] as $member) {
                        $playercount += 1;
                    }
                    self::$joinlist[$xuid][$i] = $partyname;
                    $ownername = $party["owner"]->getName();
                    invformapi::setItem($player, $i, -967, 1);
                    invformapi::setName($player, $i, Party::$parties[$partyname]["name"]);
                    invformapi::setLore($player, $i, "Player: " . yaml::getcolor() . $playercount . "\n§rOwner: " . yaml::getcolor() . $ownername);
                    $i += 1;
                }
            }
        }

        if ($id == 13) { //party list
            invformapi::closeInvform($player);
            $formid = 413;
            $FormName = PracticePlayer::getLangText($player, "party.disband");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $i = 0;
            foreach (Party::$parties as $owner) {
                invformapi::setItem($player, $i, -967, count($owner["players"]));
                invformapi::setName($player, $i, $owner["name"]);
                if ($owner["open"]) {
                    $publicName = PracticePlayer::getLangText($player, "party.public");
                    invformapi::setLore($player, $i, "§a" . $publicName);
                } else {
                    $privateName = PracticePlayer::getLangText($player, "party.private");
                    invformapi::setLore($player, $i, yaml::getcolor() . "" . $privateName);
                }
                $i += 1;
            }
            practice::CreateTask("practice\utils\\task\\sendinvform", [$player, $formid], 0);
        }

        if ($id == 14) { //leave party
            Party::removePlayer($partyname, $player);
            invformapi::closeInvform($player);
            $formid = 414;
            return;
        }

        if ($id == 15) { //player selector
            $formid = 415;
            $i = 0;
            self::$memberlist[$xuid] = [];
            $owner = Party::$parties[$partyname]["owner"];
            foreach (Party::$parties[$partyname]["players"] as $member) {
                self::$memberlist[$xuid][] = $member;
                invformapi::setItem($player, $i, -967, 1);
                invformapi::setName($player, $i, $member->getName());
                if ($owner === $member) {
                    invformapi::setName($player, $i, yaml::getcolor() . "" . $member->getName());
                }
                $i += 1;
            }
        }

        if ($id == 16) { //confirmation
            $formid = 416;
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            if (PlayerDataManager::getdata($player, "confirm_form") == "disband") {
                $itemName = PracticePlayer::getLangText($player, "party.settings.disband");
                invformapi::setItem($player, 0, 422, 1);
                invformapi::setName($player, 0, $itemName);
            }
            if (PlayerDataManager::getdata($player, "confirm_form") == "promote") {
                $itemName = PracticePlayer::getLangText($player, "party.settings.promote");
                invformapi::setItem($player, 0, 601, 1);
                invformapi::setName($player, 0, $itemName);
                $itemName = PlayerDataManager::getdata($player, "confirm_form_player");
                invformapi::setItem($player, 1, -967, 1);
                invformapi::setName($player, 1, $itemName->getName());
            }
            if (PlayerDataManager::getdata($player, "confirm_form") == "ban") {
                $itemName = PracticePlayer::getLangText($player, "party.settings.promote");
                invformapi::setItem($player, 0, 101, 1);
                invformapi::setName($player, 0, $itemName);
                $itemName = PlayerDataManager::getdata($player, "confirm_form_player");
                invformapi::setItem($player, 1, -967, 1);
                invformapi::setName($player, 1, $itemName->getName());
            }
            $cancelName = PracticePlayer::getLangText($player, "general.cancel");
            invformapi::setItem($player, 12, 434, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "" . $cancelName);
            $confirmName = PracticePlayer::getLangText($player, "general.confirm");
            invformapi::setItem($player, 14, 431, 1);
            invformapi::setName($player, 14, "§a" . $confirmName);
        }

        if ($id == 17) { //party invite
            self::$requestdata[$xuid] = [];
            invformapi::closeInvform($player);
            $formid = 417;
            formapi::setType($player, "custom_form");
            formapi::setTitle($player, "form.duel.request");
            formapi::setContent($player, "");
            $players = Server::getInstance()->getOnlinePlayers();
            $playerlist = [];
            foreach ($players as $playera) {
                $playerlist[] = $playera->getName();
                self::$requestdata[$xuid][] = $playera->getName();
            }
            $playerName = $player->getName();
            $playerlist = array_values(array_filter($playerlist, function ($name) use ($playerName) {
                return $name !== $playerName;
            }));
            self::$requestdata[$xuid] = array_values(array_filter(self::$requestdata[$xuid], function ($name) use ($playerName) {
                return $name !== $playerName;
            }));
            if (count($playerlist) == 0) {
                formapi::addLabel($player, "form.duel.request.none");
                $formid = -1;
            } else {
                formapi::addDropDown($player, "form.duel.request.select", $playerlist);
            }
            PlayerDataManager::setdata($player, "menuid", $formid);
            practice::CreateTask("practice\utils\\task\\sendform", [$player, $formid], 5);
            return;
        }

        if ($id == 18) { //party game settings
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
            }
            $returntext = PracticePlayer::getLangText($player, "general.return");
            invformapi::setItem($player, 4, 398, 1);
            invformapi::setName($player, 4, $returntext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
            }
            $formid = 418;
        }

        if ($id == 19) { //party game settings
            $formid = 419;
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $duelname = $gamemode[$gameid]["name"];
            $duelitem = $gamemode[$gameid]["icon"];
            $duelitemmeta = $gamemode[$gameid]["meta"] ?? 0;
            invformapi::setItem($player, 1, $duelitem, 1);
            invformapi::setName($player, 1, $duelname);
            invformapi::setMeta($player, 1, $duelitemmeta);
            $returntext = PracticePlayer::getLangText($player, "general.return");
            invformapi::setItem($player, 0, 398, 1);
            invformapi::setName($player, 0, $returntext);
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $knockbackName = PracticePlayer::getLangText($player, "party.settings.game.knockback");
            invformapi::setItem($player, 11, 352, 1); //knockback
            invformapi::setName($player, 11, $knockbackName);
            $delayName = PracticePlayer::getLangText($player, "party.settings.game.delay");
            invformapi::setItem($player, 12, 345, 1); //hitdelay
            invformapi::setName($player, 12, $delayName);
            $maxhpName = PracticePlayer::getLangText($player, "party.settings.game.maxhp");
            invformapi::setItem($player, 13, 436, 1); //maxhp
            invformapi::setName($player, 13, $maxhpName);
            invformapi::setMeta($player, 13, 21);
            $cooltimeyName = PracticePlayer::getLangText($player, "party.settings.game.cooltime");
            invformapi::setItem($player, 14, 324, 1); //itemcooltime
            invformapi::setName($player, 14, $cooltimeyName);
            if ($dueltype == "normal") {
                $mapitemName = PracticePlayer::getLangText($player, "party.settings.game.map");
                invformapi::setItem($player, 15, -649, 1); //mapselect
                invformapi::setName($player, 15, $mapitemName);
                invformapi::setLore($player, 15, "§8Disabled");
            }
            if ($dueltype == "score") {;
                $scoreName = PracticePlayer::getLangText($player, "party.settings.game.score");
                invformapi::setItem($player, 15, 399, 1);
                invformapi::setName($player, 15, $scoreName);
            }
            if ($dueltype == "bed") {
                $nobed = Party::$parties[$partyname][$gameid]["nobed"] ?? false;
                invformapi::setItem($player, 15, 444, 1);
                if ($nobed) {
                    $text = "\n§a" . PracticePlayer::getLangText($player, "general.enable");
                    invformapi::setMeta($player, 15, 14);
                } else {
                    $text = "\n" . yaml::getcolor() . PracticePlayer::getLangText($player, "general.disable");
                    invformapi::setMeta($player, 15, 8);
                }
                $nobedName = PracticePlayer::getLangText($player, "party.settings.game.nobed");
                invformapi::setName($player, 15, $nobedName);
                invformapi::setLore($player, 15, $text);
                $RespawnName = PracticePlayer::getLangText($player, "party.settings.game.respawn");
                invformapi::setItem($player, 16, 601, 1);
                invformapi::setName($player, 16, $RespawnName);
            }
        }

        if ($id == 20) { //score settings
            $formid = 420;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $resettext = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 10, 454, 1);
            invformapi::setName($player, 10, "§a" . $resettext);
            $pointtext = PracticePlayer::getLangText($player, "party.settings.game.maxpoint");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 13, 417, 1);
            invformapi::setName($player, 13, $pointtext);
            invformapi::setItem($player, 14, -647, 1);
            invformapi::setName($player, 14, "§a+1");
            if ($dueltype == "hit") {
                invformapi::setItem($player, 11, -671, 1);
                invformapi::setName($player, 11, yaml::getcolor() . "-10");
                invformapi::setItem($player, 15, -647, 1);
                invformapi::setName($player, 15, "§a+10");
            }
            invformapi::setLore($player, 13, yaml::getcolor() . "" . $pointtext . ": §f" . Party::$parties[$partyname][$gameid]["score"]);
        }

        $itemlist = [
            "ender_pearl" => "Ender Pearl",
            "snowball" => "Snowball",
            "steak" => "Steak",
            "gapple" => "Golden Apple",
            "enchant_gapple" => "Enchanted Golden Apple",
            "gapple_head" => "Gold Ingot",
            "arrow" => "Arrow",
            "potion" => "Potion",
            "splash_potion" => "Splash Potion",
            "totem" => "Totem",
        ];
        $itemidlist = [
            "ender_pearl" => 448,
            "snowball" => 399,
            "steak" => "Steak",
            "gapple" => 280,
            "enchant_gapple" => 281,
            "gapple_head" => 330,
            "arrow" => 325,
            "potion" => 453,
            "splash_potion" => 594,
            "totem" => 601,
        ];
        if ($id == 21) { //cooltime settings
            $formid = 421;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $returntext = PracticePlayer::getLangText($player, "general.return");
            invformapi::setItem($player, 0, 398, 1);
            invformapi::setName($player, 0, $returntext);
            $cooltime = $config["itemcooltime"][$gamemode[$gameid]["itemcooltime"]];
            foreach ($cooltime as $itemname => $value) {
                if (stripos($itemname, "slot") == false && $itemname !== "priority_item") {
                    $slot = $config["itemcooltime"][$gamemode[$gameid]["itemcooltime"]][$itemname . "_slot"];
                    invformapi::setItem($player, $slot, $itemidlist[$itemname], 1);
                    invformapi::setName($player, $slot, $itemlist[$itemname]);
                }
            }
        }

        if ($id == 22) { //cooltimeInt settings
            $formid = 422;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $itemid = PlayerDataManager::getData($player, "select_itemid");
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);

            $itemid = PlayerDataManager::getData($player, "select_itemid");
            invformapi::setItem($player, 4, $itemidlist[$itemid], 1);
            invformapi::setName($player, 4, $itemlist[$itemid]);
            $resettext = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 10, 454, 1);
            invformapi::setName($player, 10, "§a" . $resettext);
            $pointtext = PracticePlayer::getLangText($player, "party.settings.game.cooltimes");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 13, 417, 1);
            invformapi::setName($player, 13, $pointtext);
            invformapi::setItem($player, 14, -647, 1);
            invformapi::setName($player, 14, "§a+1");
            invformapi::setLore($player, 13, yaml::getcolor() . "" . $pointtext . ": §f" . Party::$parties[$partyname][$gameid]["cooltime"][$itemid]);
        }

        if ($id == 23) { //maxhp settings
            $formid = 423;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $itemid = PlayerDataManager::getData($player, "select_itemid");
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $resettext = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 10, 454, 1);
            invformapi::setName($player, 10, "§a" . $resettext);
            $pointtext = PracticePlayer::getLangText($player, "party.settings.game.maxhps");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 13, 417, 1);
            invformapi::setName($player, 13, $pointtext);
            invformapi::setItem($player, 14, -647, 1);
            invformapi::setName($player, 14, "§a+1");
            invformapi::setLore($player, 13, yaml::getcolor() . "" . $pointtext . ": §f" . Party::$parties[$partyname][$gameid]["maxhp"]);
        }

        if ($id == 24) { // knockback settings
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $formid = 424;
            $FormName = PracticePlayer::getLangText($player, "party.settings");

            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            for ($i = 0; $i < 27; $i++) invformapi::setItem($player, $i, -657, 1, "");

            invformapi::setItem($player, 1, $gamemode[$gameid]["icon"], 1);
            invformapi::setName($player, 1, $gamemode[$gameid]["name"]);
            invformapi::setMeta($player, 1, $gamemode[$gameid]["meta"] ?? 0);
            invformapi::setItem($player, 0, 398, 1);
            invformapi::setName($player, 0, PracticePlayer::getLangText($player, "general.return"));

            $settings = [
                "horizontal" => [2, 3, 4, 5, 6],
                "vertical" => [11, 12, 13, 14, 15],
                "maxkb" => [20, 21, 22, 23, 24]
            ];
            foreach ($settings as $type => $slots) {
                $text = PracticePlayer::getLangText($player, "party.settings.game.kb.$type");
                $kb = Party::$parties[$partyname][$gameid]["knockback"][$type];
                $colors = $kb == "default" ? ["§8", "§8", 684, -649, -649] : [yaml::getcolor() . "", "§a", 417, -671, -647];
                invformapi::setItem($player, $slots[0], $colors[3], 1);
                invformapi::setName($player, $slots[0], $colors[0] . "-0.1");
                invformapi::setItem($player, $slots[1], $colors[3], 1);
                invformapi::setName($player, $slots[1], $colors[0] . "-0.01");
                invformapi::setItem($player, $slots[2], $colors[2], 1);
                invformapi::setName($player, $slots[2], $text);
                invformapi::setItem($player, $slots[3], $colors[4], 1);
                invformapi::setName($player, $slots[3], $colors[1] . "+0.01");
                invformapi::setItem($player, $slots[4], $colors[4], 1);
                invformapi::setName($player, $slots[4], $colors[1] . "+0.1");
                invformapi::setLore($player, $slots[2], yaml::getcolor() . "" . $text . ": §f" . $kb);
            }
        }

        if ($id == 25) { //hitdelay settings
            $formid = 425;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $itemid = PlayerDataManager::getData($player, "select_itemid");
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $resettext = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 10, 454, 1);
            invformapi::setName($player, 10, "§a" . $resettext);
            $pointtext = PracticePlayer::getLangText($player, "party.settings.game.hitdelay");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 13, 417, 1);
            invformapi::setName($player, 13, $pointtext);
            invformapi::setItem($player, 14, -647, 1);
            invformapi::setName($player, 14, "§a+1");
            invformapi::setLore($player, 13, yaml::getcolor() . "" . $pointtext . ": §f" . Party::$parties[$partyname][$gameid]["hitdelay"]);
        }

        if ($id == 26) { //respawn settings
            $formid = 426;
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");
            $dueltype = $gamemode[$gameid]["dueltype"];
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $resettext = PracticePlayer::getLangText($player, "general.reset");
            invformapi::setItem($player, 10, 454, 1);
            invformapi::setName($player, 10, "§a" . $resettext);
            $respawntext = PracticePlayer::getLangText($player, "party.settings.game.respawn");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 13, 417, 1);
            invformapi::setName($player, 13, $respawntext);
            invformapi::setItem($player, 14, -647, 1);
            invformapi::setName($player, 14, "§a+1");
            invformapi::setLore($player, 13, yaml::getcolor() . "" . $respawntext . ": §f" . Party::$parties[$partyname][$gameid]["respawn"]);
        }
        if ($id == 27) { //party info
            $formid = 427;
            $FormName = PracticePlayer::getLangText($player, "party.info");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $listitem = PracticePlayer::getLangText($player, "party.list.member");
            invformapi::setItem($player, 3, 796, 1);
            invformapi::setName($player, 3, $listitem);
            $listitem = PracticePlayer::getLangText($player, "form.party.list");
            invformapi::setItem($player, 4, 585, 1);
            invformapi::setName($player, 4, $listitem);
            $owner = "§rOwner" . yaml::getcolor() . ": " . Party::$parties[$partyname]["owner"]->getName();
            invformapi::setItem($player, 5, -967, 1);
            invformapi::setName($player, 5, $owner);
            $soon = PracticePlayer::getLangText($player, "general.soon");
            invformapi::setItem($player, 6, -649, 1);
            invformapi::setName($player, 6, $soon);
            $opentext = PracticePlayer::getLangText($player, "party.settings.open");
            self::toggleitem($player, 11, Party::$parties[$partyname]["open"]);
            invformapi::setName($player, 11, $opentext);
            $ffatext = PracticePlayer::getLangText($player, "party.settings.partyffa");
            self::toggleitem($player, 12, Party::$parties[$partyname]["partyffa"]);
            invformapi::setName($player, 12, $ffatext);
            $invitetext = PracticePlayer::getLangText($player, "party.settings.membrt_invite");
            self::toggleitem($player, 13, Party::$parties[$partyname]["member_invite"]);
            invformapi::setName($player, 13, $invitetext);
            $selecttext = PracticePlayer::getLangText($player, "party.settings.select");
            self::toggleitem($player, 14, Party::$parties[$partyname]["member_select"]);
            invformapi::setName($player, 14, $selecttext);
            $gametext = PracticePlayer::getLangText($player, "party.settings.game");
            invformapi::setItem($player, 15, 554, 1);
            invformapi::setName($player, 15, $gametext);
        }

        if ($id == 28) { //member info
            $formid = 428;
            $i = 0;
            self::$memberlist[$xuid] = [];
            $owner = Party::$parties[$partyname]["owner"];
            foreach (Party::$parties[$partyname]["players"] as $member) {
                self::$memberlist[$xuid][] = $member;
                invformapi::setItem($player, $i, -967, 1);
                invformapi::setName($player, $i, $member->getName());
                if ($owner === $member) {
                    invformapi::setName($player, $i, yaml::getcolor() . "" . $member->getName());
                }
                $i += 1;
            }
        }

        if ($id == 29) { //game info
            $formid = 429;
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
            }
            foreach ($gamemode as $game => $value) {
                $text = self::arrayToYaml(Party::$parties[$partyname][$game]);
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], yaml::getcolor() . "" . $value["name"] . "§r\n" . $text);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
            }
        }

        if ($id == 30) { //party game duel
            $parties = Party::$parties;
            $gameCounts = [];
            $gamePlaying = [];
            $gamemode = $config["games"]["party"];
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
                $gamePlaying[$game] = 0;
                foreach ($parties as $party) {
                    if ($party["inqueue"] && $party["game"] == $game) {
                        $gameCounts[$game] += 1;
                    }
                    if ($party["induel"] && $party["game"] == $game) {
                        $gamePlaying[$game] += 1;
                    }
                }
            }

            $requesttext = PracticePlayer::getLangText($player, "party.game.default");
            invformapi::setItem($player, 4, 793, 1);
            invformapi::setName($player, 4, $requesttext);
            $requesttext = PracticePlayer::getLangText($player, "party.game.duel");
            invformapi::setItem($player, 8, 412, 1);
            invformapi::setName($player, 8, $requesttext);
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
                invformapi::setMeta($player, $value["slot"], $value["meta"] ?? 0);
                invformapi::setLore($player, $value["slot"], "\n" . yaml::getcolor() . "Queue: §l§f" . $gameCounts[$game] . "\n§rPlaying: " . "0");
            }
            $formid = 430;
        }
        if ($id == 31) { //party duel accept
            $requesttext = PracticePlayer::getLangText($player, "party.game.selector");
            invformapi::setItem($player, 7, 793, 1);
            invformapi::setName($player, 7, $requesttext);
            $requesttext = PracticePlayer::getLangText($player, "form.duel.request");
            invformapi::setItem($player, 8, 412, 1);
            invformapi::setName($player, 8, $requesttext);
            self::$requestdata[$partyname] = [];
            $count = -1;
            foreach (Request::partycheckRequest($partyname) as $name => $value) {
                $count += 1;
                $remaining = 300 - (time() - $value["time"]);

                $gamelist = [];
                $gamemode = $config["games"]["duel"];
                foreach ($gamemode as $id => $game) {
                    $gamelist[$id]["game"] = $game["name"];
                    $gamelist[$id]["party"] = false;
                }
                $gamemode = $config["games"]["party"];
                foreach ($gamemode as $id => $game) {
                    $gamelist[$id]["game"] = $game["name"];
                    $gamelist[$id]["party"] = true;
                }
                if (isset(Party::$parties[$name])) {
                    invformapi::setItem($player, $count, -967, 1);
                    invformapi::setName($player, $count, Party::$parties[$name]["name"]);
                    invformapi::setLore($player, $count, "\n§rGame: " . yaml::getcolor() . $gamelist[$value["game"]]["game"] . "\n§rRemaining: §r" . $remaining . "§rs");
                    self::$requestdata[$partyname][$name]["game"] = $value;
                    self::$requestdata[$partyname][$name]["party"] = $gamelist[$value["game"]]["party"];
                    self::$requestdata[$partyname][$name]["id"] = $name;
                }
            }
            $formid = 431;
        }

        if ($id == 32) { //party duel request
            $formid = 432;
            $FormName = PracticePlayer::getLangText($player, "party.list");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $i = 0;
            self::$requestdata[$player->getXuid()] = [];
            foreach (Party::$parties as $id => $owner) {
                if ($id !== PlayerDataManager::getData($player, "party_name")) {
                    self::$requestdata[$player->getXuid()][] = $id;
                    invformapi::setItem($player, $i, -967, count($owner["players"]));
                    invformapi::setName($player, $i, $owner["name"]);
                    $i += 1;
                }
            }
        }

        if ($id == 33) {
            $formid = 433;
            $gamemode = $config["games"]["duel"];
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $requesttext = PracticePlayer::getLangText($player, "party.game.party");
            invformapi::setItem($player, 4, 551, 1);
            invformapi::setName($player, 4, $requesttext);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
            }
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
            }
        }

        if ($id == 34) {
            $formid = 434;
            $gamemode = $config["games"]["party"];
            $FormName = PracticePlayer::getLangText($player, "party.settings");
            invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
            $requesttext = PracticePlayer::getLangText($player, "party.game.default");
            invformapi::setItem($player, 4, 793, 1);
            invformapi::setName($player, 4, $requesttext);
            foreach ($gamemode as $game => $value) {
                $gameCounts[$game] = 0;
            }
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
            }
        }

        if ($isinv) {
            if ($open) {
                invformapi::sendInvform($player, $formid);
            } else {
                invformapi::UpdateInvform($player, $formid);
            }
        }
    }

    public static function spectate($player, $id, $open = false)
    {
        $config = yaml::getconfig();
        invformapi::resetInventory($player);
        $FormName = PracticePlayer::getLangText($player, "form.spectate.name");
        invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
        invformapi::setItem($player, 0, -657, 1);
        invformapi::setName($player, 0, "");
        invformapi::setItem($player, 1, -657, 1);
        invformapi::setName($player, 1, "");
        invformapi::setItem($player, 7, -657, 1);
        invformapi::setName($player, 7, "");
        invformapi::setItem($player, 8, -657, 1);
        invformapi::setName($player, 8, "");
        invformapi::setItem($player, 9, -657, 1);
        invformapi::setName($player, 9, "");
        invformapi::setItem($player, 17, -657, 1);
        invformapi::setName($player, 17, "");
        invformapi::setItem($player, 18, -657, 1);
        invformapi::setName($player, 18, "");
        invformapi::setItem($player, 26, -657, 1);
        invformapi::setName($player, 26, "");
        invformapi::setItem($player, 36, -657, 1);
        invformapi::setName($player, 36, "");

        $ffaName = PracticePlayer::getLangText($player, "form.spectate.ffa");
        invformapi::setItem($player, 2, 423, 1);
        invformapi::setName($player, 2, $ffaName);
        $duelName = PracticePlayer::getLangText($player, "form.spectate.duel");
        invformapi::setItem($player, 3, 438, 1);
        invformapi::setName($player, 3, $duelName);
        $partyduelName = PracticePlayer::getLangText($player, "form.spectate.partyduel");
        invformapi::setItem($player, 4, 551, 1);
        invformapi::setName($player, 4, $partyduelName);
        $practiceName = PracticePlayer::getLangText($player, "form.spectate.practice");
        invformapi::setItem($player, 5, 349, 1);
        invformapi::setName($player, 5, $practiceName);
        $eventName = PracticePlayer::getLangText($player, "form.spectate.event");
        $soontext = PracticePlayer::getLangText($player, "general.soon");
        invformapi::setItem($player, 6, 601, 1);
        invformapi::setName($player, 6, $eventName);
        invformapi::setLore($player, 6, "\n§8" . $soontext);
        if ($id == 1) {
            for ($i = 9; $i < 27; $i++) {
                invformapi::setItem($player, $i, -657, 1);
                invformapi::setName($player, $i, "");
            }
            $formid = 501;
        }
        if ($id == 2) { //ffa spectate
            invformapi::setEnchant($player, 2, 17, 1);
            $gamemode = $config["games"]["ffa"];
            foreach ($gamemode as $game => $value) {
                $meta = $value["meta"] ?? 0;
                invformapi::setItem($player, $value["slot"], $value["icon"], 1);
                invformapi::setMeta($player, $value["slot"], $meta);
                invformapi::setName($player, $value["slot"], $value["name"]);
            }
            $formid = 502;
        }
        if ($id == 3) { //duel spectate
            invformapi::setEnchant($player, 3, 17, 1);
            $i = 0;
            foreach (PlayerDuel::$dueldatas as $id => $duel) {
                if ($duel["isparty"] == false) {
                    $meta = $duel["meta"] ?? 0;
                    $i += 1;
                    if ($i < 7) {
                        $slot = $i + 9;
                    } else {
                        $slot = $i + 18;
                    }
                    $gamename = $gamemode = $config["games"]["duel"][$duel["game"]]["name"];
                    $icon = $gamemode = $config["games"]["duel"][$duel["game"]]["icon"];
                    invformapi::setItem($player, $slot, $icon, 1);
                    invformapi::setName($player, $slot, $gamename);
                    invformapi::setMeta($player, $slot, $meta);
                    $redName = $duel["red"][0]->getName();
                    $blueName = $duel["blue"][0]->getName();
                    invformapi::setLore($player, $slot, "\n" . yaml::getcolor() . $redName . " §fvs §9" . $blueName);
                }
            }
            $formid = 503;
        }
        if ($id == 4) { //party duel spectate
            invformapi::setEnchant($player, 4, 17, 1);
            $i = 0;
            foreach (PlayerDuel::$dueldatas as $id => $duel) {
                if ($duel["isparty"] == true && $duel["split"] == false) {
                    if (Party::$parties[PlayerDataManager::getData($duel["players"][0], "party_name")]["partygame"]) {
                        $type = "party";
                    } else {
                        $type = "duel";
                    }
                    $meta = $duel["meta"] ?? 0;
                    $i += 1;
                    if ($i < 7) {
                        $slot = $i + 9;
                    } else {
                        $slot = $i + 18;
                    }
                    $gamename = $config["games"][$type][$duel["game"]]["name"];
                    $icon = $config["games"][$type][$duel["game"]]["icon"];
                    invformapi::setItem($player, $slot, $icon, 1);
                    invformapi::setName($player, $slot, $gamename);
                    invformapi::setMeta($player, $slot, $meta);
                    $redName = Party::$parties[PlayerDataManager::getdata($duel["red"][0], "party_name")]["name"];
                    $blueName = Party::$parties[PlayerDataManager::getdata($duel["blue"][0], "party_name")]["name"];
                    invformapi::setLore($player, $slot, "\n" . yaml::getcolor() . $redName . " §fvs §9" . $blueName);
                }
            }
            invformapi::setItem($player, 26, 454, 1);
            $splittext = PracticePlayer::getLangText($player, "form.spectate.splitduel");
            invformapi::setName($player, 26, $splittext);
            $formid = 504;
        }

        if ($id == 7) { //split party duel spectate
            invformapi::setEnchant($player, 4, 17, 1);
            $i = 0;
            foreach (PlayerDuel::$dueldatas as $id => $duel) {
                if ($duel["isparty"] == true && $duel["split"] == true) {
                    $meta = $duel["meta"] ?? 0;
                    $i += 1;
                    if ($i < 7) {
                        $slot = $i + 9;
                    } else {
                        $slot = $i + 18;
                    }
                    $gamename = $config["games"]["duel"][$duel["game"]]["name"];
                    $icon = $config["games"]["duel"][$duel["game"]]["icon"];
                    invformapi::setItem($player, $slot, $icon, 1);
                    invformapi::setName($player, $slot, $gamename);
                    invformapi::setMeta($player, $slot, $meta);
                    $partyname = Party::$parties[PlayerDataManager::getdata($duel["red"][0], "party_name")]["name"];
                    invformapi::setLore($player, $slot, "\n" . $partyname);
                }
            }
            invformapi::setItem($player, 26, 628, 1);
            $splittext = PracticePlayer::getLangText($player, "form.spectate.partyduel");
            invformapi::setName($player, 26, $splittext);
            $formid = 507;
        }
        if ($id == 5) { //practice spectate
            invformapi::setEnchant($player, 5, 17, 1);
            $i = 0;
            foreach (PracticeGame::$practicedatas as $id => $practice) {
                $meta = $practice["meta"] ?? 0;
                $i += 1;
                if ($i < 7) {
                    $slot = $i + 9;
                } else {
                    $slot = $i + 18;
                }
                $gamename = $config["games"]["practice"][$practice["game"]]["name"];
                $icon = $config["games"]["practice"][$practice["game"]]["icon"];
                invformapi::setItem($player, $slot, $icon, 1);
                invformapi::setName($player, $slot, $gamename);
                invformapi::setMeta($player, $slot, $meta);
                $playername = $practice["player"]->getName();
                invformapi::setLore($player, $slot, "\n" . $playername);
            }
            $formid = 505;
        }
        if ($id == 6) {
            invformapi::setEnchant($player, 6, 17, 1);
            $formid = 506;
        }

        if ($open) {
            invformapi::sendInvform($player, $formid);
        } else {
            invformapi::UpdateInvform($player, $formid);
        }
    }
    public static function cosmetics($player, $id, $open = false)
    {
        invformapi::resetInventory($player);
        for ($i = 0; $i < 27; $i++) {
            invformapi::setItem($player, $i, -657, 1);
            invformapi::setName($player, $i, "");
        }
        $FormName = PracticePlayer::getLangText($player, "form.cosmetics.name");
        invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
        if ($id == 1) {
            $createitem = PracticePlayer::getLangText($player, "form.party.create");
            invformapi::setItem($player, 0, 554, 1);
            invformapi::setName($player, 0, $createitem);
            $formid = 600;
        }

        if ($open) {
            invformapi::sendInvform($player, $formid);
        } else {
            invformapi::UpdateInvform($player, $formid);
        }
    }

    public static function settings($player, $id, $open = false)
    {
        $xuid = $player->getXuid();
        $config = PracticePlayer::$playerdata;
        $formid = 700;
        invformapi::resetInventory($player);
        for ($i = 0; $i < 27; $i++) {
            invformapi::setItem($player, $i, -657, 1);
            invformapi::setName($player, $i, "");
        }
        $FormName = PracticePlayer::getLangText($player, "form.settings.name");
        invformapi::setInventoryName($player, yaml::getcolor() . "" . $FormName);
        $ffaitem = PracticePlayer::getLangText($player, "form.settings.ffa");
        invformapi::setItem($player, 2, 423, 1);
        invformapi::setName($player, 2, $ffaitem);
        $uiitem = PracticePlayer::getLangText($player, "form.settings.general.ui");
        invformapi::setItem($player, 3, 413, 1);
        invformapi::setName($player, 3, $uiitem);
        $visalitem = PracticePlayer::getLangText($player, "form.settings.general.visal");
        invformapi::setItem($player, 4, 581, 1);
        invformapi::setName($player, 4, $visalitem);
        $cosmeticitem = PracticePlayer::getLangText($player, "form.settings.general.other");
        invformapi::setItem($player, 5, 604, 1);
        invformapi::setName($player, 5, $cosmeticitem);
        $kititem = PracticePlayer::getLangText($player, "form.settings.kit");
        invformapi::setItem($player, 6, 438, 1);
        invformapi::setName($player, 6, $kititem);
        if ($id == 1) {
            $formid = 701;
        }
        if ($id == 2) { //ffa settings
            invformapi::setEnchant($player, 2, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.ffa.spawn_protectiion");
            invformapi::setItem($player, 12, 684, 1);
            invformapi::setName($player, 12, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.ffa.arena_respawn");
            self::toggleitem($player, 13, $config[$xuid]["settings"]["arena_respawn"]);
            invformapi::setName($player, 13, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.ffa_hide_non_opporent");
            self::toggleitem($player, 14, $config[$xuid]["settings"]["hide non opporent"]);
            invformapi::setName($player, 14, $item);
            $formid = 702;
        }
        if ($id == 12) { //spawn protection settings
            invformapi::setEnchant($player, 2, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.ffa.spawn_protectiion.time");
            invformapi::setItem($player, 13, 684, 1);
            invformapi::setName($player, 13, $item . $config[$xuid]["settings"]["spawn_protection"]);
            invformapi::setItem($player, 11, -671, 1);
            invformapi::setName($player, 11, yaml::getcolor() . "-0.5");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-0.1");
            invformapi::setItem($player, 14, -655, 1);
            invformapi::setName($player, 14, "§a+0.1");
            invformapi::setItem($player, 15, -655, 1);
            invformapi::setName($player, 15, "§a+0.5");
            $formid = 712;
        }
        if ($id == 3 || $id >= 7 && $id <= 11) { //ui settings
            invformapi::setEnchant($player, 3, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard");
            invformapi::setItem($player, 11, 531, 1);
            invformapi::setName($player, 11, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar");
            invformapi::setItem($player, 12, 324, 1);
            invformapi::setName($player, 12, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat");
            invformapi::setItem($player, 13, 614, 1);
            invformapi::setName($player, 13, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.ui.title");
            invformapi::setItem($player, 14, 631, 1);
            invformapi::setName($player, 14, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo");
            invformapi::setItem($player, 15, 731, 1);
            invformapi::setName($player, 15, $item);
            $formid = 703;

            if ($id == 7) { //scoreboard
                invformapi::setEnchant($player, 11, 17, 1);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.show");
                self::toggleitem($player, 19, $config[$xuid]["settings"]["scoreboard"]["show"]);
                invformapi::setName($player, 19, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.status");
                self::toggleitem($player, 20, $config[$xuid]["settings"]["scoreboard"]["status"]);
                invformapi::setName($player, 20, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.info");
                self::toggleitem($player, 21, $config[$xuid]["settings"]["scoreboard"]["info"]);
                invformapi::setName($player, 21, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.opporent.playing");
                self::toggleitem($player, 22, $config[$xuid]["settings"]["scoreboard"]["playing"]);
                invformapi::setName($player, 22, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.ping");
                self::toggleitem($player, 23, $config[$xuid]["settings"]["scoreboard"]["ping"]);
                invformapi::setName($player, 23, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.drop");
                self::toggleitem($player, 24, $config[$xuid]["settings"]["scoreboard"]["drop"]);
                invformapi::setName($player, 24, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.scoreboard.opporent.combat");
                self::toggleitem($player, 25, $config[$xuid]["settings"]["scoreboard"]["combat"]);
                invformapi::setName($player, 25, $item);
                $formid = 707;
            }
            if ($id == 8) { //actionbar
                invformapi::setEnchant($player, 12, 17, 1);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar.alwayshow");
                self::toggleitem($player, 20, $config[$xuid]["settings"]["actionbar_setting"]["alwaysshow"]);
                invformapi::setName($player, 20, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar.ping");
                self::toggleitem($player, 21, $config[$xuid]["settings"]["actionbar_setting"]["ping"]);
                invformapi::setName($player, 21, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar.cps");
                self::toggleitem($player, 22, $config[$xuid]["settings"]["actionbar_setting"]["cpscount"]);
                invformapi::setName($player, 22, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar.combo");
                self::toggleitem($player, 23, $config[$xuid]["settings"]["actionbar_setting"]["combo"]);
                invformapi::setName($player, 23, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.actionbar.reach");
                self::toggleitem($player, 24, $config[$xuid]["settings"]["actionbar_setting"]["reach"]);
                invformapi::setName($player, 24, $item);
                $formid = 708;
            }

            if ($id == 9) { //chat_setting
                invformapi::setEnchant($player, 13, 17, 1);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.playerchat");
                self::toggleitem($player, 19, $config[$xuid]["settings"]["chat_setting"]["chat"]);
                invformapi::setName($player, 19, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.jqmessage");
                self::toggleitem($player, 20, $config[$xuid]["settings"]["chat_setting"]["jqmessage"]);
                invformapi::setName($player, 20, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.announce");
                self::toggleitem($player, 21, $config[$xuid]["settings"]["chat_setting"]["announge"]);
                invformapi::setName($player, 21, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.killlog");
                self::toggleitem($player, 22, $config[$xuid]["settings"]["chat_setting"]["killlog"]);
                invformapi::setName($player, 22, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.combat");
                self::toggleitem($player, 23, $config[$xuid]["settings"]["chat_setting"]["combatlog"]);
                invformapi::setName($player, 23, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.notification");
                self::toggleitem($player, 24, $config[$xuid]["settings"]["chat_setting"]["notification"]);
                invformapi::setName($player, 24, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.chat.duel");
                self::toggleitem($player, 25, $config[$xuid]["settings"]["chat_setting"]["duel_message"]);
                invformapi::setName($player, 25, $item);
                $formid = 709;
            }
            if ($id == 10) { //title
                invformapi::setEnchant($player, 14, 17, 1);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.title.countdown");
                self::toggleitem($player, 21, $config[$xuid]["settings"]["title"]["countdown"]);
                invformapi::setName($player, 21, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.title.result");
                self::toggleitem($player, 22, $config[$xuid]["settings"]["title"]["result"]);
                invformapi::setName($player, 22, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.title.other");
                self::toggleitem($player, 23, $config[$xuid]["settings"]["title"]["other"]);
                invformapi::setName($player, 23, $item);
                $formid = 710;
            }
            if ($id == 11) { //nameinfo
                invformapi::setEnchant($player, 15, 17, 1);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.only");
                self::toggleitem($player, 19, $config[$xuid]["settings"]["scoreboard"]["show"]);
                invformapi::setName($player, 19, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.nocombat.device");
                self::toggleitem($player, 20, $config[$xuid]["settings"]["scoreboard"]["nocombat_device"]);
                invformapi::setName($player, 20, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.nocombat.ping");
                self::toggleitem($player, 21, $config[$xuid]["settings"]["scoreboard"]["nocombat_ping"]);
                invformapi::setName($player, 21, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.nocombat.drop");
                self::toggleitem($player, 22, $config[$xuid]["settings"]["scoreboard"]["nocombat_drop"]);
                invformapi::setName($player, 22, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.incombat.cps");
                self::toggleitem($player, 23, $config[$xuid]["settings"]["scoreboard"]["incombat_cps"]);
                invformapi::setName($player, 23, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.incombat.ping");
                self::toggleitem($player, 24, $config[$xuid]["settings"]["scoreboard"]["incombat_ping"]);
                invformapi::setName($player, 24, $item);
                $item = PracticePlayer::getLangText($player, "form.settings.general.ui.nameinfo.incombat.drop");
                self::toggleitem($player, 25, $config[$xuid]["settings"]["scoreboard"]["incombat_drop"]);
                invformapi::setName($player, 25, $item);
                $formid = 711;
            }
        }
        if ($id == 4) { //visal settings
            invformapi::setEnchant($player, 4, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.critical");
            invformapi::setItem($player, 11, -658, 1);
            invformapi::setName($player, 11, $item);
            $hide = PracticePlayer::getLangText($player, "form.settings.general.visal.critical.hide");
            $default = PracticePlayer::getLangText($player, "form.settings.general.visal.critical.default");
            $vanilla = PracticePlayer::getLangText($player, "form.settings.general.visal.critical.vanilla");
            $always = PracticePlayer::getLangText($player, "form.settings.general.visal.critical.always");
            $hit = PracticePlayer::getLangText($player, "form.settings.general.visal.critical.hit");
            $list = [$hide, $default, $vanilla, $always, $hit];
            $text = "\n";
            foreach ($list as $item) {
                if ($list[$config[$xuid]["settings"]["crit_particle"]] == $item) {
                    $text .= yaml::getcolor() . "" . $item . "\n§r";
                } else {
                    $text .= $item . "\n";
                }
            }
            invformapi::setLore($player, 11, $text . "\n");
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.night_vision");
            self::toggleitem($player, 12, $config[$xuid]["settings"]["fullbright"]);
            invformapi::setName($player, 12, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.ligiting_kill");
            self::toggleitem($player, 13, $config[$xuid]["settings"]["lighting_kill"]);
            invformapi::setName($player, 13, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.smoothpearl");
            self::toggleitem($player, 14, $config[$xuid]["settings"]["smooth_pearl"]);
            invformapi::setName($player, 14, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.time");
            invformapi::setItem($player, 15, 419, 1);
            invformapi::setName($player, 15, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.rain");
            self::toggleitem($player, 22, $config[$xuid]["settings"]["rain"]);
            invformapi::setName($player, 22, $item);
            $formid = 704;
        }
        if ($id == 5) { //other settings
            invformapi::setEnchant($player, 5, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.duel_request");
            self::toggleitem($player, 11, $config[$xuid]["settings"]["duel_request"]);
            invformapi::setName($player, 11, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.party_request");
            self::toggleitem($player, 12, $config[$xuid]["settings"]["party_request"]);
            invformapi::setName($player, 12, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.show_killstreak");
            self::toggleitem($player, 13, $config[$xuid]["settings"]["show_killstreak"]);
            invformapi::setName($player, 13, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.auto_sprint");
            self::toggleitem($player, 14, $config[$xuid]["settings"]["toggle_sprint"]);
            invformapi::setName($player, 14, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.duel_result");
            self::toggleitem($player, 15, $config[$xuid]["settings"]["duel_result"]);
            invformapi::setName($player, 15, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.lobby_hideplayer");
            self::toggleitem($player, 21, $config[$xuid]["settings"]["lobby_hide"]);
            invformapi::setName($player, 21, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.nick");
            $soon = PracticePlayer::getLangText($player, "general.soon");
            invformapi::setItem($player, 22, -649, 1);
            invformapi::setName($player, 22, $item);
            invformapi::setLore($player, 22, $soon);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.anticpskick");
            self::toggleitem($player, 23, $config[$xuid]["settings"]["cpslimit"]);
            invformapi::setName($player, 23, $item);
            $item = PracticePlayer::getLangText($player, "form.settings.general.other.vanillahook");
            self::toggleitem($player, 24, $config[$xuid]["settings"]["vanilla_hook"]);
            invformapi::setName($player, 24, $item);
            $formid = 705;
        }
        if ($id == 6) { //kit settings
            invformapi::setEnchant($player, 6, 17, 1);
            $formid = 706;
        }
        if ($id == 13) { //time settings
            invformapi::setEnchant($player, 5, 17, 1);
            $item = PracticePlayer::getLangText($player, "form.settings.general.visal.time.time");
            invformapi::setItem($player, 13, 419, 1);
            invformapi::setName($player, 13, $item . $config[$xuid]["settings"]["time"] / 10);
            invformapi::setItem($player, 11, -671, 1);
            invformapi::setName($player, 11, yaml::getcolor() . "-10");
            invformapi::setItem($player, 12, -671, 1);
            invformapi::setName($player, 12, yaml::getcolor() . "-1");
            invformapi::setItem($player, 14, -655, 1);
            invformapi::setName($player, 14, "§a+1");
            invformapi::setItem($player, 15, -655, 1);
            invformapi::setName($player, 15, "§a+10");
            $formid = 713;
        }
        if ($open) {
            invformapi::sendInvform($player, $formid);
        } else {
            invformapi::UpdateInvform($player, $formid);
        }
    }

    private static function toggleitem($player, $slot, $value): void
    {
        if ($value == "true") {
            $text = "\n§a" . PracticePlayer::getLangText($player, "general.enable");
            invformapi::setItem($player, $slot, -655, 1);
        } else {
            $text = "\n§c" . PracticePlayer::getLangText($player, "general.disable");
            invformapi::setItem($player, $slot, -671, 1);
        }
        invformapi::setLore($player, $slot, $text);
    }

    public static function arrayToYaml(array $data, int $depth = 0)
    {
        $yaml = "";
        $indent = str_repeat("  ", $depth);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_keys($value) === range(0, count($value) - 1)) {
                    foreach ($value as $item) {
                        $yaml .= "{$indent}- " . (is_array($item) ? "\n" . arrayToYaml($item, $depth + 1) : $item) . "\n";
                    }
                } else {
                    $yaml .= "{$indent}{$key}:\n" . self::arrayToYaml($value, $depth + 1);
                }
            } else {
                $yaml .= "{$indent}{$key}: " . yaml::getcolor() . "{$value}§r\n";
            }
        }
        return $yaml;
    }
}
//※下の奴はフォームIDの割り当て(例:2の場合200~299)
//1 = ffa
//2 = duel
//3 = practice
//4 = party
//5 = spectate
//6 = cosmetics
//7 = settings
//8 = region
//9 = duel_history
//10 = chainge_log
//11 = report
//12 = event
//13 = 
//2000 = rule form