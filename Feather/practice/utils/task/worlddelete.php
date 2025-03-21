<?php

namespace practice\utils\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class worlddelete extends AsyncTask
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

            if (!is_array($item) || !isset($item[0], $item[1])) {
                throw new \RuntimeException("無効なデータフォーマット");
            }
            $defaultWorldName = $item[0];
            $duelId = $item[1];
            $currentPath = __DIR__;
            $filePath = str_replace("plugins\Feather\src\practice\utils\\task", "worlds\\" . $defaultWorldName, $currentPath);
            $destinationPath = $filePath . "=" . $duelId;
            self::deleteDirectory($destinationPath);
            $this->setResult(["status" => "success", "worldName" => $defaultWorldName, "duelId" => $duelId]);
        } catch (\Throwable $e) {
            $this->setResult(["status" => "error", "message" => $e->getMessage()]);
        }
    }

    public function onCompletion(): void
    {
        $result = $this->getResult();
        if ($result["status"] == "error") {
            //var_dump($result["message"]);
        }
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
}
