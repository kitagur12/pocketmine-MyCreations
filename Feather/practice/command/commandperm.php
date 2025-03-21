<?php

namespace practice\command;


use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\permission\PermissionAttachment;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

class CommandPerm
{

    public static function createperm(): void
    {
        $pluginManager = PermissionManager::getInstance();
        $permissions = [
            "server.command",
            "server.moderate",
            "server.admin",
            "server.nick",
            "server.event",
            "server.player",
            "server.mute"
        ];
        foreach ($permissions as $permissionName) {
            $permission = new Permission($permissionName);
            $pluginManager->addPermission($permission);
        }
    }

    public static function addPermission($player, $permissionName): void
    {
        if (is_string($permissionName)) {
            $permissionName = [$permissionName];
        }
        $attachment = $player->addAttachment(Server::getInstance()->getPluginManager()->getPlugin("Feather"));
        foreach ($permissionName as $perm) {
            $attachment->setPermission($perm, true);
        }
    }
    public static function removePermission($player): void
    {
        $attachment = $player->addAttachment(Server::getInstance()->getPluginManager()->getPlugin("Feather"));
        foreach($player->getEffectivePermissions() as $permission){
            if (($attachment = $permission->getAttachment()) !== null) {
                $player->removeAttachment($attachment);
            }
        }
    }
}
