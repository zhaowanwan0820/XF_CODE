<?php
/**
 * service基类
 * 数据缓存类
 *
 */
class ItzBaseService {

    protected $cache = null;
    /**
     * 一级cache超时时间
     * @var int 单位s，默认5分钟
     */
    protected $expire1 = 300;
    /**
     * 二级cache超时时间
     * @var int 单位s，默认一天
     */
    protected $expire2 = 86400;
    /**
     * 二级cache是否使用，默认不使用
     * @var bool 默认false
     */
    protected $secondaryFlag = false;
    /**
     * 是否使用cache，在开发环境或者调试阶段可以关闭cache
     * @var bool 默认true
     */
    protected $cacheFlag = true;
    /**
     * 随机的时间范围，对于设置的cache时间进行一定的随机，防止cache同时失效
     * @var int 单位s， 默认30，可以继承覆盖
     */
    protected $randomTime = 30;
    /**
     * 允许service设置拼接cacheKey的前缀，便于更新cache
     */
    protected $cacheKeyPrefix = '';
    /**
     * 允许用户只更新cache不读cache
     * 用于离线设置cache或者其他特殊场景
     */
    protected $onlySetFlag = false;
    /**
     * 允许用户只读cache不更新cache
     * 用于读取离线设置的cache数据，不访问后端，提高请求性能
     */
    protected $onlyGetFlag = false;
    /**
     * 允许用户删除cache
     */
    protected $removeFlag = false;

	private $internalNetFlag = false;

    public function __construct( $cache) {
        try {
            if($cache){
                $this->cache = $cache;
            }else{
                $this->cache = Yii::app()->dcache;
            }
        }
        catch( Exception $e ) {
            $this->cacheFlag = false;
            Yii::log("cache not set".print_r($e->getMessage(),true),"error");
        }
        //配置中如果设置useCache为0，则不使用cache
        if( isset(Yii::app()->useCache) && isset( Yii::app()->useCache->flag ) && Yii::app()->useCache->flag == 0 ) {
            $this->cacheFlag = false;
        }
    }

    /**
     * 设置缓存的超时时间
     * @param $expire1 一级cache超时时间，单位s
     * @param $expire2 二级cache超时时间，单位s
     */
    public function setExpire( $expire1, $expire2=86400 ) {
        $this->expire1 = $expire1;
        $this->expire2 = $expire2;
    }

    /**
     * 设置开启二级缓存
     */
    public function useSecondaryCache() {
        $this->secondaryFlag = true;
    }

    /**
     * 关闭cache功能
     */
    public function closeCache() {
        $this->cacheFlag = false;
    }

    /**
     * 设置是否只读cache不写cache
     */
    public function setOnlyRead( $flag=true ) {
        $this->onlyGetFlag = $flag;
    }

