<?php

namespace practice\player;

use pocketmine\Server;
use pocketmine\player\GameMode;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use practice\utils\sql;
use practice\utils\yaml;
use practice\utils\PlayerDataManager;
use practice\arena\ArenaManaer;
use practice\player\PlayerConfig;
use practice\command\CommandPerm;
use practice\handler\GameruleHandler;
use practice\handler\setTimeHandler;
use practice\form\invformapi;
use practice\practice\practice as practicegame;
use practice\form\Forms;
use practice\player\scoreboard\Scorehud;
use practice\practice;
use practice\handler\PlayerDeathHandler;
use practice\player\PlayerQueue;
use practice\party\Party;
use practice\handler\ChatHandler;

class PracticePlayer
{
    private static array $langlist = ["en", "ja"];
    public static array $playerdata = [];
    public static array $langdata = [];

    public static function login($event)
    {
        $player = $event->getPlayer();
        $ip = $player->getNetworkSession()->getip();
        $xuid = $player->getXuid();
        PlayerDataManager::setData($xuid, "ip", $ip);
        self::getdata($player);
        self::setup($player);
    }

    private static function setup($player)
    {
        $player->setGamemode(GameMode::SURVIVAL);
        PlayerDataManager::setData($player, "queue", "null");
        PlayerDataManager::setData($player, "builder", "false");
        PlayerDataManager::setdata($player, "team", "null");
        PlayerDataManager::setdata($player, "lasthitplayer", "null");
        PlayerDataManager::setdata($player, "name", $player->getName());
        self::PlayerSettingUpdate($player);
        ArenaManaer::joinlobby($player);
        self::UpdateName($player);
        //Forms::joinffa($player); //Proxy対策
        invformapi::closeInvform($player);
    }

