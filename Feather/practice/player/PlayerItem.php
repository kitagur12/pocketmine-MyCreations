<?php

namespace practice\player;

use pocketmine\Server;
use pocketmine\item\VanillaItems;
use pocketmine\entity\Location;
use practice\player\PracticePlayer;
use practice\entity\FishingHookEntity;
use practice\utils\PlayerDataManager;
use practice\form\Forms;
use practice\items\gapplehead;
use practice\items\enderpearl;
use practice\arena\ArenaManaer;
use practice\player\PlayerQueue;
use practice\items\splash_potion;

class PlayerItem
{
    public static function ItemUse($event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $nbt = $item->getNamedTag();
        if (PlayerDataManager::getdata($player, "itemuse") == "false") {
            $event->cancel();
        } else {
            $splash_potion = VanillaItems::SPLASH_POTION()->getVanillaName();
            if ($splash_potion == $item->getVanillaName()) {
                splash_potion::use($event, $player, $item);
            }
            $fishing_rod = VanillaItems::FISHING_ROD()->getVanillaName();
            if ($fishing_rod == $item->getVanillaName()) {
                $using = false;
                $location = $player->getLocation();
                foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
                    foreach ($world->getEntities() as $entity) {
                        if ($entity instanceof FishingHookEntity && $entity->getOwningEntity() == $player) {
                            $entity->flagForDespawn();
                            $using = true;
                        }
                    }
                }
                if ($using) {
                    if (PracticePlayer::$playerdata[$player->getXuid()]["settings"]["vanilla_hook"] == true) {
                        $fishing_hook = new FishingHookEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
                        $fishing_hook->spawnToAll();
                    }
                } else {
                    $fishing_hook = new FishingHookEntity(Location::fromObject($player->getEyePos(), $player->getWorld(), $location->yaw, $location->pitch), $player);
                    $fishing_hook->spawnToAll();
                }
            }
        }

        if ($nbt === null) {
            $itemId = -1;
        } else {
            if ($nbt->getTag("id") !== null) {
                $itemId = $nbt->getInt("id");
            } else {
                $itemId = -1;
            }
        }

        if ($itemId !== null) {
            switch ($itemId) {

                case "0":
                    Forms::joinffa($player);
                    break;
                case "1":
                    Forms::duel($player, 1, true);
                    break;
                case "2":
                    Forms::practice($player, 1, true);
                    break;
                case "3":
                    Forms::party($player, 1, true);
                    break;
                case "4":
                    Forms::spectate($player, 1, true);
                    break;
                case "5":
                    Forms::cosmetics($player, 1, true);
                    break;
                case "6":
                    Forms::settings($player, 1, true);
                    break;
                case "8":
                    $direction = $player->getDirectionVector()->normalize()->multiply(1.5);
                    $direction = $direction->add(0, 0.2, 0);
                    $player->setMotion($direction);
                    break;
                case "101":
                    Forms::party($player, 6, true);
                    break;
                case "102":
                    Forms::party($player, 7, true);
                    break;
                case "103":
                    Forms::party($player, 8, true);
                    break;
                case "108":
                    Forms::party($player, 9, true);
                    break;
                case "109":
                    Forms::party($player, 10, true);
                    break;
                case "110":
                    Forms::party($player, 14, true);
                    break;
                case "119":
                    Forms::party($player, 27, true);
                    break;
                case "201":
                    Forms::practice($player, 1, true);
                    break;
                case "202":
                    Forms::practice($player, 2, true);
                    break;
                case "203":
                    Forms::practice($player, 3, true);
                    break;
                case "204":
                    Forms::practice($player, 4, true);
                    break;
                case "205":
                    Forms::practice($player, 5, true);
                    break;
                case "10000":
                    enderpearl::use($event, $player, $item);
                    break;
                case "10001":
                    gapplehead::use($player, $item);
                    break;
                case "10002":
                    PlayerQueue::leaveQueue($player);
                    ArenaManaer::lobby($player);
                    break;
                case "10003":
                    PlayerQueue::leaveQueue($player);
                    ArenaManaer::joinlobby($player);
                    break;
            }
        }
    }
}
