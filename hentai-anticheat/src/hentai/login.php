<?php

namespace hentai;

use hentai\sql;
use hentai\moderate;

class login
{
    public static function login($data): void
    {
        $SkinResourcePatch = $data['SkinResourcePatch'] ?? '';
        $SkinId = $data['SkinId'] ?? '';
        $SelfSignedId = $data['SelfSignedId'] ?? '';
        $deviceId = $data['DeviceId'] ?? '';
        $language = $data['LanguageCode'] ?? '';
        $isediter = $data['IsEditorMode'] ?? '';
        $deviceos = $data['DeviceOS'] ?? '';
        $clientId = $data['ClientRandomId'] ?? '';
        $version = $data['GameVersion'] ?? '';
        $platform = $data['PlatformType'] ?? '';
        $xuid = $data['xuid'] ?? '';
        $name = $data['ThirdPartyName'] ?? '';
        $skin = $data['SkinData'] ?? '';
        $ip = $data['ip'] ?? '';
        $name = $data['ThirdPartyName'] ?? '';
        variable::setData($xuid, "xuid", $xuid);
        variable::setData($xuid, "SelfSignedId", $SelfSignedId);
        variable::setData($xuid, "SkinId", $SkinId);
        variable::setData($xuid, "SkinResourcePatch", $SkinResourcePatch);
        variable::setData($xuid, "name", $name);
        variable::setData($xuid, "player_language", $language);
        variable::setData($xuid, "deviceos", $deviceos);
        variable::setData($xuid, "clientid", $clientId);
        variable::setData($xuid, "version", $version);
        variable::setData($xuid, "platform", $platform);
        variable::setData($xuid, "editer", $isediter);
        variable::setData($xuid, "deviceId", $deviceId);
        variable::setData($xuid, "skin", $skin);
        variable::setData($xuid, "ip", $ip);
        $sqldata = sql::get("player_info", $xuid);
        if ($sqldata !== null) {
            variable::setData($xuid, "sql_name", $sqldata['name'] ?? '');
            variable::setData($xuid, "sql_ip", $sqldata['ip'] ?? '');
            variable::setData($xuid, "sql_deviceid", $sqldata['deviceid'] ?? '');
            variable::setData($xuid, "sql_clientid", $sqldata['clientID']);
            variable::setData($xuid, "sql_selfsignedid", $sqldata['SelfSignedId'] ?? '');
            variable::setData($xuid, "sql_skinid", $sqldata['SkinId'] ?? '');
            variable::setData($xuid, "sql_skinresourcepatch", $sqldata['SkinResourcePatch'] ?? '');
        }
        sql::runsql("INSERT INTO player_info (xuid, name, ip, deviceid, clientID, SelfSignedId, SkinId, SkinResourcePatch) VALUES (:xuid, :name, :ip, :deviceid, :clientID, :SelfSignedId, :SkinId, :SkinResourcePatch) ON DUPLICATE KEY UPDATE name = VALUES(name), ip = VALUES(ip), deviceid = VALUES(deviceid), clientID = VALUES(clientID), SelfSignedId = VALUES(SelfSignedId), SkinId = VALUES(SkinId), SkinResourcePatch = VALUES(SkinResourcePatch);", [':xuid' => $xuid, ':name' => $name, ':ip' => $ip, ':deviceid' => $deviceId, ':clientID' => $clientId, ':SelfSignedId' => $SelfSignedId, ':SkinId' => $SkinId, ':SkinResourcePatch' => $SkinResourcePatch]);
    }

    public static function pmlogin($ev): void
    {
        $player = $ev->getPlayer();
        $moderate = new moderate;
        $moderate->bancheck($player);
    }

    public static function pmjoin($ev): void {}

    public static function connection($ev): void
    {
        $packet = $ev->getPacket();
        $origin = $ev->getOrigin();
        $ip = $origin->getip();
        $clientDataJwt = $packet->clientDataJwt;
        $chainDataJwt = $packet->chainDataJwt;
        $decodedJwt = self::decodeJwt($clientDataJwt);
        $chainDataJwt = $chainDataJwt->chain;
        $decodedChainData = [];
        foreach ($chainDataJwt as $jwtToken) {
            $decodedChainData[] = self::decodeJwt($jwtToken);
        }
        foreach ($decodedChainData as $item) {
            if (isset($item['payload']['extraData']['XUID'])) {
                $xuid = $item['payload']['extraData']['XUID'];
                break;
            }
        }
        $payload = $decodedJwt['payload'] ?? [];
        $payload['xuid'] = $xuid;
        $payload['ip'] = $ip;
        self::login($payload);
    }

    public static function decodeJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return ['error' => 'Invalid JWT format'];
        }
        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);

        return [
            'header' => $header,
            'payload' => $payload,
        ];
    }
}

class variable
{
    public static array $playerData = [];

    public static function setData($player, string $key, $value): void
    {
        $uuid = self::uuid($player);
        if (!isset(self::$playerData[$uuid])) {
            self::$playerData[$uuid] = [];
        }
        self::$playerData[$uuid][$key] = $value;
    }

    public static function getData($player, string $key)
    {
        $uuid = self::uuid($player);
        $return = self::$playerData[$uuid][$key] ?? "null";
        return $return;
    }

    public static function removePlayerData($player): void
    {
        $uuid = self::uuid($player);
        unset(self::$playerData[$uuid]);
    }

    public static function hasData($player, string $key): bool
    {
        $uuid = self::uuid($player);
        return isset(self::$playerData[$uuid][$key]);
    }

    public static function uuid($player): string
    {
        if (is_string($player)) {
            return $player;
        } else {
            $uuid = $player->getxuid();
            if ($uuid == "") {
                $uuid = "0000000000000000";
            }
            return $uuid;
        }
    }

    public static function getAllData($player): array
    {
        $uuid = self::uuid($player);
        return self::$playerData[$uuid] ?? [];
    }
}