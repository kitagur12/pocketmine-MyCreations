<?php

namespace practice\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\utils\Random;
use practice\command\commands\hook;

class FishingHookEntity  extends Projectile
{
    public static function getNetworkTypeId(): string
    {
        return EntityIds::FISHING_HOOK;
    }
    protected function getInitialDragMultiplier(): float
    {
        return 0;
    }

    protected function getInitialGravity(): float
    {
        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.25, 0.25);
    }

    public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $nbt);

        if ($shootingEntity instanceof Player) {
            $direction = $shootingEntity->getDirectionVector();
            $newPos = $shootingEntity->getPosition()->addVector($direction->multiply(0));
            $newPos = $newPos->add(0,1.3,0);
            $this->teleport($newPos);
            $this->setMotion($shootingEntity->getDirectionVector()->multiply(0.4));
            $this->setGravity(hook::$hook[0] ?? 0.06);
            $this->handleHookCasting($this->getMotion()->getX(), $this->getMotion()->getY(), $this->getMotion()->getZ(), hook::$hook[1] ?? 3);
        } else {
            $this->flagForDespawn();
        }
    }

    public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $damage = $this->getResultDamage();

        if ($this->getOwningEntity() !== null) {
            $event = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);

            if (!$event->isCancelled()) {
                $entityHit->attack($event);
            }
        }

        $this->isCollided = true;
        $this->flagForDespawn();
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        $player = $this->getOwningEntity();
        $despawn = false;
        if ($player instanceof Player) {
            if (
                $player->getInventory()->getItemInHand()->getTypeId() !== ItemTypeIds::FISHING_ROD ||
                !$player->isAlive() ||
                $player->isClosed() ||
                $player->getLocation()->getWorld()->getFolderName() !== $this->getLocation()->getWorld()->getFolderName()
            ) {
                $despawn = true;
            }
        } else {
            $despawn = true;
        }

        if ($despawn) {
            $this->flagForDespawn();
            $hasUpdate = true;
        }

        return $hasUpdate;
    }

	public function handleHookCasting(float $x, float $y, float $z, float $p) : void{
		$rand = new Random();
		$this->motion->x = $x * $p;
		$this->motion->y = $y * $p;
		$this->motion->z = $z * $p;
	}
}
