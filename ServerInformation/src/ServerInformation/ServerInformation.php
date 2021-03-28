<?php
declare(strict_types=1);

namespace ServerInformation;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\OnScreenTextureAnimationPacket;
use pocketmine\utils\Config;
use pocketmine\scheduler\Task;
use pocketmine\item\Item;
use ServerInformation\Commands\EventCommand;
use ServerInformation\Inventory\DoubleChestInventory;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\block\Block;
use pocketmine\tile\Chest;
// monster
use pocketmine\level\Position;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;

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
  
  public function onLoad()
  {
    self::$instance = $this;
  }
  
  public function onEnable()
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
    $this->getScheduler ()->scheduleRepeatingTask ( new PlayerSaveTask ( $this, $this->player ), 20 );
  }
  public function onConTentsUIOpen ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->ConTentsUI($this->player);
      }
    }, 20);
  }
  public function ConTentsUI(Player $player)
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
    $player->sendDataPacket($packet);
    return true;
  }
  public function onClausesOpen ($player) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player) {
        $this->owner = $owner;
        $this->player = $player;
      }
      public function onRun($currentTick) {
        $this->owner->ClausesUI($this->player);
      }
    }, 20);
  }
  public function ClausesUI(Player $player)
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
    $player->sendDataPacket($packet);
    return true;
  }
  public function onPlayerInfoOpen ($player,$players) {
    $this->getScheduler()->scheduleDelayedTask(new class ($this, $player, $players) extends Task {
      protected $owner;
      public function __construct(ServerInformation $owner,Player $player,Player $players) {
        $this->owner = $owner;
        $this->player = $player;
        $this->players = $players;
      }
      public function onRun($currentTick) {
        $this->owner->PlayerInfo($this->player,$this->players);
      }
    }, 20);
  }
  public function PlayerInfo(Player $player, $players)
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
    $packet->formId = 156322;
    $packet->formData = json_encode($encode);
    $player->sendDataPacket($packet);
    return true;
  }
  public function onPlayerOpen($player) {
    $name = $player->getName ();
    $inv = new DoubleChestInventory("§6§l[ §f서버 동접자 §6]");
    $page = (int)$this->pldb [$name] ["Page"];
    if (isset($this->pldb ["플레이어"] [$page])) {
      foreach($this->pldb ["플레이어"] [$page] as $count => $v){
        $name = $this->pldb ["플레이어"] [$page] [$count];
        $CheckItem = Item::get(397, 3, 1)->setCustomName("{$name}")->setLore([ "§r§7해당 플레이어의 정보를 봅니다.\n인벤토리로 가져가보세요." ]);
        $inv->setItem( $count , $CheckItem );
        $inv->setItem( 53 , Item::get(144, 0, 1)->setCustomName("§r§f다음 페이지")->setLore([ "§r§7다음 페이지로 이동합니다.\n인벤토리로 가져가보세요." ]) );
        $inv->setItem( 45 , Item::get(144, 0, 1)->setCustomName("§r§f이전 페이지")->setLore([ "§r§7이전 페이지로 이동합니다.\n인벤토리로 가져가보세요." ]) );
      }
    }
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
      }
    }, 20);
  }
  public function onOpen($player) {
    $name = $player->getName ();
    $inv = new DoubleChestInventory("§6§l[ §f서버정보 §6]");
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f서버동접")->setLore([ "§r§7서버의 동시접속자들을 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 1 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f서버콘텐츠")->setLore([ "§r§7서버의 콘텐츠를 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 4 , $CheckItem );
    $CheckItem = Item::get(144, 0, 1)->setCustomName("§r§f서버약관")->setLore([ "§r§7서버의 약관을 확인합니다.\n인벤토리로 가져가보세요." ]);
    $inv->setItem( 7 , $CheckItem );
    $inv->sendContents($inv->getViewers());
    $this->getScheduler()->scheduleDelayedTask(new class ($player, $inv) extends Task {
      public function __construct($player, $inv) {
        $this->player = $player;
        $this->inv = $inv;
      }
      public function onRun($currentTick) {
        $this->player->addWindow($this->inv);
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
  public function onDisable()
  {
    $this->save();
  }
  public function save()
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->message->setAll($this->pldb);
    $this->message->save();
  }
}
class PlayerSaveTask extends Task {
  protected $owner;
  protected $player;
  protected $pldb;
  public function __construct(ServerInformation $owner, Config $player) {
    $this->owner = $owner;
    $this->player = $player;
    $this->pldb = $this->player->getAll ();
  }
  public function onRun(int $currentTick) {
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
