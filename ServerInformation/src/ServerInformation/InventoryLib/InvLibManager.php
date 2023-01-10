<?php

declare(strict_types = 1);

namespace ServerInformation\InventoryLib;

use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\world\Position;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

use const null;

final class InvLibManager{

	private static ?TaskScheduler $scheduler = null;

	public static function register(Plugin $plugin) : void{
		if(self::$scheduler === null){
			self::$scheduler = $plugin->getScheduler();
			Server::getInstance()->getPluginManager()->registerEvent(InventoryTransactionEvent::class, function(InventoryTransactionEvent $ev) : void{
				$transaction = $ev->getTransaction();
				foreach($transaction->getActions() as $action){
					if(!$action instanceof SlotChangeAction) continue;
					$inventory = $action->getInventory();
					if(!$inventory instanceof LibInventory) continue;
					(function() use($transaction, $action, $ev){
						if($this->onActionSenssor(new InvLibAction($transaction->getSource(), $action->getSlot(), $action->getSourceItem(), $action->getTargetItem()))){
							$ev->cancel();
						}
					})->call($inventory);
				}
			}, EventPriority::NORMAL, $plugin, true);
		}
	}

	public static function getScheduler() : ?TaskScheduler{
		return self::$scheduler;
	}

	public static function create(LibInvType $type, Position $holder, string $title = '') : LibInventory{
		return new LibInventory($type, $holder, $title);
	}

}
