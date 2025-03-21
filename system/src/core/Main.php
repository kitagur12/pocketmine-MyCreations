<?php

namespace core;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\world\Position;
use pocketmine\world\format\Chunk;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandEnum;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\entity\EntityDataHelper;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\block\VanillaBlocks;

use function spl_object_id;
use SplQueue;

class Main extends PluginBase implements Listener
{

    private array $nameColors = [
        "kitagur1" => "§4",
        "Eozah" => "§g",
        "taikuraMCPvP" => "§c",
        "TKNatto" => "§g",
        "Ep59" => "§g",
        "Jrzly" => "§g",
        "BL41D" => "§g",
        "xKa0Mj" => "§g",
        "ShqrkMC" => "§g",
        "Stone51645" => "§b",
        "citrus mcf" => "§d",
        "kitagur1aa" => "§7[§4Owner§7] §r",
        "Eozahaa" => "§7[§gMod§7] §r",
        "taikuraMCPvPaa" => "§7[§cAdmin§7] §r",
        "TKNattoaa" => "§7[§gMod§7] §r",
        "Ep59aa" => "§7[§gMod§7] §r",
        "Jrzlyaa" => "§7[§gMod§7] §r",
        "BL41Daa" => "§7[§gMod§7] §r",
        "xKa0Mjaa" => "§7[§gMod§7] §r",
        "ShqrkMCaa" => "§7[§gMod§7] §r",
        "Stone51645aa" => "§7[§bFamous§7] §r",
        "citrus mcfaa" => "§7[§dYT§7] §r"

    ];

    private yaml $yaml;
    private array $lastChatTime = [];
    private array $clickQueues = [];

    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        self::disablecommand();
        $this->yaml = new yaml();

        EntityFactory::getInstance()->register(
            DeathEntity::class,
            function ($world, $nbt): DeathEntity {
                return new DeathEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
            },
            ['DeathEntity']
        );
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;
            public function __construct($plugin)
            {
                $this->plugin = $plugin;
            }

