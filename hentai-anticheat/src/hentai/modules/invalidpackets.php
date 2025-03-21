<?php

namespace hentai\modules;

use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use ReflectionClass;
use hentai\info;

class invalidpackets
{
    private array $inmenu;

    public function check($packet, $player)
    {
    }
    
    public function update($packet, $player)
    {
    }
}
