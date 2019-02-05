<?php

namespace Bluplayz;

use pocketmine\scheduler\Task;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;

class BWRefreshSigns extends Task {

    public $prefix;

    public function __construct(Bedwars $plugin) {
        $this->arenaData = $config->getAll();
        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new RefreshSignScheduler($this), 20*5);
        if(boolval($this->arenaData["enabled"])) {
            $this->loadGame();
        }
    }
    public function onRun($tick) {
        $levels = $this->plugin->getServer()->getDefaultLevel();
        $tiles = $levels->getTiles();
        foreach ($tiles as $t) {
            if ($t instanceof Sign) {
                $text = $t->getText();
                if ($text[0] == $this->prefix) {
                    $arena = substr($text[1], 0, -4);
                    $config = new Config($this->plugin->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                    $players = $this->plugin->getPlayers($arena);
                    $status = $config->get("Status");

                    $welt = $this->plugin->getArenaWorlds($arena)[0];
                    $level = $this->plugin->getServer()->getLevelByName($welt);

                    $arenasign = $text[1];

                    $teams = (int) $config->get("Teams");
                    $ppt = (int) $config->get("PlayersPerTeam");

                    $maxplayers = $teams * $ppt;
                    $ingame = TextFormat::GREEN."Betreten";

                    if ($status != "Lobby") {
                        $ingame = TextFormat::RED . "Ingame";
                    }
                    if (count($players) >= $maxplayers) {
                        $ingame = TextFormat::RED . "Voll";
                    }
                    if ($status == "End") {
                        $ingame = TextFormat::RED . "Restart";
                    }
                    $t->setText($this->prefix, $arenasign, $ingame, TextFormat::WHITE . (count($players)) . TextFormat::GRAY . " / ". TextFormat::RED . $maxplayers);
                }
            }
        }
    }
}
