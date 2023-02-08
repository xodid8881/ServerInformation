<?php
declare(strict_types=1);

namespace ServerInformation;

use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use ServerInformation\Commands\EventCommand;

use LifeInventoryLib\LifeInventoryLib;
use LifeInventoryLib\InventoryLib\LibInvType;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\block\Block;
use pocketmine\tile\Chest;
// monster
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\world\Position;

class ServerInformation extends PluginBase
{
  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;
  const PREFIX = "§c【 §fServerInformation §c】  §7: ";

  public static function getInstance(): ServerInformation
  {
    return static::$instance;
  }

  public function onLoad():void
  {
    self::$instance = $this;
  }

  public function onEnable():void
  {
    $this->player = new Config ($this->getDataFolder() . "players.yml", Config::YAML);
    $this->pldb = $this->player->getAll();
    $this->message = new Config ($this->getDataFolder() . "messages.yml", Config::YAML,
    [
      "ServerContentsMessage" => "§r§7Server Contents\nMineFarm Server",
      "ServerAgreeMessage" => "§r§7ServerAgree\nThank You",
      "ServerContentsUi" => [
        "WarpSlot" => [
          "ServerWarp" => [
            "ItemSlot" => 1,
            "Item" => 1,
            "type" => "DOUBLE_CHEST"
            // DOUBLE_CHEST, CHEST
          ]
        ],
        "ContentsSlotSetting" => [
          "ServerWarp" => [
            "WarpList" => [
              "Spawn" => [
                "ItemSlot" => 1,
                "Item" => 1,
                "Command" => "spawn",
                "Message" => "Spawn Warp Complete!"
              ]
            ]
          ]
        ]
      ]
    ]);
    $this->messagedb = $this->message->getAll();
    $this->getServer()->getCommandMap()->register('ServerInformation', new EventCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getScheduler ()->scheduleRepeatingTask ( new PlayerSaveTask ( $this, $this->player ), 20*10 );
  }

  public function getWarpSettingLists(): array
  {
    $arr = [];
    foreach($this->messagedb ["ServerContentsUi"] ["WarpSlot"] as $Warp => $v) {
      $arr[] = $Warp;
    }
    return $arr;
  }

  public function onOpenWarpSettingLists($player):void
  {
    $playerPos = $player->getPosition();
    $name = $player->getName ();
    $inv = LifeInventoryLib::getInstance ()->create("DOUBLE_CHEST", new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), 'WarpConTents',$player);

    foreach($this->getWarpSettingLists() as $Warp){
      $SlotCode = $this->messagedb ["ServerContentsUi"] ["WarpSlot"] [$Warp] ["ItemSlot"];
      $ItemCode = $this->messagedb ["ServerContentsUi"] ["WarpSlot"] [$Warp] ["Item"];
      $CheckItem = ItemFactory::getInstance()->get($ItemCode, 0, 1)->setCustomName($Warp)->setLore([ $Warp . " List Open" ]);
      $inv->setItem( $SlotCode , $CheckItem );
    }

    LifeInventoryLib::getInstance ()->send($inv, $player);
  }

  public function getWarpLists(): array
  {
    $arr = [];
    foreach($this->messagedb ["ServerContentsUi"] ["WarpSlotSetting"] as $Warp => $v) {
      $arr[] = $Warp;
    }
    return $arr;
  }

  public function getCommandWarpLists($data): array
  {
    $arr = [];
    foreach($this->messagedb ["ServerContentsUi"] ["WarpSlotSetting"] [$data] ["WarpList"] as $Warp => $v) {
      $arr[] = $Warp;
    }
    return $arr;
  }

  public function onOpenWarpLists($player,$type):void
  {
    $playerPos = $player->getPosition();
    $name = $player->getName ();
    $inv = LifeInventoryLib::getInstance ()->create($type, new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), 'Warps',$player);

    foreach($this->getWarpLists() as $Warp){
      foreach($this->getCommandWarpLists($Warp) as $WarpName){
        $SlotCode = $this->messagedb ["ServerContentsUi"] ["ContentsSlotSetting"] [$Warp] ["WarpList"] [$WarpName] ["ItemSlot"];
        $ItemCode = $this->messagedb ["ServerContentsUi"] ["ContentsSlotSetting"] [$Warp] ["WarpList"] [$WarpName] ["Item"];
        $CheckItem = ItemFactory::getInstance()->get($ItemCode, 0, 1)->setCustomName($WarpName)->setLore([ $Warp . " Warp Event" ]);
        $inv->setItem( $SlotCode , $CheckItem );
      }
    }

