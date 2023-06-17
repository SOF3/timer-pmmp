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
use SOFe\Timer\libs\_3fcb18254a4967a7\SOFe\Zleep\Zleep;
use WeakMap;
use function count;
use function floor;
use function fmod;
use function microtime;
use function sprintf;















































final class Session {
	public bool $display = false;
	public ?float $relativeStart = null;
	public ?float $pausedDuration = null;

	public function __construct(
		private Plugin $plugin,
		private Player $player,
	) {
	}

	public function loop() : Generator {
		while ($this->player->isOnline()) {
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