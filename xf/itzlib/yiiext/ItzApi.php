<?php
class ItzApi extends CComponent{
    public $echoJson = false;
    private $_pageSize = 10;
    private static $_itzapis=array();
    private $_result,$_data,$_code,$_info;
    private $_pc,$_wap,$_app,$_api;
    public $logcategory="itzapi";
    public $code_data = array();

    //缓存属性
    public $cache,$cacheKey,$cacheDependency;
    public $cacheExpire = 300;
    public $randomTime = 20;
    private $_cacheFlag,$_removeFlag;


    public function __construct($echoJson = false){
        $this->echoJson = $echoJson;
        
		$this->init();
        
        try{
            $this->cache = Yii::app()->dcache;
        }catch( Exception $e ) {
            $this->_cacheFlag = false;
            Yii::log('cache not set'.print_r($e->getMessage(),true),'error');
        }
	}
    
    public function init(){}
    public function afterConstruct(){}
        
    /**
     * 支持多个对象的单例 
     */
    public static function api($echoJson = false){
        $className = get_called_class();
		if(isset(self::$_itzapis[$className]))
			return self::$_itzapis[$className];
		else
		{     
			$itzapi=self::$_itzapis[$className]=new $className($echoJson);
			return $itzapi;
		}
	}
    
    public function setResult($result=array()){
        $this->data = isset($result['data'])?$result['data']:array();
        $this->code = isset($result['code'])?$result['code']:0;
        $this->info = isset($result['info'])?$result['info']:'';
        $this->_result = array('data'=>$this->data,'code'=>$this->code,'info'=>$this->info);
    }
    
    public function getPageSize(){
        return $this->_pageSize;
    }
    
    public function setPageSize($value){
        $this->_pageSize = $value;
        return $this;
    }
    
    public function getResult(){
        if(empty($this->result)){
            $this->_result = array('data'=>$this->data,'code'=>$this->code,'info'=>$this->info);
        } 
        if($this->echoJson){
            echo CJSON::encode($this->_result);
        }else{
            return $this->_result;
        }
        return $this->result;
    }
    
    public function setData($data=array()){
        $this->_data = $data;
    }
    
    public function getData(){
        if(empty($this->_data) && !is_array($this->_data)){
            $this->_data = array();
        }
        return $this->_data;
    }
    
    public function setCode($data=0){
        $this->_code = $data;
    }
    
    public function getCode(){
        if(!isset($this->_code)){
            $this->_code = 0;
        }
        if($this->_code != 0){
            Yii::log('ItzApiError: code:'.$this->_code.';  info:'.$this->info, 'error', $this->logcategory);
        }
        return $this->_code;
    }
    
    public function setInfo($info=''){
        $this->_info = $info;
    }
    
    public function getInfo(){
        $this->_info = ErrorCode::errInfo($this->_code, $this->code_data);
        return $this->_info;
    }
    
    /**
     * 魔术方法API缓存处理
     */
    public function __call($name, $args) {
        if(substr($name,-9) == 'FromCache'){
            return $this->apiCache($name, $args);
        }else{
            return parent::__call($name, $args);
        }
    }
    
    public function cache($cacheExpire=300,$cacheDependency=null){
        $this->cacheExpire = $cacheExpire;
        if(is_string($cacheDependency)){
            $this->cacheDependency = new CDbCacheDependency($cacheDependency);
        }elseif(is_object($cacheDependency)){
            $this->cacheDependency = $cacheDependency;
        }
        return $this;
    }
    
    public function getCacheFlag(){
        if(!isset($this->_cacheFlag)){
            //配置中如果设置useCache为0，则不使用cache
            if( isset(Yii::app()->useCache) && isset( Yii::app()->useCache->flag ) && Yii::app()->useCache->flag == 0 ) {
                $this->_cacheFlag = false;
            }
            if( isset($_GET['noCache']) ) {
				$this->_cacheFlag = false;
			}
            $this->_cacheFlag = true;
        }
        return $this->_cacheFlag;
    }
    
    public function getRemoveFlag(){
        $this->_removeFlag = isset($_GET['removeCache']);
        return $this->_removeFlag;
    }
    
