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

use ServerInformation\InventoryLib\InvLibManager;
use ServerInformation\InventoryLib\LibInvType;
use ServerInformation\InventoryLib\InvLibAction;
use ServerInformation\InventoryLib\SimpleInventory;
use ServerInformation\InventoryLib\LibInventory;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\inventory\ContainerInventory;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;

class EventListener implements Listener
{
  
  protected $plugin;
  
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
      if (is_null($packet->formData)) return;
      $data = json_decode($packet->formData, true);
      if (!is_array($data)) return;
      if (is_null($data)) return;
      if ($id === 156321) {
        if ($data === 0) {
          $player->sendMessage( $this->plugin->tag() . '이용을 종료했습니다.');
          return true;
        }
      }
      if ($id === 156322) {
        if ($data === 0) {
          $player->sendMessage( $this->plugin->tag() . '이용을 종료했습니다.');
          return true;
        }
      }
      if ($id === 156323) {
        if ($data === 0) {
          $player->sendMessage( $this->plugin->tag() . '이용을 종료했습니다.');
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
          if ($id == 144) {
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버동접"){
              $inv->onClose ($player);
              $this->plugin->onPlayerOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f다음 페이지"){
              $this->plugin->pldb [$name] ["Page"] += 1;
              $this->plugin->save ();
              $page = (int)$this->plugin->pldb [$name] ["Page"];
              if (isset($this->plugin->pldb ["플레이어"] [$page])){
                $inv->onClose ($player);
                $this->plugin->onPlayerOpen ($player);
                return true;
              } else {
                $player->sendMessage ("[ 서버정보 ] 이전 페이지는 존재하지 않습니다.");
                $inv->onClose ($player);
                return true;
              }
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f이전 페이지"){
              $this->plugin->pldb [$name] ["Page"] -= 1;
              $this->plugin->save ();
              $page = (int)$this->plugin->pldb [$name] ["Page"];
              if ($page >= 1){
                if (isset($this->plugin->pldb ["플레이어"] [$page])){
                  $inv->onClose ($player);
                  $this->plugin->onPlayerOpen ($player);
                  return true;
                } else {
                  $player->sendMessage ("[ 서버정보 ] 이전 페이지는 존재하지 않습니다.");
                  $inv->onClose ($player);
                  return true;
                }
              } else {
                $player->sendMessage ("[ 서버정보 ] 이전 페이지는 존재하지 않습니다.");
                $inv->onClose ($player);
                return true;
              }
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버콘텐츠"){
              $inv->onClose ($player);
              $this->plugin->onConTentsUIOpen ($player);
              return true;
            }
            if ($inv->getItem ($slot)->getCustomName() == "§r§f서버약관"){
              $inv->onClose ($player);
              $this->plugin->onClausesOpen ($player);
              return true;
            }
          }
          if ($id == 397 && $damage == 3) {
            $inv->onClose ($player);
            $name = $inv->getItem ($slot)->getCustomName();
            $players = Server::getInstance()->getPlayerByPrefix ( $name );
            $this->plugin->onPlayerInfoOpen ($player, $players);
            return true;
          }
        }
      }
    }
  }
}
