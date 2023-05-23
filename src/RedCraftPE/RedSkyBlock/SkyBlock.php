<?php

declare(strict_types=1);

namespace RedCraftPE\RedSkyBlock;

use CortexPE\Commando\PacketHooker;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\world\World;
use RedCraftPE\RedSkyBlock\Commands\SBCommand;
use RedCraftPE\RedSkyBlock\Tasks\AutoSaveIslands;
use RedCraftPE\RedSkyBlock\Utils\ConfigManager;
use RedCraftPE\RedSkyBlock\Utils\IslandManager;
use RedCraftPE\RedSkyBlock\Utils\MessageConstructor;
use RedCraftPE\RedSkyBlock\Utils\ZoneManager;

class SkyBlock extends PluginBase {

	public static $instance;

	public $listener;
	public $mShop;
	public $cfg;
	public $skyblock;
	public $messages;
	public $zoneManager;
	public $configManager;
	public $islandManager;

	public function onEnable(): void {

		//database setup:
		if (!file_exists($this->getDataFolder() . "../RedSkyBlock")) {

			mkdir($this->getDataFolder() . "../RedSkyBlock");
		}
		if (!file_exists($this->getDataFolder() . "../RedSkyBlock/skyblock.json")) {

			$this->saveResource("skyblock.json");
		}
		if (!file_exists($this->getDataFolder() . "../RedSkyBlock/config.yml")) {

			$this->saveResource("config.yml");
		}
		if (!file_exists($this->getDataFolder() . "../RedSkyBlock/messages.yml")) {

			$this->saveResource("messages.yml");
		}
		if (!file_exists($this->getDataFolder() . "../RedSkyBlock/Players")) {

			mkdir($this->getDataFolder() . "../RedSkyBlock/Players");
		}

		$this->skyblock = new Config($this->getDataFolder() . "../RedSkyBlock/skyblock.json", Config::JSON);
		$this->cfg = new Config($this->getDataFolder() . "../RedSkyBlock/config.yml", Config::YAML);
		$this->messages = new Config($this->getDataFolder() . "../RedSkyBlock/messages.yml", Config::YAML);
		$this->skyblock->reload();
		$this->cfg->reload();
		$this->messages->reload();

		//register config manager:
		$this->configManager = new ConfigManager($this);
		//register zone manager:
		$this->zoneManager = new ZoneManager($this);
		//register island manager:
		$this->islandManager = new IslandManager($this);
		$this->islandManager->constructAllIslands();
		//register message constructor:
		$this->mShop = new MessageConstructor($this);
		//register listener for RedSkyBlock:
		$this->listener = new SkyblockListener($this);

		//begin autosave
		$autosaveTimer = $this->cfg->get("Autosave Timer");
		$ticks = round($autosaveTimer * 1200); //converts minutes to ticks
		$this->getScheduler()->scheduleRepeatingTask(new AutoSaveIslands($this), $ticks);

		//register PacketHooker:
		if (!PacketHooker::isRegistered()) {

			PacketHooker::register($this);
		}
		if (!InvMenuHandler::isRegistered()) {

			InvMenuHandler::register($this);
		}

		//register SB Base command:
		$this->getServer()->getCommandMap()->register(strtolower($this->getName()), new SBCommand(
			$this,
			"skyblock",
			"The base command for RedSkyBlock.",
			["is", "sb", "island", "isle"]
		));

		self::$instance = $this;

		//Determine if a skyblock world is being used: -- from older RedSkyBlock will probably be udpated

		if ($this->skyblock->get("Master World") === false) {

			$message = $this->mShop->construct("NO_MASTER");
			$this->getLogger()->info($message);
			$masterWorld = false;
		} else {

			if ($this->getServer()->getWorldManager()->loadWorld($this->skyblock->get("Master World"))) {

				$this->getServer()->getWorldManager()->loadWorld($this->skyblock->get("Master World"));
				if ($this->cfg->get("Nether Islands")) {

					$this->getServer()->getWorldManager()->loadWorld($this->skyblock->get("Master World") . "-Nether");
				}
			} else {

				$message = $this->mShop->construct("LOAD_ERROR");
				$this->getLogger()->info($message);
			}

			$masterWorld = $this->getServer()->getWorldManager()->getWorldByName($this->skyblock->get("Master World"));
			if (!$masterWorld instanceof World) {

				$message = $this->mShop->construct("MASTER_FAILED");
				$message = str_replace("{MWORLD}", $this->skyblock->get("Master World"), $message);
				$this->getLogger()->info($message);
				$masterWorld = null;
			} else {

				$message = $this->mShop->construct("MASTER_SUCCESS");
				$message = str_replace("{MWORLD}", $masterWorld->getFolderName(), $message);
				$this->getLogger()->info($message);
			}
		}
	}

	public static function getInstance() {

		return self::$instance;
	}

	public function onDisable(): void {

		IslandManager::getInstance()->saveAllIslands();
	}
}
