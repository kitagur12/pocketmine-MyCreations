<?php

namespace practice\practice;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\block\Bed;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use practice\player\knockback;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\Server;
use pocketmine\item\VanillaItems;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
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

class practice
{
    public static array $practicedatas = [];

    const PRACTICE_SETUP = 0;
    const PRACTICE_STANDING = 1;
    const PRACTICE_READY = 2;
    const PRACTICE_PLAYING = 3;
    const PRACTICE_FINISHED = 4;
    const PRACTICE_CLOSED = 5;

    public static function createPractice(array $practicedata): void
    {
        $config = yaml::getconfig();

        $map = $config["games"]["practice"][$practicedata["game"]]["map"];
        $type = $config["games"]["practice"][$practicedata["game"]]["type"];
        $maps = $config["map"][$map]["map"];
        if (is_string($maps)) {
            $maps = [$maps];
        }
        $count = count($maps) - 1;
        $mapid = mt_rand(0, $count);
        $practiceid = bin2hex(random_bytes(64));
        self::$practicedatas[$practiceid]["status"] = self::PRACTICE_SETUP;
        self::$practicedatas[$practiceid]["spectate"] = true;
        self::$practicedatas[$practiceid]["game"] = $practicedata["game"];
        self::$practicedatas[$practiceid]["type"] = $type;
        self::$practicedatas[$practiceid]["map"] = $maps[$mapid];
        self::$practicedatas[$practiceid]["mapid"] = $mapid;
        self::$practicedatas[$practiceid]["mapids"] = $map;
        self::$practicedatas[$practiceid]["player"] = $practicedata["player"];
        self::$practicedatas[$practiceid]["practiceid"] = $practiceid;
        self::$practicedatas[$practiceid]["tick"] = -1;
        self::$practicedatas[$practiceid]["world"] = false;
        self::UpdateSetting($practiceid);
    }

