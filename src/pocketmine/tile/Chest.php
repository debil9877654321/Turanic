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

namespace pocketmine\tile;

use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

class Chest extends Spawnable implements InventoryHolder, Container, Nameable {
    use NameableTrait, ContainerTrait;

	/** @var ChestInventory */
	protected $inventory;
	/** @var DoubleChestInventory */
	protected $doubleInventory = null;

	/**
	 * Chest constructor.
	 *
	 * @param Level       $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->inventory = new ChestInventory($this);
        $this->loadItems();
	}

	public function close(){
		if($this->closed === false){
            $this->inventory->removeAllViewers(true);

            if($this->doubleInventory !== null){
				$this->doubleInventory->removeAllViewers(true);
				$this->doubleInventory->invalidate();
				$this->doubleInventory = null;
			}

			$this->inventory = null;

			parent::close();
		}
	}

	public function saveNBT(){
	    parent::saveNBT();
		$this->saveItems();
	}

	/**
	 * @return int
	 */
	public function getSize(){
		return 27;
	}

	/**
	 * @return ChestInventory|DoubleChestInventory
	 */
	public function getInventory(){
		if($this->isPaired() and $this->doubleInventory === null){
			$this->checkPairing();
		}
		return $this->doubleInventory instanceof DoubleChestInventory ? $this->doubleInventory : $this->inventory;
	}

	/**
	 * @return ChestInventory
	 */
	public function getRealInventory(){
		return $this->inventory;
	}

	/**
	 * @return DoubleChestInventory|null
	 */
	public function getDoubleInventory(){
		return $this->doubleInventory;
	}

	protected function checkPairing(){
		if($this->isPaired() and !$this->getLevel()->isChunkLoaded($this->namedtag->getInt("pairx") >> 4, $this->namedtag->getInt("pairz") >> 4)){
			//paired to a tile in an unloaded chunk
			$this->doubleInventory = null;

		}elseif(($pair = $this->getPair()) instanceof Chest){
			if(!$pair->isPaired()){
				$pair->createPair($this);
				$pair->checkPairing();
			}
			if($this->doubleInventory === null){
				if(($p = $pair->getDoubleInventory()) instanceof DoubleChestInventory){
					$this->doubleInventory = $p;
				}else{
					if(($pair->x + ($pair->z << 15)) > ($this->x + ($this->z << 15))){ //Order them correctly
						$this->doubleInventory = new DoubleChestInventory($pair, $this);
					}else{
						$this->doubleInventory = new DoubleChestInventory($this, $pair);
					}
				}
			}
		}else{
			$this->doubleInventory = null;
			$this->namedtag->removeTag("pairx", "pairz");
		}
	}

    public function getDefaultName() : string{
        return "Chest";
  	}

	/**
	 * @return bool
	 */
	public function isPaired() : bool{
	    return $this->namedtag->hasTag("pairx") and $this->namedtag->hasTag("pairz");
	}

	/**
	 * @return Chest
	 */
	public function getPair(){
        if($this->isPaired()){
            $tile = $this->getLevel()->getTileAt($this->namedtag->getInt("pairx"), $this->y, $this->namedtag->getInt("pairz"));
            if($tile instanceof Chest){
                return $tile;
            }
        }
        return null;
	}

	/**
	 * @param Chest $tile
	 *
	 * @return bool
	 */
	public function pairWith(Chest $tile){
		if($this->isPaired() or $tile->isPaired()){
			return false;
		}

		$this->createPair($tile);

		$this->onChanged();
		$tile->onChanged();
		$this->checkPairing();

		return true;
	}

	/**
	 * @param Chest $tile
	 */
	private function createPair(Chest $tile){
		$this->namedtag->setInt("pairx", $tile->x);
		$this->namedtag->setInt("pairz", $tile->z);

		$tile->namedtag->setInt("pairx", $this->x);
		$tile->namedtag->setInt("pairz", $this->z);
	}

	/**
	 * @return bool
	 */
	public function unpair(){
		if(!$this->isPaired()){
			return false;
		}

		$tile = $this->getPair();
        $this->namedtag->removeTag("pairx", "pairz");

		$this->onChanged();

		if($tile instanceof Chest){
            $this->namedtag->removeTag("pairx", "pairz");
			$tile->checkPairing();
			$tile->onChanged();
		}
		$this->checkPairing();

		return true;
	}

	public function addAdditionalSpawnData(CompoundTag $nbt){
        if($this->isPaired()) {
            $nbt->setTag($this->namedtag->getTag("pairx"));
            $nbt->setTag($this->namedtag->getTag("pairz"));
        }

        if($this->hasName()) {
            $nbt->setTag($this->namedtag->getTag("CustomName"));
        }
    }

    /**
     * @param CompoundTag $nbt
     * @param Vector3 $pos
     * @param null $face
     * @param Item|null $item
     * @param null $player
     */
    protected static function createAdditionalNBT(CompoundTag $nbt, Vector3 $pos, $face = null, $item = null, $player = null){
        $nbt->setTag(new ListTag("Items", [], NBT::TAG_Compound));
		if($item !== null and $item->hasCustomName()){
			$nbt->setString("CustomName", $item->getCustomName());
		}
    }
}
