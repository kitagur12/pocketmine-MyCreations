<?php

namespace hentai\command;

use pocketmine\permission\PermissionManager;
use pocketmine\permission\Permission;
use hentai\info;

class CommandPerm {
    public static function createperm(): void {
        $pluginManager = PermissionManager::getInstance();
        $config = info::$config;
        $commandPerm = $config['config']['other']['commandperm'] ?? null;
        $permissions = [
            $commandPerm,
        ];
        foreach ($permissions as $permissionName) {
            $permission = new Permission($permissionName);
            $pluginManager->addPermission($permission);
        }
    }

    public static function grantPermission($player, string $permissionName): void {
        $attachment = $player->addAttachment($player->getServer()->getPluginManager()->getPlugin("system"));
        $attachment->setPermission($permissionName, true);
    }
}
