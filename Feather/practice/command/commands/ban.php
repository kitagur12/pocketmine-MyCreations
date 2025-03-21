<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use system\player\PlayerDataManager;
use system\utils\console\console;
use system\player\banplayer;
use practice\command\selector;


class ban {
    public static function command_ban(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("ban", new banCommand());
    }
}

class banCommand extends Command {

    public function __construct() {
        parent::__construct("ban");
        $this->setPermission("server.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $server = Server::getInstance();
        $logger = new console();
        if (count($args) === 4) {
            if (is_numeric($args[1]) || is_string($args[2]) || is_string($args[3])) {
                $reason = $args[3];
                $int = (int)$args[1];
                if ($args[2]  == "hour") {
                    $time = $int * 3600;
                } elseif ($args[2]  == "day" || $args[2]  == "d" ) {
                    $time = $int * 86400;
                } else if ($args[2]  == "week" || $args[2]  == "w" ) {
                    $time = $int * 86400 * 7;
                } elseif ($args[2]  == "month" || $args[2]  == "m" ) {
                    $time = $int * 86400 * 30;
                } elseif ($args[2]  == "year" || $args[2]  == "y" ) {
                    $time = $int * 86400 * 365;
                } elseif ($args[2]  == "min" || $args[2]  == "minute" ) {
                    $time = $int * 60;
                } else {
                    $time = $int * 86400;
                }
        
                $dateTime = new \DateTime(); // 現在の日時を取得
                
                // 秒数をDateIntervalオブジェクトに変換
                $interval = new \DateInterval("PT{$time}S"); // P + T + 秒数
                
                // 時間に追加
                $dateTime->add($interval);
                $datetime = $dateTime->format('Y-m-d H:i:s');
                $xuid = $sender->getXuid();
                $banner = PlayerDataManager::getData($xuid,"name");
                $banner = selector::selector($sender, $banner);
                $banner = $banner->getXuid();
                $player = selector::selector($sender, $args[0]);
                if ($player == null) {
                    return;
                }
                $xuid = $player->getXuid();
                banplayer::banplayer($xuid, $datetime, $banner, $reason);
            }
    } else {

    }
    }
}

/*
                            "command1" => "<target:player> <int:time> hour <string:reason>",
                            "command2" => "<target:player> <int:time> day <string:reason>",
                            "command3" => "<target:player> <int:time> week <string:reason>",
                            "command4" => "<target:player> <int:time> month <string:reason>",
                            "command5" => "<target:player> <int:time> year <string:reason>",
*/