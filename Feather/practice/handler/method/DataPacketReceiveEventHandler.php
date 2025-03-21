<?php

namespace practice\handler\method;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestSlotInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\math\Vector2;
use practice\handler\method\PlayerMoveEventHandler;
use practice\utils\PlayerDataManager;
use practice\player\PracticePlayer;
use practice\form\ReciveFormEvent;
use practice\handler\ActionbarHandler;
use practice\practice;
use practice\form\invformapi;
use practice\utils\yaml;
use ReflectionClass;
use SplQueue;

class DataPacketReceiveEventHandler
{
    private static array $clickQueues = [];
    private static array $dropQueues = [];

    public static function onPacketReceive($event)
    {
        $packet = $event->getPacket();
        $origin = $event->getOrigin();
        $player = $origin->getPlayer();
        if ($packet instanceof \pocketmine\network\mcpe\protocol\LoginPacket) {
            PracticePlayer::clientinfo($packet, $player);
        }
        if ($packet instanceof \pocketmine\network\mcpe\protocol\ModalFormResponsePacket) {
            ReciveFormEvent::getformdata($packet, $player, true);
        }
        if ($player !== null) {
            if ($packet instanceof PlayerAuthInputPacket) {
                $getRawMove = $packet->getRawMove();
                if ($getRawMove instanceof Vector2) {
                    if ($getRawMove->y > 0.1) {
                        if (PracticePlayer::$playerdata[$player->getXuid()]["settings"]["toggle_sprint"]) {
                            $player->setSprinting(true);
                        }
                    }
                }
                PlayerDataManager::setdata($player, "connected", "true", false);
                $flags = $packet->getInputFlags();
                if ($flags->get(PlayerAuthInputFlags::MISSED_SWING)) {
                    self::registerClick($player);
                }
                self::registerDrop($player);
                if (PlayerDataManager::getData($player, "spectate", false) == "true") {
                    $reflection = new ReflectionClass(PlayerAuthInputPacket::class);
                    $property = $reflection->getProperty("position");
                    $property->setAccessible(true);
                    $rawposition = $property->getValue($packet);
                    $newPosition = new Vector3($rawposition->getX(), $rawposition->getY() + 10000, $rawposition->getZ());
                    PlayerDataManager::setData($player, "blocktp", "true", false);
                    $player->teleport($newPosition);
                    PlayerDataManager::setData($player, "blocktp", "false", false);
                    $property->setValue($packet, $newPosition);
                }
            } else {
                //var_dump($packet); //全てのパケットログ 
            }
        }

        if ($packet instanceof ContainerClosePacket) {
            $isform = PlayerDataManager::getData($player, "formqueue");
            if ($isform == "true") {
                $formid = PlayerDataManager::getData($player, "formqueue_id");
                PlayerDataManager::setData($player, "formqueue", "false");
                practice::CreateTask("practice\utils\\task\\sendinvform2", [$player, $formid], 1);
            } else {
                invformapi::closeInvform($player);
            }
        }

        if ($packet instanceof ItemStackRequestPacket) {
            foreach ($packet->getRequests() as $request) {
                foreach ($request->getActions() as $action) {
                    if ($action instanceof TakeStackRequestAction || $action instanceof PlaceStackRequestAction || $action instanceof SwapStackRequestAction) {
                        if ($action instanceof SwapStackRequestAction) {
                            $sourceSlotInfo = $action->getSlot1();
                        } else {
                            $sourceSlotInfo = $action->getSource();
                        }
                        if ($sourceSlotInfo instanceof ItemStackRequestSlotInfo) {
                            $reflection = new \ReflectionClass($sourceSlotInfo);
                            $slotIdProperty = $reflection->getProperty("slotId");
                            $slotIdProperty->setAccessible(true);
                            $slotId = $slotIdProperty->getValue($sourceSlotInfo);
                            $containerNameProperty = $reflection->getProperty("containerName");
                            $containerNameProperty->setAccessible(true);
                            $containerName = $containerNameProperty->getValue($sourceSlotInfo);

                            $containerIdReflection = new \ReflectionClass($containerName);
                            $containerIdProperty = $containerIdReflection->getProperty("containerId");
                            $containerIdProperty->setAccessible(true);
                            $containerId = $containerIdProperty->getValue($containerName);
                            if ($containerId === 7) {
                                $formid = PlayerDataManager::getdata($player, "invformid");
                                ReciveFormEvent::getformdata($slotId, $player);
                            }
                        }
                    }
                }
            }
        }
        if ($packet instanceof InventoryTransactionPacket) {
            $trData = $packet->trData;
            if ($trData instanceof UseItemOnEntityTransactionData) {
                $actionType = $trData->getActionType();
                if ($actionType === 1) {
                    $id = $trData->getActorRuntimeId();
                    $xuid = $player->getXuid();
                    $d = PlayerMoveEventHandler::$d[$xuid] ?? -1;
                    if (PracticePlayer::$playerdata[$player->getXuid()]["settings"]["crit_particle"] == 2 && !$player->isOnGround() && !$player->isSprinting() && $d > 0) {
                        $player->getNetworkSession()->sendDataPacket(AnimatePacket::boatHack($id, 4, 1));
                    }
                    if (PracticePlayer::$playerdata[$player->getXuid()]["settings"]["crit_particle"] == 3) {
                        $player->getNetworkSession()->sendDataPacket(AnimatePacket::boatHack($id, 4, 1));
                    }
                    if ($player === null) {
                        return;
                    }

                    self::registerClick($player);
                }
            }
        }
    }

