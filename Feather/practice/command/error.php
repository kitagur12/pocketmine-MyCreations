<?php

namespace practice\command;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class error extends PluginBase implements Listener {

    public function onEnable(): void {
        // イベントリスナーを登録
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    // コマンドが実行される前に処理を実行する
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event): void {
        // コマンドの名前を取得
        $command = $event->getMessage();

        // コマンドが実行される前に何か処理を行う
        $this->getServer()->getLogger()->info("Command executed: " . $command);

        // ここで任意の処理を追加できます
        // 例: コマンドが "test" の場合に特別な処理を行う
        if (strpos($command, "/test") === 0) {
            // 例: コマンドが "test" の場合にメッセージを送信
            $event->getPlayer()->sendMessage("The 'test' command was executed!");
        }
    }
}
