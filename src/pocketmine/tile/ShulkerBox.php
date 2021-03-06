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

use pocketmine\inventory\ShulkerBoxInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class ShulkerBox extends Spawnable implements InventoryHolder, Container, Nameable {
    use NameableTrait, ContainerTrait;

    protected $inventory;

    public function __construct(Level $level, CompoundTag $nbt){
        parent::__construct($level, $nbt);
        $this->inventory = new ShulkerBoxInventory($this);
        $this->loadItems();
    }

    /**
     * @return int
     */
    public function getSize(){
        return 27;
    }

    public function getDefaultName(): string{
        return "Shulker Box";
    }

    /**
     * Get the object related inventory
     *
     * @return Inventory
     */
    public function getInventory(){
        return $this->inventory;
    }

    public function getRealInventory(){
        return $this->inventory;
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->saveItems();
    }

    public function addAdditionalSpawnData(CompoundTag $nbt){
        $nbt->setTag($this->namedtag->getTag("Items"));
        if($this->hasName()){
            $nbt->setTag($this->namedtag->getTag("CustomName"));
        }
    }

    public function close(){
        if($this->closed === false){
            $this->inventory->removeAllViewers(true);
            $this->inventory = null;
            parent::close();
        }
    }
}