    public static function registerClick($player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset(self::$clickQueues[$playerName])) {
            self::$clickQueues[$playerName] = new SplQueue();
        }

        self::$clickQueues[$playerName]->enqueue($currentTime);

        self::removeOldClicks($player);
        DataPacketReceiveEventHandler::removeOldClicks($player);
        $cps = DataPacketReceiveEventHandler::getCPS($player);
        $combo = 0;
        $reach = 0;
        $message = "";
        $xuid = $player->getXuid();
        $isping = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["ping"];
        $iscps = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["cpscount"];
        $iscombo = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["combo"];
        $isreach = PracticePlayer::$playerdata[$xuid]["settings"]["actionbar_setting"]["reach"];
        if ($iscps) {
            $message = yaml::getcolor() . "Cps§7 : " . $cps;
            if ($iscombo || $isreach || $isping) {
                $message = $message . " | ";
            }
        }
        if ($iscombo) {
            $message = $message . yaml::getcolor() . "Combo§7 : " . $combo;
            if ($isreach || $isping) {
                $message = $message . " | ";
            }
        }
        if ($isreach) {
            $message = $message . yaml::getcolor() . "Reach§7 : " . $reach;
            if ($isping) {
                $message = $message . " | ";
            }
        }
        if ($isping) {
            $message = $message . yaml::getcolor() . "Ping§7 : " . $player->getNetworkSession()->getPing();
        }
        ActionbarHandler::sendMessage($player, $message);
    }

    public static function registerDrop($player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset(self::$dropQueues[$playerName])) {
            self::$dropQueues[$playerName] = new SplQueue();
        }

        self::$dropQueues[$playerName]->enqueue($currentTime);
        self::removeOlddrops($player);
    }

    public static function removeOldClicks($player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset(self::$clickQueues[$playerName])) {
            return;
        }

        while (!self::$clickQueues[$playerName]->isEmpty()) {
            $clickTime = self::$clickQueues[$playerName]->bottom();
            if (($currentTime - $clickTime) >= 1.0) {
                self::$clickQueues[$playerName]->dequeue();
            } else {
                break;
            }
        }
    }

    public static function removeOldDrops($player): void
    {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        if (!isset(self::$dropQueues[$playerName])) {
            return;
        }

        while (!self::$dropQueues[$playerName]->isEmpty()) {
            $dropTime = self::$dropQueues[$playerName]->bottom();
            if (($currentTime - $dropTime) >= 1.0) {
                self::$dropQueues[$playerName]->dequeue();
            } else {
                break;
            }
        }
    }

    public static function getCPS($player): int
    {
        if ($player !== null) {
            $playerName = $player->getName();
        } else {
            $playerName = self::$clickQueues[0];
        }


        if (!isset(self::$clickQueues[$playerName])) {
            return 0;
        }

        return self::$clickQueues[$playerName]->count();
    }

    public static function getDrop($player): int
    {
        if ($player !== null) {
            $playerName = $player->getName();
        } else {
            $playerName = self::$dropQueues[0];
        }


        if (!isset(self::$dropQueues[$playerName])) {
            return 0;
        }

        return self::$dropQueues[$playerName]->count();
    }
}
