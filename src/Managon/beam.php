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
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;

class beam extends PluginBase implements Listener{
     private $speed = 0.8;
     private $px, $py, $pz;
     private $bound = false;
     private $explode = false;
     private $attack = true;
     private $damage = 3;
     private $weightsave = true;
     private $i;
     private $taskid;

     public function onEnable(){
     	Server::getInstance()->getPluginManager()->registerEvents($this, $this);
     	$this->server = Server::getInstance();
      if(!file_exists($this->getDataFolder())){
      @mkdir($this->getDataFolder(), 0744, true);
  }
        $this->con = new Config($this->getDataFolder(). "Config.json", Config::JSON, array(
          "bound" => false,
          "explode" => false,
          "attack" => true,
          "damage" => 3,
          "weight_save" => true));
        $this->_toVar();
     }

     public function _toVar(){
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
        
        $this->weightsave = (bool) $this->con->get("weight_save");
        if($this->weightsave) $this->speed = 2;
     }

     public function onReceive(DataPacketReceiveEvent $event){
     	$packet = $event->getPacket();
     	$player = $event->getPlayer();
     	if($packet instanceof UseItemPacket){
         if($player->getInventory()->getItemInHand()->getId() === 280){
           $x = $player->x;
           $y = $player->y + 1.35;
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
      if(-$pitch > 40) $pitch += -6;//微調整
      elseif(-$pitch <= -40) $pitch += 10;//微調整
      if($this->weightsave)
      {
      	$this->taskid = $this->server->getScheduler()->scheduleRepeatingTask(new ws($this, $yaw, -$pitch, $level), 1)->getTaskId();
      	return;
      }
      
      for($c = 0; $c <= 1400; $c++){
       $pos = $this->getNextPos($yaw, -$pitch);
       $particle = new RedstoneParticle($pos, 4);
       $level->addParticle($particle);
       $this->px += $pos->x;
       $this->py += $pos->y;
       $this->pz += $pos->z;
       if($level->getBlock($pos)->getId() !== 0){
       		if($this->bound) $this->beam_returnPitch($yaw, $pitch, $level, $pos);
       	$e = new ex($this, $pos, $level);
       	$this->server->getScheduler()->scheduleDelayedTask($e, 8);
       	break;
       }elseif($this->attack){
      		foreach($level->getPlayers() as $p){
        		if($p !== $player){
        			if($pos->distance($p) < 2){
        			$ev = new EntityDamageByEntityEvent($player, $p, 1, $this->damage, 0.4);
          			$p->attack($this->damage, $ev);
          			break;
		 		}
      			}
      		}
    		}
     	}
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }
    
    public function moveHook_save($yaw, $pitch, Level $level)
    {
    	$this->i++;
    	if($this->i == 50)
    	{
    		$this->i = 0;
    		$this->server->getScheduler()->cancelTask($this->taskid);
    		return;
    	}
    	$pos = $this->getNextPos($yaw, $pitch);
    	$particle = new RedstoneParticle($pos, 4);
        $level->addParticle($particle);
        $this->px += $pos->x;
        $this->py += $pos->y;
        $this->pz += $pos->z;
        if($level->getBlock($pos)->getId() !== 0){
       		if($this->bound) $this->beam_returnPitch($yaw, $pitch, $level, $pos);
       	$e = new ex($this, $pos, $level);
       	$this->server->getScheduler()->scheduleDelayedTask($e, 8);
       	break;
       }elseif($this->attack){
      		foreach($level->getPlayers() as $p){
        		if($p !== $player){
        			if($pos->distance($p) < 2){
        			$ev = new EntityDamageByEntityEvent($player, $p, 1, $this->damage, 0.4);
          			$p->attack($this->damage, $ev);
          			break;
		 		}
      			}
      		}
    	}
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
            if($pos->distance($p) === 1){
              $ev = new EntityDamageByEntityEvent($player,$p, 1, $this->damage, 0.4);
              $p->attack($this->damage, $ev);
            }
                $ev = new EntityDamageEvent($p, 10, $e = (6 - $pos->distance($p)));
                $p->attack($e, $ev);
                $p->setMotion(new Vector3(mt_rand(-0.01,0.2), mt_rand(0.5, 0.7), mt_rand(-0.01, 0.2)));
            }
        }
      }
     }
    }

    public function beam_returnPitch($yaw, $pitch, Level $level, Vector3 $pos1){
      $this->px = $pos1->x;
      $this->py = $pos1->y;
      $this->pz = $pos1->z;
      if($this->weightsave)
      {
      	$this->taskid = $this->server->getScheduler()->scheduleRepeatingTask(new ws($this, $yaw, $pitch), 1)->getTaskId();
      	return;
      }
      for($count = 0; $count <= 1400; $count++){
       $pos = $this->getNextPos($yaw, $pitch);
       $this->px += $pos->x;
       $this->py += $pos->y;
       $this->pz += $pos->z;
       $particle = new RedstoneParticle($pos, 4);
       $level->addParticle($particle);
      }
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }
  }
  
  public function getNextPos($yaw, $pitch)
  {
  	$y = tan(deg2rad($pitch))*$this->speed;
        $base_t = ($this->speed**2 - $y ** 2) ** 0.5;
        $x = cos(deg2rad($yaw+90))*$base_t;
        $z = sin(deg2rad($yaw+90))*$base_t;
        return new Vector3($this->px + $x, $this->py + $y, $this->pz + $z);
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
  
  class ws extends PluginTask{
  	public function __construct(PluginBase $owner, $yaw, $pitch, Level $level)
  	{
  		parent::__construct($owner);
  		$this->owner = $owner;
  		$this->yaw = $yaw;
  		$this->pitch = $pitch;
  		$this->level = $level;
  	}
  	
  	public function onRun($currentTick)
  	{
  		$this->owner->moveHook_save($this->yaw, $this->pitch, $this->level);
  	}
  }
