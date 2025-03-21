<?php

namespace practice\command;

use pocketmine\Server;

class DisableCommand
{
    public static function DisableCommand(): void
    {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        //$commandMap->unregister($commandMap->getCommand("deop")); 
        //$commandMap->unregister($commandMap->getCommand("op")); 
        $commandMap->unregister($commandMap->getCommand("ban"));
        $commandMap->unregister($commandMap->getCommand("ban-ip"));
        $commandMap->unregister($commandMap->getCommand("banlist"));
        $commandMap->unregister($commandMap->getCommand("checkperm"));
        $commandMap->unregister($commandMap->getCommand("clear"));
        $commandMap->unregister($commandMap->getCommand("defaultgamemode"));
        $commandMap->unregister($commandMap->getCommand("difficulty"));
        $commandMap->unregister($commandMap->getCommand("dumpmemory"));
        //$commandMap->unregister($commandMap->getCommand("effect"));
        $commandMap->unregister($commandMap->getCommand("enchant"));
        $commandMap->unregister($commandMap->getCommand("extractplugin"));
        $commandMap->unregister($commandMap->getCommand("gamemode"));
        $commandMap->unregister($commandMap->getCommand("gc"));
        $commandMap->unregister($commandMap->getCommand("genplugin"));
        $commandMap->unregister($commandMap->getCommand("give"));
        $commandMap->unregister($commandMap->getCommand("handlers"));
        $commandMap->unregister($commandMap->getCommand("handlersbyplugin"));
        $commandMap->unregister($commandMap->getCommand("kick"));
        $commandMap->unregister($commandMap->getCommand("kill"));
        $commandMap->unregister($commandMap->getCommand("list"));
        $commandMap->unregister($commandMap->getCommand("listperms"));
        $commandMap->unregister($commandMap->getCommand("makeplugin"));
        $commandMap->unregister($commandMap->getCommand("me"));
        $commandMap->unregister($commandMap->getCommand("pardon"));
        $commandMap->unregister($commandMap->getCommand("pardon-ip"));
        $commandMap->unregister($commandMap->getCommand("particle"));
        $commandMap->unregister($commandMap->getCommand("plugins"));
        $commandMap->unregister($commandMap->getCommand("save-all"));
        $commandMap->unregister($commandMap->getCommand("save-off"));
        $commandMap->unregister($commandMap->getCommand("save-on"));
        $commandMap->unregister($commandMap->getCommand("say"));
        $commandMap->unregister($commandMap->getCommand("seed"));
        $commandMap->unregister($commandMap->getCommand("setworldspawn"));
        $commandMap->unregister($commandMap->getCommand("spawnpoint"));
        $commandMap->unregister($commandMap->getCommand("time"));
        $commandMap->unregister($commandMap->getCommand("tell"));
        $commandMap->unregister($commandMap->getCommand("timings"));
        $commandMap->unregister($commandMap->getCommand("title"));
        $commandMap->unregister($commandMap->getCommand("tp"));
        $commandMap->unregister($commandMap->getCommand("version"));
        $commandMap->unregister($commandMap->getCommand("whitelist"));
        $commandMap->unregister($commandMap->getCommand("status"));
        $commandMap->unregister($commandMap->getCommand("transferserver"));
        $commandMap->unregister($commandMap->getCommand("help"));
    }
}
