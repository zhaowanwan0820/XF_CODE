<?php
/**
 * memcache缓存导向redis
 * return array(
    'class'=>'application.lib.extensions.redis.CMem2RedisCache',
    'keyPrefix'=>'itzDCache',
    'servers'=>array(
        array(
            'host'=>'192.168.23.8',
            'port'=>11211,
            'persistent'=>true,
            'timeout'=> 10
        ),
    ),
);
 * @author gao
 * @date 2015年9月11日
 */
class CMem2RedisCache extends CCache
{
	/**
	 * @var boolean whether to use memcached or memcache as the underlying caching extension.
	 * If true {@link http://pecl.php.net/package/memcached memcached} will be used.
	 * If false {@link http://pecl.php.net/package/memcache memcache}. will be used.
	 * Defaults to false.
	 */
	public $useMemcached=false;
	/**
	 * @var Memcache the Memcache instance
	 */
	private $_cache=null;
	/**
	 * @var array list of memcache server configurations
	 */
	private $_servers=array();

	public $clearOldExpireKey='';

	/**
	 * Initializes this application component.
	 * This method is required by the {@link IApplicationComponent} interface.
	 * It creates the memcache instance and adds memcache servers.
	 * @throws CException if memcache extension is not loaded
	 */
	public function init()
	{
		parent::init();
		$this->_cache = $cache=RedisService::getInstance();
	}

	/**
	 * @throws CException if extension isn't loaded
	 * @return Memcache|Memcached the memcache instance (or memcached if {@link useMemcached} is true) used by this component.
	 */
// 	public function getMemCache()
// 	{
// 		if($this->_cache!==null)
// 			return $this->_cache;
// 		else
// 		{
// 			$extension=$this->useMemcached ? 'memcached' : 'memcache';
// 			if(!extension_loaded($extension))
// 				throw new CException(Yii::t('yii',"CMemCache requires PHP {extension} extension to be loaded.",
//                     array('{extension}'=>$extension)));
// 			return $this->_cache=$this->useMemcached ? new Memcached : new Memcache;
// 		}
// 	}

	/**
	 * @return array list of memcache server configurations. Each element is a {@link CMemCacheServerConfiguration}.
	 */
	public function getServers()
	{
		return $this->_servers;
	}

	/**
	 * @param array $config list of memcache server configurations. Each element must be an array
	 * with the following keys: host, port, persistent, weight, timeout, retryInterval, status.
	 * @see http://www.php.net/manual/en/function.Memcache-addServer.php
	 */
	public function setServers($config)
	{
		return array();
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		$key = $this->keyPrefix.$key;
		$expiretime = $this->_cache->ttl($key);
		//一周之内，把以前一直不过期的废除
		Yii::trace("---------->".__FUNCTION__.":key:({$key})\tttl($expiretime)");
		if($expiretime==-1 && $this->clearOldExpireKey){
			$this->delete($key);
			return false;
		}else{
			return $this->_cache->get($key);
		}
	}

	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * @param array $keys a list of keys identifying the cached values
	 * @return array a list of cached values indexed by the keys
	 */
	protected function getValues($keys)
	{
		$key = $this->keyPrefix.$keys;
		return $this->useMemcached ? $this->_cache->getMulti($key) : $this->_cache->get($key);
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key,$value,$expire)
	{
		$key = $this->keyPrefix.$key;
		Yii::trace("---------->".__FUNCTION__.":key:({$key})");
		if($expire>0)
			$expire+=0;
		else
			$expire=0;


		#return $this->useMemcached ? $this->_cache->set($key,$value,$expire) : $this->_cache->set($key,$value,0,$expire);
		return $this->_cache->set($key,$value,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key,$value,$expire)
	{
		$key = $this->keyPrefix.$key;
		return $this->setValue($key, $value, $expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$key = $this->keyPrefix.$key;
		#return $this->_cache->delete($key, 0);
		return $this->_cache->del($key);
	}

	/**
	 * Deletes all values from cache.
	 * This is the implementation of the method declared in the parent class.
	 * @return boolean whether the flush operation was successful.
	 * @since 1.1.5
	 */
	protected function flushValues()
	{
		return $this->_cache->flush();
	}
}


