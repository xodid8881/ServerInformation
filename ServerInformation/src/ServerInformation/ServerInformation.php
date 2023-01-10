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

use ServerInformation\InventoryLib\InvLibManager;
use ServerInformation\InventoryLib\LibInvType;
use ServerInformation\InventoryLib\InvLibAction;
use ServerInformation\InventoryLib\SimpleInventory;
use ServerInformation\InventoryLib\LibInventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\block\Block;
use pocketmine\tile\Chest;
// monster
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\world\World;
use pocketmine\world\Position;

class ServerInformation extends PluginBase
{
  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;
  
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
      "콘텐츠설명" => "§r§7서버 콘텐츠\n서버",
      "서버약관설명" => "§r§7서버 약관안내\n감사합니다."
    ]);
    $this->messagedb = $this->message->getAll();
    $this->getServer()->getCommandMap()->register('ServerInformation', new EventCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getScheduler ()->scheduleRepeatingTask ( new PlayerSaveTask ( $this, $this->player ), 20*10 );
    InvLibManager::register($this);
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
      'title' => '§l§6[ §f서버 콘텐츠 §6]',
      'content' => "{$this->messagedb ["콘텐츠설명"]}",
      'buttons' => [
        [
          'text' => '§l§6[ §f나가기 §6]'
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
      'title' => '§l§6[ §f서버 약관 §6]',
      'content' => "{$this->messagedb ["서버약관설명"]}",
      'buttons' => [
        [
          'text' => '§l§6[ §f나가기 §6]'
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
      'title' => '§l§6[ §f플레이어 정보 §6]',
      'content' => "§r§7플레이어 정보\n{$playername}",
      'buttons' => [
        [
          'text' => '§l§6[ §f나가기 §6]'
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
    $inv = InvLibManager::create(LibInvType::DOUBLE_CHEST(), new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), '§6§l[ §f서버 동접자 §6]');
    $page = (int)$this->pldb [$name] ["Page"];
    if (isset($this->pldb ["플레이어"] [$page])) {
      foreach($this->pldb ["플레이어"] [$page] as $count => $v){
        $name = $this->pldb ["플레이어"] [$page] [$count];
        $CheckItem = ItemFactory::getInstance()->get(397, 3, 1)->setCustomName("{$name}")->setLore([ "§r§7해당 플레이어의 정보를 봅니다.\n인벤토리로 가져가보세요." ]);
        $inv->setItem( $count , $CheckItem );
        $inv->setItem( 53 , ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("§r§f다음 페이지")->setLore([ "§r§7다음 페이지로 이동합니다.\n인벤토리로 가져가보세요." ]) );
        $inv->setItem( 45 , ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("§r§f이전 페이지")->setLore([ "§r§7이전 페이지로 이동합니다.\n인벤토리로 가져가보세요." ]) );
      }
    }
    $inv->setListener(function(InvLibAction $action):void{
      $action->setCancelled();
    });
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun():void {
        $this->inv->send($this->player);
      }
    }, 20);
  }
  public function onOpen($player):void
  {
    $playerPos = $player->getPosition();
    $name = $player->getName ();
    $inv = InvLibManager::create(LibInvType::DOUBLE_CHEST(), new Position($playerPos->x, $playerPos->y - 2, $playerPos->z, $playerPos->getWorld()), '§6§l[ §f서버정보 §6]');
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("§r§f서버동접")->setLore([ "§r§7서버의 동시접속자들을 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 1 , $CheckItem );
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("§r§f서버콘텐츠")->setLore([ "§r§7서버의 콘텐츠를 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 4 , $CheckItem );
    $CheckItem = ItemFactory::getInstance()->get(144, 0, 1)->setCustomName("§r§f서버약관")->setLore([ "§r§7서버의 약관을 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 7 , $CheckItem );
    $inv->setListener(function(InvLibAction $action):void{
      $action->setCancelled();
    });
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun():void {
        $this->inv->send($this->player);
      }
    }, 20);
  }
  public function playerCounts($name,$count,$page)
  {
    $playerCount = count ( $this->getServer ()->getOnlinePlayers () );
    if ($count <= $playerCount) {
      if (!isset ($this->pldb ["플레이어"] [$page] [$count])) {
        $this->pldb ["플레이어"] [$page] [$count] = $name;
        $this->save ();
      }
    }
  }
  public function onDisable():void
  {
    $this->save();
  }
  public function save():void
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->message->setAll($this->pldb);
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