    LifeInventoryLib::getInstance ()->send($inv, $player);
  }

  public function onConTentsUIOpen ($player):void
  {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun():void {
        $this->owner->ConTentsUI($this->player);
      }
    }, 20);
  }
  public function ConTentsUI(Player $player):void
  {
    $encode = [
      'type' => 'form',
      'title' => 'Server Contents',
      'content' => "{$this->messagedb ["ServerContentsMessage"]}",
      'buttons' => [
        [
          'text' => 'warp list'
        ],
        [
          'text' => 'command set'
        ],
        [
          'text' => 'Exit'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 156321;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
  }
  public function onClausesOpen ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun():void {
        $this->owner->ClausesUI($this->player);
      }
    }, 20);
  }
  public function ClausesUI(Player $player):void
  {
    $encode = [
      'type' => 'form',
      'title' => 'Server Terms',
      'content' => "{$this->messagedb ["ServerAgreeMessage"]}",
      'buttons' => [
        [
          'text' => 'Exit'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 156322;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
  }
  public function onPlayerInfoOpen ($player,$players):void
  {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player, $players) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player,Player $players) {
        $this->owner = $owner;
        $this->player = $player;
        $this->players = $players;
      }
      public function onRun():void {
        $this->owner->PlayerInfo($this->player,$this->players);
      }
    }, 20);
  }
  public function PlayerInfo(Player $player, $players):void
  {
    $playername = $players->getName ();
    $encode = [
      'type' => 'form',
      'title' => 'Information window',
      'content' => "Player info\n{$playername}",
      'buttons' => [
        [
          'text' => 'Exit'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 156323;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
  }
  public function onPlayerOpen($player):void
  {
    $playerPos = $player->getPosition();
    $name = $player->getName ();
    $inv = LifeInventoryLib::getInstance ()->create("DOUBLE_CHEST", new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), ' server concurrent',$player);
    $page = (int)$this->pldb [$name] ["Page"];
    if (isset($this->pldb ["player"] [$page])) {
      foreach($this->pldb ["player"] [$page] as $count => $v){
        $name = $this->pldb ["player"] [$page] [$count];
        $CheckItem = ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("{$name}")->setLore([ "View that player's information.\nTake it to your inventory. " ]);
        $inv->setItem( $count , $CheckItem );
        $inv->setItem( 53 , ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("Next Page")->setLore([ "Go to next page.\nImported to inventory Go." ]) );
        $inv->setItem( 45 , ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("Back page")->setLore([ "Go to previous page.\nImported to inventory Go." ]) );
      }
    }
    LifeInventoryLib::getInstance ()->send($inv, $player);
  }
  public function onOpen($player):void
  {
    $playerPos = $player->getPosition();
    $name = $player->getName ();
    $inv = LifeInventoryLib::getInstance ()->create("DOUBLE_CHEST", new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), ' server info',$player);
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("Server Player Count")->setLore([ "Check the number of concurrent connections on the server.\nTake it to the inventory. " ]);
    $inv->setItem( 1 , $CheckItem );
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("Server Contects")->setLore([ "Check the contents of the server.\nTake it to the inventory." ] );
    $inv->setItem( 4 , $CheckItem );
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("Server Agree")->setLore([ "Check the server agreement.\nTake it to your inventory." ] );
    $inv->setItem( 7 , $CheckItem );
    LifeInventoryLib::getInstance ()->send($inv, $player);
  }
  public function playerCounts($name,$count,$page)
  {
    $this->pldb ["player"] [$page] = [];
    $this->pldb ["player"] [$page] [$count] = $name;
    $this->save ();
  }
  public function onDisable():void
  {
    $this->save();
  }
  public function save():void
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->message->setAll($this->messagedb);
    $this->message->save();
  }
}

class PlayerSaveTask extends Task
{
  protected $owner;
  protected $player;
  protected $pldb;
  public function __construct(ServerInformation $owner, Config $player) {
    $this->owner = $owner;
    $this->player = $player;
    $this->pldb = $this->player->getAll ();
  }
  public function onRun():void {
    $count = 0;
    $page = 1;
    unset($this->owner->pldb ["player"]);
    $this->owner->save ();
    foreach ( $this->owner->getServer ()->getOnlinePlayers () as $player ) {
      if ($count >= 44) {
        $count = 0;
        $page += 1;
      }
      $name = $player->getName ();
      $this->owner->playerCounts ($name,$count,$page);
      ++$count;
    }
  }
}