    public static function practicetick(): void
    {
        foreach (self::$practicedatas as $id => $data) {
            $config = yaml::getconfig();
            $player = $data["player"];
            $data["tick"] += 1;
            if ($data["status"] == self::PRACTICE_SETUP) {
                PlayerDataManager::setdata($player, "practiceid", $data["practiceid"]);
                PlayerDataManager::setdata($player, "gametype", "practice");
                PlayerDataManager::setdata($player, "ingame", $data["game"]);
                PracticePlayer::resetstatus($player);
                if (self::$practicedatas[$id]["world"] === false) {
                    self::$practicedatas[$id]["world"] = "aaaaaaaaaa";
                    WorldManaer::clonePracticeWorld($data["map"], $data["practiceid"]);
                }
                if (self::$practicedatas[$id]["world"] === "bbbbbbbbbb") {
                    self::teleportPlayer($player);
                    $botgame = [
                        "clutch",
                        "reduce",
                        "trackaim",
                        "360"
                    ];
                    if (in_array($data["game"], $botgame)) {
                        $config = yaml::getconfig();
                        $id = PlayerDataManager::getdata($player, "practiceid");
                        $data = self::$practicedatas[$id];
                        $map = $data["mapids"];
                        $mapname = $data["map"];
                        $spawnlist = $config["map"][$map][$mapname];
                        $id = PlayerDataManager::getdata($player, "practiceid");
                        $spawn = $spawnlist[1];
                        $world = $data["player"]->getWorld();
                        $location = new Location($spawn[0] + 0.5, $spawn[1], $spawn[2] + 0.5, $world, 0, 0);
                        $skin = $data["player"]->getSkin();
                        $npc = new Human($location, $skin);
                        $npc->spawnToAll();
                        $entities = $world->getEntities();
                        foreach ($entities as $entity) {
                            if ($entity instanceof Human && !($entity instanceof Player)) {
                                $data["bot"] = $entity;
                                $data["bot"]->setNameTag(yaml::getcolor() . $config["games"]["practice"][$data["game"]]["name"] . " BOT");
                                $data["bot"]->setNameTagAlwaysVisible(true);
                            }
                        }
                    }
                    $data["tick"] = -1;
                    $data["status"] = self::PRACTICE_STANDING;
                }
                $deathheight = $config["games"]["practice"][$data["game"]]["deathheight"] ?? -64;
                $y = $player->getPosition()->getY();
                if ($y < $deathheight) {
                    self::respawn($id);
                }
            }
            if (isset($data["bot"])) {
                $playerPosition = $data["player"]->getPosition();
                $npcPosition = $data["bot"]->getPosition();
                $deltaX = $playerPosition->getX() - $npcPosition->getX();
                $deltaZ = $playerPosition->getZ() - $npcPosition->getZ();
                $deltaY = $playerPosition->getY() - $npcPosition->getY();
                $yaw = rad2deg(atan2($deltaZ, $deltaX)) - 90;
                $pitch = -rad2deg(atan2($deltaY, sqrt($deltaX ** 2 + $deltaZ ** 2)));
                $data["bot"]->setRotation($yaw, $pitch);
            }
            if ($data["status"] == self::PRACTICE_STANDING) {
                if ($data["tick"] == 0) {
                    $player->setNoClientPredictions(false);
                    self::resetblock($id);
                    PlayerKits::getkit($player, "practice", "standing");
                    PlayerDataManager::setdata($player, "canblock", "false");
                    PlayerDataManager::setdata($player, "openchest", "false");
                    self::teleportPlayer($player);
                    if (isset($data["hitremaining"])) {
                        unset($data["hitremaining"]);
                    }
                }
            }

            if ($data["status"] == self::PRACTICE_READY) {
                $skip = false;
                if ($data["tick"] == 0) {
                    if ($data["type"] == "blockin") {
                        PlayerKits::getKit($player, "lobby", "void");
                    }
                    if ($data["type"] == "bridge" || $data["type"] == "clutch") {
                        if ($data["type"] == "bridge") {
                            $skip = true;
                            $data["tick"] = -1;
                            $data["status"] = self::PRACTICE_PLAYING;
                        }
                        self::$practicedatas[$id] = $data;
                        TitleHandler::sendTitle($player, " ", false, "countdown", 5, 100, 0);
                        $blockType = $data["settings"]["blocktype"];
                        $block = VanillaBlocks::$blockType();

                        $item = $block->asItem();
                        $item->setCount(64);
                        for ($i = 0; $i < 36; $i++) {
                            $player->getInventory()->setItem($i, $item);
                        }
                        PlayerDataManager::setdata($player, "canblock", "true");
                    }
                    if ($data["type"] == "chestrooting") {
                        PlayerKits::getKit($player, "lobby", "void");
                    }
                    if ($data["type"] == "bedsurround") {
                        PlayerKits::getKit($player, "lobby", "void");
                    }
                    $item = VanillaItems::DYE();
                    $item->setColor(DyeColor::RED);
                    $nbt = $item->getNamedTag();
                    $nbt->setInt("id", 205);
                    $item->setNamedTag($nbt);
                    $text = PracticePlayer::getLangText($player, "item.practice.return");
                    $item->setCustomName("§f" . $text);
                    $player->getInventory()->setItem(8, $item);
                }
                if (!$skip) {
                    if ($data["tick"] == 0) {
                        PlayerDataManager::setdata($player, "canblock", "false");
                        $player->setNoClientPredictions(true);
                        self::teleportPlayer($player);
                        TitleHandler::sendTitle($player, yaml::getcolor() . "3...", false, "countdown", 5, 100, 0);
                        PracticePlayer::playSound($player, "random.click");
                        PlayerDataManager::setdata($player, "itemuse", "false");
                    }
                    if ($data["tick"] == 20) {
                        TitleHandler::sendTitle($player, yaml::getcolor() . "2...", false, "countdown", 5, 100, 0);
                        PracticePlayer::playSound($player, "random.click");
                    }
                    if ($data["tick"] == 40) {
                        TitleHandler::sendTitle($player, yaml::getcolor() . "1...", false, "countdown", 5, 100, 0);
                        PracticePlayer::playSound($player, "random.click");
                    }
                    if ($data["tick"] == 60) {
                        TitleHandler::sendTitle($player, "practice.start", true, "countdown", 5, 20, 10);
                        PracticePlayer::playSound($player, "random.orb");
                        $data["tick"] = -1;
                        $data["status"] = self::PRACTICE_PLAYING;
                        //var_dump("?????????????");
                    }
                }
            }

            if ($data["status"] == self::PRACTICE_PLAYING) {
                if ($data["tick"] == 0) {
                    $player->setNoClientPredictions(false);
                    if ($data["type"] == "chestrooting") {
                        PlayerDataManager::setdata($player, "openchest", "true");
                    }
                    if ($data["type"] == "bridge" || $data["type"] == "clutch" || $data["type"] == "blockin") {
                        PlayerDataManager::setdata($player, "canblock", "true");
                    }
                }
                if ($data["type"] == "clutch") {
                    $delay = $data["settings"]["hitdelay"];
                    if ($data["settings"]["hitdelay"] == 0) {
                        $delay = 1;
                    }
                    if (!isset($data["hitremaining"])) {
                        $data["hitremaining"] = $data["settings"]["hitdelay"];
                        $data["hitcount"] = $data["settings"]["hitcount"];
                        if ($data["hitcount"] < 0) {
                            $data["hitcount"] = 0;
                        }
                        $rand = rand(-$data["settings"]["hitrandomise"], $data["settings"]["hitrandomise"]);
                        $data["hitcooltime"] = $data["settings"]["hitcooltime"] + $rand;
                        if ($data["hitcooltime"] < 0) {
                            $data["hitcooltime"] = 0;
                        }
                        $data["count"] = 0;
                        $data["tick2"] = 0;
                        $data["tick3"] = 0;
                        $data["lastblockpos"] = $data["player"]->getPosition();
                    }

                    $data["tick2"] += 1;
                    if (($data["tick2"] > $data["hitremaining"] && $data["count"] !== $data["hitcount"]) || $data["settings"]["hitdelay"] == 0) {
                        $data["count"] += 1;
                        $data["tick2"] = 0;
                        $rand = rand(-$data["settings"]["attackrandomise"], $data["settings"]["attackrandomise"]);
                        $data["hitremaining"] = $data["settings"]["hitdelay"] * 2 + $rand;
                        if ($data["hitremaining"] < 0) {
                            $data["hitremaining"] = 0;
                        }
                        $data["tick2"] = 0;
                        $event = new EntityDamageEvent(
                            $data["player"],
                            1,
                            0,
                        );
                        self::kb($event);
                        $event = new EntityDamageEvent(
                            $data["bot"],
                            1,
                            0,
                        );
                        $event->setAttackCooldown(0);
                        $data["player"]->attack($event);
                        $packet = AnimatePacket::create($data["bot"]->getId(), AnimatePacket::ACTION_SWING_ARM);
                        foreach ($data["player"]->getWorld()->getPlayers() as $player) {
                            $player->getNetworkSession()->sendDataPacket($packet);
                        }
                        $data["tick3"] = 0;
                        $rand = rand(-$data["settings"]["hitrandomise"], $data["settings"]["hitrandomise"]);
                        $data["hitcooltime"] = $data["settings"]["hitcooltime"] + $rand;
                        if ($data["hitcooltime"] < 0) {
                            $data["hitcooltime"] = 0;
                        }
                    } else {
                        $data["tick3"] += 1;
                        if ($data["tick3"] > ($data["hitcooltime"] + 1) * $data["settings"]["hitdelay"]) {
                            $data["count"] = 0;
                            $rand = rand(-$data["settings"]["hitrandomise"], $data["settings"]["hitrandomise"]);
                            $data["hitcount"] = $data["settings"]["hitcount"] + $rand;
                            if ($data["hitcount"] < 0) {
                                $data["hitcount"] = 0;
                            }
                        }
                        $data["tick2"] += 1;
                    }
                    $pos1 = $data["player"]->getPosition();
                    $pos2 = $data["lastblockpos"] ?? $data["player"]->getPosition();
                    $distance = sqrt(
                        ($pos1->getX() - $pos2->getX()) ** 2 +
                        ($pos1->getZ() - $pos2->getZ()) ** 2
                    );
                    if ($distance > 5) {
                        self::teleportPlayer($player);
                        $data["tick"] = -1;
                        $data["status"] = self::PRACTICE_STANDING;
                    }
                }
            }

            $deathheight = $config["games"]["practice"][$data["game"]]["deathheight"] ?? -64;
            $y = $player->getPosition()->getY();
            if ($y < $deathheight) {
                self::respawn($id);
                $data["tick"] = -1;
                $data["status"] = self::PRACTICE_STANDING;
            }
            
            if ($data["status"] == self::PRACTICE_CLOSED) {
                foreach (Server::getInstance()->getOnlinePlayers() as $players) {
                    if (PlayerDataManager::getdata($players, "spectator") == "true" && PlayerDataManager::getdata($players, "specid") == $id) {
                        ArenaManaer::joinlobby($players);
                    }
                }
                if ($player->isOnline()) {
                    $player->setNoClientPredictions(false);
                }
                if (isset($data["bot"])) {
                    $data["bot"]->kill();
                }
                unset(self::$practicedatas[$id]);
                WorldManaer::deleteDuelWorld($data["map"], $id);
            } else {
                self::$practicedatas[$id] = $data;
            }
        }
    }

