<?php

declare(strict_types=1);

namespace NhanAZ\RedSkyBlockX\Commands\SubCommands;

use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use NhanAZ\RedSkyBlockX\Commands\SBSubCommand;
use pocketmine\player\Player;

class OnIsland extends SBSubCommand {

	public function prepare(): void {
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission("redskyblockx.island");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
		if (!$sender instanceof Player) return;
		if ($this->checkIsland($sender)) {
			$island = $this->plugin->islandManager->getIsland($sender);
			if ($island === null) return;
			$playersOnIsland = $this->plugin->islandManager->getPlayersAtIsland($island);
			$playersOnIsland = implode(", ", $playersOnIsland);
			$message = $this->getMShop()->construct("PLAYERS_ON_ISLAND");
			$message = str_replace("{PLAYERS}", $playersOnIsland, $message);
			$sender->sendMessage($message);
		} else {
			$message = $this->getMShop()->construct("NO_ISLAND");
			$sender->sendMessage($message);
		}
	}
}