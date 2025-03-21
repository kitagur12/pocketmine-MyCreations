<?php

namespace practice;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
//--------------------------------------------------------------
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\FarmlandHydrationChangeEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityPreExplodeEvent;
//--------------------------------------------------------------
use practice\utils\task\AlwaysTask;
use practice\items\ItemRegist;
use practice\command\commandLoader;
use practice\player\PracticePlayer;
use practice\player\PlayerItem;
use practice\utils\yaml;
use practice\utils\sql;
use practice\player\PlayerConfig;
use practice\player\PlayerQueue;
use practice\arena\WorldManaer;
use practice\utils\CrashSender;
use practice\entity\EntityHandler;
use practice\handler\method\EntityDamageHandler;
use practice\handler\method\BlockBreakEventHandler;
use practice\handler\method\BlockPlaceEventHandler;
use practice\handler\method\DataPacketReceiveEventHandler;
use practice\handler\method\DataPacketSendEventHandler;
use practice\handler\method\PlayerDropItemEventHandler;
use practice\handler\method\PlayerExhaustEventHandler;
use practice\handler\method\EntityTeleportEventHandler;
use practice\handler\method\BlockUpdateEventHandler;
use practice\handler\method\EntityTrampleFarmlandEventHandler;
use practice\handler\method\PlayerInteractEventHandler;
use practice\handler\method\ProjectileHitEventHandler;
use practice\handler\method\PlayerBucketEventHandler;
use practice\handler\method\BlockFormEventHandler;
use practice\handler\method\EntityDamageByEntityEventHandler;
use practice\handler\method\EntityDamageByChildEntityEventHandler;
use practice\handler\method\PlayerChatEventHandler;
use practice\handler\method\PlayerItemConsumeEventHandler;
use practice\handler\method\ProjectileLaunchEventHandler;
use practice\handler\method\ProjectileHitEntityEventHandler;
use practice\handler\method\InventoryTransactionEventHandler;
use practice\handler\method\PlayerMoveEventHandler;
use practice\handler\method\EntitySpawnEventHandler;
use practice\handler\method\EntityMotionEventHandler;
//--------------------------------------------------------------

class practice extends PluginBase
{

    private static $instance;

    public function onLoad(): void
    {
        $message = "§dPractice Core is Loading now..";
        $this->getLogger()->info($message);
        self::$instance = $this;
    }

