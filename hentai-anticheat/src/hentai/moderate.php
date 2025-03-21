<?php

namespace hentai;

use hentai\info;
use hentai\sql;
use hentai\variable;

class moderate
{
    public function ban($player, string $reason, int $duration, string $staff): void
    {
        if ($player !== null) {
            $config = info::$config;
            $bypassperm = $config['config']['moderate']['kickbypass'] ?? null;
            $message = "§cYou are §l§4Banned§r§c From §e" . $staff . "§c.\n§7Reason: §c" . $reason . "\n§7Duration: §c" . $duration;
            if (!$this->checkPermission($player, $bypassperm)) {
                $xuid = variable::getData($player, "xuid");
                $deviceId = variable::getData($player, "deviceId");
                $clientId = variable::getData($player, "clientid");
                $ip = variable::getData($player, "ip");
                $unban = time() + $duration;
                $name = variable::getData($player, "name");
                sql::runsql("INSERT INTO ban_info (xuid, name, ip, deviceid, clientID, reason, banner, unban) VALUES (:xuid, :name, :ip, :deviceid, :clientID, :reason, :banner, :unban) ON DUPLICATE KEY UPDATE name = VALUES(name), ip = VALUES(ip), deviceid = VALUES(deviceid), clientID = VALUES(clientID), reason = VALUES(reason), banner = VALUES(banner), unban = VALUES(unban);", [':xuid' => $xuid, ':name' => $name, ':ip' => $ip, ':deviceid' => $deviceId, ':clientID' => $clientId, ':reason' => $reason, ':banner' => $staff, ':unban' => $unban]);
                $player->kick($reason);
            } else {
                $player->sendmessage("§7[§dhentai§7] " . $message);
            }
        }
    }

    public function unban(string $player, string $staff): void
    {
        sql::runsql("DELETE FROM ban_info WHERE name = :name", [':name' => $player]);
    }

    public function bancheck($player): void
    {
        $xuid = variable::getData($player, "xuid");
        $deviceId = variable::getData($player, "deviceId");
        $clientId = variable::getData($player, "clientid");
        $ip = variable::getData($player, "ip");
        $isban = sql::search("ban_info", [$xuid, $ip, $deviceId, $clientId]);
        if ($isban !== null) {
            $player->kick("you are banned this server");
        }
    }

    public function kick($player, string $reason, string $staff): void
    {
        $config = info::$config;
        $message = "§cYou are §l§4Kicked§r§c From §e" . $staff . "§c.\n§7Reason: §c" . $reason;
        $bypassperm = $config['config']['moderate']['kickbypass'] ?? null;
        if (!$this->checkPermission($player, $bypassperm[0])) {
            $player->kick($reason);
        } else {
            $player->sendmessage("§7[§dhentai§7] " . $message);
        }
    }

    public function reason($player, string $reason, string $staff, int $duration = null, $type): void
    {
        $config = info::$config;
        $name = $player->name();
        if ($type == "ban") {
            $kickreason = $config['config']['moderate']['ban']['message'] ?? null;
        } else {
            $kickreason = $config['config']['moderate']['kick']['message'] ?? null;
        }
        $result1 = str_replace("{reason}", $reason, $kickreason);
        $result1 = str_replace("{player}", $name, $result1);
        $result1 = str_replace("{until}", $duration, $result1);
        $result1 = str_replace("{until_unban}", date('Y-m-d H:i:s', $duration), $result1);
        $result1 = str_replace("{time}", time(), $result1);
        $result1 = str_replace("{staff}", $staff, $result1);
    }

    public function checkPermission($player, $permission): bool
    {
        return $player->hasPermission($permission[0]);
    }
}