    public static function teleportPlayer($player): void
    {
        $config = yaml::getconfig();
        $id = PlayerDataManager::getdata($player, "practiceid");
        $data = self::$practicedatas[$id];
        $map = $data["mapids"];
        $mapname = $data["map"];
        $spawnlist = $config["map"][$map][$mapname];
        $id = PlayerDataManager::getdata($player, "practiceid");
        $spawn = $spawnlist[0];
        $world = WorldManaer::getWorldByExactName($mapname . "=" . $id);
        $Position = new Position($spawn[0], $spawn[1], $spawn[2], $world, $spawn[3], $spawn[4]);
        $to = $Position;
        if (!is_float($to->getFloorX()) && !is_float($to->getFloorY()) && !is_float($to->getFloorZ())) {
            $Position = new Position($to->getFloorX() + 0.5, $to->getFloorY(), $to->getFloorZ() + 0.5, $to->getWorld());
        }
        if (count($spawn) == 3) {
            $player->teleport($Position);
        } else {
            $player->teleport($Position, $spawn[3], $spawn[4]);
        }
    }

    public static function PracticeDamageHandler($event)
    {
        $player = $event->getEntity();
        $cause = $event->getCause();
        if ($cause == EntityDamageEvent::CAUSE_VOID) {
            $event->cancel();
            $id = PlayerDataManager::getdata($player, "practiceid");
            self::respawn($id);
        } else {
            $event->cancel();
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();
        }
    }

