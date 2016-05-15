<?php

namespace Blubberboy333\PvPArena;

use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Level;
use pocketmine\level\Position;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{
    public function onEnable(){
        $this->fighters = array();
        $this->saveDefaultConfig();
        if(!(is_dir($this->getDataFolder()."Arenas/"))){
            mkdir($this->getDataFolder()."Arenas/");
        }
        if(!(is_dir($this->getDataFolder()."Players/"))){
            mkdir($this->getDataFolder()."Players/");
        }
        $this->getLogger()->info(TextFormat::GREEN."Done!");
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch(strtolower($command->getName())){
            case "arena":
            if($sender->hasPermission("pvp") || $sender->hasPemission("pvp.cmd") || $sender->hasPemission("pvp.cmd.arena")){
                if(isset($args[0])){
                    if($args[0] == "new"){
                        if($isset($args[1])){
                            if($this->checkArena($args[1]) == true){
                                $sender->sendMessage(TextFormat::YELLOW."There is already an arena by that name!");
                                return true;
                            }else{
                                mkdir($this->getDataFolder()."Arenas/".$args[1]."/");
                                $mainFile = new Config($this->getDataFolder()."Arenas/".$args[1]."/"."Main.yml", CONFIG::YAML);
                                $items = $this->getConfig()->get("Items");
                                $mainFile->set("Items", $items);
                                $mainFile->set("ActiveFighters", array());
                                $mainFile->set("Active", "false");
                                $mainFile->save();
                                $sender->sendMessage(TextFormat::GREEN."You have made an arena file! Now set the player spawn points!");
                                if($sender instanceof Player){
                                    $this->getLogger()->info(TextFormat::YELLOW.$sender->getName()." made an arena named ".$args[1]."!");
                                }
                                return true;
                            }
                        }else{
                            $sender->sendMessage("You need to specify an arena!");
                            return true;
                        }
                    }elseif($args[0] == "delete"){
                        if(isset($args[1])){
                            if($this->checkArena($args[1]) == true){
                                rmdir($this->getDataFolder()."Arenas/".$args[1]."/");
                                $sender->sendMessage(TextFormat::YELLOW."You have deleted the arena named ".$args[1]);
                                if($sender instanceof Player){
                                    $this->getLogger()->info(TextFormat::YELLOW.$sender->getName()." deleted the arena named ".$args[1]);
                                }
                                return true;
                            }else{
                                $sender->sendMessage(TextFormat::YELLOW."There is no arena named ".$args[1]);
                                return true;
                            }
                        }else{
                            $sender->sendMessage("You need to specify an arena!");
                            return true;
                        }
                    }elseif($args[0] == "set"){
                        if($sender instanceof Player){
                            if(isset($args[1])){
                                if($this->checkArena($args[1]) == true){
                                    if(isset($args[2])){
                                        if(is_numeric($args[2])){
                                            $fighterFile = new Config($this->getDataFolder()."Arenas/".$args[1]."Fighter".$args[2].".yml", Config::YAML);
                                            $x = $sender->X();
                                            $y = $sender->Y();
                                            $z = $sender->Z();
                                            $world = $sender->getLevel()->getName();
                                            $fighterFile->set("X", $x);
                                            $fighterFile->set("y", $y);
                                            $fighterFile->set("Z", $z);
                                            $fighterFile->set("World", $world);
                                            $sender->sendMessage("Done!");
                                            return true;
                                        }else{
                                            $sender->sendMessage("You must have a number for the fighter!");
                                            return true;
                                        }
                                    }else{
                                        $sender->sendMessage("You didn't set a fighter!");
                                        return false;
                                    }
                                }else{
                                    $sender->sendMessage("There is no arena by the name of ".$args[1]);
                                    return true;
                                }
                            }else{
                                $sender->sendMessage("You need to specify an arena!");
                                return true;
                            }
                        }else{
                            $sender->sendMessage(TextFormat::RED."That command can only be used in-game!");
                            return true;
                        }
                    }elseif($args[0] == "leave"){
                        if($sender instanceof Player){
                            if(file_exists($this->getDataFolder()."Players/".$sender->getName().".yml")){
                                if($sender->getLevel()->getName() == $this->getConfig()->get("World")){
                                    $sender->teleport(new Vector3($this->getConfig()->get("X"), $this->getConfig()->get("Y"), $this->getConfig()->get("Z")));
                                }else{
                                    $sender->teleport(new Position($this->getConfig()->get("X"), $this->getConfig()->get("Y"), $this->getConfig()->get("Z"), $this->getConfig()->get("World")));
                                }
                                $playerFile = new Config($this->getDataFolder()."Players/".$sender->getName().".yml", Config::YAML);
                                $arena = $playerFile->get("Match");
                                $arenaFile = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                                if(count($arenaFile->get("ActiveFighters")) == 2){
                                    
                                }
                                $sender->sendMessage("You have left the match.");
                            }else{
                                $sender->sendMessage(TextFormat::YELLOW."You aren't in a match!");
                                return true;
                            }
                        }
                    }else{
                        $sender->sendMessage(TextFormat::RED."Unknown subcommand: ".$args[0]);
                        return true;
                    }
                }else{
                    return false;
                }
            }else{
                $sender->sendMessage(TextFormat::RED."You don't have permission to use that command!");
                return true;
            }
            case "fight":
            if($sender instanceof Player){
                if($sender->hasPermission("pvp") || $sender->hasPermission("pvp.cmd") || $sender->hasPermission("pvp.cmd.fight")){
                    if(isset($args[0])){
                        if($this->checkArena($args[0]) == true){
                            $arenaFile = new Config($this->getDataFolder()."Arenas/".$args[1]."/"."Main.yml", CONFIG::YAML);
                            if(count($arenaFile->get("ActiveFighters")) == 0){
                                $player1 = new Config($this->getDataFolder()."Arenas/".$args[0]."Fighter1.yml", Config::YAML);
                                $x = $player1->get("X");
                                $y = $player1->get("Y");
                                $z = $player1->get("Z");
                                $world = $player1->get("World");
                                $sender->sendMessage(TextFormat::BLUE."Joining arena: ".$args[0]);
                                if($sender->getLevel()->getName() == $world){
                                    $sender->teleport(new Vector3($x, $y, $z));
                                }else{
                                    $sender->teleport(new Position($x, $y, $z, $world));
                                }
                                foreach($arenaFile->get("Items") as $i){
                                    $sender->getInventory()->addItem(Item::get($i));
                                }
                                array_push($arenaFile->get("ActiveFighters"), $sender->getName());
                                $arenaFile->save();
                                array_push($this->fighters($sender->getName()));
                                $playerFile = new Config($this->getDataFolder()."Players/".$sender->getName().".yml", Config::YAML);
                                $playerFile->set("Match", $args[0]);
                                $playerFile->save();
                                $sender->sendMessage(TextFormat::GREEN."Waiting for 1 more player...");
                                return true;
                            }elseif(count($arenaFile->get("ActiveFighters")) == 1){
                                $player2 = new Config($this->getDataFolder()."Arenas/".$args[0]."Fighter2.yml", Config::YAML);
                                $x = $player2->get("X");
                                $y = $player2->get("Y");
                                $z = $player2->get("Z");
                                $world = $player2->get("World");
                                $sender->sendMessage(TextFormat::BLUE."Joining arena: ".$args[0]);
                                if($sender->getLevel()->getName() == $world){
                                    $sender->teleport(new Vector3($x, $y, $z));
                                }else{
                                    $sender->teleport(new Position($x, $y, $z, $world));
                                }
                                foreach($arenaFile->get("Items") as $i){
                                    $sender->getInventory()->addItem(Item::get($i));
                                }
                                array_push($arenaFile->get("ActiveFighters"), $sender->getName());
                                $arenaFile->save();
                                array_push($this->fighters, $sender->getName());
                                $sender->sendMessage(TextFormat::GREEN."Match Starting!");
                                $arenaFile->set("Active", "true");
                                $arenaFile->save();
                                $playerFile = new Config($this->getDataFolder()."Players/".$sender->getName().".yml", Config::YAML);
                                $playerFile->set("Match", $args[0]);
                                $playerFile->save();
                                return true;
                            }else{
                                $sender->sendMessage(TextFormat::YELLOW."There is already a match going on there, please wait until the round is over");
                            }
                        }else{
                            $sender->sendMessage("There is no arena by that name!");
                            return true;
                        }
                    }else{
                        $sender->sendMessage("You must choose an arena!");
                        return false;
                    }
                }else{
                    $sender->sendMessage(TextFormat::RED."You don't have permission to use that command!");
                    return true;
                }
            }else{
                $sender->sendMessage(TextFormat::RED."This command can only be used in-game");
                return true;
            }
        }
    }
    
    public function checkArena($arena){
        if(is_dir($this->getDataFolder()."Arenas/".$arena."/")){
            return true;
        }else{
            return false;
        }
    }
    
    public function onEntityMotionEvent(EntityMotionEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if(in_array($player->getName(), $this->fighters())){
                if(file_exists($this->getDataFolder()."Players/".$player->getName().".yml")){
                    $playerFile = new Config($this->getDataFolder()."Players/".$player->getName().".yml", Config::YAML);
                    $arena = $playerFile->get("Match");
                    $arenaFile = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
                    if($arenaFile->get("Active") !== true){
                        $event->setCancelled();
                    }
                }
            }
        }
    }
    
    public function onPlayerDeathEvent(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        if(in_array($player->getName(), $this->fighters)){
            $playerFile = new Config($this->getDataFolder()."Players/".$player->getName().".yml", Config::YAML);
            $arena = $playerFile->get("Match");
            $arenaFile = new Config($this->getDataFolder()."Arenas/".$arena.".yml", Config::YAML);
            foreach($arenaFile->get("ActiveFighters") as $i){
                if($i !== $player->getName()){
                    $this->winMatch($i, $arena);
                }
            }
        }
    }
    
    public function winMatch($player, $match){
        $winner = $this->getServer()->getPlayer($player);
        $message = $this->getConfig()->get("Message");
        $replace = str_replace(array("{NAME}", "{ARENA}"), array($winner->getName(), $match), $message);
        $this->getServer()->broadcastMessage($replace);
        $x = $this->getConfig()->get("X");
        $y = $this->getConfig()->get("Y");
        $z = $this->getConfig()->get("Z");
        $world = $this->getConfig()->get("World");
        if($winner->getLevel()->getName() == $world){
            $winner->teleport(new Vector3($x, $y, $z));
        }else{
            $winner->teleport(new Position($x, $y, $z, $world));
        }
    }
}