            public function onRun(): void
            {
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    $server = Server::getInstance();
                    $player->getHungerManager()->setFood(20);
                    $this->plugin->removeOldClicks($player);
                    $this->plugin->updateNameTag($player);
                    $this->plugin->updateActionBar($player);
                    $combattime = PlayerDataManager::getData($player, "lasthit");
                    if ($player->getPosition()->getY() <= -25) {
                        $combatplayername = PlayerDataManager::getData($player, "combatplayer");
                        if ($combatplayername !== null) {
                            $specificPlayer = $server->getPlayerByPrefix($combatplayername);
                            if ($specificPlayer !== null) {
                                $this->plugin->handlePlayerDeath($player, $specificPlayer);
                            } else {
                                $this->plugin->handlePlayerDeath($player, $player);
                            }
                        } else {
                            $this->plugin->handlePlayerDeath($player, $player);
                        }
                    }
                    if ($combattime !== null) {
                        $nowtime = time();
                        $timeDifference = abs((int)$combattime - $nowtime);
                        if ($timeDifference >= 10) {
                            $combatplayername = PlayerDataManager::getData($player, "combatplayer");
                            $specificPlayer = $server->getPlayerByPrefix($combatplayername);
                            if ($specificPlayer !== null) {
                                $plugin = $this->plugin;
                                $plugin::block($player, false);
                                $plugin::block($specificPlayer, false);
                                PlayerDataManager::setData($specificPlayer, "combat", "false");
                                PlayerDataManager::setData($specificPlayer, "combatplayer", "false");
                            }
                            PlayerDataManager::setData($player, "combat", "false");
                        }

                        if ($this->plugin->isCombat($player)) {
                            $yaml = new yaml();
                            $playerdata = $yaml->getPlayerData($player->getName());
                            $combatplayername = PlayerDataManager::getData($player, "combatplayer");
                            $specificPlayer = $server->getPlayerByPrefix($combatplayername);
                            if ($specificPlayer !== null) {
                                if ($playerdata["hide"]) {
                                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $players) {
                                        if ($players == $specificPlayer) {
                                            $player->showPlayer($specificPlayer);
                                        } else {
                                            $player->hidePlayer($players);
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ($this->plugin->getServer()->getOnlinePlayers() as $players) {
                                $player->showPlayer($players);
                            }
                        }
                    }
                }
            }
        }, 0);
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;
            public function __construct($plugin)
            {
                $this->plugin = $plugin;
            }
            public function onRun(): void
            {
                $this->plugin->getServer()->broadcastMessage("§l§g» §r§9Discord§r: §ghttps://discord.gg/uj4dyJ4kQn\n§l§g» §r§d招待§aしたい人は§bkitagur1§aに§6フレンド§aを送ってね！！");
            }
        }, 20 * 180);
    }

    public function updateActionBar($player): void
    {
        $playerData = $this->yaml->getPlayerData($player->getName());
        $combattime = PlayerDataManager::getData($player, "lasthit");
        if ($combattime !== null) {
            $nowtime = time();
            $timeDifference = abs((int)$combattime - $nowtime);
            $timeDifference = abs(10 - $timeDifference);
        } else {
            $timeDifference = 0;
        }


        $cps = self::getCPS($player);
        $ping = $player->getNetworkSession()->getPing();
        $kills = $playerData["kills"] ?? 0;
        $deaths = $playerData["deaths"] ?? 0;
        $rate = $playerData["rating"];
        $kdRatio = $deaths > 0 ? $kills / $deaths : $kills;
        $formattedKdRatio = number_format($kdRatio, 2, '.', '');
        $formattedKdRatio = rtrim(rtrim($formattedKdRatio, '0'), '.');
        $kd = "a";
        if (self::isCombat($player)) {
            $player->sendActionBarMessage(TextFormat::YELLOW . "Combat: " . $timeDifference . "s | CPS: $cps" . " | " . $ping . "ms");
        } else {
            $player->sendActionBarMessage(TextFormat::GREEN . "rate: " . $rate . " | K/D: " . $formattedKdRatio . " | " . $ping . "ms | CPS: $cps");
        }
    }

    public function updateNameTag($player): void
    {
        $name = $player->getName();
        $color = $this->nameColors[$name] ?? TextFormat::WHITE;
        $name = $color . $player->getName();

        $playerData = $this->yaml->getPlayerData($player->getName());
        $cps = self::getCPS($player);
        $ping = $player->getNetworkSession()->getPing();
        $rate = $playerData["rating"];
        if (self::isCombat($player)) {
            $player->setNameTag($name . "\n§eCPS: $cps" . " | " . $ping . "ms");
        } else {
            $player->setNameTag($name . "\n§arate: " . $rate . " | " . $ping . "ms");
        }
    }

    public function ondrop(PlayerDropItemEvent $event): void
    {
        $event->cancel();
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $tp = VanillaItems::NETHER_STAR();
        $tp->setCustomName("§r§f§bStage Teleport §8[Use]");
        $settings = VanillaItems::BOOK();
        $settings->setCustomName("§r§f§dSettings §8[Use]");
        if ($tp == $item) {
            if (!self::isCombat($player)) {
                $spawnPos = new Position(0, 0, 0, $player->getWorld());
                $damagerDistance = $player->getPosition()->distance($spawnPos);
                if ($damagerDistance <= 1500) {
                    $this->handleTeleportCommand($player, "default");
                } else {
                    $this->handleTeleportCommand($player, "midfight");
                }
            } else {
                $player->sendMessage("§l§c» §r§cyou are in Combat.");
                self::playSound("note.bass", 1, 1, $player);
            }
        }

        if ($settings == $item) {
            $yaml = new yaml();
            $playerdata = $yaml->getPlayerData($player->getName());
            formapi::reset($player);
            formapi::setType($player, "custom_form");
            formapi::setTitle($player, "Settings");
            formapi::addToggle($player, "Hide Non Opporent", $playerdata["hide"]);
            formapi::addToggle($player, "Night Vision", $playerdata["nv"]);
            formapi::addStepSlider($player, "Sword Slot", ["1", "2", "3", "4", "5", "6", "7"], (int) $playerdata["slot"]);
            formapi::sendForm($player, 1);
        }
    }

    public static function cpscount(): string
    {
        return "0";
    }

    public static function cpsqueue(): void {}

    public function decayClicks(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->removeOldClicks($player);
        }
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $color = $this->nameColors[$name] ?? TextFormat::WHITE;
        $name = $color . $player->getName();

        $player->setHealth($player->getMaxHealth());
        $player->setNameTag($name);

        $joinMessage = "§f[§a+§f] " . TextFormat::RESET . $name;
        $event->setJoinMessage($joinMessage);
        $player->sendMessage("§4§l» §r§gwelcome to\n§g§l»§cただの§4ひつま§cぶしのさーばー§7(§b?§7)§g«§r\n\n§dAntiCheat is Enabled!!
        ");
        PlayerDataManager::setData($player, "combat", "false");
        self::playSound("random.levelup", 1, 1, $player);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $server = Server::getInstance();
        $player = $event->getPlayer();
        $name = $player->getName();
        $color = $this->nameColors[$name] ?? TextFormat::WHITE;
        $name = $color . $player->getName();
        $combatplayername = PlayerDataManager::getData($player, "combat");
        if ($combatplayername == "true") {
            $combatplayername = PlayerDataManager::getData($player, "combatplayer");
            $specificPlayer = $server->getPlayerByPrefix($combatplayername);
            $this->handlePlayerDeath($player, $specificPlayer);
            PlayerDataManager::setData($specificPlayer, "combat", "false");
            PlayerDataManager::setData($specificPlayer, "combat", "false");
        }
        PlayerDataManager::removePlayerData($player);
        $quitMessage = "§f[§c-§f] " . TextFormat::RESET . $name;
        $event->setQuitMessage($quitMessage);
    }

    public function onlogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        self::effectUpdate($player);
        $destination = new Vector3(0.5, 2, 0.5);
        $this->giveItems($player);
        $player->teleport($destination);
    }

    public function giveItems(Player $player): void
    {
        $player->getInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $inventory = $player->getInventory();
        $armorInventory = $player->getArmorInventory();

        $diamondSword = VanillaItems::DIAMOND_SWORD();
        $tp = VanillaItems::NETHER_STAR();
        $settings = VanillaItems::BOOK();
        $diamondHelmet = VanillaItems::DIAMOND_HELMET();
        $diamondChestplate = VanillaItems::DIAMOND_CHESTPLATE();
        $diamondLeggings = VanillaItems::DIAMOND_LEGGINGS();
        $diamondBoots = VanillaItems::DIAMOND_BOOTS();

        $this->clearAndRemoveItems($inventory, $diamondSword);
        $this->clearAndRemoveItems($inventory, $diamondHelmet);
        $this->clearAndRemoveItems($inventory, $diamondChestplate);
        $this->clearAndRemoveItems($inventory, $diamondLeggings);
        $this->clearAndRemoveItems($inventory, $diamondBoots);

        $diamondSword->setUnbreakable();
        $diamondHelmet->setUnbreakable();
        $diamondChestplate->setUnbreakable();
        $diamondLeggings->setUnbreakable();
        $diamondBoots->setUnbreakable();
        $tp->setCustomName("§r§f§bStage Teleport §8[Use]");
        $settings->setCustomName("§r§f§dSettings §8[Use]");

        $yaml = new yaml();
        $playerData = $yaml->getPlayerData($player->getName());

        $inventory->setItem((int) $playerData["slot"], $diamondSword);
        $inventory->setItem(7, $tp);
        $inventory->setItem(8, $settings);
        $armorInventory->setHelmet($diamondHelmet);
        $armorInventory->setChestplate($diamondChestplate);
        $armorInventory->setLeggings($diamondLeggings);
        $armorInventory->setBoots($diamondBoots);
    }

    private function clearAndRemoveItems($inventory, Item $item): void
    {
        $itemsToRemove = [];
        foreach ($inventory->getContents() as $invItem) {
            if ($invItem->getName() === $item->getName()) {
                $itemsToRemove[] = $invItem;
            }
        }

        foreach ($itemsToRemove as $itemToRemove) {
            $inventory->removeItem($itemToRemove);
        }
    }

    function containsKey(array $array, string $substring): bool
    {
        foreach ($array as $key => $value) {
            if (strpos($key, $substring) !== false) {
                return true;
            }
        }
        return false;
    }

    public function handleDataPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if ($player !== null) {
            if ($packet instanceof PlayerAuthInputPacket) {
                $flags = $packet->getInputFlags();
                if ($flags->get(PlayerAuthInputFlags::MISSED_SWING)) {
                    $this->registerClick($player);
                }
            }
            if ($packet instanceof ModalFormResponsePacket) {
                $formdata = $packet->formData;
                if ($formdata == null) {
                    return;
                }
                $formdata = json_decode($formdata, true);
                $yaml = new yaml();
                $playerdata = $yaml->getPlayerData($player->getName());
                $playerdata["hide"] = $formdata[0];
                $playerdata["nv"] = $formdata[1];
                $playerdata["slot"] = $formdata[2];
                $yaml->dataFile->set($player->getName(), $playerdata);
                $yaml->dataFile->save();
                self::effectUpdate($player);
            }
        }

        if ($packet instanceof InventoryTransactionPacket) {
            $trData = $packet->trData;

            if ($trData instanceof UseItemOnEntityTransactionData) {
                $actionType = $trData->getActionType();
                if ($actionType === 1) {
                    $player = $event->getOrigin()->getPlayer();
                    if ($player === null) {
                        return;
                    }

                    $this->registerClick($player);
                }
            }
        }
    }




    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $message = $event->getMessage();

        $currentTime = microtime(true);
        $color = $this->nameColors;
        if (!self::containsKey($color, $name)) {
            if (isset($this->lastChatTime[$name])) {
                $timeSinceLastChat = $currentTime - $this->lastChatTime[$name];
                if ($timeSinceLastChat < 1) {
                    $player->sendMessage(TextFormat::RED . "チャットは1秒ごとにしか送信できません。");
                    $event->cancel();
                    return;
                }
            }
        }

        $this->lastChatTime[$name] = $currentTime;

        $event->cancel();
        $color = $this->nameColors[$name] ?? TextFormat::WHITE;
        $rank = $this->nameColors[$name . "aa"] ?? TextFormat::WHITE;
        $formattedMessage = $rank . $color . $name . TextFormat::RESET . " §7§l» §r§f" . $message;
        $this->getLogger()->info($formattedMessage);
        foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->sendMessage($formattedMessage);
        }
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $server = Server::getInstance();
            $nowtime = time();
            $combattime = PlayerDataManager::getData($entity, "lasthit");
            $timeDifference = abs((int)$combattime - $nowtime);
            $timeDifference = abs((int)$combattime - $nowtime);
            $damager = $event instanceof EntityDamageByEntityEvent ? $event->getDamager() : null;
            if ($damager instanceof Player &&  $event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                $spawnPos = new Position(0, 0, 0, $damager->getWorld());
                $damagerDistance = $damager->getPosition()->distance($spawnPos);
                $entityDistance = $entity->getPosition()->distance($spawnPos);
                $dac = self::isCombat($damager);
                $atc = self::isCombat($entity);
                if (($damagerDistance <= 5 || $entityDistance <= 5) && !($dac || $atc)) {
                    $event->cancel();
                } else {
                    if (!combat::hit($damager, $entity)) {
                        $event->cancel();
                    } else {
                        if ($damagerDistance <= 1500 || $entityDistance <= 1500) {
                            self::block($entity);
                            self::block($damager);
                            $base = $event->getBaseDamage();
                            $event->cancel();
                            $newEvent = new EntityDamageByEntityEvent(
                                $damager,
                                $entity,
                                EntityDamageEvent::CAUSE_CUSTOM,
                                $base,
                            );
                            $entity->attack($newEvent);
                        } else {
                            $event->setBaseDamage($event->getBaseDamage() / 1.1);
                            if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
                                $event->cancel();
                                $this->handlePlayerDeath($entity, $damager);
                            }
                        }
                    }
                }
            } elseif ($damager instanceof Player &&  $event->getCause() == EntityDamageEvent::CAUSE_CUSTOM) {
                $final = $event->getFinalDamage();
                $event->setAttackCooldown(10);
                $final = floor($final);
                $hp = $entity->getHealth() - $final;
                if ($hp <= 0) {
                    $event->cancel();
                    $this->handlePlayerDeath($entity, $damager);
                } else {
                    $entity->setHealth($hp);
                }
                $entity->setHealth($entity->getHealth() + $event->getFinalDamage());
            } else {
                $event->cancel();
            }
        }
    }

    public function isCombat($player): bool
    {
        $bool = PlayerDataManager::getData($player, "combat");
        if ($bool === "true") {
            $bool = true;
        } else {
            $bool = false;
        }
        return $bool;
    }

    public function handlePlayerDeath(Player $player, ?Player $damager): void
    {
        $globalMessage = TextFormat::RED . "§l§g» §r§c" . $player->getName() . " §7was killed by §d" . $damager->getName();
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            self::broadcastDeathAnimation($player, $onlinePlayer);
            if ($onlinePlayer !== $player && $onlinePlayer !== $damager) {
                $onlinePlayer->sendMessage($globalMessage);
            }
            $onlinePlayer->hidePlayer($player);
        }
        $player->setHealth($player->getMaxHealth());
        $world = $player->getWorld();
        self::sendLightning($damager, $player->getPosition());
        self::playSound("cauldron.explode", 2, 1, $damager);
        $player->teleport(new Position(0, 2, 0, $world));
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->showPlayer($player);
        }
        self::block($player, false);
        if ($damager !== null) {
            self::block($damager, false);
            $bool = PlayerDataManager::getData($player, "combat");
            $damager->setHealth($damager->getMaxHealth());
            if ($bool === "true") {
                $this->giveItems($player);
                $this->giveItems($damager);

                PlayerDataManager::setData($player, "combat", "false");
                PlayerDataManager::setData($damager, "combat", "false");
                PlayerDataManager::setData($player, "combatplayer", "false");
                PlayerDataManager::setData($damager, "combatplayer", "false");
                self::playSound("random.orb", 1, 1, $damager);

                $playerName = $player->getName();
                $damagerName = $damager->getName();

                $loserData = $this->yaml->getPlayerData($playerName);
                $winnerData = $this->yaml->getPlayerData($damagerName);

                $celo = self::elocalc($winnerData["rating"], $loserData["rating"]);
                $playerData = $this->yaml->getPlayerData($playerName);
                $playerData["kills"] = $winnerData["kills"];
                $playerData["deaths"] = $winnerData["deaths"] + 1;
                $playerData["rating"] = ceil($winnerData["rating"] - $celo);
                $playerData["nv"] = $playerData["nv"];
                $playerData["slot"] = $playerData["slot"];
                $playerData = $this->yaml->getPlayerData($damagerName);
                $playerData["kills"] = $winnerData["kills"] + 1;
                $playerData["deaths"] = $winnerData["deaths"];
                $playerData["rating"] = ceil($winnerData["rating"] + $celo);
                $playerData["nv"] = $playerData["nv"];
                $playerData["slot"] = $playerData["slot"];
                $player->sendMessage(TextFormat::RED . "§l§b» §r§c" . $player->getName() . " §7was killed by §d" . $damager->getName() . " §7[§c-§d" . $celo . "§7]");
                $damager->sendMessage(TextFormat::RED . "§l§d» §r§c" . $player->getName() . " §7was killed by §d" . $damager->getName() . " §7[§a+§d" . $celo . "§7]");
            }
        } else {
            PlayerDataManager::setData($player, "combat", "false");
        }
    }

    public static function block($player, $hide = true)
    {
        if ($hide) {
            $block = vanillaBlocks::AIR();
            $blockid = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
            for ($x = -4; $x <= 4; $x++) {
                for ($y = 1; $y <= 6; $y++) {
                    for ($z = -4; $z <= 4; $z++) {
                        $updateBlockPacket = UpdateBlockPacket::create(
                            new BlockPosition($x, $y, $z),
                            $blockid,
                            UpdateBlockPacket::FLAG_NETWORK,
                            UpdateBlockPacket::DATA_LAYER_NORMAL
                        );
                        $player->getNetworkSession()->sendDataPacket($updateBlockPacket);
                    }
                }
            }
        } else {
            $world = $player->getWorld();
            for ($x = -4; $x <= 4; $x++) {
                for ($y = 1; $y <= 6; $y++) {
                    for ($z = -4; $z <= 4; $z++) {
                        $block = $world->getBlock(new Vector3($x, $y, $z),);
                        $blockid = TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId($block->getStateId());
                        
                        $updateBlockPacket = UpdateBlockPacket::create(
                            new BlockPosition($x, $y, $z),
                            $blockid,
                            UpdateBlockPacket::FLAG_NETWORK,
                            UpdateBlockPacket::DATA_LAYER_NORMAL
                        );
                        $player->getNetworkSession()->sendDataPacket($updateBlockPacket);
                    }
                }
            }
        }
    }

    public function getCPS(Player $player): int
    {
        $playerName = $player->getName();

        if (!isset($this->clickQueues[$playerName])) {
            return 0;
        }

        return $this->clickQueues[$playerName]->count();
    }

    public function registerClick(Player $player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset($this->clickQueues[$playerName])) {
            $this->clickQueues[$playerName] = new SplQueue();
        }

        $this->clickQueues[$playerName]->enqueue($currentTime);

        $this->removeOldClicks($player);
    }

    public function removeOldClicks(Player $player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset($this->clickQueues[$playerName])) {
            return;
        }

        while (!$this->clickQueues[$playerName]->isEmpty()) {
            $clickTime = $this->clickQueues[$playerName]->bottom();
            if (($currentTime - $clickTime) >= 1.0) {
                $this->clickQueues[$playerName]->dequeue();
            } else {
                break;
            }
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $knockback = 0.4;
        $event->setKnockBack($knockback);
    }



    public static function effectUpdate($player): void
    {
        $yaml = new yaml();
        $playerdata = $yaml->getPlayerData($player->getName());
        $effectManager = $player->getEffects();
        if ($playerdata["nv"]) {
            $nightVision = new EffectInstance(VanillaEffects::NIGHT_VISION(), 999999999, 0, false);
            $effectManager->add($nightVision);
        } else {
            $effectManager->remove(VanillaEffects::NIGHT_VISION());
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command->getName()) {
            case "night_vision":
                if ($sender instanceof Player) {
                    $yaml = new yaml();
                    $playerdata = $yaml->getPlayerData($sender->getName());
                    if ($playerdata["nv"]) {
                        $sender->sendMessage(TextFormat::DARK_PURPLE . "§l§g» §r§dNight vision §cremoved!");
                        self::playSound("note.bass", 1, 1, $sender);
                    } else {
                        $sender->sendMessage(TextFormat::DARK_PURPLE . "§l§g» §r§dNight vision §aadded!\n" . TextFormat::RESET . "§eTo revert, run the §d/nv §ecommand again.");
                        self::playSound("random.orb", 1, 1, $sender);
                    }
                    $playerdata["nv"] = !$playerdata["nv"];
                    $yaml->dataFile->set($sender->getName(), $playerdata);
                    $yaml->dataFile->save();
                    self::effectUpdate($sender);
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            case "stage":
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        if (self::isCombat($sender)) {
                            $sender->sendMessage("§l§c» §r§cyou are in Combat.");
                            self::playSound("note.bass", 1, 1, $sender);
                        } else {
                            $this->handleTeleportCommand($sender, $args[0]);
                        }
                    } else {
                        $sender->sendMessage("§l§c» §r§cUsage: /tp [number]");
                        self::playSound("note.bass", 1, 1, $sender);
                    }
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            case "ff":
                if ($sender instanceof Player) {
                    $server = Server::getInstance();
                    $combatplayername = PlayerDataManager::getData($sender, "combat");
                    if ($combatplayername == "true") {
                        $combatplayername = PlayerDataManager::getData($sender, "combatplayer");
                        $specificPlayer = $server->getPlayerByPrefix($combatplayername);
                        $this->handlePlayerDeath($sender, $specificPlayer);
                        PlayerDataManager::setData($specificPlayer, "combat", "false");
                        PlayerDataManager::setData($specificPlayer, "combat", "false");
                    }
                    $destination = new Vector3(0.5, 2, 0.5);
                    $this->giveItems($sender);
                    $sender->teleport($destination);
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            case "playerdata":
                if ($sender instanceof Player) {
                    $server = Server::getInstance();
                    if (isset($args[0])) {
                        $data = $this->yaml->getPlayerData($args[0]);
                        $name = $args[0];
                    } else {
                        $data = $this->yaml->getPlayerData($sender->getName());
                        $name = $sender->getName();
                    }
                    if ($data["kills"] == "0" && $data["deaths"] == "0") {
                        $sender->sendMessage("§l§c» §r§cCan't data found");
                        self::playSound("note.bass", 1, 1, $sender);
                    } else {
                        $sender->sendMessage("§g" . $name . "§a's status\n§aKill: §c" . $data["kills"] . "\n§aDeath: §b" . $data["deaths"] . "\n§aRate: §g" . $data["rating"]);
                        self::playSound("random.orb", 1, 1, $sender);
                    }
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            case "ping":
                if ($sender instanceof Player) {
                    $ok = true;
                    $tps = $this->getServer()->getTicksPerSecond();
                    if (isset($args[0])) {
                        $server = Server::getInstance();
                        $selector = $server->getPlayerByPrefix($args[0]);
                        if ($selector !== null) {
                            $ping = $selector->getNetworkSession()->getPing();
                            $text = $selector->getName();
                        } else {
                            $sender->sendMessage("§l§c» §r§cCan't Player found");
                            self::playSound("note.bass", 1, 1, $sender);
                            $ok = false;
                        }
                    } else {
                        $ping = $sender->getNetworkSession()->getPing();
                        $text = "Your";
                    }
                    if ($ok) {
                        $sender->sendMessage(TextFormat::GREEN . "§a$text ping is §d" . $ping . " ms\n§bserver tps is §a" . $tps);
                        self::playSound("random.orb", 1, 1, $sender);
                    }
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            case "plv":
                if ($sender instanceof Player) {
                    if (isset($args[0])) {
                        $server = Server::getInstance();
                        $player = $server->getPlayerByPrefix($args[0]);
                        if ($player !== null) {
                            $complay = PlayerDataManager::getData($player, "combatplayer");
                            $iscom = PlayerDataManager::getData($player, "combat");
                            $iscom = PlayerDataManager::getData($player, "combat");
                            $lh = PlayerDataManager::getData($player, "lasthit");
                            $nowtime = time();
                            $time = abs((int)$lh - $nowtime);
                            $sender->sendMessage(TextFormat::GREEN . "iscombat: " . $iscom . " combatplayer: " . $complay . " lasthit: " . $lh . " time: " . $time);
                        } else {
                            $sender->sendMessage("§l§c» §r§cCan't data found");
                        }
                    }
                } else {
                    $sender->sendMessage("This command can only be used in-game.");
                }
                return true;

            default:
                return false;
        }
    }

    public static function playSound(string $name, int $vol, int $pitch, Player $player = null, array $recipents = []): void
    {
        $pk = new PlaySoundPacket();
        $pk->soundName = $name;
        $pk->x = $player->getLocation()->getX();
        $pk->y = $player->getLocation()->getY();
        $pk->z = $player->getLocation()->getZ();
        $pk->volume = $vol;
        $pk->pitch = $pitch;
        $player->getNetworkSession()?->sendDataPacket($pk);
    }

    private function handleTeleportCommand(Player $player, $id): void
    {
        $positions = [
            "midfight" => new Vector3(0.5, 2, 0.5),
            "default" => new Vector3(3000.5, 2, 0.5),
        ];

        if (isset($positions[$id])) {
            $player->teleport($positions[$id]);
            self::playSound("mob.shulker.teleport", 2, 1, $player);
        } else {
            $player->sendMessage(TextFormat::RED . "Invalid stage id");
            self::playSound("note.bass", 1, 1, $player);
        }
    }

    public static function elocalc($killer, $death): int
    {
        $killer = (int)$killer;
        $death = (int)$death;
        $E_win = 1 / (1 + pow(10, ($death - $killer) / 400));

        $rate = 32 * (1 - $E_win);

        $rate = round($rate);
        return $rate;
    }

    public static function disablecommand(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->unregister($commandMap->getCommand("checkperm"));
        $commandMap->unregister($commandMap->getCommand("defaultgamemode"));
        $commandMap->unregister($commandMap->getCommand("difficulty"));
        $commandMap->unregister($commandMap->getCommand("dumpmemory"));
        $commandMap->unregister($commandMap->getCommand("enchant"));
        $commandMap->unregister($commandMap->getCommand("extractplugin"));
        $commandMap->unregister($commandMap->getCommand("gc"));
        $commandMap->unregister($commandMap->getCommand("genplugin"));
        $commandMap->unregister($commandMap->getCommand("handlers"));
        $commandMap->unregister($commandMap->getCommand("handlersbyplugin"));
        $commandMap->unregister($commandMap->getCommand("listperms"));
        $commandMap->unregister($commandMap->getCommand("makeplugin"));
        $commandMap->unregister($commandMap->getCommand("me"));
        $commandMap->unregister($commandMap->getCommand("particle"));
        $commandMap->unregister($commandMap->getCommand("plugins"));
        $commandMap->unregister($commandMap->getCommand("save-all"));
        $commandMap->unregister($commandMap->getCommand("save-off"));
        $commandMap->unregister($commandMap->getCommand("save-on"));
        $commandMap->unregister($commandMap->getCommand("seed"));
        $commandMap->unregister($commandMap->getCommand("setworldspawn"));
        $commandMap->unregister($commandMap->getCommand("spawnpoint"));
        $commandMap->unregister($commandMap->getCommand("tell"));
        $commandMap->unregister($commandMap->getCommand("timings"));
        $commandMap->unregister($commandMap->getCommand("version"));
        $commandMap->unregister($commandMap->getCommand("whitelist"));
        $commandMap->unregister($commandMap->getCommand("status"));
        $commandMap->unregister($commandMap->getCommand("transferserver"));
        $commandMap->unregister($commandMap->getCommand("kill"));
        $commandMap->unregister($commandMap->getCommand("clear"));
        $commandMap->unregister($commandMap->getCommand("help"));
    }

    public function onSend(DataPacketSendEvent $event)
    {
        foreach ($event->getPackets() as $pk) {
            if ($pk instanceof AvailableCommandsPacket) {
                foreach ($pk->commandData as $commandName => $commandData) {

                    if ($commandName === "op") {
                        $commands = [
                            "command1" => "<target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "ban") {
                        $commands = [
                            "command1" => "<target:player> <string:reason>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    if ($commandName === "gamemode") {
                        $commands = [
                            "command1" => "adventure <target:player>",
                            "command2" => "survival <target:player>",
                            "command3" => "creative <target:player>",
                            "command4" => "spectator <target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "tp") {
                        $commands = [
                            "command1" => "<target:player> <target:player>",
                            "command2" => "<target:player> <int:x> <int:y> <int:z> <int:rotx> <int:roty>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }

                    if ($commandName === "stage") {
                        $commands = [
                            "command1" => "default",
                            "command2" => "midfight",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    if ($commandName === "ping") {
                        $commands = [
                            "command1" => "<target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                    if ($commandName === "playerdata") {
                        $commands = [
                            "command1" => "<target:player>",
                        ];
                        $result = self::subcommand($commands);
                        $commandData->overloads = $result;
                    }
                }
            }
        }
        $event->setPackets([$pk]);
    }

    private static function subcommand(array $commands): array
    {
        $overloads = [];
        $typeMapping = [
            "target" => AvailableCommandsPacket::ARG_TYPE_TARGET,
            "string" => AvailableCommandsPacket::ARG_TYPE_STRING,
            "int" => AvailableCommandsPacket::ARG_TYPE_INT,
            "float" => AvailableCommandsPacket::ARG_TYPE_FLOAT,
            "value" => AvailableCommandsPacket::ARG_TYPE_VALUE,
            "op" => AvailableCommandsPacket::ARG_TYPE_OPERATOR,
            "rawtext" => AvailableCommandsPacket::ARG_TYPE_RAWTEXT,
            "json" => AvailableCommandsPacket::ARG_TYPE_JSON,
            "block_states" => AvailableCommandsPacket::ARG_TYPE_BLOCK_STATES,
            "command" => AvailableCommandsPacket::ARG_TYPE_COMMAND,
            "filepath" => AvailableCommandsPacket::ARG_TYPE_FILEPATH,
            "message" => AvailableCommandsPacket::ARG_TYPE_MESSAGE,
        ];

        foreach ($commands as $command) {
            $commandParts = explode(" ", $command);
            $params = [];
            foreach ($commandParts as $part) {
                if (preg_match('/^<([^:>]+):([^>]+)>$/', $part, $matches)) {
                    [$full, $prefix, $name] = $matches;
                    if (isset($typeMapping[$prefix])) {
                        if (!is_int($typeMapping[$prefix])) {
                            $sb = CommandParameter::enum($name, new CommandEnum("$prefix", $typeMapping[$prefix]), 0);
                        } elseif (isset($typeMapping[$prefix])) {
                            $sb = CommandParameter::standard($name, $typeMapping[$prefix], 0, true);
                        }
                    } else {
                        //print("Unknown prefix: $prefix\n");
                    }
                } else {
                    $enum = new CommandEnum($part, [$part]);
                    $sb = CommandParameter::enum($part, $enum, 0);
                }
                $params[] = $sb;
            }
            $overloads[] = new CommandOverload(false, $params);
        }

        return $overloads;
    }

    public static function sendLightning($player, $position, $bright = false): void
    {
        if ($bright) {
            $position = new Position(0, 999999999999999, 0, $position->getWorld());
        }
        $uniqueId = mt_rand(1000, 999999);
        $runtimeId = mt_rand(1000, 999999);
        $metadataCollection = new EntityMetadataCollection();
        $metadataCollection->setFloat(0, 1.8);
        $packet = new AddActorPacket();
        $packet->actorUniqueId = $uniqueId;
        $packet->actorRuntimeId = $runtimeId;
        $packet->type = "minecraft:lightning_bolt";
        $packet->position = $position;
        $packet->motion = null;
        $packet->pitch = 0.0;
        $packet->yaw = 0.0;
        $packet->headYaw = 0.0;
        $packet->bodyYaw = 0.0;
        $packet->attributes = [];
        $packet->metadata = [];
        $packet->syncedProperties = new PropertySyncData([], []);
        $packet->links = [];
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public static function broadcastDeathAnimation($player, $killer): void
    {
        $playerLocation = $player->getLocation();
        $killerLocation = $killer->getLocation();

        if ($playerLocation->getY() > 0) {
            $dymmyskin = $player->getSkin();
            if ($dymmyskin == null) {
                $skinData = str_repeat("\x00", 64 * 32 * 4);
                $dymmyskin = new Skin("0", $skinData);
            }
            $deathEntity = new DeathEntity($playerLocation, $dymmyskin);
            $deathEntity->spawnTo($killer);
            $deathEntity->knockBack($playerLocation->getX() - $killerLocation->getX(), $playerLocation->getZ() - $killerLocation->getZ());
            $deathEntity->kill();
        };
    }
}

class PlayerDataManager
{
    private static array $playerData = [];

    public static function setData($player, string $key, $value): void
    {
        $uuid = self::uuid($player);
        if (!isset(self::$playerData[$uuid])) {
            self::$playerData[$uuid] = [];
        }
        self::$playerData[$uuid][$key] = $value;
    }

    public static function getData($player, string $key)
    {
        $uuid = self::uuid($player);
        return self::$playerData[$uuid][$key] ?? null;
    }

    public static function removePlayerData($player): void
    {
        $uuid = self::uuid($player);
        unset(self::$playerData[$uuid]);
    }

    public static function hasData($player, string $key): bool
    {
        $uuid = self::uuid($player);
        return isset(self::$playerData[$uuid][$key]);
    }

    public static function uuid($player): string
    {
        if (is_string($player)) {
            return $player;
        } else {
            $uuid = $player->getxuid();
            return $uuid;
        }
    }
}

class combat
{

    public static function isCombat($player): bool
    {
        $bool = PlayerDataManager::getData($player, "combat");
        if ($bool === "true") {
            $bool = true;
        } else {
            $bool = false;
        }
        return $bool;
    }

    public static function hit($damager, $attacker): bool
    {
        $bool = false;
        $dac = self::isCombat($damager);
        $atc = self::isCombat($attacker);
        $time = microtime(true);
        if (!$dac && !$atc) {
            $bool = true;
            PlayerDataManager::setData($attacker, "combat", "true");
            PlayerDataManager::setData($damager, "combat", "true");
            PlayerDataManager::setData($attacker, "lasthit", "$time");
            PlayerDataManager::setData($damager, "lasthit", "$time");
            $dan = $damager->getName();
            $atn = $attacker->getName();
            PlayerDataManager::setData($attacker, "combatplayer", "$dan");
            PlayerDataManager::setData($damager, "combatplayer", "$atn");
        }
        if ($dac && $atc) {
            $time = microtime(true);
            $atn = PlayerDataManager::getData($attacker, "combatplayer");
            $dan = PlayerDataManager::getData($damager, "combatplayer");
            if ($dan == $attacker->getName() || $atn == $damager->getName()) {
                $bool = true;
                PlayerDataManager::setData($attacker, "lasthit", "$time");
                PlayerDataManager::setData($damager, "lasthit", "$time");
            }
        }
        return $bool;
    }
}

class yaml
{
    public $dataFile;
    private array $defaultData = [
        "kills" => 0,
        "deaths" => 0,
        "rating" => 1000,
        "hide" => true,
        "nv" => false,
        "slot" => 0
    ];

    public function __construct()
    {
        $filePath = $this->getDataFilePath();
        if (!file_exists($filePath)) {
            $result = file_put_contents($filePath, yaml_emit([]));
            if ($result === false) {
                throw new \RuntimeException("Failed to create the file: $filePath");
            }
        }
        $this->dataFile = new \pocketmine\utils\Config($filePath, \pocketmine\utils\Config::YAML);
    }

    public function getPlayerData(string $playerName): array
    {
        return $this->dataFile->get($playerName, $this->defaultData);
    }

    public function setPlayerData(string $playerName, int $kills, int $deaths, float $rating): void
    {
        $playerData = $this->getPlayerData($playerName);
        $playerData["kills"] = $kills;
        $playerData["deaths"] = $deaths;
        $playerData["rating"] = ceil($rating);
        $playerData["nv"] = $playerData["nv"];
        $playerData["slot"] = $playerData["slot"];
        $this->dataFile->set($playerName, $playerData);
        $this->dataFile->save();
    }

    private function getDataFilePath(): string
    {
        return __DIR__ . "/player_data.yml";
    }
}

class DeathEntity extends Human
{

    public function __construct($location, $skin, $nbt = null)
    {
        $dymmyskin = $skin;
        if ($skin == null) {
            $skinData = str_repeat("\x00", 64 * 32 * 4);
            $dymmyskin = new Skin("0", $skinData);
        }
        $this->skin = $skin ?? $dymmyskin;
        parent::__construct($location, $dymmyskin);
    }
    public function spawnTo(Player $player): void
    {
        $id = spl_object_id($player);
        if (!isset($this->hasSpawned[$id]) && $player->getWorld() === $this->getWorld() && $player->hasReceivedChunk($this->location->getFloorX() >> Chunk::COORD_BIT_SIZE, $this->location->getFloorZ() >> Chunk::COORD_BIT_SIZE)) {
            $this->hasSpawned[$id] = $player;
            $this->sendSpawnPacket($player);
        }
    }

    public function spawnToSpecifyPlayer(Player $player): void {}
}
