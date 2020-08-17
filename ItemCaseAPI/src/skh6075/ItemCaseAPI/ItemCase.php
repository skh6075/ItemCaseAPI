<?php


namespace skh6075\ItemCaseAPI;

use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;

class ItemCase {

    /** @var int */
    protected $id = -1;
    
    /** @var null|Position */
    protected $position = null;
    
    /** @var null|Item */
    protected $item = null;
    
    /** @var null|AddItemActorPacket */
    protected $sendPacket = null;
    
    /** @var null|RemoveActorPacket */
    protected $removePacket = null;
    
    
    public function __construct (Position $pos, Item $item) {
        $this->position = $pos;
        $this->item = $item;
        $this->id = Entity::$entityCount ++;
        
        $this->sendPacket = new AddItemActorPacket ();
        $this->sendPacket->entityRuntimeId = $this->id;
        $this->sendPacket->position = $this->position->add (0.5, 0, 0.5);
        $this->sendPacket->item = $this->item;
        $this->sendPacket->motion = new Vector3 (0, 0, 0); //Zero Vector
        $this->sendPacket->metadata = [
            Entity::DATA_FLAGS => [
                Entity::DATA_TYPE_LONG,
                1 << Entity::DATA_FLAG_IMMOBILE
            ]
        ];
        
        $this->removePacket = new RemoveActorPacket ();
        $this->removePacket->entityUniqueId = $this->id;
    }
    
    public function getPosition (): ?Position{
        return $this->position;
    }
    
    public function getItem (): ?Item{
        return $this->item;
    }
    
    public function send (Player $player): void{
        $this->remove ($player);
        if ($this->sendPacket instanceof AddItemActorPacket)
            $player->sendDataPacket ($this->sendPacket);
    }
    
    public function remove (Player $player): void{
        if ($this->removePacket instanceof RemoveActorPacket)
            $player->sendDataPacket ($this->removePacket);
    }
}