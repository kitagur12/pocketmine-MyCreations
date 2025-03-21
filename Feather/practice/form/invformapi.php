<?php

namespace practice\form;

use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Nameable;
use pocketmine\block\tile\Tile;
use pocketmine\block\VanillaBlocks;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use practice\utils\PlayerDataManager;
class invformapi
{

    private static array $blockpos = [];
    private static array $invdata = [];

    public static function resetInventory(Player $player): void
    {
        unset(self::$invdata[$player->getXuid()]);
        self::setItem($player, 0, -158, 1);
    }

    public static function setInventoryName(Player $player, $name): void
    {
        self::$invdata[$player->getXuid()]["name"] = $name;
    }

    public static function setItem(Player $player, $slot, $Item, $count): void
    {
        self::$invdata[$player->getXuid()][$slot] = [
            "Count" => $count,
            "Name" => (int) $Item,
        ];
    }

    public static function setLore(Player $player, $slot, $lore): void
    {
        self::$invdata[$player->getXuid()][$slot]["tag"]["display"]["Lore"] = "§r§f" . $lore;
    }

    public static function setMeta(Player $player, $slot, $meta): void
    {
        self::$invdata[$player->getXuid()][$slot]["meta"] = $meta;
    }

    public static function setEnchant(Player $player, $slot, $enchant, $level): void
    {
        self::$invdata[$player->getXuid()][$slot]["tag"]["ench"][] = ["id" => $enchant, "lvl" => $level];
    }

    public static function setName(Player $player, $slot, $name): void
    {
        self::$invdata[$player->getXuid()][$slot]["tag"]["display"]["Name"] = "§r§f" . $name;
    }

    public static function setInvtype(Player $player, $type, $invtypeid): void
    {
        self::$invdata[$player->getXuid()]["invtype"] = $type;
        self::$invdata[$player->getXuid()]["invtypeid"] = $invtypeid; //pocketmine\network\mcpe\protocol\types\inventory\WindowTypesクラス参照
    }

    public static function sendInvform(Player $player, int $formid): void
    {
        PlayerDataManager::setdata($player, "isformcall", "true");
        $itemdata = self::$invdata[$player->getXuid()];
        $itemdata["windowId"] = $formid;
        
        $pos = $player->getPosition()->add(0, -2, 0);
        if (isset($itemdata["invtype"])) {
            if (is_int($itemdata["invtype"])) {
                $blockid = $itemdata["invtype"];
                $invtypeid = $itemdata["invtypeid"];
            } else {
                $block = vanillaBlocks::{$itemdata["invtype"]}();
                $invtypeid = $itemdata["invtypeid"];
                $blockid = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
            }

        } else {
            $block = vanillaBlocks::CHEST();
            $invtypeid = 0;
            $blockid = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
        }
        
        $updateBlockPacket = UpdateBlockPacket::create(
            BlockPosition::fromVector3($pos),
            $blockid,
            UpdateBlockPacket::FLAG_NETWORK,
            UpdateBlockPacket::DATA_LAYER_NORMAL
        );
        $player->getNetworkSession()->sendDataPacket($updateBlockPacket);
        $packet = self::createChestWithItems($pos, $itemdata["name"] ?? "");
        $player->getNetworkSession()->sendDataPacket($packet);
        self::$blockpos[$player->getXuid()] = $pos;
        PlayerDataManager::setdata($player, "invformid", $itemdata["windowId"]);
        PlayerDataManager::setdata($player, "menuid", $itemdata["windowId"]);
        PlayerDataManager::setdata($player, "useinv", "true");
        $addActorPacket = ContainerOpenPacket::blockInv($formid, $invtypeid, BlockPosition::fromVector3($pos));
        $player->getNetworkSession()->sendDataPacket($addActorPacket);
        self::UpdateInvform($player, $formid);
    }

