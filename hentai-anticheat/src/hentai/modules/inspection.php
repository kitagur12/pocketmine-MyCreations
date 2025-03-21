<?php

namespace hentai\modules;


use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\player\GameMode;
use pocketmine\entity\Human;
use pocketmine\player\Player;
use ReflectionClass;
use hentai\info;

class inspection
{
    public static array $human = [];

    public function check($packet, $player)
    {
        $xuid = $player->getXuid();
        if (!isset(self::$human[$xuid])) {
            return;
        }
        if (self::$human[$xuid]["count"] == 15) {
            self::$human[$xuid]["count"] = 0;

            $ping = $player->getNetworkSession()->getPing();
            $pinglag = info::$config["config"]["checks"]["inventoryMove"]["pinglag"];
            $multiplier = info::$config["config"]["checks"]["inventoryMove"]["multiplier"];
            $range = info::$config["config"]["checks"]["inventoryMove"]["range"];
            info::$playerinfo[$xuid]["invmove"]["open_invtime"] = info::$playerinfo[$xuid]["invmove"]["open_invtime"] ?? 0;
            if ((info::$playerinfo[$xuid]["invmove"]["open_invtime"] + round($ping * ($multiplier + ($ping / $range))) + $pinglag < round(microtime(true) * 1000))) {
                self::$human[$xuid]["human"]->teleport($player->getPosition()->asVector3());
            }
        } else {
            self::$human[$xuid]["count"] += 1;
        }

        if (!isset(info::$playerinfo[$xuid]["inspection"]["botx"])) {
            info::$playerinfo[$xuid]["inspection"]["botx"] = 0;
        }
        if (!isset(info::$playerinfo[$xuid]["inspection"]["boty"])) {
            info::$playerinfo[$xuid]["inspection"]["botz"] = 0;
        }
        if (!isset(info::$playerinfo[$xuid]["inspection"]["botz"])) {
            info::$playerinfo[$xuid]["inspection"]["botz"] = 0;
        }
        if ((info::$playerinfo[$xuid]["inspection"]["botx"] == self::$human[$xuid]["human"]->getPosition()->getX() || info::$playerinfo[$xuid]["inspection"]["botz"] == self::$human[$xuid]["human"]->getPosition()->getZ()) && info::$playerinfo[$xuid]["inspection"]["boty"] < $player->getPosition()->getY()) {
            self::$human[$xuid]["human"]->teleport(self::$human[$xuid]["human"]->getPosition()->asVector3()->add(0, 0.6, 0));
        }

        info::$playerinfo[$xuid]["inspection"]["botx"] = self::$human[$xuid]["human"]->getPosition()->getX();
        info::$playerinfo[$xuid]["inspection"]["boty"] = self::$human[$xuid]["human"]->getPosition()->getY();
        info::$playerinfo[$xuid]["inspection"]["botz"] = self::$human[$xuid]["human"]->getPosition()->getZ();
        //var_dump($packet);
        if ($packet instanceof PlayerAuthInputPacket) {
            $flags = $packet->getInputFlags();

            $reflection = new ReflectionClass(self::$human[$xuid]["human"]);
            $human = $reflection->getMethod('move');
            $human->setAccessible(true);

            if ($flags->get(PlayerAuthInputFlags::START_SPRINTING)) {
                self::$human[$xuid]["sprint"] = 0.27;
            }

            if ($flags->get(PlayerAuthInputFlags::STOP_SPRINTING)) {
                self::$human[$xuid]["sprint"] = 0.21;
            }

            if ($flags->get(PlayerAuthInputFlags::START_SNEAKING)) {
                self::$human[$xuid]["sprint"] = 0.06;
                self::$human[$xuid]["human"]->setSneaking(true);
            }

            if ($flags->get(PlayerAuthInputFlags::STOP_SPRINTING)) {
                self::$human[$xuid]["sprint"] = 0.21;
                self::$human[$xuid]["human"]->setSneaking(false);
            }

            if ($flags->get(PlayerAuthInputFlags::START_JUMPING)) {
                self::$human[$xuid]["human"]->jump();
            }

            $speed = self::$human[$xuid]["sprint"];
            $yaw = $player->getLocation()->yaw;
            $rad = deg2rad($yaw);

            if ($flags->get(PlayerAuthInputFlags::UP)) {
                $dx = -sin($rad) * $speed;
                $dz = cos($rad) * $speed;
                $human->invoke(self::$human[$xuid]["human"], $dx, 0, $dz);
            }

            if ($flags->get(PlayerAuthInputFlags::DOWN)) {
                $dx = -sin($rad + M_PI) * $speed;
                $dz = cos($rad + M_PI) * $speed;
                $human->invoke(self::$human[$xuid]["human"], $dx, 0, $dz);
            }

            if ($flags->get(PlayerAuthInputFlags::LEFT)) {
                $dx = -sin($rad - M_PI_2) * $speed;
                $dz = cos($rad - M_PI_2) * $speed;
                $human->invoke(self::$human[$xuid]["human"], $dx, 0, $dz);
            }

            if ($flags->get(PlayerAuthInputFlags::RIGHT)) {
                $dx = -sin($rad + M_PI_2) * $speed;
                $dz = cos($rad + M_PI_2) * $speed;
                $human->invoke(self::$human[$xuid]["human"], $dx, 0, $dz);
            }
            $gameMode = [
                GameMode::ADVENTURE,
                GameMode::SURVIVAL
            ];
            //var_dump($player->getGamemode());
            if (in_array($player->getGamemode(), $gameMode))
                if ($player->getPosition()->asVector3()->distance(self::$human[$xuid]["human"]->getPosition()->asVector3()) > 6) {
                    info::$playerinfo[$xuid]["global"]["check_score"] += 100;
                    info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] = info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] ?? 0;
                    info::$playerinfo[$xuid]["global"]["module_score"]["invmove"] += 100;
                }
        }

        if ($packet instanceof ContainerClosePacket) {
            info::$playerinfo[$xuid]["invmove"]["open_inv"] = false;
            self::$human[$xuid]["human"]->teleport($player->getPosition()->asVector3());
        }
    }

    public function update($packet, $player)
    {
        $xuid = $player->getXuid();

        if ($packet instanceof MoveActorAbsolutePacket) {
            $botlist = [];
            foreach (self::$human as $data) {
                $botlist[] = $data["human"]->getId();
                
            }
            if (in_array($packet->actorRuntimeId, $botlist)) {
                $packet->position = $packet->position->add(0, 1000000, 0);
            }
        }
        
        if ($packet instanceof ContainerOpenPacket) {
            self::$human[$xuid]["human"]->teleport($player->getPosition()->asVector3());
            info::$playerinfo[$xuid]["invmove"]["open_inv"] = true;
            info::$playerinfo[$xuid]["invmove"]["Camera"] = false;
            info::$playerinfo[$xuid]["invmove"]["opened_inv"] = false;
            info::$playerinfo[$xuid]["invmove"]["module_score"] = 0;
            info::$playerinfo[$xuid]["invmove"]["open_invtime"] = round(microtime(true) * 1000);
            //var_dump(info::$playerinfo[$xuid]["invmove"]["open_invtime"]);
        }
    }

    public function spawn($player)
    {
        $xuid = $player->getXuid();
        self::$human[$xuid]["sprint"] = 0.21;
        self::$human[$xuid]["count"] = 0;
        self::$human[$xuid]["human"] = new Human($player->getLocation(), $player->getSkin());
        self::$human[$xuid]["human"]->spawnToAll();
    }

    public function kill($player)
    {
        $xuid = $player->getXuid();
        if (isset( self::$human[$xuid]["human"])) {
            self::$human[$xuid]["human"]->kill();
            unset(self::$human[$xuid]);
        }
    }

    public function damage($event)
    {
        $entity = $event->getEntity();
        foreach (self::$human as $data) {
            $humanEntity = $data["human"];
            if ($entity == $humanEntity) {
                $event->cancel();
            }
        }
    }

    public function teleport($event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $xuid = $entity->getXuid();
            if (!isset(self::$human[$xuid])) {
                return;
            }
            self::$human[$xuid]["human"]->teleport($event->getTo()->asVector3());
        }
    }

    public function motion($event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $xuid = $entity->getXuid();
            if (!isset(self::$human[$xuid])) {
                return;
            }
            self::$human[$xuid]["human"]->setMotion($event->getVector());
        }
    }
}
