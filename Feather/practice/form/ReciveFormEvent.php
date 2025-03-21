<?php

namespace practice\form;

use pocketmine\Server;
use pocketmine\world\Position;
use practice\form\forms;
use practice\form\invformapi;
use practice\utils\yaml;
use practice\arena\ArenaManaer;
use practice\player\PlayerQueue;
use practice\player\PlayerKits;
use practice\practice;
use practice\utils\PlayerDataManager;
use practice\duel\PlayerDuel;
use practice\practice\practice as practicegame;
use practice\duel\Request;
use practice\party\Party;
use practice\arena\WorldManaer;
use practice\player\PracticePlayer;
use practice\handler\ChatHandler;

class ReciveFormEvent
{
    public static function getformdata($SlotId, $player, $packet = null)
    {
        $xuid = $player->getXuid();
        $server = Server::getInstance();
        $formid = PlayerDataManager::getData($player, "menuid");
        $partyname = PlayerDataManager::getdata($player, "party_name");
        if (isset($packet)) {
            $formdata = $SlotId->formData;
        }
        $config = PracticePlayer::$playerdata;
        if ($formid == 1) {
            $config = yaml::getconfig();
            $id = [];
            foreach ($config["games"]["ffa"] as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (isset($id[$SlotId])) {
                PlayerQueue::joinQueue($player, "ffa_" . $id[$SlotId]);
            }
        }
        if ($formid == 200) {
            if ($SlotId == 10) {
                Forms::duel($player, 2);
            }
            if ($SlotId == 11) {
                Forms::duel($player, 3);
            }
            if ($SlotId == 14) {
                invformapi::closeInvform($player);
                practice::CreateTask("practice\utils\\task\\requestform", [$player], 5);
            }
            if ($SlotId == 15) {
                Forms::duel($player, 6);
            }
        }
        if ($formid == 202) {
            if ($SlotId == 4) {
                Forms::duel($player, 1);
                return;
            }
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $id = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (in_array($SlotId, array_keys($id))) {
                $first_game_key = $id[$SlotId];
                PlayerKits::getkit($player, "lobby", "queue");
                $bool = PlayerQueue::joinQueue($player, "duel_unranked_" . $first_game_key);
                PlayerDataManager::setdata($player, "queuegametype", "duelqueue");
                PlayerDataManager::setdata($player, "queuegameinfo", "unranked_" . $first_game_key);
                if (!$bool) {
                    $queue = PlayerDataManager::getData($player, "queue");
                    if ($queue == "null") {
                        PlayerKits::getkit($player, "lobby", "lobby");
                    }
                } else {
                    $text = PracticePlayer::getLangText($player, "duel.inqueue");
                    ChatHandler::sendMessage($player, $text . $config["games"]["duel"][$first_game_key]["name"], false,  "notification");
                }
            }
            invformapi::closeInvform($player);
        }
        if ($formid == 203) {
            if ($SlotId == 4) {
                Forms::duel($player, 1);
                return;
            }
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $id = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (in_array($SlotId, array_keys($id))) {
                $first_game_key = $id[$SlotId];
                PlayerKits::getkit($player, "lobby", "queue");
                $bool = PlayerQueue::joinQueue($player, "duel_ranked_" . $first_game_key);
                PlayerDataManager::setdata($player, "queuegametype", "duelqueue");
                PlayerDataManager::setdata($player, "queuegameinfo", "ranked_" . $first_game_key);
                if (!$bool) {
                    $queue = PlayerDataManager::getData($player, "queue");
                    if ($queue == "null") {
                        PlayerKits::getkit($player, "lobby", "lobby");
                    }
                }
            }
            invformapi::closeInvform($player);
        }

        if ($formid == 205) {
            if ($formdata == null) {
                return;
            }
            $formdata = json_decode($formdata, true);
            $formdata[0] = $formdata[0] ?? "";
            if ($formdata[0] == "") {
                return;
            }
            $specificPlayer = $server->getPlayerByPrefix(Forms::$playerlist[$xuid][$formdata[0]]);
            if ($specificPlayer !== null && $config[$specificPlayer->getXuid()]["settings"]["duel_request"] == true) {
                Request::createRequest($player, $specificPlayer, $formdata[1]);
            }
        }

        if ($formid == 206) {
            $requestdata = Forms::$requestdata[$xuid];
            $count = -1;
            $id = [];
            foreach ($requestdata as $name => $value) {
                $count += 1;
                $id[] = $name;
            }
            if (isset($id[$SlotId])) {
                $name = $id[$SlotId];
                $specificPlayer = $server->getPlayerByPrefix($name);
                if ($specificPlayer !== null) {
                    if (Playerdatamanager::getData($specificPlayer, "ingame") == "lobby") {
                        Request::acceptRequest($player, $specificPlayer);
                    }
                }
            }
        }

        if ($formid == 301) {
            $config = yaml::getconfig();
            $gamemode = $config["games"]["practice"];
            $list = [];
            foreach ($gamemode as $game => $value) {
                $list[$value["slot"]] = $game;
            }
            if (isset($list[$SlotId])) {
                PlayerQueue::joinQueue($player, "practice_" . $list[$SlotId]);
            }
        }

        if ($formid == 303) {
            $ingame = PlayerDataManager::getData($player, "ingame");
            $practiceid = PlayerDataManager::getdata($player, "practiceid");
            $savedata = PracticePlayer::$playerdata[$player->getXuid()]["practice"][$ingame];
            foreach($savedata as $slot => $data) {
                invformapi::setItem($player, $slot, -657, 1);
                invformapi::setName($player, $slot, "Config: $slot\n\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            };
            PracticePlayer::$playerdata[$player->getXuid()]["practice"][$ingame]["$SlotId"] = json_encode(PracticeGame::$practicedatas[$practiceid]["settings"]);
            Forms::practice($player, 3);
        }

        if ($formid == 304) {
            $ingame = PlayerDataManager::getData($player, "ingame");
            $practiceid = PlayerDataManager::getdata($player, "practiceid");

            if ($SlotId == 0) {
                PracticeGame::UpdateSetting($practiceid);
                Forms::practice($player, 4);
            }
            if ($SlotId == 9) {
                PracticeGame::UpdateSetting($practiceid);
                Forms::practice($player, 3);
            }
            if ($SlotId == 18) {
                PracticeGame::$practicedatas[$practiceid]["spectate"] = !PracticeGame::$practicedatas[$practiceid]["spectate"];
                Forms::practice($player, 4);
            }
            if ($ingame == "clutch") {
                if ($SlotId == 1) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["horizontalkb"] -= 0.05;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["horizontalkb"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["horizontalkb"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 19) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["horizontalkb"] += 0.05;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 2) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["verticalkb"] -= 0.05;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["verticalkb"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["verticalkb"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 20) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["verticalkb"] += 0.05;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 3) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitcount"] -= 1;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["hitcount"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["hitcount"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 21) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitcount"] += 1;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 4) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitdelay"] -= 1;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["hitdelay"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["hitdelay"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 22) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitdelay"] += 1;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 5) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitcooltime"] -= 1;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["hitcooltime"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["hitcooltime"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 23) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitcooltime"] += 1;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 6) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["attackrandomise"] -= 1;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["attackrandomise"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["attackrandomise"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 24) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["attackrandomise"] += 1;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 7) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitrandomise"] -= 1;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["hitrandomise"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["hitrandomise"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 25) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["hitrandomise"] += 1;
                    Forms::practice($player, 4);
                }
                if ($SlotId == 8) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["directionrandomise"] -= 10;
                    if (PracticeGame::$practicedatas[$practiceid]["settings"]["directionrandomise"] < 0) {
                        PracticeGame::$practicedatas[$practiceid]["settings"]["directionrandomise"] = 0;
                    }
                    Forms::practice($player, 4);
                }
                if ($SlotId == 26) {
                    PracticeGame::$practicedatas[$practiceid]["settings"]["directionrandomise"] += 10;
                    Forms::practice($player, 4);
                }
            }
        }

        if ($formid == 400) {
            if ($SlotId == 0) {
                Forms::party($player, 2);
            }
            if ($SlotId == 1) {
                Forms::party($player, 11);
            }
            if ($SlotId == 2) {
                Forms::party($player, 12);
            }
            if ($SlotId == 4) {
                Forms::party($player, 13);
            }
        }
        if ($formid == 401) {
            if ($SlotId == 1) {
                Party::createParty($player, $player->getName() . " Party", true);
                invformapi::closeInvform($player);
            }
            if ($SlotId == 3) {
                Party::createParty($player, $player->getName() . " Party", false);
                invformapi::closeInvform($player);
            }
        }
        if ($formid == 406) {
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $id = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (in_array($SlotId, array_keys($id))) {
                $first_game_key = $id[$SlotId];
                PlayerKits::getkit($player, "lobby", "queue");
                $bool = PlayerQueue::joinQueue($player, "party_duel_" . $first_game_key);
                if (!$bool) {
                    $queue = PlayerDataManager::getData($player, "queue");
                    if ($queue == "null") {
                        Party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = false;
                        PlayerKits::getkit($player, "lobby", "party");
                    }
                } else {
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["partygame"] = false;
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = true;
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["game"] = $first_game_key;
                }
                invformapi::closeInvform($player);
                return;
            }
            if ($SlotId == 4) {
                Forms::party($player, 30);
            }
            if ($SlotId == 8) {
                Forms::party($player, 31);
            }
        }
        if ($formid == 407) {

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $id = [];
            $vote = [];
            $slot = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
                $slot[$game] = $value["slot"];
                $vote[$game] = Party::$parties[$partyname]["vote"][$game];
            }
            if ($SlotId == 5) {
                $SlotId = array_rand($id);
            }
            if ($SlotId == 3) {
                $maxVotes = max($vote);
                $topGames = array_filter($vote, function ($votes) use ($maxVotes) {
                    return $votes == $maxVotes;
                });
                $SlotId = $slot[array_rand($topGames)];
            }


            if (in_array($SlotId, array_keys($id))) {
                $first_game_key = $id[$SlotId];
                $playercount = count(Party::$parties[$partyname]["players"]);
                if ($playercount !== 1) {
                    $bool = PlayerQueue::joinQueue($player, "split_duel_" . $first_game_key);
                    Party::resetvote($partyname);
                    if (!$bool) {
                        $queue = PlayerDataManager::getData($player, "queue");
                        if ($queue == "null") {
                            PlayerKits::getkit($player, "lobby", "party");
                        }
                    }
                }
            }
            invformapi::closeInvform($player);
        }
        if ($formid == 408) {
            $config = yaml::getconfig();
            $id = [];
            foreach ($config["games"]["ffa"] as $game => $value) {
                $id[$value["slot"]] = $game;
            }

            if (isset($id[$SlotId])) {
                if (Party::$parties[$partyname]["partyffa"]) {
                    ArenaManaer::tpArena($player, "ffa", $id[$SlotId], null, true);
                    PlayerDataManager::setdata($player, "party_ffa", "true");
                } else {
                    invformapi::closeInvform($player);
                    return;
                }
            }
        }
        if ($formid == 409) {
            $config = yaml::getconfig();
            $id = [];
            foreach ($config["games"]["duel"] as $game => $value) {
                $id[$value["slot"]] = $game;
            }

            if (isset($id[$SlotId])) {
                Party::vote($partyname, $player, $id[$SlotId]);
            }
            if ($SlotId == 4) {
                $ids = array_values($id);
                $select = $ids[rand(0, count($id) - 1)];
                Party::vote($partyname, $player, $select);
            }
            Forms::party($player, 9);
        }
        if ($formid == 410) {
            if ($SlotId == 11) {
                Forms::party($player, 17);
            }
            if ($SlotId == 12) {
                PlayerDataManager::setdata($player, "p_menuid", "promote");
                Forms::party($player, 15);
            }
            if ($SlotId == 13) {
                PlayerDataManager::setdata($player, "p_menuid", "kick");
                Forms::party($player, 15);
            }
            if ($SlotId == 14) {
                PlayerDataManager::setdata($player, "p_menuid", "ban");
                Forms::party($player, 15);
            }
            if ($SlotId == 15) {
                PlayerDataManager::setdata($player, "confirm_form", "disband");
                Forms::party($player, 16);
            }
            if ($SlotId == 20) {
                Party::$parties[$partyname]["open"] = !Party::$parties[$partyname]["open"];
                Forms::party($player, 10);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                Party::$parties[$partyname]["partyffa"] = !Party::$parties[$partyname]["partyffa"];
                Forms::party($player, 10);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                Party::$parties[$partyname]["member_invite"] = !Party::$parties[$partyname]["member_invite"];
                Forms::party($player, 10);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                Party::$parties[$partyname]["member_select"] = !Party::$parties[$partyname]["member_select"];
                Forms::party($player, 10);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                Forms::party($player, 18);
                PracticePlayer::playSound($player, "random.click");
            }
        }
        if ($formid == 411) {
            $datas = Forms::$joinlist[$xuid];
            if (in_array($SlotId, array_keys($datas))) {
                if (isset(party::$parties[$datas[$SlotId]])) {
                    party::addPlayer($datas[$SlotId], $player);
                    invformapi::closeInvform($player);
                }
            }
        }

        if ($formid == 412) {
            if (isset(Forms::$joinlist[$xuid][$SlotId])) {
                $partyname = Forms::$joinlist[$xuid][$SlotId];
                Party::addPlayer($partyname, $player);
                invformapi::closeInvform($player);
            }
        }

        if ($formid == 415) {
            if (isset(Forms::$memberlist[$xuid][$SlotId])) {
                $select = Forms::$memberlist[$xuid][$SlotId];
                $menuid = PlayerDataManager::getdata($player, "p_menuid");

                $name = $select->getName();
                $isowner = PlayerDataManager::getdata($select, "party_owner");
                if ($isowner == "false") {
                    if ($menuid == "ban") {
                        PlayerDataManager::setdata($player, "confirm_form_player", $select);
                        PlayerDataManager::setdata($player, "confirm_form", "ban");
                        Forms::party($player, 16);
                    }
                    if ($menuid == "kick") {
                        Party::removePlayer($partyname, $select);
                        invformapi::closeInvform($player);
                    }
                    if ($menuid == "promote") {
                        PlayerDataManager::setdata($player, "confirm_form_player", $select);
                        PlayerDataManager::setdata($player, "confirm_form", "promote");
                        Forms::party($player, 16);
                    }
                }
            }
        }
        if ($formid == 416) {
            $selectid = PlayerDataManager::getdata($player, "confirm_form");
            if ($SlotId == 12) {
                Forms::party($player, 10);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                if ($selectid == "disband") {
                    Party::destroyParty($partyname);
                }
                if ($selectid == "promote") {
                    $select = PlayerDataManager::getdata($player, "confirm_form_player");
                    if ($select->isOnline()) {
                        Party::PromotePlayer($partyname, $select);
                    }
                }
                if ($selectid == "ban") {
                    $select = PlayerDataManager::getdata($player, "confirm_form_player");
                    if ($select->isOnline()) {
                        Party::blacklistPlayer($partyname, $select);
                    }
                }
                PracticePlayer::playSound($player, "random.click");
                invformapi::closeInvform($player);
            }
        }
        if ($formid == 417) {
            $requestdata = Forms::$requestdata[$xuid];
            if ($requestdata !== null && $formdata !== null) {
                if ($requestdata[0] !== null) {
                    var_dump($formdata);
                    var_dump($requestdata);
                    $name = $requestdata[json_decode($formdata, true)[0]];
                    $specificPlayer = $server->getPlayerByPrefix($name);
                    var_dump($specificPlayer);

                    if ($specificPlayer !== null) {
                        Party::invitePlayer($partyname, $specificPlayer->getName());
                    }
                }
            }
        }

        if ($formid == 418) {
            if ($SlotId == 4) {
                Forms::party($player, 10);
                return;
            }
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $id = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (in_array($SlotId, array_keys($id))) {
                $gameid = $id[$SlotId];
                PlayerDataManager::setData($player, "select_gameid", $gameid);
                forms::party($player, 19);
            }
        }

        if ($formid == 419) {
            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            if ($SlotId == 0) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 18);
                return;
            }
            if ($SlotId == 11) {
                PracticePlayer::playSound($player, "random.click");
                forms::party($player, 24);
            }
            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                forms::party($player, 25);
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                forms::party($player, 23);
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                forms::party($player, 21);
            }
            if ($SlotId == 15) {
                PracticePlayer::playSound($player, "random.click");
                $gameid = PlayerDataManager::getData($player, "select_gameid");
                $dueltype = $gamemode[$gameid]["dueltype"];
                if ($dueltype == "normal") {
                }
                if ($dueltype == "score") {
                    forms::party($player, 20);
                }
                if ($dueltype == "point") {
                    forms::party($player, 20);
                }
                if ($dueltype == "bed") {
                    $bed = Party::$parties[$partyname][$gameid]["nobed"] ?? false;
                    Party::$parties[$partyname][$gameid]["nobed"] = !$bed;
                    forms::party($player, 19);
                }
                if ($dueltype == "preem") {
                    forms::party($player, 20);
                }
            }
            if ($SlotId == 16) {
                PracticePlayer::playSound($player, "random.click");
                $gameid = PlayerDataManager::getData($player, "select_gameid");
                $dueltype = $gamemode[$gameid]["dueltype"];
                if ($dueltype == "bed") {
                    forms::party($player, 26);
                }
            }
        }

        if ($formid == 420) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 10) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["score"] = $config["games"]["duel"][$gameid]["point"];
                Forms::party($player, 20);
                return;
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 19);
                return;
            }

            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["score"] -= 1;
                if (Party::$parties[$partyname][$gameid]["score"] < 1) {
                    Party::$parties[$partyname][$gameid]["score"] = 1;
                }
                Forms::party($player, 20);
                return;
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["score"] += 1;
                Forms::party($player, 20);
                return;
            }

            if ($dueltype == "hit") {
                if ($SlotId == 11) {
                    PracticePlayer::playSound($player, "random.click");
                    Party::$parties[$partyname][$gameid]["score"] -= 10;
                    if (Party::$parties[$partyname][$gameid]["score"] < 1) {
                        Party::$parties[$partyname][$gameid]["score"] = 1;
                    }
                    Forms::party($player, 20);
                    return;
                }

                if ($SlotId == 15) {
                    PracticePlayer::playSound($player, "random.click");
                    Party::$parties[$partyname][$gameid]["score"] += 10;
                    Forms::party($player, 20);
                    return;
                }
            }
        }

        if ($formid == 421) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 0) {
                Forms::party($player, 19);
                return;
            }
            $cooltime = $config["itemcooltime"][$gamemode[$gameid]["itemcooltime"]];
            $slots = [];
            foreach ($cooltime as $itemname => $value) {
                if (stripos($itemname, "slot") == false && $itemname !== "priority_item") {
                    $slots[$config["itemcooltime"][$gamemode[$gameid]["itemcooltime"]][$itemname . "_slot"]] = $itemname;
                }
            }
            if (isset($slots[$SlotId])) {
                PlayerDataManager::setData($player, "select_itemid", $slots[$SlotId]);
                Forms::party($player, 22);
            }
        }

        if ($formid == 422) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 10) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["cooltime"][$itemid] = $config["itemcooltime"][$config["games"]["duel"][$gameid]["itemcooltime"]][$itemid];
                Forms::party($player, 22);
                return;
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 21);
                return;
            }

            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["cooltime"][$itemid] -= 1;
                if (Party::$parties[$partyname][$gameid]["cooltime"][$itemid] < 0) {
                    Party::$parties[$partyname][$gameid]["cooltime"][$itemid] = 0;
                }
                Forms::party($player, 22);
                return;
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["cooltime"][$itemid] += 1;
                Forms::party($player, 22);
                return;
            }
        }

        if ($formid == 423) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 10) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["maxhp"] = 20;
                Forms::party($player, 23);
                return;
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 19);
                return;
            }

            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["maxhp"] -= 1;
                if (Party::$parties[$partyname][$gameid]["maxhp"] < 1) {
                    Party::$parties[$partyname][$gameid]["maxhp"] = 1;
                }
                Forms::party($player, 23);
                return;
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["maxhp"] += 1;
                Forms::party($player, 23);
                return;
            }
        }

        if ($formid == 424) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 0) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 19);
                return;
            }
            if ($SlotId == 4) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["horizontal"];
                if ($value == "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["horizontal"] = 0.4;
                } else {
                    Party::$parties[$partyname][$gameid]["knockback"]["horizontal"] = "default";
                }
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 24);
                return;
            }
            if ($SlotId == 13) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["vertical"];
                if ($value == "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["vertical"] = 0.4;
                } else {
                    Party::$parties[$partyname][$gameid]["knockback"]["vertical"] = "default";
                }
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 24);
                return;
            }
            if ($SlotId == 22) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["maxkb"];
                if ($value == "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["maxkb"] = 0.4;
                } else {
                    Party::$parties[$partyname][$gameid]["knockback"]["maxkb"] = "default";
                }
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 24);
                return;
            }
            $horizontal = [2 => -0.1, 3 => -0.01, 5 => 0.01, 6 => 0.1];
            if (isset($horizontal[$SlotId])) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["horizontal"];
                if ($value !== "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["horizontal"] += $horizontal[$SlotId];
                    PracticePlayer::playSound($player, "random.click");
                    Forms::party($player, 24);
                }
                return;
            }
            $vertical = [11 => -0.1, 12 => -0.01, 14 => 0.01, 15 => 0.1];
            if (isset($vertical[$SlotId])) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["vertical"];
                if ($value !== "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["vertical"] += $vertical[$SlotId];
                    PracticePlayer::playSound($player, "random.click");
                    Forms::party($player, 24);
                }
                return;
            }
            $maxkb = [20 => -0.1, 21 => -0.01, 23 => 0.01, 24 => 0.1];
            if (isset($maxkb[$SlotId])) {
                $value = Party::$parties[$partyname][$gameid]["knockback"]["maxkb"];
                if ($value !== "default") {
                    Party::$parties[$partyname][$gameid]["knockback"]["maxkb"] += $maxkb[$SlotId];
                    PracticePlayer::playSound($player, "random.click");
                    Forms::party($player, 24);
                }
                return;
            }
        }

        if ($formid == 425) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 10) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["hitdelay"] = $config["games"]["duel"][$gameid]["hitdelay"];
                Forms::party($player, 25);
                return;
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 19);
                return;
            }

            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["hitdelay"] -= 1;
                if (Party::$parties[$partyname][$gameid]["hitdelay"] < 0) {
                    Party::$parties[$partyname][$gameid]["hitdelay"] = 0;
                }
                Forms::party($player, 25);
                return;
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["hitdelay"] += 1;
                Forms::party($player, 25);
                return;
            }
        }

        if ($formid == 426) {
            $gameid = PlayerDataManager::getData($player, "select_gameid");
            $itemid = PlayerDataManager::getData($player, "select_itemid");

            $config = yaml::getconfig();
            $gamemode = $config["games"]["duel"];
            $dueltype = $gamemode[$gameid]["dueltype"];
            if ($SlotId == 10) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["respawn"] = $config["games"]["duel"][$gameid]["respawn"];
                Forms::party($player, 26);
                return;
            }
            if ($SlotId == 13) {
                PracticePlayer::playSound($player, "random.click");
                Forms::party($player, 19);
                return;
            }

            if ($SlotId == 12) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["respawn"] -= 1;
                if (Party::$parties[$partyname][$gameid]["respawn"] < 0) {
                    Party::$parties[$partyname][$gameid]["respawn"] = 0;
                }
                Forms::party($player, 26);
                return;
            }
            if ($SlotId == 14) {
                PracticePlayer::playSound($player, "random.click");
                Party::$parties[$partyname][$gameid]["respawn"] += 1;
                Forms::party($player, 26);
                return;
            }
        }

        if ($formid == 427) {
            if ($SlotId == 3) {
                Forms::party($player, 28);
                return;
            }
            if ($SlotId == 4) {
                Forms::party($player, 13);
                return;
            }
            if ($SlotId == 15) {
                Forms::party($player, 29);
                return;
            }
        }

        if ($formid == 430) {
            $config = yaml::getconfig();
            $gamemode = $config["games"]["party"];
            $id = [];
            foreach ($gamemode as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (in_array($SlotId, array_keys($id))) {
                $first_game_key = $id[$SlotId];
                PlayerKits::getkit($player, "lobby", "queue");
                $bool = PlayerQueue::joinQueue($player, "party_party_" . $first_game_key);
                if (!$bool) {
                    $queue = PlayerDataManager::getData($player, "queue");
                    if ($queue == "null") {
                        Party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = false;
                        PlayerKits::getkit($player, "lobby", "party");
                    }
                } else {
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["partygame"] = true;
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["inqueue"] = true;
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["game"] = $first_game_key;
                }
                invformapi::closeInvform($player);
                return;
            }
            if ($SlotId == 4) {
                Forms::party($player, 6);
            }
            if ($SlotId == 8) {
                Forms::party($player, 31);
            }
        }

        if ($formid == 431) {
            $count = -1;
            $list = [];
            $party = [];
            $id = [];
            foreach (forms::$requestdata[$partyname] as $name => $value) {
                $list[] = $value["game"];
                $party[] = $value["party"];
                $id[] = $value["id"];
            }
            if (isset($list[$SlotId])) {
                Request::partyacceptRequest(PlayerDataManager::getData($player, "party_name"), $id[$SlotId]);
                if ($party[$SlotId]) {
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["partygame"] = true;
                } else {
                    Party::$parties[PlayerDataManager::getData($player, "party_name")]["partygame"] = false;
                }
            }
            $formid = 431;
            if ($SlotId == 7) {
                Forms::party($player, 6);
            }
            if ($SlotId == 8) {
                Forms::party($player, 32);
            }
        }

        if ($formid == 432) {
            if (isset(Forms::$requestdata[$player->getXuid()][$SlotId])) {
                PlayerDataManager::setData($player, "p_requestid", Forms::$requestdata[$player->getXuid()][$SlotId]);
                Forms::party($player, 33);
            }
        }

        if ($formid == 433) {
            $config = yaml::getconfig();
            $id = [];
            foreach ($config["games"]["duel"] as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (isset($id[$SlotId])) {
                Request::partycreateRequest(PlayerDataManager::getData($player, "party_name"), PlayerDataManager::getData($player, "p_requestid"), $id[$SlotId]);
                invformapi::closeInvform($player);
            }

            if ($SlotId == 4) {
                Forms::party($player, 34);
            }
        }

        if ($formid == 434) {
            $config = yaml::getconfig();
            $id = [];
            foreach ($config["games"]["party"] as $game => $value) {
                $id[$value["slot"]] = $game;
            }
            if (isset($id[$SlotId])) {
                Request::partycreateRequest(PlayerDataManager::getData($player, "party_name"), PlayerDataManager::getData($player, "p_requestid"), $id[$SlotId]);
                invformapi::closeInvform($player);
            }


            if ($SlotId == 4) {
                Forms::party($player, 33);
            }
        }

        if ($formid >= 500 && $formid <= 599) {
            if ($SlotId == 2) {
                Forms::spectate($player, 2);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 3) {
                Forms::spectate($player, 3);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 4) {
                Forms::spectate($player, 4);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 5) {
                Forms::spectate($player, 5);
                PracticePlayer::playSound($player, "random.click");
            }
            /*if ($SlotId == 6) {
                Forms::spectate($player, 6);
                PracticePlayer::playSound($player, "random.click");
            }*/
        }

        if ($formid == 502) {
            $config = yaml::getconfig();
            $id = [];
            if (PlayerDataManager::getData($player, "ingame") == "lobby") {
                foreach ($config["games"]["ffa"] as $game => $value) {
                    $id[$value["slot"]] = $game;
                }
                if (isset($id[$SlotId])) {
                    foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                        $players->hidePlayer($player);
                    }
                    ArenaManaer::Arenatp($player, "ffa", $id[$SlotId]);
                    PracticePlayer::setspectate($player);
                    PlayerKits::getkit($player, "lobby", "queue2");
                    PlayerDataManager::setData($player, "spectator", "true");
                    PlayerDataManager::setdata($player, "iscanattack", "false");
                }
            }
        }

        if ($formid == 503) {
            $config = yaml::getconfig();
            $id = [];
            if (PlayerDataManager::getData($player, "ingame") == "lobby") {
                $i = 0;
                $ids = [];
                $duels = [];
                foreach (PlayerDuel::$dueldatas as $id => $duel) {
                    if ($duel["isparty"] == false) {
                        $meta = $duel["meta"] ?? 0;
                        $i += 1;
                        if ($i < 7) {
                            $slot = $i + 9;
                        } else {
                            $slot = $i + 18;
                        }
                        $ids[] = $slot;
                        $duels[$slot] = $id;
                    }
                }
                if (in_array($SlotId, array_values($ids))) {
                    if (isset($duels[$SlotId])) {
                        $duelid = $duels[$SlotId];
                        $map = PlayerDuel::$dueldatas[$duelid]["map"];
                        $mapid = PlayerDuel::$dueldatas[$duelid]["mapid"];
                        $maps = $config["map"][$map]["map"];
                        $mapname = $maps[$mapid];
                        $spawnlist = $config["map"][$map][$mapname];
                        $spawn = $spawnlist[0];
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
                            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                                $players->hidePlayer($player);
                            }
                            PracticePlayer::setspectate($player);
                            PlayerKits::getkit($player, "lobby", "queue2");
                            PlayerDataManager::setData($player, "specid", $duelid);
                            PlayerDataManager::setData($player, "spectator", "true");
                            PlayerDataManager::setdata($player, "iscanattack", "false");
                        }
                    }
                }
            }
        }

        if ($formid == 504) {
            if ($SlotId == 26) {
                Forms::spectate($player, 7);
                PracticePlayer::playSound($player, "random.click");
            } else {
                $config = yaml::getconfig();
                $id = [];
                if (PlayerDataManager::getData($player, "ingame") == "lobby") {
                    $i = 0;
                    $ids = [];
                    $duels = [];
                    foreach (PlayerDuel::$dueldatas as $id => $duel) {
                        if ($duel["isparty"] == true && $duel["split"] == false) {
                            $i += 1;
                            if ($i < 7) {
                                $slot = $i + 9;
                            } else {
                                $slot = $i + 18;
                            }
                            $ids[] = $slot;
                            $duels[$slot] = $id;
                        }
                    }
                    if (in_array($SlotId, array_values($ids))) {
                        if (isset($duels[$SlotId])) {
                            $duelid = $duels[$SlotId];
                            $map = PlayerDuel::$dueldatas[$duelid]["map"];
                            $mapid = PlayerDuel::$dueldatas[$duelid]["mapid"];
                            $maps = $config["map"][$map]["map"];
                            $mapname = $maps[$mapid];
                            $spawnlist = $config["map"][$map][$mapname];
                            $spawn = $spawnlist[0];
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
                                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                                    $players->hidePlayer($player);
                                }
                                PracticePlayer::setspectate($player);
                                PlayerKits::getkit($player, "lobby", "queue2");
                                PlayerDataManager::setData($player, "specid", $duelid);
                                PlayerDataManager::setData($player, "spectator", "true");
                                PlayerDataManager::setdata($player, "iscanattack", "false");
                            }
                        }
                    }
                }
            }
        }

        if ($formid == 505) {
            $config = yaml::getconfig();
            $id = [];
            if (PlayerDataManager::getData($player, "ingame") == "lobby") {
                $i = 0;
                $ids = [];
                $practices = [];
                foreach (practicegame::$practicedatas as $id => $practice) {
                    $i += 1;
                    if ($i < 7) {
                        $slot = $i + 9;
                    } else {
                        $slot = $i + 18;
                    }
                    $ids[] = $slot;
                    $practices[$slot] = $id;
                }
                if (in_array($SlotId, array_values($ids))) {
                    if (isset($practices[$SlotId])) {
                        invformapi::closeInvform($player);
                        $practice = $practices[$SlotId];
                        $map = practicegame::$practicedatas[$practice]["mapids"];
                        $mapid = practicegame::$practicedatas[$practice]["mapid"];
                        $mapname = practicegame::$practicedatas[$practice]["map"];
                        $spawnlist = $config["map"][$map][$mapname];
                        $spawn = $spawnlist[0];
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
                            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                                $players->hidePlayer($player);
                            }
                            PracticePlayer::setspectate($player);
                            PlayerKits::getkit($player, "lobby", "queue2");
                            PlayerDataManager::setData($player, "specid", $practice);
                            PlayerDataManager::setData($player, "spectator", "true");
                            PlayerDataManager::setdata($player, "iscanattack", "false");
                        }
                    }
                }
            }
        }

        if ($formid == 507) {
            if ($SlotId == 26) {
                Forms::spectate($player, 4);
                PracticePlayer::playSound($player, "random.click");
            } else {
                $config = yaml::getconfig();
                $id = [];
                if (PlayerDataManager::getData($player, "ingame") == "lobby") {
                    $i = 0;
                    $ids = [];
                    $duels = [];
                    foreach (PlayerDuel::$dueldatas as $id => $duel) {
                        if ($duel["isparty"] == true && $duel["split"] == true) {
                            $i += 1;
                            if ($i < 7) {
                                $slot = $i + 9;
                            } else {
                                $slot = $i + 18;
                            }
                            $ids[] = $slot;
                            $duels[$slot] = $id;
                        }
                    }
                    if (in_array($SlotId, array_values($ids))) {
                        if (isset($duels[$SlotId])) {
                            $duelid = $duels[$SlotId];
                            $map = PlayerDuel::$dueldatas[$duelid]["map"];
                            $mapid = PlayerDuel::$dueldatas[$duelid]["mapid"];
                            $maps = $config["map"][$map]["map"];
                            $mapname = $maps[$mapid];
                            $spawnlist = $config["map"][$map][$mapname];
                            $spawn = $spawnlist[0];
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
                                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                                    $players->hidePlayer($player);
                                }
                                PracticePlayer::setspectate($player);
                                PlayerKits::getkit($player, "lobby", "queue2");
                                PlayerDataManager::setData($player, "specid", $duelid);
                                PlayerDataManager::setData($player, "spectator", "true");
                                PlayerDataManager::setdata($player, "iscanattack", "false");
                            }
                        }
                    }
                }
            }
        }

        if ($formid >= 700 && $formid <= 799) {
            if ($SlotId == 2) {
                Forms::settings($player, 2);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 3) {
                Forms::settings($player, 3);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 4) {
                Forms::settings($player, 4);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 5) {
                Forms::settings($player, 5);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 6) {
                Forms::settings($player, 6);
                PracticePlayer::playSound($player, "random.click");
            }
        }
        if ($formid == 702) {
            $item = false;
            if ($SlotId == 12) {
                Forms::settings($player, 12);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 13) {
                $item = true;
                $config[$xuid]["settings"]["arena_respawn"] = !$config[$xuid]["settings"]["arena_respawn"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                $item = true;
                $config[$xuid]["settings"]["hide non opporent"] = !$config[$xuid]["settings"]["hide non opporent"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 2);
            }
        }

        if ($formid == 707) {
            $item = false;
            if ($SlotId == 19) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["show"] = !$config[$xuid]["settings"]["scoreboard"]["show"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 20) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["status"] = !$config[$xuid]["settings"]["scoreboard"]["status"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["info"] = !$config[$xuid]["settings"]["scoreboard"]["info"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["playing"] = !$config[$xuid]["settings"]["scoreboard"]["playing"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["ping"] = !$config[$xuid]["settings"]["scoreboard"]["ping"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["drop"] = !$config[$xuid]["settings"]["scoreboard"]["drop"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 25) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["combat"] = !$config[$xuid]["settings"]["scoreboard"]["combat"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 7);
            }
        }

        if ($formid == 708) {
            $item = false;
            if ($SlotId == 20) {
                $item = true;
                $config[$xuid]["settings"]["actionbar_setting"]["alwaysshow"] = !$config[$xuid]["settings"]["actionbar_setting"]["alwaysshow"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["actionbar_setting"]["ping"] = !$config[$xuid]["settings"]["actionbar_setting"]["ping"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["actionbar_setting"]["cpscount"] = !$config[$xuid]["settings"]["actionbar_setting"]["cpscount"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["actionbar_setting"]["combo"] = !$config[$xuid]["settings"]["actionbar_setting"]["combo"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                $item = true;
                $config[$xuid]["settings"]["actionbar_setting"]["reach"] = ! $config[$xuid]["settings"]["actionbar_setting"]["reach"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 8);
            }
        }
        if ($formid == 709) {
            $item = false;
            if ($SlotId == 19) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["chat"] = !$config[$xuid]["settings"]["chat_setting"]["chat"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 20) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["jqmessage"] = !$config[$xuid]["settings"]["chat_setting"]["jqmessage"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["announge"] = !$config[$xuid]["settings"]["chat_setting"]["announge"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["killlog"] = !$config[$xuid]["settings"]["chat_setting"]["killlog"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["combatlog"] = !$config[$xuid]["settings"]["chat_setting"]["combatlog"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["notification"] = !$config[$xuid]["settings"]["chat_setting"]["notification"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 25) {
                $item = true;
                $config[$xuid]["settings"]["chat_setting"]["duel_message"] = !$config[$xuid]["settings"]["chat_setting"]["duel_message"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 9);
            }
        }

        if ($formid == 710) {
            $item = false;
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["title"]["countdown"] = !$config[$xuid]["settings"]["title"]["countdown"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["title"]["result"] = !$config[$xuid]["settings"]["title"]["result"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["title"]["other"] = !$config[$xuid]["settings"]["title"]["other"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 10);
            }
        }

        if ($formid == 711) {
            $item = false;
            if ($SlotId == 19) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["show"] = !$config[$xuid]["settings"]["scoreboard"]["show"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 20) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["nocombat_device"] = !$config[$xuid]["settings"]["scoreboard"]["nocombat_device"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["nocombat_ping"] = !$config[$xuid]["settings"]["scoreboard"]["nocombat_ping"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["nocombat_drop"] = !$config[$xuid]["settings"]["scoreboard"]["nocombat_drop"];
                PracticePlayer::playSound($player, "random.click");;
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["incombat_cps"] = !$config[$xuid]["settings"]["scoreboard"]["incombat_cps"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["incombat_ping"] = !$config[$xuid]["settings"]["scoreboard"]["incombat_ping"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 25) {
                $item = true;
                $config[$xuid]["settings"]["scoreboard"]["incombat_drop"] = !$config[$xuid]["settings"]["scoreboard"]["incombat_drop"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 11);
            }
        }

        if ($formid == 712) {
            $item = false;
            if ($SlotId == 11) {
                $item = true;
                $config[$xuid]["settings"]["spawn_protection"] -= 0.5;
                $config[$xuid]["settings"]["spawn_protection"] = round($config[$xuid]["settings"]["spawn_protection"], 1);
                if ($config[$xuid]["settings"]["spawn_protection"] < 0) {
                    $config[$xuid]["settings"]["spawn_protection"] = 0;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 12) {
                $item = true;
                $config[$xuid]["settings"]["spawn_protection"] -= 0.1;
                $config[$xuid]["settings"]["spawn_protection"] = round($config[$xuid]["settings"]["spawn_protection"], 1);
                if ($config[$xuid]["settings"]["spawn_protection"] < 0) {
                    $config[$xuid]["settings"]["spawn_protection"] = 0;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 13) {
                Forms::settings($player, 2);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                $item = true;
                $config[$xuid]["settings"]["spawn_protection"] += 0.1;
                $config[$xuid]["settings"]["spawn_protection"] = round($config[$xuid]["settings"]["spawn_protection"], 1);
                if ($config[$xuid]["settings"]["spawn_protection"] > 5) {
                    $config[$xuid]["settings"]["spawn_protection"] = 5;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 15) {
                $item = true;
                $config[$xuid]["settings"]["spawn_protection"] += 0.5;
                $config[$xuid]["settings"]["spawn_protection"] = round($config[$xuid]["settings"]["spawn_protection"], 1);
                if ($config[$xuid]["settings"]["spawn_protection"] > 5) {
                    $config[$xuid]["settings"]["spawn_protection"] = 5;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 12);
            }
        }
        if ($formid == 703 || $formid >= 707 && $formid <= 711) {
            if ($SlotId == 11) {
                Forms::settings($player, 7);
            }
            if ($SlotId == 12) {
                Forms::settings($player, 8);
            }
            if ($SlotId == 13) {
                Forms::settings($player, 9);
            }
            if ($SlotId == 14) {
                Forms::settings($player, 10);
            }
            if ($SlotId == 15) {
                Forms::settings($player, 11);
            }
        }
        if ($formid == 704) {
            $item = false;
            if ($SlotId == 11) {
                $item = true;
                if ($config[$xuid]["settings"]["crit_particle"] == 4) {
                    $config[$xuid]["settings"]["crit_particle"] = 0;
                } else {
                    $config[$xuid]["settings"]["crit_particle"] += 1;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 12) {
                $item = true;
                $config[$xuid]["settings"]["fullbright"] = !$config[$xuid]["settings"]["fullbright"];
                PracticePlayer::playSound($player, "random.click");
                PracticePlayer::$playerdata = $config;
                PracticePlayer::PlayerSettingUpdate($player);
            }
            if ($SlotId == 13) {
                $item = true;
                $config[$xuid]["settings"]["lighting_kill"] = !$config[$xuid]["settings"]["lighting_kill"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                $item = true;
                $config[$xuid]["settings"]["smooth_pearl"] = !$config[$xuid]["settings"]["smooth_pearl"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 15) {
                Forms::settings($player, 13);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 22) {
                $item = true;
                Forms::settings($player, 13);
                $config[$xuid]["settings"]["rain"] = !$config[$xuid]["settings"]["rain"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                if ($SlotId == 22) PracticePlayer::setPlayerRain($player, PracticePlayer::$playerdata[$xuid]["settings"]["rain"]);
                Forms::settings($player, 4);
            }
        }
        if ($formid == 705) {
            $item = false;
            if ($SlotId == 11) {
                $item = true;
                $config[$xuid]["settings"]["duel_request"] = !$config[$xuid]["settings"]["duel_request"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 12) {
                $item = true;
                $config[$xuid]["settings"]["party_request"] = !$config[$xuid]["settings"]["party_request"];
                PracticePlayer::playSound($player, "random.click");
                PracticePlayer::$playerdata = $config;
                PracticePlayer::PlayerSettingUpdate($player);
            }
            if ($SlotId == 13) {
                $item = true;
                $config[$xuid]["settings"]["show_killstreak"] = !$config[$xuid]["settings"]["show_killstreak"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                $item = true;
                $config[$xuid]["settings"]["toggle_sprint"] = !$config[$xuid]["settings"]["toggle_sprint"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 15) {
                $item = true;
                $config[$xuid]["settings"]["duel_result"] = !$config[$xuid]["settings"]["duel_result"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 21) {
                $item = true;
                $config[$xuid]["settings"]["lobby_hide"] = !$config[$xuid]["settings"]["lobby_hide"];
                PracticePlayer::playSound($player, "random.click");
                PracticePlayer::$playerdata = $config;
                PracticePlayer::PlayerSettingUpdate($player);
            }
            if ($SlotId == 22) {
                $item = true;
                $config[$xuid]["settings"]["nick"] = !$config[$xuid]["settings"]["nick"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 23) {
                $item = true;
                $config[$xuid]["settings"]["cpslimit"] = !$config[$xuid]["settings"]["cpslimit"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 24) {
                $item = true;
                $config[$xuid]["settings"]["vanilla_hook"] = !$config[$xuid]["settings"]["vanilla_hook"];
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                Forms::settings($player, 5);
            }
        }

        if ($formid == 713) {
            $item = false;
            if ($SlotId == 11) {
                $item = true;
                $config[$xuid]["settings"]["time"] -= 1000;
                $config[$xuid]["settings"]["time"] = round($config[$xuid]["settings"]["time"], 0);
                if ($config[$xuid]["settings"]["time"] < 0) {
                    $config[$xuid]["settings"]["time"] = 24000;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 12) {
                $item = true;
                $config[$xuid]["settings"]["time"] -= 100;
                $config[$xuid]["settings"]["time"] = round($config[$xuid]["settings"]["time"], 0);
                if ($config[$xuid]["settings"]["time"] < 0) {
                    $config[$xuid]["settings"]["time"] = 24000;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 13) {
                Forms::settings($player, 4);
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 14) {
                $item = true;
                $config[$xuid]["settings"]["time"] += 100;
                $config[$xuid]["settings"]["time"] = round($config[$xuid]["settings"]["time"], 0);
                if ($config[$xuid]["settings"]["time"] > 24000) {
                    $config[$xuid]["settings"]["time"] = 0;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($SlotId == 15) {
                $item = true;
                $config[$xuid]["settings"]["time"] += 1000;
                $config[$xuid]["settings"]["time"] = round($config[$xuid]["settings"]["time"], 0);
                if ($config[$xuid]["settings"]["time"] > 24000) {
                    $config[$xuid]["settings"]["time"] = 0;
                }
                PracticePlayer::playSound($player, "random.click");
            }
            if ($item) {
                PracticePlayer::$playerdata = $config;
                PracticePlayer::PlayerSettingUpdate($player);
                Forms::settings($player, 13);
            }
        }
    }
}
