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
use SOFe\AwaitGenerator\Await;
use SOFe\Zleep\Zleep;
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
					$session->show();
					break;
				case "hide":
					$session->hide();
					break;
				default:
					$sender->sendMessage(TextFormat::RED . "Unknown subcommand \"$subcmd\"");
					return false;
			}
		}

		return true;
	}
}

final class Session {
	private bool $display = false;
	private ?float $relativeStart = null;
	private ?float $pausedDuration = null;
	private bool $looping = false;

	public function __construct(
		private Plugin $plugin,
		private Player $player,
	) {
	}

	private function loop() : Generator {
		$this->looping = true;
		try {
			while ($this->player->isOnline() && $this->display) {
				if ($this->display) {
					$duration = 0.0;
					if ($this->relativeStart !== null) {
						$duration = microtime(true) - $this->relativeStart;
					} elseif ($this->pausedDuration !== null) {
						$duration = $this->pausedDuration;
					}

					$this->player->sendPopup(self::formatDuration($duration));
				}

				yield from Zleep::sleepSeconds($this->plugin, 1.);
			}
		} finally {
			$this->looping = false;
		}
	}

	public function start() : void {
		if ($this->relativeStart !== null) {
			return;
		}
		$this->relativeStart = microtime(true) - $this->getDuration();
		$this->pausedDuration = null;
	}

	public function pause() : void {
		$this->pausedDuration = $this->getDuration();
		$this->relativeStart = null;
	}

	public function reset() : void {
		if ($this->relativeStart !== null) {
			$this->relativeStart = microtime(true);
		} else {
			$this->pausedDuration = 0.0;
		}
	}

	public function show() : void {
		$this->display = true;
		if(!$this->looping) {
			Await::g2c($this->loop());
		}
	}

	public function hide() : void {
		$this->display = false;
	}

	private function getDuration() : float {
		if ($this->relativeStart !== null) {
			return microtime(true) - $this->relativeStart;
		}

		if ($this->pausedDuration !== null) {
			return $this->pausedDuration;
		}

		return 0.0;
	}

	private static function formatDuration(float $seconds) : string {
		$minutes = (int) floor($seconds / 60.0);
		$seconds = (int) floor(fmod($seconds, 60.0));
		return sprintf("%d:%02d", $minutes, $seconds);
	}
}
