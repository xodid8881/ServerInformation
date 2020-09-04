<?php
declare(strict_types=1);

namespace ServerInformation;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\item\Item;
use pocketmine\tile\Chest;
use ServerInformation\Inventory\DoubleChestInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;

class EventListener implements Listener
{
  
  protected $plugin;
  
  public function __construct(ServerInformation $plugin)
  {
    $this->plugin = $plugin;
  }
  public function onInvClose(InventoryCloseEvent $ev) {
    $player = $ev->getPlayer();
    $inv = $ev->getInventory();
    if ($inv instanceof DoubleChestInventory) {
      $inv->onClose($player);
      return true;
    }
  }
  public function onTransaction(InventoryTransactionEvent $event) {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if ($inv instanceof DoubleChestInventory) {
          $slot = $action->getSlot ();
          $id = $inv->getItem ($slot)->getId ();
          $damage = $inv->getItem ($slot)->getDamage ();
          if ($id == 144) {
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버동접"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->onPlayerOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버컨탠츠"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->onConTentsUIOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버약관"){
              $event->setCancelled ();
              $inv->onClose ($player);
              $this->plugin->onClausesOpen ($player);
              return true;
            }
          }
          if ($id == 397 && $damage == 3) {
            $event->setCancelled ();
            $inv->onClose ($player);
            $name = $inv->getItem ($slot)->getCustomName();
            $players = Server::getInstance()->getPlayer ( $name );
            $this->plugin->onPlayerInfoOpen ($player, $players);
            return true;
          }
        }
      }
    }
  }
}
