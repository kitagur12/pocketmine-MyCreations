<?php

namespace practice\entity;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use practice\entity\DeathEntity;
use practice\entity\FishingHookEntity;

class EntityHandler
{
    public static function regist(): void
    {
        EntityFactory::getInstance()->register(
            DeathEntity::class,
            function ($world, $nbt): DeathEntity {
                return new DeathEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
            },
            ['DeathEntity']
        );
        EntityFactory::getInstance()->register(
            FishingHookEntity::class,
            function ($world, $nbt): FishingHookEntity {
                return new FishingHookEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
            },
            ['FishingHook']
        );
    }
}
