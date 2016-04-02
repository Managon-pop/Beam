<?php

namespace Managon;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\entity\Entity;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\level\particle\RedStoneParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;

class beam extends PluginBase implements Listener{
     private $speed = 1.2;
     private $px, $py, $pz;
     private $bound = false;
     private $explode = false;
     private $attack = true;
     private $damage = 3;

     public function onEnable(){
     	Server::getInstance()->getPluginManager()->registerEvents($this, $this);
      if(!file_exists($this->getDataFolder())){
      @mkdir($this->getDataFolder(), 0744, true);
  }
        $this->con = new Config($this->getDataFolder(). "Config.json", Config::JSON, array(
          "bound" => false,
          "explode" => false,
          "attack" => true,
          "damage" => 3));
        $this->_toArray();
     }

     public function _toArray(){
        $bound = $this->con->get("bound");

        if($bound) $this->bound = true;
        else $this->bound = false;

        $explode = $this->con->get("explode");

        if($explode) $this->explode = true;
        else $this->explode = false;

        $attack = $this->con->get("attack");
        if($attack) $this->attack = true;
        else $this->attack = false;
        
        $this->damage = (Int) $this->con->get("damage")*2;
     }

     public function onRecive(DataPacketReceiveEvent $event){
     	$packet = $event->getPacket();
     	$player = $event->getPlayer();
     	if($packet instanceof UseItemPacket){
         if($player->getInventory()->getItemInHand()->getId() === 280){
           $x = $player->x;
           $y = $player->y + 1.5;//目の高さ
           $z = $player->z;
           $yaw = $player->getYaw();
           $pitch = $player->getPitch();
           $this->px = $x;
           $this->py = $y;
           $this->pz = $z;
           $this->moveHook($yaw,$pitch,$player->getLevel(), $player);
     }
  }
}

     public function moveHook($yaw, $pitch, Level $level, Player $player){
      if(-$pitch > 40) $plus = -8;
      else $plus = 0;
      for($c = 0; $c <= 1600; $c++){
       $y = tan(deg2rad(-$pitch+$plus))*$this->speed;
       $base_t = ($this->speed**2 - $y ** 2) ** 0.5;
       $x = cos(deg2rad($yaw+90))*$base_t;
       $z = sin(deg2rad($yaw+90))*$base_t;
       $pos = new Vector3($x + $this->px, $y + $this->py, $z + $this->pz);
       $particle = new RedStoneParticle($pos, 8);
       $level->addParticle($particle);
       if($level->getBlock($pos)->getId() !== 0){
       if($this->bound) $this->beam_returnPitch($c, $yaw, $pitch, $level, $pos);
       $e = new ex($this, $pos, $level);
       Server::getInstance()->getScheduler()->scheduleDelayedTask($e, 10);
       break;
     }elseif($this->attack){
      foreach($level->getPlayers() as $p){
        if($p !== $player){
        if($pos->distance($p) < 2){
          $ev = new EntityDamageEvent($p, 20, $this->damage);
          $p->attack($this->damage, $ev);
          $p->setMotion(new Vector3(mt_rand(-0.01,0.2), mt_rand(0.5, 0.7), mt_rand(-0.01, 0.2)));
          break;
        }
      }
      }
    }
       $this->px += $x;
       $this->py += $y;
       $this->pz += $z;
     }
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }

    public function e(Vector3 $pos, Level $level){
       if($this->explode){
        $i = 0;
        while(true){
        $i++;
        if($i > 40) break;
        $explodeParticle = new LargeExplodeParticle(new Vector3($pos->x + mt_rand(-5,5), $pos->y + mt_rand(1, 5), $pos->z + mt_rand(-5, 5)));
        $level->addParticle($explodeParticle);
        foreach($level->getPlayers() as $p){
          if($pos->distance($p) < rand(4, 5)){
                $ev = new EntityDamageEvent($p, EntityDamageEvent::CAUSE_CUSTOM, $e = (10 - $pos->distance($p)));
                $p->attack($e, $ev);
            }
        }
      }
     }
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }

    public function beam_returnPitch($count, $yaw, $pitch, Level $level, Vector3 $pos1){
      $this->px = $pos1->x;
      $this->py = $pos1->y;
      $this->pz = $pos1->z;
      for($count = 0; $count <= 1600; $count++){
       $y = tan(deg2rad($pitch))*$this->speed;
       $base_t = ($this->speed**2 - $y ** 2) ** 0.5;
       $x = cos(deg2rad($yaw+90))*$base_t;
       $z = sin(deg2rad($yaw+90))*$base_t;
       $this->px += $x;
       $this->py += $y;
       $this->pz += $z;
       $pos = new Vector3($this->px, $this->py, $this->pz);
       $particle = new RedStoneParticle($pos, 8);
       $level->addParticle($particle);
      }
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }
  }

  class ex extends PluginTask{
    public function __construct(PluginBase $owner, Vector3 $pos, Level $level){
      parent::__construct($owner);
      $this->owner = $owner;
      $this->pos = $pos;
      $this->level = $level;
    }

    public function onRun($currentTick){
      $this->owner->e($this->pos, $this->level);
    }
  }