    public static function PlayerJoin($event)
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        self::UpdateRank($player);
        GameruleHandler::setGamerule($player, "showCoordinates", true);
        GameruleHandler::setGamerule($player, "doDaylightCycle", false);
        CommandPerm::addPermission($player, "server.command");
        PlayerDataManager::setData($player, "combat_lasthit", "0");
        PlayerDataManager::setData($player, "combatplayer", "dummy");
        PlayerDataManager::setdata($player, "joined", "true");
        $event->setJoinMessage("");
        $config = yaml::getconfig();
        $rank = self::$playerdata[$xuid]["status"]["rank"];
        $message = "§8[§a+§8] §a" . $player->getName();
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            ChatHandler::sendMessage($players, $message, false, "jqmessage");
        }
        self::setPlayerRain($player, self::$playerdata[$player->getXuid()]["settings"]["rain"]);
        Scorehud::UpdateScore($player);
    }

    public static function PlayerQuit($event)
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        if (isset(self::$playerdata[$xuid])) {
            $json = json_encode(self::$playerdata[$xuid], JSON_UNESCAPED_SLASHES);
            $player_info = [
                "xuid" => $xuid,
                "data" => $json,
            ];
            $isparty = PlayerDataManager::getdata($player, "party");
            if (PlayerDataManager::getData($player, "gametype") == "practice") {
                $practiceid = PlayerDataManager::getdata($player, "practiceid");
                practicegame::$practicedatas[$practiceid]["status"] = practicegame::PRACTICE_CLOSED;
            }
            if ($isparty == "true") {
                $partyname = PlayerDataManager::getdata($player, "party_name");
                $isowner = PlayerDataManager::getdata($player, "party_owner");
                if ($isowner == "true") {
                    Party::destroyParty($partyname);
                } else {
                    Party::removePlayer($partyname, $player);
                }
            }
            sql::allsave("player_info", $player_info);
            PlayerQueue::leaveQueue($player);
            PlayerDeathHandler::PlayerDeath($player);
            PlayerDataManager::removePlayerData($player);
            $event->setQuitMessage("");
            $config = yaml::getconfig();
            $rank = self::$playerdata[$xuid]["status"]["rank"];
            $message = "§8[§c-§8] §c" . $player->getName();
            foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                ChatHandler::sendMessage($players, $message, false, "jqmessage");
            }
        }
    }

    private static function getdata($player)
    {
        $xuid = $player->getXuid();
        $playerinfo = sql::runsql("SELECT * FROM player_info WHERE xuid = '$xuid';");
        if ($playerinfo == null) {
            PlayerConfig::createdata($xuid);
            $playerinfo = sql::runsql("SELECT * FROM player_info WHERE xuid = '$xuid';");
        }
        $playerinfo = PlayerConfig::syncPlayerData($playerinfo["0"]);
        self::$playerdata[$xuid] = json_decode($playerinfo["data"], true);
    }

    public static function clientinfo($packet, $origin)
    {
        $clientDataJwt = $packet->clientDataJwt;
        $chainDataJwt = $packet->chainDataJwt;
        $decodedJwt = self::decodeJwt($clientDataJwt);
        $chainDataJwt = $chainDataJwt->chain;
        $decodedChainData = [];
        foreach ($chainDataJwt as $jwtToken) {
            $decodedChainData[] = self::decodeJwt($jwtToken);
        }
        foreach ($decodedChainData as $item) {
            if (isset($item['payload']['extraData']['XUID'])) {
                $xuid = $item['payload']['extraData']['XUID'];
                break;
            }
        }
        $payload = $decodedJwt['payload'] ?? [];
        //$logger->info($payload);
        $data = $payload;
        $deviceid = $data['DeviceId'] ?? '';
        $language = $data['LanguageCode'] ?? '';
        $isediter = $data['IsEditorMode'] ?? '';
        $deviceos = $data['DeviceOS'] ?? '';
        $clientid = $data['ClientRandomId'] ?? '';
        $version = $data['GameVersion'] ?? '';
        $platform = $data['PlatformType'] ?? '';
        $name = $data['ThirdPartyName'] ?? '';
        $skin = $data['SkinData'] ?? '';
        PlayerDataManager::setData($xuid, "xuid", $xuid);
        PlayerDataManager::setData($xuid, "name", $name);
        PlayerDataManager::setData($xuid, "player_language", $language);
        PlayerDataManager::setData($xuid, "deviceos", $deviceos);
        PlayerDataManager::setData($xuid, "clientid", $clientid);
        PlayerDataManager::setData($xuid, "version", $version);
        PlayerDataManager::setData($xuid, "platform", $platform);
        PlayerDataManager::setData($xuid, "editer", $isediter);
        PlayerDataManager::setData($xuid, "deviceid", $deviceid);
        PlayerDataManager::setData($xuid, "skin", $skin, false);
    }



    private static function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return ['error' => 'Invalid JWT format'];
        }
        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);

        return [
            'header' => $header,
            'payload' => $payload,
        ];
    }

    public static function getLangText($player, $text): string
    {
        $langdata = [];
        $xuid = $player->getXuid();
        $language = self::$playerdata[$xuid]["settings"]["lang"];
        if (in_array(explode("_", $language)[0], self::$langlist)) {
            $lang = explode("_", $language)[0];
        } else {
            $lang = "en";
        }
        $langdata = self::$langdata[$lang];
        if (isset($langdata[$text])) {
            $langtext = $langdata[$text];
        } else {
            $text = "lang.error";
            $langtext = $langdata[$text];
        }
        $langtext = str_replace("§v", yaml::getcolor(), $langtext);
        return $langtext;
    }

    public static function setLangText()
    {
        foreach (self::$langlist as $lang) {
            if (in_array(explode("_", $lang)[0], self::$langlist)) {
                $lang = explode("_", $lang)[0];
            } else {
                $lang = "en";
            }
            $currentPath = __DIR__;
            $filePath = str_replace("src\practice\player", "resources\languages" . "\\" . "$lang.yaml", $currentPath);
            self::$langdata[$lang] = yaml_parse_file($filePath);
        }
    }

    public static function cheangeLanguage($player, $language): bool
    {
        $xuid = $player->getXuid();
        if (in_array($language, self::$langlist)) {
            self::$playerdata[$xuid]["settings"]["lang"] = $language;
            return true;
        } else {
            return false;
        }
    }

    public static function sendMessage($player, $message, $args = false): void
    {
        $message = self::getLangText($player, $message);
        if ($args !== false) {
            if (is_string($args)) {
                $args = [$args];
            }
            $count = 0;
            foreach ($args as $replace) {
                $count = $count + 1;
                $message = str_replace("{" . $count . "}", $replace, $message);
            }
        }
        $player->sendMessage($message);
    }

    public static function sendActionBarMessage($player, $message, $args = false): void
    {
        $message = self::getLangText($player, $message);
        if ($args !== false) {
            if (is_string($args)) {
                $args = [$args];
            }
            $count = 0;
            foreach ($args as $replace) {
                $count = $count + 1;
                $message = str_replace("{" . $count . "}", $replace, $message);
            }
        }
        $player->sendActionBarMessage($message);
    }

    public static function PlayerSettingUpdate($player): void
    {
        $xuid = $player->getXuid();
        setTimeHandler::setTime($player, self::$playerdata[$xuid]["settings"]["time"]);
        if (PlayerDataManager::getdata($player, "ingame") == "lobby") {
            if (self::$playerdata[$xuid]["settings"]["lobby_hide"] == true) {
                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                    $player->hidePlayer($players);
                }
            } else {
                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                    $player->showPlayer($players);
                }
            }
        }
        $effectManager = $player->getEffects();
        if (self::$playerdata[$xuid]["settings"]["fullbright"] == true) {
            $nightVision = new EffectInstance(VanillaEffects::NIGHT_VISION(), 2147483647, 0, false);
            $effectManager->add($nightVision);
        } else {
            $effectManager->remove(VanillaEffects::NIGHT_VISION());
        }
    }

    public static function playSound($player, string $name, int $vol = 1, int $pitch = 1, array $recipents = []): void
    {
        $packet = new PlaySoundPacket();
        $packet->soundName = $name;
        $packet->x = $player->getLocation()->getX();
        $packet->y = $player->getLocation()->getY();
        $packet->z = $player->getLocation()->getZ();
        $packet->volume = $vol;
        $packet->pitch = $pitch;
        $player->getNetworkSession()?->sendDataPacket($packet);
    }

    public static function UpdateRank($player): void
    {
        $config = yaml::getconfig();
        $xuid = $player->getXuid();
        $ranklist = [];
        foreach ($config["rank"] as $key => $value) {
            $ranklist[] = $key;
        }
        CommandPerm::removePermission($player, $value["permission"]);
        $playerrank = self::$playerdata[$xuid]["status"]["rank"];
        if (in_array($playerrank, $ranklist)) {
            if (isset($config["rank"][$playerrank]["permission"])) {
                CommandPerm::addPermission($player, $config["rank"][$playerrank]["permission"]);
            }
        }
        self::UpdateName($player);
    }
    public static function UpdateName($player): void
    {
        $gametype = PlayerDataManager::getdata($player, "gametype");
        $team = PlayerDataManager::getdata($player, "duel_team");
        $name = PlayerDataManager::getdata($player, "name");
        $xuid = $player->getXuid();
        $config = yaml::getconfig();
        $rank = self::$playerdata[$xuid]["status"]["rank"];
        $showrank = $config["rank"][$rank]["show"];
        $rankcolor = $config["rank"][$rank]["color"];
        if ($rank !== "member") {
            $showrank = "§7[" . $rankcolor . $showrank . "§r§7] " . $rankcolor;
        } else {
            $showrank = $rankcolor;
        }
        if ($gametype == "lobby") {
            $name = $showrank . $name;
        } elseif ($gametype == "duel") {
            if ($team == "null") {
                $name = $rankcolor . $name;
            } else {
                $name = $name;
            }
        } else {
            $name = $rankcolor . $name;
        }
        $player->setNameTag($name);
    }

    public static function setPlayerRain($player, bool $rain): void
    {
        if ($rain) {
            $pk = new LevelEventPacket();
            $pk->eventId = 3001;
            $pk->eventData = 65535;
            $pk->position = $player->getPosition();
            $player->getNetworkSession()->sendDataPacket($pk);
        } else {
            $pk = new LevelEventPacket();
            $pk->eventId = 3003;
            $pk->eventData = 65535;
            $pk->position = $player->getPosition();
            $player->getNetworkSession()->sendDataPacket($pk);
        }
    }


    public static function resetstatus($player): void
    {
        $player->getEffects()->clear();
        self::PlayerSettingUpdate($player);
        practice::CreateTask("practice\utils\\task\CancelFire", $player, 0);
    }

    public static function setspectate($player): void
    {
        PlayerDataManager::setdata($player, "spectate", "true");
        $player->setGamemode(GameMode::ADVENTURE);
        $player->setAllowFlight(true);
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            $players->hidePlayer($player);
        }
    }

    public static function unsetspectate($player): void
    {
        PlayerDataManager::setdata($player, "spectate", "false");
        $player->setAllowFlight(false);
        $player->setFlying(false);
        foreach (Server::getInstance()->getOnlinePlayers() as $players) {
            $players->showPlayer($player);
        }
    }

    public static function unsetowning($player): void
    {
        foreach ($player->getWorld()->getEntities() as $entity) {
            $owner = $entity->getOwningEntity();
            if ($owner == $player) {
                $entity->setOwningEntity(null);
            }
        }
    }
}