    public static function closeInvform(Player $player): void
    {
        PlayerDataManager::setdata($player, "isformcall", "false");
        if (is_int(PlayerDataManager::getdata($player, "invformid"))) {
            $windowId = PlayerDataManager::getdata($player, "invformid") ?? 0;
            $windowType = 0;
            $server = true;
            $packet = ContainerClosePacket::create($windowId, $windowType, $server);
            $player->getNetworkSession()->sendDataPacket($packet);
            PlayerDataManager::setdata($player, "useinv", "false");
            $world = $player->getWorld();
            if (isset(self::$blockpos[$player->getXuid()])) {
                $pos = self::$blockpos[$player->getXuid()];
                $block = $world->getBlock($pos);
                $updateBlockPacket = UpdateBlockPacket::create(
                    BlockPosition::fromVector3($pos),
                    TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId()),
                    UpdateBlockPacket::FLAG_NETWORK,
                    UpdateBlockPacket::DATA_LAYER_NORMAL
                );
                $player->getNetworkSession()->sendDataPacket($updateBlockPacket);
            }
        }
    }

    public static function UpdateInvform(Player $player, int $menuid): void
    {
        $items = self::$invdata[$player->getXuid()];
        unset($items["name"]);
        unset($items["windowId"]);
        if (isset($items["invtype"])) {
            unset($items["invtype"]);
            unset($items["invtypeid"]);
        }
        $windowId = PlayerDataManager::getdata($player, "invformid");
        for ($i = 0; $i < 27; $i++) {
            $itemid = -158;
            $FullContainerName = new FullContainerName($windowId);
            $oldItemStack = new ItemStack(0, 0, 0, 0, "");
            $oldItem = new ItemStackWrapper(0, $oldItemStack);
            $newItemStack = new ItemStack($itemid, 0, 1, 0, "");
            $newItem = new ItemStackWrapper(50, $newItemStack);
            $packet = InventorySlotPacket::create($windowId, $i, $FullContainerName, $oldItem, $newItem);
            $player->getNetworkSession()->sendDataPacket($packet);
        }
        foreach ($items as $slot => $item) {
            $itemid = $item["Name"];
            $FullContainerName = new FullContainerName($windowId);
            $oldItemStack = new ItemStack(0, 0, 0, 0, "");
            $oldItem = new ItemStackWrapper(0, $oldItemStack);
            if (isset($item["tag"])) {
                $nbt = self::createItemNBT($item);
                $extraData = new ItemStackExtraData($nbt, [], []);
                $serializer = PacketSerializer::encoder();
                $extraData->write($serializer);
                $binaryData = $serializer->getBuffer();
            } else {
                $binaryData = "";
            }

            $meta = $item["meta"] ?? 0;
            $newItemStack = new ItemStack($itemid, $meta, $item["Count"], 0, $binaryData);
            $newItem = new ItemStackWrapper(50, $newItemStack);
            $packet = InventorySlotPacket::create($windowId, $slot, $FullContainerName, $oldItem, $newItem);
            $player->getNetworkSession()->sendDataPacket($packet);
        }
        PlayerDataManager::setdata($player, "menuid", $menuid);
    }

    public static function createChestWithItems(Vector3 $pos, $name): BlockActorDataPacket
    {
        $nbtData = BlockActorDataPacket::create(
            BlockPosition::fromVector3($pos),
            new CacheableNbt(
                CompoundTag::create()
                    ->setString(Nameable::TAG_CUSTOM_NAME, $name)
                    ->setString(Tile::TAG_ID, "Chest")
                    ->setInt(Chest::TAG_PAIRX, (int) $pos->x)
                    ->setInt(Chest::TAG_PAIRZ, (int) $pos->z)
            )
        );

        return $nbtData;
    }

    public static function createItemNBT(array $item)
    {
        $tag = $item["tag"];
        $tagCompound = CompoundTag::create();
        if (isset($tag["RepairCost"])) {
            $tagCompound->setInt("RepairCost", $tag["RepairCost"]);
        }
        if (isset($tag["display"])) {
            $tagCompound2 = CompoundTag::create();
            if (isset($tag["display"]["Name"])) {
                $tagCompound2->setString("Name", $tag["display"]["Name"]);
            }
            if (isset($tag["display"]["Lore"])) {
                $loreListTag = new ListTag();
                $loreListTag->push(new StringTag($tag["display"]["Lore"]));
                $tagCompound2->setTag("Lore", $loreListTag);
            }
            $tagCompound->setTag("display", $tagCompound2);
        }
        if (isset($tag["ench"])) {
            $enchtag = CompoundTag::create();
            foreach ($tag["ench"] as $enchant) {
                $enchtag->setShort("id", $enchant["id"]);
                $enchtag->setShort("lvl", $enchant["lvl"]);
            }
            $enchListTag = new ListTag();
            $enchListTag->push($enchtag);
            $tagCompound->setTag("ench", $enchListTag);
        }
        return $tagCompound;
    }

    public static function convertToNBTFormat(array $items): array //ここ最初は使ってたけどアイテム置き換えできたから需要なくなったｗｗ
    {
        $nbtItems = [];
        unset($items["name"]);
        unset($items["windowId"]);
        foreach ($items as $slot => $item) {
            $itemTag = CompoundTag::create()
                ->setByte("Slot", $slot)
                ->setByte("Count", $item["Count"]);

            if (isset($item["Name"])) {
                $itemTag->setString("Name", $item["Name"]);
            }
            if (isset($item["display"]["Name"])) {
                $displayTag = CompoundTag::create()->setString("Name", $item["display"]["Name"]);
                $itemTag->setTag("display", $displayTag);
            }
            if (isset($item["Damage"])) {
                $itemTag->setShort("Damage", $item["Damage"]);
            }
            if (isset($item["tag"])) {
                $tag = $item["tag"];
                $tagCompound = CompoundTag::create();
                if (isset($tag["RepairCost"])) {
                    $tagCompound->setInt("RepairCost", $tag["RepairCost"]);
                }
                if (isset($tag["display"])) {
                    $tagCompound2 = CompoundTag::create();
                    if (isset($tag["display"]["Name"])) {
                        $tagCompound2->setString("Name", $tag["display"]["Name"]);
                    }
                    if (isset($tag["display"]["Lore"])) {
                        $loreListTag = new ListTag();
                        $loreListTag->push(new StringTag($tag["display"]["Lore"]));
                        $tagCompound2->setTag("Lore", $loreListTag);
                    }
                    $tagCompound->setTag("display", $tagCompound2);
                }
                if (isset($tag["ench"])) {
                    $enchants = [];
                    foreach ($tag["ench"] as $enchant) {
                        $enchants[] = CompoundTag::create()
                            ->setString("id", $enchant["id"]);
                    }
                    $tagCompound->setTag("ench", new ListTag([$enchants,]));
                }
                $itemTag->setTag("tag", $tagCompound);
            }

            $nbtItems[] = $itemTag;
        }

        return $nbtItems;
    }
}
