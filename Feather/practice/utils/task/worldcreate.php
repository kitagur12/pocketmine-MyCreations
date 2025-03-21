<?php

namespace practice\utils\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use practice\practice\practice;
use practice\duel\PlayerDuel;

class worldcreate extends AsyncTask
{
    private string $itemSerialized;

    public function __construct(array $item)
    {
        $this->itemSerialized = json_encode($item);
    }

    public function onRun(): void
    {
        try {
            $item = json_decode($this->itemSerialized, true);
            $defaultWorldName = $item[0];
            $duelId = $item[1];
            $currentPath = __DIR__;
            $rawfilePath = str_replace("plugins\Feather\src\practice\utils\\task", "worlds\\raw\\" . $defaultWorldName, $currentPath);
            $filePath = str_replace("plugins\Feather\src\practice\utils\\task", "worlds\\" . $defaultWorldName, $currentPath);
            $destinationPath = $filePath . "=" . $duelId;
            self::copyDirectory($rawfilePath, $destinationPath);
            $this->setResult(["status" => "success", "world" => $defaultWorldName, "duelId" => $duelId]);
        } catch (\Throwable $e) {
            $this->setResult(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function onCompletion(): void
    {
        $item = json_decode($this->itemSerialized, true);
        $result = $this->getResult();
        $server = Server::getInstance();
        $worldManager = $server->getWorldManager();
        $duelId = $item[1];
        if (isset($result["world"])) {
            $worldName = $result["world"] . "=" . $result["duelId"];
            $isduel = $item[2] ?? false;
            $isparty = $item[3] ?? false;
            if ($worldManager->isWorldLoaded($worldName)) {
                $worldManager->unloadWorld($worldManager->getWorldByName($worldName));
            }
            $worldManager->loadWorld($worldName);
            if ($isduel) {
                PlayerDuel::$dueldatas[$duelId]["world"] = "bbbbbbbbbb";
            }

            if ($isparty) {
                practice::$practicedatas[$duelId]["world"] = "bbbbbbbbbb";
            }
        } else {
            var_dump($result["message"]);
        }
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $files = array_diff(scandir($source), ['.', '..']);
        foreach ($files as $file) {
            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                self::copyDirectory($sourcePath, $destinationPath);
            } elseif (is_file($sourcePath)) {
                copy($sourcePath, $destinationPath);
            }
        }
    }
}