    public static function PracticePlaceHandler($event)
    {
        $player = $event->getPlayer();
        $id = PlayerDataManager::getData($player, "practiceid");
        foreach ($event->getTransaction()->getBlocks() as $blockData) {
            $block = $blockData[3];
            $position = $block->getPosition();
        }
        $canblock = PlayerDataManager::getData($player, "canblock");
        if ($canblock !== "true") {
            $event->cancel();
            return;
        }
        $positionString = $position->getX() . ':' . $position->getY() . ':' . $position->getZ();
        self::$practicedatas[$id]["blockdata"][$positionString] = true;
        $ingame = PlayerDataManager::getData($player, "ingame");
        if ($ingame == "bridge" || $ingame == "clutch") {
            $Inventory = $player->getInventory();
            $item = VanillaBlocks::{self::$practicedatas[$id]["settings"]["blocktype"]}()->asItem();
            $Inventory->addItem($item);
            self::$practicedatas[$id]["lastblockpos"] = $position;
        }
    }

    public static function PracticeBreakHandler($event)
    {
        $player = $event->getPlayer();
        $id = PlayerDataManager::getData($player, "practiceid");
        $canblock = PlayerDataManager::getData($player, "canblock");
        if ($canblock !== "true") {
            $event->cancel();
            return;
        }
        $position = $event->getblock()->getPosition();
        $positionString = $position->getX() . ':' . $position->getY() . ':' . $position->getZ();
        if (!isset(self::$practicedatas[$id]["blockdata"][$positionString])) {
            $event->cancel();
            return;
        }
        unset(self::$practicedatas[$id]["blockdata"][$positionString]);
    }

    public static function resetblock($id)
    {
        $datas = self::$practicedatas[$id]["blockdata"] ?? [];
        $world = self::$practicedatas[$id]["player"]->getWorld();
        foreach ($datas as $data => $value) {
            $parts = explode(":", $data);
            $pos = new Vector3($parts[0], $parts[1], $parts[2]);
            $block = vanillablocks::AIR();
            $world->setBlock($pos, $block);
        }
    }

    public static function respawn($id)
    {
        $player = self::$practicedatas[$id]["player"];
        self::teleportPlayer($player);
        self::$practicedatas[$id]["tick"] = -1;
        self::$practicedatas[$id]["status"] = self::PRACTICE_STANDING;
    }

