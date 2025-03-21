<?php

namespace practice\handler;

use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\GameRuleType;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;

class GameruleHandler
{
    public static function setGamerule($player, $gameRuleName, $value)
    {
        if ($value) {
            $className = "practice\\handler\\GameRuletrue";
        } else {
            $className = "practice\\handler\\GameRulefalse";
        }
        $packet = GameRulesChangedPacket::create([
            $gameRuleName => (new $className())
        ]);
        $player->getNetworkSession()->sendDataPacket($packet);
    }
    
    
}

class GameRuletrue extends GameRule
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function getTypeId(): int
    {
        return GameRuleType::BOOL;
    }

    public function encode(PacketSerializer $out): void
    {
        $out->putBool(true);
    }
}

class GameRulefalse extends GameRule
{
    public function __construct()
    {
        parent::__construct(false);
    }

    public function getTypeId(): int
    {
        return GameRuleType::BOOL;
    }

    public function encode(PacketSerializer $out): void
    {
        $out->putBool(false);
    }
}
