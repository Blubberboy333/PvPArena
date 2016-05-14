<?php

namespace Blubberboy333\PvPArena;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{
  public function onEnable(){
    $this->config = new Config($this->getDataFolder() . "config.yml" , Config::YAML, Array(
            "World" => "pocketmine",
            ));
    
  }
  public function onCommand(CommandSender $sender, Command $command, $label, array $args){
    switch($command->getName()){
      case "pvp":
        if(isset($args[0])){
                    if($sender instanceof Player){
                    switch(strtolower($args[0])){
                      case "join":
                        $configWorld = $this->config->get("World");
                        $this->getServer()->loadLevel($configWorld);
                        $world = $sender->getServer()->getLevelByName($configWorld);
                        $sender->teleport($world->getSpawnLocation(), 0, 0);
                    }
              }
        }
    }
    
  }
  
}
