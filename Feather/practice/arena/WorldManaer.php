<?php

namespace practice\arena;

use pocketmine\Server;
use practice\utils\yaml;
use practice\practice;

class WorldManaer
{
    public static function setup()
    {
        $config = yaml::getconfig();
        $maps = $config["map"];
        $ffa = $config["games"]["ffa"];
        $currentPath = __DIR__;
        $filePath = str_replace("plugins\Feather\src\practice\arena", "worlds\\", $currentPath);
        self::deleteUnlistedFolders($filePath, ["raw", "world"]);
        foreach ($ffa as $game) {
            $map = $game["map"];
            $maps = $config["map"][$game["map"]]["map"];
            if (is_string($maps)) {
                $maps = [$maps];
            }
            foreach ($maps as $map) {
                $ffaworld = self::getWorldByExactName($map . "=defalt");
                if ($ffaworld == null) {
                    practice::CreateAsyncTask("practice\utils\\task\worldcreate", [$map, "defalt", false]);
                }
            }
        }

        $lobby = $config["games"]["lobby"];
        foreach ($lobby as $game) {
            if (isset($game["map"])) {
                $map = $game["map"];
                $maps = $config["map"][$game["map"]]["map"];
                if (is_string($maps)) {
                    $maps = [$maps];
                }
                foreach ($maps as $map) {
                    $lobbyworld = self::getWorldByExactName($map . "=defalt");
                    if ($lobbyworld == null) {
                        practice::CreateAsyncTask("practice\utils\\task\worldcreate", [$map, "defalt"]);
                    }
                }
            }
        }
    }

    public static function cloneDuelWorld($defaltworldname, $duelid)
    {
        practice::CreateAsyncTask("practice\utils\\task\worldcreate", [$defaltworldname, $duelid, true]);
    }

    public static function clonePracticeWorld($defaltworldname, $duelid)
    {
        practice::CreateAsyncTask("practice\utils\\task\worldcreate", [$defaltworldname, $duelid, false, true]);
    }

    public static function deleteDuelWorld($defaltworldname, $duelid)
    {
        $worldManager = Server::getInstance()->getWorldManager();
        $worldName = $defaltworldname . "=" . $duelid;
        if ($worldManager->isWorldLoaded($worldName)) {
            $worldManager->unloadWorld($worldManager->getWorldByName($worldName));
        }
        practice::CreateAsyncTask("practice\utils\\task\worlddelete", [$defaltworldname, $duelid]);
    }

    public static function getWorldByExactName(string $worldName)
    {
        $worldManager = Server::getInstance()->getWorldManager();
        $worlds = $worldManager->getWorlds();

        foreach ($worlds as $world) {
            if ($world->getFolderName() === $worldName) {
                return $world;
            }
        }
        return null;
    }

    private static function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        rmdir($dir);
    }

    private static function deleteUnlistedFolders(string $targetDir, array $allowedFolders): void
    {
        if (!is_dir($targetDir)) {
            return;
        }

        $items = scandir($targetDir);
        foreach ($items as $item) {
            $path = $targetDir . DIRECTORY_SEPARATOR . $item;
            if ($item === "." || $item === ".." || in_array($item, $allowedFolders, true)) {
                continue;
            }
            if (is_dir($path)) {
                self::deleteDirectory($path);
            }
        }
    }
}
