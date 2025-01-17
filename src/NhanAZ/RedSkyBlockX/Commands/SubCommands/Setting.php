<?php

declare(strict_types=1);

namespace NhanAZ\RedSkyBlockX\Commands\SubCommands;

use CortexPE\Commando\args\BooleanArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use NhanAZ\RedSkyBlockX\Commands\SBSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use function array_key_exists;
use function boolval;
use function str_replace;

class Setting extends SBSubCommand {

	public function prepare() : void {
		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission("redskyblockx.island");
		$this->registerArgument(0, new RawStringArgument("setting", false));
		$this->registerArgument(1, new BooleanArgument("value", false));
	}

	/**
	 * @param array<string> $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void {
		if (!$sender instanceof Player) return;
		if ($this->checkIsland($sender)) {
			$island = $this->plugin->islandManager->getIsland($sender);
			if ($island === null) return;
			$defaultSettings = $island->getDefaultSettings();
			$setting = $args["setting"];
			if (array_key_exists($setting, $defaultSettings)) {
				$bias = $args["value"];
				$biasStringVal = "off";
				if ($bias) {
					$biasStringVal = "on";
				} else {
					$biasStringVal = "off";
				}
				$island->changeSetting($setting, boolval($bias));
				$message = $this->getMShop()->construct("SETTING_CHANGED");
				$message = str_replace("{SETTING}", $setting, $message);
				$message = str_replace("{VALUE}", $biasStringVal, $message);
				$sender->sendMessage($message);
			} else {
				$message = $this->getMShop()->construct("SETTING_NOT_EXIST");
				$message = str_replace("{SETTING}", $setting, $message);
				$sender->sendMessage($message);
			}
		} else {
			$message = $this->getMShop()->construct("NO_ISLAND");
			$sender->sendMessage($message);
		}
	}
}
