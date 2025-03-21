<?php

namespace practice\handler\method;

use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\player\Player;
use practice\player\PracticePlayer;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class ProjectileHitEventHandler
{

    protected const MAX = 1.0515;

    protected float $gravity = 0.06;
    protected float $drag = 0.0025;

    public static function onProjectileHitEvent($event)
    {
        $entity = $event->getEntity();
        $owner = $entity->getOwningEntity();
        $raytrace = $event->getRayTraceResult();
        if ($entity instanceof Arrow && $owner instanceof Player) {
            $entity->flagForDespawn();
        }
        if ($entity instanceof EnderPearl && $owner instanceof Player) {
            $vector = $event->getRayTraceResult()->getHitVector();
            (function () use ($vector, $owner): void {
                $owner->setPosition($vector);
            })->call($owner);
            $location = $owner->getLocation();
            $xuid = $owner->getXuid();
            $config = PracticePlayer::$playerdata;
            if ($config[$xuid]["settings"]["smooth_pearl"]) {
                $owner->getNetworkSession()->syncMovement($location, $location->yaw, $location->pitch);
            } else {
                $owner->getNetworkSession()->syncMovement($location, $location->yaw, $location->pitch, MovePlayerPacket::MODE_TELEPORT);
            }
            $entity->setOwningEntity(null);
        }
        if ($entity instanceof SplashPotion) {
            $owner = $entity->getOwningEntity();
            if (!$owner instanceof PracticePlayer) {
                $entity->flagForDespawn();
                return;
            }
            $hitResult = $event->getHitResult();
            if ($hitResult->getHitEntity() !== null) {
                $targetPlayer = $hitResult->getHitEntity();
                if ($targetPlayer instanceof Player) {
                    foreach ($entity->getPotionEffects() as $effect) {
                        if (!$effect->getType() instanceof InstantEffect) {
                            $newDuration = (int)round($effect->getDuration() * 0.75 * self::MAX);
                            if ($newDuration >= 20) {
                                $effect->setDuration($newDuration);
                                $targetPlayer->getEffects()->add($effect);
                            }
                        } else {
                            $effect->getType()->applyEffect($targetPlayer, $effect, self::MAX);
                        }
                    }
                }
            }
            $radius = 3.0;
            foreach ($entity->getWorld()->getNearbyEntities($entity->getBoundingBox()->expand($radius, $radius, $radius)) as $nearbyEntity) {
                if ($nearbyEntity instanceof Player && $nearbyEntity->isAlive()) {
                    foreach ($entity->getPotionEffects() as $effect) {
                        if (!$effect->getType() instanceof InstantEffect) {
                            $newDuration = (int)round($effect->getDuration() * 0.75 * self::MAX);
                            if ($newDuration >= 20) {
                                $effect->setDuration($newDuration);
                                $nearbyEntity->getEffects()->add($effect);
                            }
                        } else {
                            $effect->getType()->applyEffect($nearbyEntity, $effect, self::MAX);
                        }
                    }
                }
            }

            $entity->flagForDespawn();
        }
    }
}
