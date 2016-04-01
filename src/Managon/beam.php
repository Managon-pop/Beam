<?php

namespace Managon;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\level\particle\RedStoneParticle;

class beam extends PluginBase implements Listener{
     private $speed = 1;
     private $px, $py, $pz;

     public function onEnable(){
     	Server::getInstance()->getPluginManager()->registerEvents($this, $this);
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
      for($c = 0; $c <= 1600; $c++){
       $x = cos(deg2rad($yaw+90))*$this->speed;
       $z = sin(deg2rad($yaw+90))*$this->speed;
       $y = tan(deg2rad(-$pitch))*$this->speed;
       $pos = new Vector3($x + $this->px, $y + $this->py, $z + $this->pz);
       $this->px += $x;
       $this->py += $y;
       $this->pz += $z;
       $particle = new RedStoneParticle($pos, 8);
       $level->addParticle($particle);
     }
     $this->px = 0;
     $this->py = 0;
     $this->pz = 0;
    }
  }