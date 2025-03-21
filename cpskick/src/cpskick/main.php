<?php

namespace cpskick;

use pocketmine\scheduler\Task;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use SplQueue;
use pocketmine\Server;

class main extends PluginBase implements Listener
{

    protected function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private $plugin;
            public function __construct($plugin)
            {
                $this->plugin = $plugin;
            }
            public function onRun(): void
            {
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
                    $this->plugin->removeOldClicks($player);
                    $this->plugin->updateActionBar($player);
                }
            }
        }, 0);
    }

    private array $clickQueues = [];
    private array $times = [];

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

    public function registerClick($player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset($this->clickQueues[$playerName])) {
            $this->clickQueues[$playerName] = new SplQueue();
        }

        $this->clickQueues[$playerName]->enqueue($currentTime);

        $this->removeOldClicks($player);
    }

    public function removeOldClicks($player): void
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

    public function getCPS($player): int
    {
        $playerName = $player->getName();

        if (!isset($this->clickQueues[$playerName])) {
            return 0;
        }

        return $this->clickQueues[$playerName]->count();
    }

    public function updateActionBar($player): void
    {
        $cps = self::getCPS($player);
        $playerName = $player->getName();
        if ($cps > 17) {
            if (!isset($this->times[$playerName]) || !is_object($this->times[$playerName])) {
                $this->times[$playerName] = (object)["time" => 0];
            }
            
            $time = $this->times[$playerName]->time;
            $this->times[$playerName]->time = $time + 0.05;
            $title = "§cCPS limit is §l§418.";
            $subTitle = "";
            $fadeIn = 0;
            $stay = 5;
            $fadeOut = 5;
            $player->sendTitle($title, $subTitle, $fadeIn, $stay, $fadeOut);
            if ($time > 1) {
                $this->times[$playerName]->time = 0;
                $player->kick("§ccps limit is 20.");
            }
        } else {
            $this->times[$playerName] = 0;
        }
    }
}
