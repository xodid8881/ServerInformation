<?php
declare(strict_types=1);

namespace ServerInformation\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use ServerInformation\ServerInformation;

class EventCommand extends Command
{

  protected $plugin;

  public function __construct(ServerInformation $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('서버정보', '서버정보 명령어.', '/서버정보');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $this->plugin->onOpen ($sender);
    return true;
  }
}
