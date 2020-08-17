<?php


namespace skh6075\ItemCaseAPI;

use pocketmine\plugin\PluginBase;
use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;

use skh6075\ScheduleAPI\ScheduleAPI;

use function implode;
use function intval;
use function strval;

function posToStr (Position $pos): string{
    return implode (':', [
        intval ($pos->x),
        intval ($pos->y),
        intval ($pos->z),
        strval ($pos->level->getFolderName ())
    ]);
}
function strToPos (string $pos): Position{
    [ $x, $y, $z, $level ] = explode (':', $pos);
    return new Position (intval ($x), intval ($y), intval ($z), Server::getInstance ()->getLevelByName ($level));
}

class ItemCaseAPI extends PluginBase {

    /** @var array[] */
    public static $cases = [];
    
    /** @var null|ItemCaseAPI */
    private static $instance = null;
    
    /** @var int */
    public const PLAYER_VIEW_DISTANCE = 20;
    
    
    public static function getInstance (): ?ItemCaseAPI{
        return self::$instance;
    }
    
    public function onLoad (): void{
        if (self::$instance === null)
            self::$instance = $this;
    }
    
    public function onEnable (): void{
        if (!class_exists (ScheduleAPI::class)) {
            $this->getLogger ()->critical ('not founded ScheduleAPI class!');
            $this->getServer ()->getPluginManager ()->disablePlugin ($this);
            return;
        }
        ScheduleAPI::repeatingTask (function () {
            foreach (Server::getInstance ()->getOnlinePlayers () as $player)
                ItemCaseAPI::itemCaseUpdate ($player);
        }, 25 * 2);
    }
    
    /**
     * @param Position $pos
     * @param Item $item
     */
    public static function addItemCase (Position $pos, Item $item): void{
        self::$cases [$pos->level->getFolderName ()] [posToStr ($pos)] = new ItemCase ($pos, $item);
    }
    
    /**
     * @param Position $pos
     * @param Item $item
     * @return bool
     */
    public static function deleteItemCase (Position $pos, Item $item): bool{
        if (!isset (self::$cases [$pos->level->getFolderName ()])) {
            return false;
        }
        if (!isset (self::$cases [$pos->level->getFolderName ()] [posToStr ($pos)])) {
            return false;
        }
        if (($class = self::$cases [$pos->level->getFolderName ()] [posToStr ($pos)]) instanceof ItemCase) {
            self::deleteViewItemCase ($class);
        }
        unset (self::$cases [$pos->level->getFolderName ()] [posToStr ($pos)]);
        return true;
    }
    
    /**
     * @param ItemCase $case
     */
    public static function deleteViewItemCase (ItemCase $case): void{
        foreach ($case->getPosition ()->level->getPlayers () as $player) {
            $case->remove ($player);
         }
     }
     
     /**
      * @param Player $player
      */
     public static function itemCaseUpdate (Player $player): void{
         if (!isset (self::$cases [$player->level->getFolderName ()])) {
             return;
         }
         foreach (self::$cases [$player->level->getFolderName ()] as $posStr => $class) {
             if ($class instanceof ItemCase) {
                 $pos = strToPos ($posStr);
                 if (!$pos instanceof Position) {
                     continue;
                 }
                 if ($pos->distance ($player->getPosition ()) <= self::PLAYER_VIEW_DISTANCE) {
                     $class->send ($player);
                 } else {
                     $class->remove ($player);
                 }
            } else {
                unset (self::$cases [$player->level->getFolderName ()] [$posStr]);
            }
        }
    }
    
}