<?php

/*
 *
 *    _______                    _
 *   |__   __|                  (_)
 *      | |_   _ _ __ __ _ _ __  _  ___
 *      | | | | | '__/ _` | '_ \| |/ __|
 *      | | |_| | | | (_| | | | | | (__
 *      |_|\__,_|_|  \__,_|_| |_|_|\___|
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Turanic
 *
 */

declare(strict_types=1);

namespace pocketmine\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\item\Potion;
use pocketmine\level\Level;
use pocketmine\level\particle\SpellParticle;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;

class ThrownPotion extends Projectile {
	const NETWORK_ID = self::THROWN_POTION;

	const DATA_POTION_ID = 37;

	public $width = 0.25;
	public $height = 0.25;

	protected $gravity = 0.1;
	protected $drag = 0.05;

	/**
	 * ThrownPotion constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 * @param Entity|null $shootingEntity
	 */
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		if(!isset($nbt->PotionId)){
			$nbt->setShort("PotionId", Potion::AWKWARD);
		}

		parent::__construct($level, $nbt, $shootingEntity);

		unset($this->dataProperties[self::DATA_SHOOTER_ID]);
		$this->setDataProperty(self::DATA_POTION_ID, self::DATA_TYPE_SHORT, $this->getPotionId());
	}

	/**
	 * @return int
	 */
	public function getPotionId() : int{
		return $this->namedtag->getShort("PotionId", Potion::AWKWARD);
	}

	/**
	 * @param $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate(int $currentTick){
		$this->timings->startTiming();

        if($this->isCollided || $this->age > 1200){
            $color = Potion::getColor($this->getPotionId());
            $this->getLevel()->addParticle(new SpellParticle($this, $color[0], $color[1], $color[2]));
            $radius = 6;
            foreach($this->getLevel()->getNearbyEntities($this->getBoundingBox()->grow($radius, $radius, $radius)) as $p){
                foreach(Potion::getEffectsById($this->getPotionId()) as $effect){
                    $p->addEffect($effect);
                }
            }
            $this->kill();
        }

        $hasUpdate =  parent::onUpdate($currentTick);

		$this->timings->stopTiming();

		return $hasUpdate;
	}

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->type = ThrownPotion::NETWORK_ID;
		$pk->entityRuntimeId = $this->getId();
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

	public function onCollideWithPlayer(Player $player): bool{
        return false;
    }
}