    public function onEnable(): void
    {
        $this->getServer()->getNetwork()->setName("§l§cFeather§r§f Practice");
        $this->getServer()->getPluginManager()->registerEvents(new Loader($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new AlwaysTask(), 0);
    }

    public static function CreateTask($class, $item, $time): void
    {
        self::$instance->getScheduler()->scheduleDelayedTask(
            new $class($item),
            $time
        );
    }

    public static function CreateAsyncTask($class, $item): void
    {
        self::$instance->getServer()->getAsyncPool()->submitTask(
            new $class($item),
        );
    }
}

class Loader implements Listener
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->setup();
    }

    public function setup(): void
    {
        $this->setPluginInstance();
        CrashSender::check();
        commandLoader::loadcommand();
        yaml::setup();
        sql::login();
        PlayerConfig::createDefaltData();
        WorldManaer::setup();
        PlayerQueue::setup();
        ItemRegist::setup();
        PracticePlayer::setLangText();
        EntityHandler::regist();
    }

    public function setPluginInstance(): void
    {
        yaml::setPluginInstance($this->plugin);
    }

    public function onlogin(PlayerLoginEvent $event)
    {
        PracticePlayer::login($event);
    }

    public function onjoin(PlayerJoinEvent $event)
    {
        PracticePlayer::PlayerJoin($event);
    }

    public function onquit(PlayerQuitEvent $event)
    {
        PracticePlayer::PlayerQuit($event);
    }

    public function onExhaust(PlayerExhaustEvent $event): void
    {
        PlayerExhaustEventHandler::onPlayerExhaust($event);
    }

    public function ondrop(PlayerDropItemEvent $event): void
    {
        PlayerDropItemEventHandler::onDropItem($event);
    }

    public function onCraftItemEvent(CraftItemEvent $event)
    {
        $event->cancel();
    }

    public function onBlockPlaceEvent(BlockPlaceEvent $event)
    {
        BlockPlaceEventHandler::onBlockPlace($event);
    }

    public function onBlockBleakEvent(BlockBreakEvent $event)
    {
        BlockBreakEventHandler::onBlockBleak($event);
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void
    {
        DataPacketReceiveEventHandler::onPacketReceive($event);
    }

    public function onPacketSend(DataPacketSendEvent $event): void
    {
        DataPacketSendEventHandler::onPacketSend($event);
    }

    public function onPlayerInteract(PlayerItemUseEvent $event): void
    {
        PlayerItem::ItemUse($event);
    }

    public function onEntityDamageEvent(EntityDamageEvent $event): void
    {
        EntityDamageHandler::damage($event);
    }

    public function onEntityTeleportEvent(EntityTeleportEvent $event): void
    {
        EntityTeleportEventHandler::onEntityTeleport($event);
    }

    public function onBlockUpdateEvent(BlockUpdateEvent $event): void
    {
        BlockUpdateEventHandler::onBlockUpdateEvent($event);
    }

    public function onEntityTrampleFarmlandEvent(EntityTrampleFarmlandEvent $event): void
    {
        EntityTrampleFarmlandEventHandler::onEntityTrampleFarmlandEvent($event);
    }

    public function onPlayerInteractEvent(PlayerInteractEvent $event): void
    {
        PlayerInteractEventHandler::onPlayerInteractEvent($event);
    }

    public function onFarmlandHydrationChangeEvent(FarmlandHydrationChangeEvent $event): void
    {
        $event->cancel();
    }

    public function onLeavesDecayEvent(LeavesDecayEvent $event): void
    {
        $event->cancel();
    }

    public function onBlockGrowEvent(BlockGrowEvent $event): void
    {
        $event->cancel();
    }

    public function onProjectileHitEvent(ProjectileHitEvent $event): void
    {
        ProjectileHitEventHandler::onProjectileHitEvent($event);
    }

    public function onProjectileHitBlockEvent(ProjectileHitBlockEvent $event): void
    {
        $entity = $event->getEntity();
        $entity->flagForDespawn();
    }

    public function onPlayerBucketEvent(PlayerBucketEvent $event): void
    {
        PlayerBucketEventHandler::onPlayerBucketEvent($event);
    }

    public function onBlockFormEvent(BlockFormEvent $event): void
    {
        BlockFormEventHandler::onBlockFormEvent($event);
    }

    public function onEntityDamageByEntityEvent(EntityDamageByEntityEvent $event): void
    {
        EntityDamageByEntityEventHandler::onEntityDamageByEntityEvent($event);
    }

    public function onPlayerChatEvent(PlayerChatEvent $event): void
    {
        PlayerChatEventHandler::onPlayerChatEvent($event);
    }

    public function onPlayerItemConsumeEvent(PlayerItemConsumeEvent $event): void
    {
        PlayerItemConsumeEventHandler::onPlayerItemConsumeEvent($event);
    }
    public function onProjectileLaunchEvent(ProjectileLaunchEvent $event): void
    {
        ProjectileLaunchEventHandler::onProjectileLaunchEvent($event);
    }

    public function onProjectileHitEntityEvent(ProjectileHitEntityEvent $event): void
    {
        ProjectileHitEntityEventHandler::onProjectileHitEntityEvent($event);
    }

    public function onInventoryTransactionEvent(InventoryTransactionEvent $event): void
    {
        InventoryTransactionEventHandler::onInventoryTransactionEvent($event);
    }

    public function onPlayerMoveEvent(PlayerMoveEvent $event): void
    {
        PlayerMoveEventHandler::onPlayerMoveEvent($event);
    }

    public function onEntityDamageByChildEntityEvent(EntityDamageByChildEntityEvent $event): void
    {
        EntityDamageByChildEntityEventHandler::onEntityDamageByChildEntityEvent($event);
    }

    public function onEntitySpawnEvent(EntitySpawnEvent $event): void
    {
        EntitySpawnEventHandler::onEntitySpawnEvent($event);
    }

    public function onEntityMotionEvent(EntityMotionEvent $event): void
    {
        EntityMotionEventHandler::onEntityMotionEvent($event);
    }

    public function onEntityPreExplodeEvent(EntityPreExplodeEvent $event): void
    {
        $event->setBlockBreaking(false);
    }
}