    public function cacheKey($name, $args){
        $method = get_class($this).'::'.$name;
        $this->cacheKey = 'ItzApi@'.$method.'_'.serialize($args);
        return $this->cacheKey;
    }
    
    public function apiCache($name, $args){
        $args['pageSize'] = $this->pageSize;
        $cacheKey = $this->cacheKey($name, $args);
        $callback = substr( $name, 0, -9);
        if($this->removeFlag){
            $this->removeCache($cacheKey);
        }
        if($this->cacheFlag) {
            $data = $this->getDataFromCache($cacheKey, $callback, $args );
        }else{
            $data = $this->getDataFromServer($callback, $args );
        }
        $this->cacheExpire = 300;
        $this->cacheDependency = null;
        return $data;
    }
    
    private function removeCache($cacheKey) {
		try {
			$res = $this->cache->delete($cacheKey);
			Yii::log( "Remove {$cacheKey} from cache success!", CLogger::LEVEL_TRACE, __METHOD__ );
		}
		catch(Exception $e) {
			Yii::log( "Remove {$cacheKey} from cache exception, " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
		}
	}
    
    /**
     * 通用方法，关闭缓存功能时，直接从后端服务获取数据
     * 
     * @param string $method
     * @param array $params
     * @return $data, null表示获取数据失败
     */
    public function getDataFromServer( $method, $params = array() ) {
        TimerUtil::start( $method.'FromServer' );
		try {
        	$data = call_user_func_array( array( $this, $method ), $params );
		}
		catch(Exception $e) {
        	throw new InvalidArgumentException("{$method} :Get data from server is error! " . $e->getMessage());
		}
		TimerUtil::stop( $method.'FromServer' );
        return $data;
    }
    
    
    /**
     * 通用方法，从缓存中获取数据
     * 首先从一级缓存中取数据，没有从后端服务取数据
     * 如果从后端获取失败同时启用了二级缓存，会从二级缓存取数据，并更新一级缓存
     * 上述过程取数据都失败，返回null，并会打印错误日志
     * 
     * @param string $cacheKey
     * @param string $method
     * @param array $params
     * @return $data, null表示获取数据失败
     */
    public function getDataFromCache($cacheKey, $method, $params = array()) {
        $data = null;

        //从cache中读取数据
        TimerUtil::start( $method.'FromCache' );
        try {
            $data = $this->cache->get($cacheKey); //取不到返回false
        }catch( Exception $e ){
            Yii::log( "{$method} : Get {$cacheKey} from cache1 error! " . $e->getMessage() , CLogger::LEVEL_WARNING, __METHOD__ );
            $data = null;
        }
        TimerUtil::stop( $method.'FromCache' );
        if( $data!== false ){
            return $data;
        }
        
        //cache中没有数据，从后端获取
        TimerUtil::start( $method.'FromServer' );
        try {
            $data = call_user_func_array ( array( $this, $method ), $params );
        }catch( Exception $e ) {
            Yii::log( "{$method} :Get data from server exception! " . $e->getMessage () . "\n" . $e->getTraceAsString (), CLogger::LEVEL_ERROR, __METHOD__ );
            $data = null;
        }
        TimerUtil::stop( $method.'FromServer' );
        if( $data !== null ) {
            try {
                $expire = $this->cacheExpire + rand( 0, $this->randomTime );
                $res = $this->cache->set($cacheKey, $data, $expire, $this->cacheDependency);
                if($res) {
                    Yii::log("{$method} :Store {$cacheKey} to cache", CLogger::LEVEL_TRACE, __METHOD__ );
                }else{
                    Yii::log("{$method} :Store {$cacheKey} to cache error, code is " . $res, CLogger::LEVEL_WARNING, __METHOD__ );
                }
            }catch( Exception $e ) {
                Yii::log( "{$method} :Store to cache error: " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
            }
            TimerUtil::stop( $method.'SetCache' );
            return $data;
        }
        //二级cache也没有取到数据，抛异常
        Yii::log("{$method} :Get data from cache and server is error!",'error');
        //throw new InvalidArgumentException("Get data from cache and server is error!");
    }
    
    public function getPc(){
        return $this->result;
    }
    
    public function getApp(){
        return $this->result;
    }
    
    public function getWap(){
        return $this->result;
    }
    
    public function getApi(){
        return $this->result;
    }
}

?>