    /**
     * 设置是否只写cache不读cache
     */
    public function setOnlyWrite( $flag=true ) {
        $this->onlySetFlag = $flag;
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
        $cacheKey1 = $this->cacheKeyPrefix . 'cache1_' . $cacheKey;
        $cacheKey2 = $this->cacheKeyPrefix . 'cache2_' . $cacheKey;
		//删除缓存
		if( $this->removeFlag ) {
			$this->removeData($cacheKey1, $cacheKey2);
		}

        //从一级cache中读取数据
        if( !$this->onlySetFlag ) {
            TimerUtil::start( $method.'FromCache1' );
            $data = $this->cache->get( $cacheKey1 ); //取不到返回false
            TimerUtil::stop( $method.'FromCache1' );
            if ($data) {
                return $data;
            } else {
                Yii::log( "{$method} : Get {$cacheKey1} from cache1 error! ", CLogger::LEVEL_INFO, __METHOD__ );
            }
        }
        // cache中没有数据，从后端获取
        TimerUtil::start( $method.'FromServer' );
        try {
            $data = call_user_func_array ( array( $this, $method ), $params );
        }
        catch( Exception $e ) {
            Yii::log( "{$method} :Get data from server exception! " . $e->getMessage () . "\n" . $e->getTraceAsString (), CLogger::LEVEL_ERROR, __METHOD__ );
            $data = null;
        }
        TimerUtil::stop( $method.'FromServer' );
        if( $data !== null ) {
            if( !$this->onlyGetFlag ) {
                TimerUtil::start( $method.'SetCache' );
                try {
                    $expire1 = $this->expire1 + rand( 0, $this->randomTime );
                    $res = $this->cache->set( $cacheKey1, $data, $expire1 );
                    if( $res != 1 ) {
                            Yii::log("{$method} :Store {$cacheKey1} to cache1 error, code is " . $res, CLogger::LEVEL_WARNING, __METHOD__ );
                    }
                    else {
                            Yii::log("{$method} :Store {$cacheKey1} to cache1", CLogger::LEVEL_TRACE, __METHOD__ );
                    }
                    if( $this->secondaryFlag ) {
                        $expire2 = $this->expire2 + rand( 0, $this->randomTime );
                        $res = $this->cache->set( $cacheKey2, $data, $expire2 );
                        if( $res != 1 ) {
                                Yii::log("{$method} :Store {$cacheKey2} to cache2 error, code is " . $res, CLogger::LEVEL_WARNING, __METHOD__ );
                        }
                        else {
                                Yii::log( "{$method} :Store {$cacheKey2} to cache2", CLogger::LEVEL_TRACE, __METHOD__ );
                        }
                    }
                }
                catch( Exception $e ) {
                    Yii::log( "{$method} :Store to cache error: " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
                }
                TimerUtil::stop( $method.'SetCache' );
            }
            return $data;
        }
        //一级cache中没有，从后端获取失败，如果启用二级cache，从二级cache中取
        if( $this->secondaryFlag && !$this->onlySetFlag ) {
            TimerUtil::start( $method.'FromCache2' );
            try {
                $data = $this->cache->get( $cacheKey2 );
                Yii::log( "{$method} :Get {$cacheKey2} from cache2!", CLogger::LEVEL_WARNING, __METHOD__ );
            }
            catch( Exception $e ) {
                Yii::log( "{$method} :Get {$cacheKey2} from cache2 error! " . $e->getMessage() , CLogger::LEVEL_WARNING, __METHOD__ );
                $data = null;
            }
            TimerUtil::stop( $method.'FromCache2' );
            if( $data!==false ) {
                TimerUtil::start( $method.'SetCache2FromCache1' );
                try {
                    $expire1 = $this->expire1 + rand( 0, $this->randomTime );
                    $res = $this->cache->set( $cacheKey1, $data, $expire1 );
                    if( $res != 1 ) {
                        Yii::log( "{$method} :Store to cache1 from cache2 error, code is " . $res, CLogger::LEVEL_WARNING, __METHOD__ );
                    }
                    else {
                        Yii::log( "{$method} :Store to cache1 from cache2!", CLogger::LEVEL_TRACE, __METHOD__ );
                    }
                }
                catch( Exception $e ) {
                    Yii::log( "{$method} :Store to cache error! " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
                }
                TimerUtil::stop( $method.'SetCache2FromCache1' );
                return $data;
            }
        }
        //二级cache也没有取到数据，抛异常
        Yii::log("{$method} :Get data from cache and server is error!","error");
        //throw new InvalidArgumentException("Get data from cache and server is error!");
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
        	$data = call_user_func_array ( array( $this, $method ), $params );
		}
		catch(Exception $e) {
        	throw new InvalidArgumentException("{$method} :Get data from server is error! " . $e->getMessage());
		}
		TimerUtil::stop( $method.'FromServer' );
        return $data;
    }

    public function __call($name, $args) {
        $pos = strrpos( $name, "FromCache" );
        if($pos === false || $pos != strlen ( $name ) - 9) {
            throw new InvalidArgumentException ( "undefined method:$name" );
        }
        $callback = substr( $name, 0, $pos );
        $method = $this->getClassFile( get_class( $this ) ) . "::$callback";
        $key = $method . "_" . serialize( $args );
		$this->getManualParams();

        if( $this->cacheFlag ) {
            $data = $this->getDataFromCache( $key, $callback, $args );
        }
        else {
            $data = $this->getDataFromServer( $callback, $args );
        }

        return $data;
    }

	private function removeData($key1, $key2) {
		try {
			$res1 = $this->cache->delete($key1);
			Yii::log( "Remove {$key1} from cache success!", CLogger::LEVEL_TRACE, __METHOD__ );
		}
		catch(Exception $e) {
			Yii::log( "Remove {$key1} from cache exception, " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
		}
		if( $this->secondaryFlag ) {
			try {
				$res2 = $this->cache->delete($key2);
				Yii::log( "Remove {$key2} from cache success!", CLogger::LEVEL_TRACE, __METHOD__ );
			}
			catch(Exception $e) {
				Yii::log( "Remove {$key2} from cache exception, " . $e->getMessage(), CLogger::LEVEL_WARNING, __METHOD__ );
			}
		}
	}

	private function getManualParams() {
		//从url中获取设置的参数控制cache行为，只允许内网用户使用。优先级比代码中set函数设置的高
		if(TRUE||$this->internalNetFlag) {
			if( isset($_GET['noCache']) ) {
				$this->cacheFlag = false;
			}
			if( isset($_GET['removeCache']) ) {
				$this->removeFlag = true;
			}
			if( isset($_GET['onlySetCache']) ) {
				$this->onlySetFlag = true;
			}
			if( isset($_GET['onlyGetCache']) ) {
				$this->onlyGetFlag = true;
			}
		}
	}

    /**
     * 获取调用的service类所在的文件名，用来拼装cache的key
     * 防止多个项目中有相同的service类中定义相同的方法，造成key重复
     *
     * @param string $class 类名
     * @return string, 该类所在的文件完整路径
     */
    private function getClassFile( $class ) {
        $fileName = $class . '.php';
        $files = get_included_files();
        foreach( $files as $file ) {
            if( substr_count( $file, $fileName ) > 0 ) {
                return $file;
            }
        }
        return $fileName;
    }
}
