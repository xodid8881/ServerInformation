<?php


declare(strict_types = 1);

namespace ServerInformation\InventoryLib;

use pocketmine\utils\EnumTrait;
use pocketmine\block\BlockLegacyIds;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class LibInvType{
	use EnumTrait;

	protected static function setup() : void{
		self::registerAll(
			new self('chest'),
			new self('double_chest'),
			new self('dropper'),
			new self('hopper')
		);
	}

	public function isDouble() : bool{
		return ($this->id() === self::DOUBLE_CHEST()->id());
	}

	public function getWindowType() : int{
		if($this->id() === self::CHEST()->id() || $this->id() === self::DOUBLE_CHEST()->id()){
			return WindowTypes::CONTAINER;
		}else if($this->id() === self::DROPPER()->id()){
			return WindowTypes::DROPPER;
		}else if($this->id() === self::HOPPER()->id()){
			return WindowTypes::HOPPER;
		}
		return 0;
	}

	public function getSize() : int{
		if($this->id() === self::CHEST()->id()){
			return 27;
		}else if($this->id() === self::DOUBLE_CHEST()->id()){
			return 54;
		}else if($this->id() === self::DROPPER()->id()){
			return 9;
		}else if($this->id() === self::HOPPER()->id()){
			return 5;
		}
		return 0;
	}

	public function getBlockId() : int{
		if($this->id() === self::CHEST()->id() || $this->id() === self::DOUBLE_CHEST()->id()){
			return BlockLegacyIds::CHEST;
		}else if($this->id() === self::DROPPER()->id()){
			return BlockLegacyIds::DROPPER;
		}else if($this->id() === self::HOPPER()->id()){
			return BlockLegacyIds::HOPPER_BLOCK;
		}
		return 0;
	}

}
