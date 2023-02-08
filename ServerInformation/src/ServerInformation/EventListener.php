<?php
declare(strict_types=1);

namespace ServerInformation;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\tile\Chest;

use LifeInventoryLib\InventoryLib\InvLibManager;
use LifeInventoryLib\InventoryLib\LibInvType;
use LifeInventoryLib\InventoryLib\InvLibAction;
use LifeInventoryLib\InventoryLib\SimpleInventory;
use LifeInventoryLib\InventoryLib\LibInventory;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener
{

  protected $plugin;
  private $warpname;
  public function __construct(ServerInformation $plugin)
  {
    $this->plugin = $plugin;
  }
  public function onJoin(PlayerJoinEvent $event) {
    $player = $event->getPlayer();
    $name = $player->getName();
    if (!isset($this->plugin->pldb [$name])){
      $this->plugin->pldb [$name] ["Page"] = 1;
      $this->plugin->save ();
    }
  }

  public function onPacket(DataPacketReceiveEvent $event)
  {
    $packet = $event->getPacket();
    $player = $event->getOrigin()->getPlayer();
    if($packet instanceof ModalFormResponsePacket) {
      $name = $player->getName();
      $id = $packet->formId;
      if($packet->formData == null) {
        return true;
      }
      $data = json_decode($packet->formData, true);
      if ($id === 156321) {
        if ($data === 0) {
          $this->plugin->onOpenWarpSettingLists ($player);
          return true;
        }
        if ($data === 1) {
          $player->sendMessage( ServerInformation::PREFIX . 'Use has ended.');
          return true;
        }
        if ($data === 2) {
          $player->sendMessage( ServerInformation::PREFIX . 'Use has ended.');
          return true;
        }
      }
      if ($id === 156322) {
        if ($data === 0) {
          $player->sendMessage( ServerInformation::PREFIX . 'Use has ended.');
          return true;
        }
      }
      if ($id === 156323) {
        if ($data === 0) {
          $player->sendMessage( ServerInformation::PREFIX . 'Use has ended.');
          return true;
        }
      }
    }
  }

  public function onTransaction(InventoryTransactionEvent $event) {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        $inv = $action->getInventory();
        if($inv instanceof LibInventory){
          $slot = $action->getSlot ();
          $id = $inv->getItem ($slot)->getId ();
          $damage = $inv->getItem ($slot)->getMeta ();
          if ($inv->getTitle() == "WarpConTents"){
            $itemname = $inv->getItem ($slot)->getCustomName();
            if ($this->plugin->messagedb ["ServerContentsUi"] ["WarpSlot"] [$itemname]){
              $type = $this->plugin->messagedb ["ServerContentsUi"] ["WarpSlot"] [$itemname] ["type"];
              $this->warpname [$name] = $itemname;
              $event->cancel ();
              $inv->onClose ($player);
              $this->plugin->onOpenWarpLists ($player,$type);
            }
          }
          if ($inv->getTitle() == "Warps"){
            $itemname = $inv->getItem ($slot)->getCustomName();
            $warpslotname = $this->warpname [$name];
            if ($this->plugin->messagedb ["ServerContentsUi"] ["ContentsSlotSetting"] [$warpslotname]){
              $WarpCommand = $this->plugin->messagedb ["ServerContentsUi"] ["ContentsSlotSetting"] [$warpslotname] ["WarpList"] [$itemname] ["Command"];
              $WarpMessage = $this->plugin->messagedb ["ServerContentsUi"] ["ContentsSlotSetting"] [$warpslotname] ["WarpList"] [$itemname] ["Message"];
              $player->sendMessage ("$WarpMessage");
              $event->cancel ();
              $inv->onClose ($player);
              $this->plugin->getServer ()->getCommandMap ()->dispatch ( $player, $WarpCommand );
            }
          }
          if ($id == 144) {
            $event->cancel ();
            if ($inv->getItem ($slot)->getCustomName() == "Server Player Count"){
              $inv->onClose ($player);
              $this->plugin->onPlayerOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "Next Page"){
              $this->plugin->pldb [$name] ["Page"] += 1;
              $this->plugin->save ();
              $page = (int)$this->plugin->pldb [$name] ["Page"];
              if (isset($this->plugin->pldb ["player"] [$page])){
                $inv->onClose ($player);
                $this->plugin->onPlayerOpen ($player);
                return true;
              } else {
                $player->sendMessage ("The page before [Server Information] does not exist.");
                $inv->onClose ($player);
                return true;
              }
            }
            if ($inv->getItem ($slot)->getCustomName() == "Back page"){
              $this->plugin->pldb [$name] ["Page"] -= 1;
              $this->plugin->save();
              $page = (int)$this->plugin->pldb [$name] ["Page"];
              if ($page >= 1){
                if (isset($this->plugin->pldb ["player"] [$page])){
                  $inv->onClose ($player);
                  $this->plugin->onPlayerOpen ($player);
                  return true;
                } else {
                  $player->sendMessage ("The page before [Server Information] does not exist.");
                  $inv->onClose ($player);
                  return true;
                }
              } else {
                $player->sendMessage ("The page before [Server Information] does not exist.");
                $inv->onClose ($player);
                return true;
              }
            }
            if ($inv->getItem ($slot)->getCustomName() == "Server Contects"){
              $inv->onClose ($player);
              $this->plugin->onConTentsUIOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "Server Agree"){
              $inv->onClose ($player);
              $this->plugin->onClausesOpen ($player);
              return true;
            }
          }
          if ($id == 397 && $damage == 3) {
            $event->cancel ();
            $inv->onClose ($player);
            $pname = $inv->getItem ($slot)->getCustomName();
            foreach ( $this->plugin->getServer ()->getOnlinePlayers () as $players ) {
              if ($players->getName () == $pname){
                $this->plugin->onPlayerInfoOpen ($player, $players);
                return true;
              }
            }
          }
        }
      }
    }
  }
}
