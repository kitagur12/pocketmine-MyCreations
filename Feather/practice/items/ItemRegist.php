<?php

namespace practice\items;

use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeContentEntry;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemComponentPacketEntry;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\SingletonTrait;
use InvalidArgumentException;
use pocketmine\block\Block;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use ReflectionClass;
use pocketmine\nbt\tag\CompoundTag;

use function array_values;

class ItemRegist
{
    public static array $item = [];
    public static array $itemtables = [];

    public static $items;

    public static function setup()
    {
        //self::registerItem(Item::class, "test:item", "test:item","arrow");
    }

    public static function regist(): mixed
    {
        //var_dump(self::$item);
        return self::$item;
    }

    public static function registerItem(string $className, string $identifier, string $name, $texturename): void
    {
        $itemId = ItemTypeIds::newId();
        $item = new $className(new ItemIdentifier($itemId), $name);
        //var_dump($item);
        self::$items = $item;
        self::registerCustomItemMapping($identifier, $itemId);

        GlobalItemDataHandlers::getDeserializer()->map($identifier, fn() => clone $item);
        GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($identifier));

        StringToItemParser::getInstance()->register($identifier, fn() => clone $item);
        $default = CompoundTag::create()->setString("default", $texturename);
        $texture = CompoundTag::create()->setTag("textures", $default);
        $components = CompoundTag::create();
        $properties = CompoundTag::create()->setTag("minecraft:icon", $texture);
        $components->setTag("item_properties", $properties);
        self::$item[$identifier] = new ItemComponentPacketEntry(
            $identifier,
            new CacheableNbt(
                CompoundTag::create()
                    ->setInt("id", $itemId)
                    ->setString("name", $identifier)
                    ->setTag("components", $components)
            )
        );

        self::$itemtables[$identifier] = new ItemTypeEntry($identifier, $itemId, true);
        CreativeInventory::getInstance()->add($item);
    }

    public static function get(string $identifier, int $amount = 1): Item
    {
        $item = StringToItemParser::getInstance()->parse($identifier);
        return $item->setCount($amount);
    }

    private static function registerCustomItemMapping(string $identifier, int $itemId): void
    {
        $dictionary = TypeConverter::getInstance()->getItemTypeDictionary();
        $reflection = new ReflectionClass($dictionary);

        $intToString = $reflection->getProperty("intToStringIdMap");
        /** @var int[] $value */
        $value = $intToString->getValue($dictionary);
        $intToString->setValue($dictionary, $value + [$itemId => $identifier]);

        $stringToInt = $reflection->getProperty("stringToIntMap");
        /** @var int[] $value */
        $value = $stringToInt->getValue($dictionary);
        $stringToInt->setValue($dictionary, $value + [$identifier => $itemId]);
    }
}
