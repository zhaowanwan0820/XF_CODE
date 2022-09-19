<?php
/**
 * @file ItzInstanceService.php
 * @author (kuangjun@xxx.com)
 * @date 2013/10/25
 *  
 **/

class ItzInstanceService extends ItzBaseService{

    private static $arrInstance;

	protected $cache=null;
                                                                                                                      
    public function __construct()
    {
	    parent::__construct($this->cache);  
    }   

    private function __clone(){}
        
    /**
     * 支持多个对象的单例 
     */
    static public function getInstance(){ 
		$className = get_called_class();
		if(!isset(self::$arrInstance[$className])){
		    self::$arrInstance[$className] = new $className();
		}
		return self::$arrInstance[$className];
    }
}

?>
