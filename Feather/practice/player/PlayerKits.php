<?php

namespace practice\player;

use pocketmine\item\Armor;
use pocketmine\item\Sword;
use pocketmine\item\Tool;
use pocketmine\item\Pickaxe;
use pocketmine\item\Hoe;
use pocketmine\item\Axe;
use pocketmine\item\Shovel;
use pocketmine\item\Bow;
use pocketmine\item\FishingRod;
use pocketmine\item\Potion;
use pocketmine\item\SplashPotion;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemBlock;
use pocketmine\item\Dye;
use pocketmine\item\Arrow;
use pocketmine\block\Wool;
use pocketmine\Server;
use pocketmine\block\utils\DyeColor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\PotionType;
use practice\player\PracticePlayer;
use practice\utils\PlayerDataManager;
use practice\utils\yaml;

class PlayerKits
{
    public static function kitplayers(string $kitname): array
    {
        $players = [];

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $xuid = $player->getXuid();
            $kit = PlayerDataManager::getData($xuid, "kit");
            if ($kit == $kitname) {
                $players[] = $player;
            }
        }
        return $players;
    }

    public static function getkit($player, $type, $game, $arrow = false): void
    {
        $xuid = $player->getXuid();
        $player->getInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $config = yaml::getconfig();
        $kit = $config["games"][$type][$game]["kit"];
        $kitdata = PracticePlayer::$playerdata[$xuid]["kits"][$kit];
        $kitdata = explode(",", $kitdata);
        $carrow = false;
        $invslot = 0;
        foreach ($kitdata as $slot) {
            if (isset($config["kits"][$kit]["item"][$slot])) {
                $item = $config["kits"][$kit]["item"][$slot];
                $itemId = $item["item"];
                $id = $item["id"];
                if ((explode("_", $itemId)[0] == "BLOCK")) {
                    $itemId = str_replace("BLOCK_", "", $itemId);
                    $blockObject = VanillaBlocks::{$itemId}();
                    $itemObject = $blockObject->asItem();
                } else {
                    $itemObject = VanillaItems::{$itemId}();
                }
                $itemObject->setCount($item["count"]);
                $nbt = $itemObject->getNamedTag();
                $nbt->setInt("id", $id);
                $itemObject->setNamedTag($nbt);
                $itemName = $item["name"];
                if ($itemName !== "default" && $itemName !== null) {
                    $text = PracticePlayer::getLangText($player, $itemName);
                    $itemObject->setCustomName("§f" . $text);
                }
                foreach ($item["data"] ?? [] as $type) {
                    if ($type == "Unbreakable") {
                        $unbreakableItems = [
                            Armor::class,
                            Sword::class,
                            FishingRod::class,
                            Axe::class,
                            Pickaxe::class,
                            Tool::class,
                            Hoe::class,
                            Shovel::class,
                            Bow::class,
                        ];
                        foreach ($unbreakableItems as $itemClass) {
                            if ($itemObject instanceof $itemClass) {
                                $itemObject->setUnbreakable();
                                break;
                            }
                        }
                    }
                    if (explode(":", $type)[0] == "enchant") {
                        $splitType = explode(":", $type);
                        list($dummy, $enchantName, $enchantLevel) = $splitType;
                        $enchant = new EnchantmentInstance(VanillaEnchantments::$enchantName(), $enchantLevel);
                        $itemObject->addEnchantment($enchant);
                    }
                    if (explode(":", $type)[0] == "potion") {
                        if ($itemObject instanceof Potion || $itemObject instanceof SplashPotion) {
                            $splitType = explode(":", $type);
                            list($dummy, $PotionType) = $splitType;
                            $itemObject->setType(PotionType::{$PotionType});
                        }
                    }
                    if (explode(":", $type)[0] == "dye") {
                        if ($itemObject instanceof Dye) {
                            $splitType = explode(":", $type);
                            $itemObject->setColor(DyeColor::{$splitType[1]});
                        }
                    }
                    if (explode(":", $type)[0] == "team") {
                        if (explode(":", $type)[1] == "wool") {
                            $team = PlayerDataManager::getdata($player, "team");
                            if ($blockObject instanceof WOOL) {
                                $splitType = explode(":", $type);
                                if ($team == "red") {
                                    $blockObject->setColor(DyeColor::RED);
                                }
                                if ($team == "blue") {
                                    $blockObject->setColor(DyeColor::BLUE);
                                }
                                if ($team == "null") {
                                    $blockObject->setColor(DyeColor::WHITE);
                                }
                                $itemObject = $blockObject->asItem();
                            }
                        }
                        if (explode(":", $type)[1] == "armor") {
                            $team = PlayerDataManager::getdata($player, "team");
                            if ($blockObject instanceof Armor) {
                                $splitType = explode(":", $type);
                                if ($team == "red") {
                                    $blockObject->setCustomColor(DyeColor::RED);
                                }
                                if ($team == "blue") {
                                    $blockObject->setCustomColor(DyeColor::BLUE);
                                }
                                if ($team == "null") {
                                    $blockObject->setCustomColor(DyeColor::BROWN);
                                }
                                $itemObject = $blockObject->asItem();
                            }
                        }

                        $itemObject->setCount($item["count"]);
                    }
                    if (explode(":", $type)[0] == "cooltime") {
                        if (explode(":", $type)[1] == "arrow") {
                            $carrow = true;
                        }
                    }
                }
                if ($carrow == true && $arrow == false) {
                    if (($itemObject instanceof Arrow)) {
                        //return;
                    }
                }
                if ($slot < 36) {
                    $player->getInventory()->setItem($slot, $itemObject);
                } elseif ($slot == 36) {
                    $player->getArmorInventory()->setHelmet($itemObject);
                } elseif ($slot == 37) {
                    $player->getArmorInventory()->setChestplate($itemObject);
                } elseif ($slot == 38) {
                    $player->getArmorInventory()->setLeggings($itemObject);
                } elseif ($slot == 39) {
                    $player->getArmorInventory()->setBoots($itemObject);
                } elseif ($slot == 40) {
                    $player->getOffHandInventory()->setItem(0, $itemObject);
                }
            }
        }
    }

    public static function getarrow($player, $type, $game): void
    {
        $xuid = $player->getXuid();
        $config = yaml::getconfig();
        $kit = $config["games"][$type][$game]["kit"];
        $kitdata = PracticePlayer::$playerdata[$xuid]["kits"][$kit];
        $kitdata = explode(",", $kitdata);
        $carrow = false;
        foreach ($kitdata as $slot) {
            if (isset($config["kits"][$kit]["item"][$slot])) {
                $item = $config["kits"][$kit]["item"][$slot];
                $itemId = $item["item"];
                $id = $item["id"];
                if ((explode("_", $itemId)[0] == "BLOCK")) {
                    $itemId = str_replace("BLOCK_", "", $itemId);
                    $blockObject = VanillaBlocks::{$itemId}();
                    $itemObject = $blockObject->asItem();
                } else {
                    $itemObject = VanillaItems::{$itemId}();
                }

                $itemObject->setCount($item["count"]);
                $nbt = $itemObject->getNamedTag();
                $nbt->setInt("id", $id);
                $itemObject->setNamedTag($nbt);
                $itemName = $item["name"];
                if ($itemName !== "default" && $itemName !== null) {
                    $text = PracticePlayer::getLangText($player, $itemName);
                    $itemObject->setCustomName("§f" . $text);
                }
                foreach ($item["data"] ?? [] as $type) {
                    if (!($itemObject instanceof Arrow)) {
                        $isset = false;
                    } else {
                        $isset = true;
                    }
                    if ($isset) {
                        for ($i = $slot; $i < 36; $i++) {
                            if ($player->getInventory()->getItem($i)->isNull()) {
                                $player->getInventory()->setItem($i, $itemObject);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function savekit($player, $kitname, $isload = false): void
    {
        $xuid = $player->getXuid();
        $itemdata = [];
        $inventoryContents = $player->getInventory()->getContents();
        $armorContents = $player->getArmorInventory()->getContents();
        $offHandContents = $player->getOffHandInventory()->getContents();
        foreach ($inventoryContents as $slot => $item) {
            $itemId = $item->getVanillaName();
            $count = $item->getCount();
            $itemId = str_replace(" ", "_", $itemId);
            $itemId = strtoupper($itemId);
            if (self::isBlockItem($item)) {
                $itemId = "BLOCK_" . $itemId;
            }
            $itemdata["item"][$slot] = ["item" => $itemId, "count" => $count];
        }

        foreach ($armorContents as $slot => $item) {
            $itemId = $item->getVanillaName();
            $count = $item->getCount();
            $itemId = str_replace(" ", "_", $itemId);
            $itemId = strtoupper($itemId);
            $itemdata["item"][$slot + 36] = ["item" => $itemId, "count" => $count];
        }

        foreach ($offHandContents as $slot => $item) {
            $itemId = $item->getVanillaName();
            $count = $item->getCount();
            $itemId = str_replace(" ", "_", $itemId);
            $itemId = strtoupper($itemId);
            $itemdata["item"][$slot + 40] = ["item" => $itemId, "count" => $count];
        }

        if (!empty($itemdata)) {
            $config = PracticePlayer::$playerdata;
            $config[$xuid]["kits"][$kitname] = $itemdata;
            PracticePlayer::$playerdata = $config;
        }
    }

    private static function isBlockItem($item): bool
    {
        return $item instanceof ItemBlock;
    }
}
