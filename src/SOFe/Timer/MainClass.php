<?php

declare(strict_types=1);

namespace SOFe\Timer;

use Generator;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use SOFe\Timer\libs\_4be245d68119d1a7\SOFe\Zleep\Zleep;
use WeakMap;
use function count;
use function floor;
use function fmod;
use function microtime;
use function sprintf;

final class MainClass extends PluginBase {
	/** @var WeakMap<Player, Session> */
	private WeakMap $sessions;

	protected function onEnable() : void {
		$this->sessions = new WeakMap;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		if (!($sender instanceof Player)) {
			$sender->sendMessage(TextFormat::RED . "Please run this command in-game");
			return true;
		}

		if (count($args) < 1) {
			return false;
		}

		$session = ($this->sessions[$sender] ??= new Session($this, $sender));
		foreach ($args as $subcmd) {
			switch($subcmd) {
				case "start":
					$session->start();
					break;
				case "stop":
					$session->pause();
					break;
				case "reset":
					$session->reset();
					break;
				case "show":
					$session->display = true;
					break;
				case "hide":
					$session->display = false;
					break;
				default:
					$sender->sendMessage(TextFormat::RED . "Unknown subcommand \"$subcmd\"");
					return false;
			}
		}

		return true;
	}
}