    public static function kb($event): void
    {
        $player = $event->getEntity();
        $id = PlayerDataManager::getData($player, "practiceid");
        $player = self::$practicedatas[$id]["player"];
        $damager = self::$practicedatas[$id]["bot"];
        $horizontalKb = self::$practicedatas[$id]["settings"]["horizontalkb"];
        $verticalKb = self::$practicedatas[$id]["settings"]["verticalkb"];
        $enchantmentLevel = 1;
        $item = $damager->getInventory()->getItemInHand();
        $enchantments = $item->getEnchantments();
        foreach ($enchantments as $enchantment) {
            if ($enchantment->getType()->getName()->getText() == "enchantment.knockback") {
                $enchantmentLevel += $enchantment->getLevel() * 2;
            }
        }
        $rand = rand(-self::$practicedatas[$id]["settings"]["directionrandomise"], self::$practicedatas[$id]["settings"]["directionrandomise"]) / 2;
        $rand /= 10;
        $dx = $player->getPosition()->getX() - $damager->getPosition()->getX();
        $dz = $player->getPosition()->getZ() - $damager->getPosition()->getZ();
        $length = sqrt($dx * $dx + $dz * $dz);
        if ($length == 0) {
            $length = 1;
        }
        $angle = atan2($dz, $dx);
        $offsetAngle = $rand;
        $newAngle = $angle + $offsetAngle;
        $x = cos($newAngle) * $length;
        $z = sin($newAngle) * $length;
        $f = sqrt($x * $x + $z * $z);
        if ($f > 0) {
            $x /= $f;
            $z /= $f;
            $p = 1;
        } else {
            $p = 1;
        }
        $motion = $player->getMotion();
        $motion->x = $x * $horizontalKb * $enchantmentLevel * $p;
        $motion->y = $verticalKb * $p;
        $motion->z = $z * $horizontalKb * $enchantmentLevel * $p;
        $player->setMotion($motion);
    }

    public static function UpdateSetting($practiceid, $dafault = false) {
        $game = self::$practicedatas[$practiceid]["game"];
        $config = [];
        if (!$dafault) {
            $config = PracticePlayer::$playerdata[self::$practicedatas[$practiceid]["player"]->getXuid()]["practice"][$game] ?? [];
        }
        if ($game == "clutch") {
            self::$practicedatas[$practiceid]["settings"]["blocktype"] = $config["clutch"]["blocktype"] ?? "SANDSTONE";
            self::$practicedatas[$practiceid]["settings"]["horizontalkb"] = $config["clutch"]["horizontalkb"] ?? 0.4;
            self::$practicedatas[$practiceid]["settings"]["verticalkb"] = $config["clutch"]["verticalkb"] ?? 0.4;
            self::$practicedatas[$practiceid]["settings"]["hitcount"] = $config["clutch"]["hitcount"] ?? 5;
            self::$practicedatas[$practiceid]["settings"]["hitdelay"] = $config["clutch"]["hitdelay"] ?? 10;
            self::$practicedatas[$practiceid]["settings"]["hitcooltime"] = $config["clutch"]["hitcooltime"] ?? 1;
            self::$practicedatas[$practiceid]["settings"]["attackrandomise"] = $config["clutch"]["attackrandomise"] ?? 0;
            self::$practicedatas[$practiceid]["settings"]["hitrandomise"] = $config["clutch"]["hitrandomise"] ?? 0;
            self::$practicedatas[$practiceid]["settings"]["directionrandomise"] = $config["clutch"]["directionrandomise"] ?? 0;
        }
        if ($game == "chestrooting") {
            self::$practicedatas[$practiceid]["settings"]["amount"] = 6;
            self::$practicedatas[$practiceid]["settings"]["randomise"] = 0;
        }
        if ($game == "bridge") {
            self::$practicedatas[$practiceid]["settings"]["blocktype"] = "SANDSTONE";
            self::$practicedatas[$practiceid]["settings"]["mode"] = 0; //0 = free, 1 = diagonal, 2 = Straight
            //以下freeモード設定
            self::$practicedatas[$practiceid]["settings"]["removetype"] = 0; //0 = 消さない, 1 = task, 2 = 落ちたら削除
        }
    }
}
