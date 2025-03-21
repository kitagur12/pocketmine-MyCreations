<?php

namespace practice\command\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use practice\command\selector;
use pocketmine\world\Position;

class tp {
    public static function command_tp(): void {
        $server = Server::getInstance();
        $commandMap = $server->getCommandMap();
        $commandMap->register("", new tpCommand());
    }
}

class tpCommand extends Command {

    public function __construct() {
        parent::__construct(
            "tp", 
            "plater tereport",
            "/tp",
            ["teleport"]
        );
        $this->setPermission("server.admin");
    }


    public function execute(CommandSender $sender, string $commandLabel, array $args): void {

        if (count($args) === 2) {
            // command1形式: プレイヤー間のテレポート
            $target1 = selector::selector($sender, $args[0]); // 1番目のプレイヤーを選択
            $target2 = selector::selector($sender, $args[1]); // 2番目のプレイヤーを選択

            if ($target1 != null && $target2 != null) {
                $target1->teleport($target2->getPosition()); // target1をtarget2の位置にテレポート
                $sender->sendMessage("{$target1->getName()} を {$target2->getName()} にテレポートしました。");
            } else {
                $sender->sendMessage("指定されたプレイヤーが見つかりません。");
            }

        } elseif (count($args) === 6) {
            // command2形式: 座標へのテレポート
            $player = selector::selector($sender, $args[0]); // プレイヤーを選択

            if ($player != null) {
                $x = (float)$args[1];
                $y = (float)$args[2];
                $z = (float)$args[3];
                $rx = (float)$args[4] ?? 0;
                $ry = (float)$args[5] ?? 0;
                $position = new Position($x, $y, $z, $player->getWorld());
                $player->teleport($position, $rx, $ry);
                $sender->sendMessage("{$player->getName()} を座標 ({$x}, {$y}, {$z}) にテレポートしました。");
            } else {
                $sender->sendMessage("指定されたプレイヤーが見つかりません。");
            }
        } else {
            // 引数の数が無効な場合
            $sender->sendMessage("無効な引数です。/tp <target:player> <target:player> または /tp <target:player> <int:x> <int:y> <int:z> 形式で指定してください。");
        }
    }
}
