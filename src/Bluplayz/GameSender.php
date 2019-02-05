<?php

namespace Bedwars;

use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\tile\Chest;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\tile\Sign;

class Bedwars extends GameSenderTask {

    public $prefix;

    public function __construct(Bedwars $plugin) {
        $this->plugin = $plugin;
        $this->prefix = $plugin->prefix;
    }

    public function onRun($tick) {

        $files = scandir($this->plugin->getDataFolder()."Arenas");
        foreach($files as $filename){
            if($filename != "." && $filename != ".."){
                $arena = str_replace(".yml", "", $filename);
                $config = new Config($this->plugin->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                $cfg = new Config($this->plugin->getDataFolder()."config.yml", Config::YAML);
                $players = $this->plugin->getPlayers($arena);
                $status = $config->get("Status");
                $teams = (int) $config->get("Teams");
                $ppt = (int) $config->get("PlayersPerTeam");
                $lobbytimer = (int) $config->get("LobbyTimer");
                $gametimer = (int) $config->get("GameTimer");
                $endtimer = (int) $config->get("EndTimer");
                $maxplayers = (int) $teams * $ppt;
                $welt = $this->plugin->getFigthWorld($arena);
                $level = $this->plugin->getServer()->getLevelByName($welt);

                $aliveTeams = $this->plugin->getAliveTeams($arena);

                $minplayers = $ppt +1;

                /*
                if((Time() % 20) == 0){
                    $this->plugin->Debug(TextFormat::GREEN."== Players Array ==");
                    var_dump($players);
                    $this->plugin->Debug(TextFormat::GREEN."== Players Array ==");
                }
                */
                if($status == "Lobby"){

                    if(count($players) < $minplayers){

                        if((Time() % 10) == 0){
                            $config->set("LobbyTimer", $cfg->get("LobbyTimer"));
                            $config->set("GameTimer", $cfg->get("GameTimer"));
                            $config->set("EndTimer", $cfg->get("EndTimer"));
                            $config->set("Status", "Lobby");
                            $config->save();
                        }


                        foreach($players as $pn){
                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                            if($p != null) {
                                $p->sendPopup(TextFormat::RED . "Wait for ".TextFormat::GOLD.$minplayers.TextFormat::RED." Member");
                            } else {
                                $this->plugin->removePlayerFromArena($arena, $pn);
                            }
                        }

                        if((Time() % 20) == 0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null) {
                                    $p->sendMessage(TextFormat::GOLD . $minplayers . TextFormat::RED ." Another player missing");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                    } else {

                        $lobbytimer--;
                        $config->set("LobbyTimer", $lobbytimer);
                        $config->save();

                        if($lobbytimer == 60 ||
                            $lobbytimer == 45 ||
                            $lobbytimer == 30 ||
                            $lobbytimer == 20 ||
                            $lobbytimer == 10
                        ){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->prefix."Round starts in ".$lobbytimer." seconds!");
                                }
                            }
                        }
                        if($lobbytimer >= 1 && $lobbytimer <= 5){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendPopup(TextFormat::YELLOW."Still ".TextFormat::RED.$lobbytimer);
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        if($lobbytimer == 0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    if($p->getNameTag() == $p->getName()) {
                                        $AT = $this->plugin->getAvailableTeam($arena);

                                        $p->setNameTag($this->plugin->getTeamColor($AT) . $pn);
                                    }
                                    $this->plugin->TeleportToTeamSpawn($p, $this->plugin->getTeam($p->getNameTag()), $arena);
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
/*
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if ($text[0] == "SHOP" || $text[1] == "SHOP" || $text[2] == "SHOP" || $text[3] == "SHOP") {
                                        //spawn Villager for Shop
                                        $this->plugin->createVillager($tile->getX(), $tile->getY(), $tile->getZ(), $tile->getLevel());
                                        $tile->getLevel()->setBlock(new Vector3($tile->getX(), $tile->getY(), $tile->getZ()), Block::get(Block::AIR));
                                    }
                                }
                            }
                            */

                            $config->set("Status", "Ingame");
                            $config->save();
                        }
                    }

                }
                elseif ($status == "Ingame"){
                    if(count($aliveTeams) <= 1){
                        if(count($aliveTeams) == 1){
                            $winnerteam = $aliveTeams[0];
                            $this->plugin->getServer()->broadcastMessage($this->prefix."Team ".TextFormat::GOLD.$winnerteam.TextFormat::WHITE." has The Bedwars round in Arena ".TextFormat::GOLD.$arena.TextFormat::WHITE." won!");
                        }
                        $config->set("Status", "End");
                        $config->save();
                    } else {

                        if ((Time() % 1) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "bronze" || strtolower($text[1]) == "bronze" || strtolower($text[2]) == "bronze" || strtolower($text[3]) == "bronze") {
                                        $loc = new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5);
                                        $needDrop = false;
                                        foreach ($players as $pn) {
                                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                                            if($p != null){
                                                $dis = $loc->distance($p);
                                                if ($dis <= 10) {
                                                    $needDrop = true;
                                                }
                                            }
                                        }
                                        if ($needDrop === true) {
                                            $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::BRICK, 0, 1));
                                            $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::BRICK, 0, 1));
                                        }
                                    }
                                }
                            }
                        }
                        if ((Time() % 8) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "eisen" || strtolower($text[1]) == "eisen" || strtolower($text[2]) == "eisen" || strtolower($text[3]) == "eisen") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::IRON_INGOT, 0, 1));
                                    }
                                }
                            }
                        }
                        if ((Time() % 30) == 0) {
                            $tiles = $level->getTiles();
                            foreach ($tiles as $tile) {
                                if ($tile instanceof Sign) {
                                    $text = $tile->getText();
                                    if (strtolower($text[0]) == "gold" || strtolower($text[1]) == "gold" || strtolower($text[2]) == "gold" || strtolower($text[3]) == "gold") {
                                        $level->dropItem(new Vector3($tile->getX() + 0.5, $tile->getY() + 2, $tile->getZ() + 0.5), Item::get(Item::GOLD_INGOT, 0, 1));
                                    }
                                }
                            }
                        }


                        foreach($players as $pn){
                            $p = $this->plugin->getServer()->getPlayerExact($pn);
                            if($p != null){
                                $this->plugin->sendIngameScoreboard($p, $arena);
                            } else {
                                $this->plugin->removePlayerFromArena($arena, $pn);
                            }
                        }

                        $gametimer--;
                        $config->set("GameTimer", $gametimer);
                        $config->save();

                        if($gametimer==900||$gametimer==600|| $gametimer==300|| $gametimer==240 || $gametimer==180){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix.$gametimer/60 . " Minutes left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer == 2||$gametimer == 3|| $gametimer==4||$gametimer==5||$gametimer==15||$gametimer==30||$gametimer==60){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix.$gametimer . " seconds left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer == 1){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."1 second left");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        elseif($gametimer==0){
                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."Deathmatch started!");

                                    $p->sendMessage($this->plugin->prefix."There was no winner!");
                                    $config->set($arena."Status", "End");
                                    $config->save();
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                    }
                }
                elseif($status == "End"){

                    if($endtimer >= 0){
                        $endtimer--;
                        $config->set("EndTimer", $endtimer);
                        $config->save();

                        if($endtimer == 15 ||
                            $endtimer == 10 ||
                            $endtimer == 5 ||
                            $endtimer == 4 ||
                            $endtimer == 3 ||
                            $endtimer == 2 ||
                            $endtimer == 1){

                            foreach($players as $pn){
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->sendMessage($this->plugin->prefix."Arena restarts in ".$endtimer." seconds !");
                                } else {
                                    $this->plugin->removePlayerFromArena($arena, $pn);
                                }
                            }
                        }
                        if($endtimer == 0){
                            foreach ($players as $pn) {
                                $p = $this->plugin->getServer()->getPlayerExact($pn);
                                if($p != null){
                                    $p->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                                    $p->setFood(20);
                                    $p->setHealth(20);
                                    $p->getInventory()->clearAll();
                                    $p->removeAllEffects();
                                    $p->setExpLevel(0);
                                    $p->setNameTag($p->getName());
                                }
                            }
                            $this->plugin->resetArena($arena, true);
                        }
                    }
                }
            }
        }
    }

}