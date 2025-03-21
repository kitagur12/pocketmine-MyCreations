<?php

namespace hentai;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\scheduler\Task;
use hentai\modules\loader;
use hentai\modules\updatestatus;
use hentai\modules\inspection;
use hentai\command\commandLoader;
use hentai\command\Completion;
use hentai\sql;
use hentai\login;

class anticheat extends PluginBase implements Listener
{

    public function onEnable(): void
    {
        $this->saveDefaultConfig();

        $config = $this->getConfig()->getAll(false);
        info::$config = $config;

        sql::login();

        commandLoader::loadcommand();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents(new Completion(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new scoreCheck(), 0);

        Server::getInstance()->broadcastMessage("§8[§dHentai§8] §rAntiCheat is §aEnabled.");
    }

    public function onLogin(PlayerLoginEvent $event): void
    {
        login::pmlogin($event);
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        $inspection = new inspection();
        $inspection->spawn($player);
        info::$playerinfo[$xuid]["global"]["check_score"] = 0;

        login::pmjoin($event);
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $xuid = $player->getXuid();
        $inspection = new inspection();
        $inspection->kill($player);
        info::$playerinfo[$xuid] = [];
    }

    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $inspection = new inspection();
        $inspection->damage($event);
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void
    {
        $inspection = new inspection();
        $inspection->teleport($event);
    }

    public function onEntityMotion(EntityMotionEvent $event): void
    {
        $inspection = new inspection();
        $inspection->motion($event);
    }

    public function onPacketReceive(DataPacketReceiveEvent $event): void
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if ($packet instanceof \pocketmine\network\mcpe\protocol\LoginPacket) {
            login::connection($event);
        }

        $loader = new loader();
        $loader->load($packet, $player);
    }

    public function onDataPacketSend(DataPacketSendEvent $event): void
    {
        $packets = $event->getPackets();
        $playersSessions = $event->getTargets();

        //var_dump($packets);
        $update = new updatestatus();
        foreach ($packets as $packet) {
            foreach ($playersSessions as $playerSession) {
                $player = $playerSession->getPlayer();
                $update->update($packet, $player);
            }
        }
    }

    public function onPluginDisable(PluginDisableEvent $ev): void
    {
        $plugin = $ev->getPlugin();

        if ($plugin == $this) {
            Server::getInstance()->broadcastMessage("§8[§dHentai§8] §rAntiCheat is §cDisabled.");
        }
    }
}

class scoreCheck extends Task
{
    public function onRun(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $xuid = $player->getXuid();
            if (isset(info::$playerinfo[$xuid])) {
                $xuid = $player->getXuid();
                $score = info::$playerinfo[$xuid]["global"]["check_score"] ?? 0;
                $modelate = new moderate();

                if ($score > 100) {
                    $modulescore = info::$playerinfo[$xuid]["global"]["module_score"];

                    $message = "";
                    foreach ($modulescore as $module => $score) {
                        $message .= " " . $module . ": $score";
                    }

                    $message = "§7Error: AAA" . base64_encode(bin2hex(random_bytes(8)));
                    $modelate->kick($player, $message, "hentai");
                }
            }
        }
    }
}

class info
{
    public static array $config;
    public static array $playerinfo = [];
}
