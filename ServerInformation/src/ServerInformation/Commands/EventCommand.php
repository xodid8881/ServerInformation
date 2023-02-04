<?php
declare(strict_types=1);

namespace ServerInformation\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use ServerInformation\ServerInformation;

class EventCommand extends Command
{

  protected $plugin;
  private $chat;

  public function __construct(ServerInformation $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('서버정보', '서버정보 명령어.', '/서버정보');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $name = $sender->getName ();
    if (! isset ( $this->chat [$name] )) {
      $this->plugin->onOpen ($sender);
      $this->chat [$name] = date("YmdHis",strtotime ("+3 seconds"));
      return true;
    }
    if (date("YmdHis") - $this->chat [$name] < 3) {
      $sender->sendMessage ( ServerInformation::PREFIX . "이용 쿨타임이 지나지 않아 불가능합니다." );
      return true;
    } else {
      $this->plugin->onOpen ($sender);
      $this->chat [$name] = date("YmdHis",strtotime ("+3 seconds"));
      return true;
    }
  }
}
