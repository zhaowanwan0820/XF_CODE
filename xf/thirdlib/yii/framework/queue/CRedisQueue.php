<?php
/**
 * CMemclient class file
 *
 */
class CRedisQueue extends CQueue
{
	/**
	 * The redis client
	 * @var
	 */
	protected $_client;

	/**
	 * The redis queue servers
	 * @var string
	 */
	public $servers;

	/**
	 * The redis server name
	 * @var string
	 */
	public $hostname = "localhost";

	/**
	 * The redis server port
	 * @var integer
	 */
	public $port=6379;

	/**
	 * The database to use, defaults to 1
	 * @var integer
	 */

	/**
	 * timeout, defaults to 0 (unlimited)
	 * @var timeout
	 */
	public $timeout=10;


	/**
	 * Alert notice phones
	 *
	 */
	public $phones='';

	/**
	 * Initializes this application component.
	 * This method is required by the {@link IApplicationComponent} interface.
	 * It creates the redis instance and adds redis servers.
	 * @throws CException if redis extension is not loaded
	 */
	public function init()
	{
		try{
			parent::init();
			if(!count($this->servers))
			{
				$this->servers=array(array('host'=>'127.0.0.1','port'=>6379));
			}
			$this->getClient();	
		} 
		catch(Exception $e)
		{
			Yii::t('yii','Dqueue Redis connect Exception: '.print_r($e,true));
			//异常则发送报警
		    $remind = [];
		    $remind['sent_user'] = 0;
		    $remind['receive_user'] = 1;
		    $remind['mtype'] = "redis_notice";
		    $remind['data']['big_content'] = $this->servers['host'].' '.$e;
            foreach(explode(',', $this->phones) as $phone){
			   $remind['phone'] = $phone;
			   $resultCode = NewRemindService::getInstance()->SendToUser($remind,false,false,true);
            }
            $this->_client == null;
		}
			
	}

	/**
	 * Sets the redis client to use with this connection
	 * @param Redis $client the redis client instance
	 */
	public function setClient(Redis $client)
	{
		$this->_client = $client;
	}


	/**
	 * Gets the redis client
	 * @return Redis the redis client
	 */
	public function getClient()
	{
		if($this->_client !== null)
			return $this->_client;
		else
		{
			$server = $this->servers;
			$redis = new Redis();
			if ($redis->connect($server['host'], $server['port'], $server['timeout'])) 
			{
				if (isset($server['password'])) 
				{
					if (!$redis->auth($server['password'])) 
					{
					   throw new Exception('Failed to Auth connection');
                    }
				}
				if (isset($server['database'])) {
					$redis->select($server['database']);
				}
				$this->_client = $redis;
			} else {
				throw new Exception($server['host'].':redis connect false');
			}
		}			
	}



	/**
	 * right push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	public function rpush($key,$value)
	{
		if($this->_client == null) return false;
		return $this->_client->rpush($this->generateUniqueKey($key),$value);
	}


	/**
	 * left push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	public function lpush($key,$value)
	{
		if($this->_client == null) return false;
		return $this->_client->lpush($this->generateUniqueKey($key),$value);
	}

	/**
	 * Removes and returns the last element of the list stored at key
	 * @param string $key the key of the value to into save
	 * @return frist list index if the value is  successfully stored into list, false otherwise .
	 */
	public function rpop($key,$value)
	{
		if($this->_client == null) return false;
		return $this->_client->rpop($this->generateUniqueKey($key),$value);
	}

	/**
	 * pop is alias for rpop,and without parameter $value
	 * @param string $key
	 */
	public function pop($key){
		if($this->_client == null) return false;
		return $this->_client->rpop($this->generateUniqueKey($key));
	}

	/**
	 * Removes and returns the first element of the list stored at key
	 * @param string $key the key of the value remove
	 * @return the value of the last element, or nil when key does not exist. .
	 */
	public function lpop($key, $value)
	{
		if($this->_client == null) return false;
		return $this->_client->lpop($this->generateUniqueKey($key),$value);
	}

	/**
	 * Removes the first count occurrences of elements equal to value from the list stored at key.
	 * The count argument influences the operation in the following ways:
	 * count > 0: Remove elements equal to value moving from head to tail.
	 * count < 0: Remove elements equal to value moving from tail to head.
     * count = 0: Remove all elements equal to value.
     * For example, LREM list -2 "hello" will remove the last two occurrences of "hello" in the list stored at list.
     * Note that non-existing keys are treated like empty lists, so when key does not exist, the command will always return 0.
	 *
	 *
	 * PhpRedis 第2，3参数位置不同，详细参阅 https://github.com/phpredis/phpredis#lrem-lremove
	 * @param string $key the key int $count of the value remove
	 * @return the number of removed elements.
	 */
	public function lrem($key, $index, $value)
	{
		if($this->_client == null) return false;
		return $this->_client->lrem($this->generateUniqueKey($key), $value, $index);
	}

	/**
	 * Returns the length of the list stored at key.
	 * If key does not exist, it is interpreted as an empty list and 0 is returned.
	 * An error is returned when the value stored at key is not a list.
	 * @param string $key the key
	 * @return the length of the list at key
	 */
	public function llen($key)
	{
		if($this->_client == null) return false;
		return $this->_client->llen($this->generateUniqueKey($key));
	}

	/**
	 * Sets the list element at index to value. For more information on the index argument, see LINDEX.
	 * An error is returned for out of range indexes.
	 * @param string $key the key
	 * @return Simple string reply ok
	 */
	public function lset($key, $index, $value)
	{
		if($this->_client == null) return false;
		return $this->_client->lset($this->generateUniqueKey($key),$index, $value);
	}

	/**
	 * Returns the specified elements of the list stored at key. The offsets start and stop are zero-based indexes,
	 * with 0 being the first element of the list (the head of the list), 1 being the next element and so on.
	 * These offsets can also be negative numbers indicating offsets starting at the end of the list. For example, -1 is the last element of the list, -2 the penultimate, and so on.
     * Consistency with range functions in various programming languages
     * Note that if you have a list of numbers from 0 to 100, LRANGE list 0 10 will return 11 elements, that is, the rightmost item is included.
     * This may or may not be consistent with behavior of range-related functions in your programming language of choice (think Ruby's Range.new, Array#slice or Python's range() function).
     * Out-of-range indexes
     * Out of range indexes will not produce an error. If start is larger than the end of the list, an empty list is returned.
     * If stop is larger than the actual end of the list, Redis will treat it like the last element of the list.
	 * @param string $key
	 * @return Array reply: list of elements in the specified range.
	 */
	public function lrange($key, $start=0, $end=-1)
	{
		if($this->_client == null) return false;
		return $this->_client->lrange($this->generateUniqueKey($key), $start, $end);
	}

	/**
	* Returns the element at index index in the list stored at key. The index is zero-based, so 0 means the first element, 1 the second element and so on.
	* Negative indices can be used to designate elements starting at the tail of the list. Here, -1 means the last element, -2 means the penultimate and so forth.
	* When the value at key is not a list, an error is returned.
	* @param string $key
	* @return Bulk string reply: the requested element, or nil when index is out of range.
	*/
	public function lindex($key, $index=0)
	{
		if($this->_client == null) return false;
		return $this->_client->lindex($this->generateUniqueKey($key),$index);
	}

	/**
	* Sets field in the hash stored at key to value. If key does not exist, a new key holding a hash is created. If field already exists in the hash, it is overwritten.
	* @return Integer reply, specifically:
	*	1 if field is a new field in the hash and value was set.
	*	0 if field already exists in the hash and the value was updated.
	*/
	public function hset($key, $field, $value)
	{
		if($this->_client == null) return false;
        return $this->_client->hset($this->generateUniqueKey($key), $field, $value);
    }

	/**
	* Returns all fields and values of the hash stored at key.
	* In the returned value, every field name is followed by its value, so the length of the reply is twice the size of the hash.
	* @return Array reply: list of fields and their values stored in the hash, or an empty list when key does not exist.
	*/
	public function hgetall($key)
	{
		if($this->_client == null) return false;
        return $this->_client->hgetall($this->generateUniqueKey($key));
    }

	/**
	* Returns if field is an existing field in the hash stored at key.
	* @return Integer reply, specifically:
	*	1 if the hash contains field.
	*	0 if the hash does not contain field, or key does not exist.
	*/
	public function hexists($key, $field)
	{
		if($this->_client == null) return false;
        return $this->_client->hExists($this->generateUniqueKey($key), $field);
    }

	/**
	* Increments the number stored at field in the hash stored at key by increment. If key does not exist, a new key holding a hash is created. If field does not exist the value is set to 0 before the operation is performed.
	* The range of values supported by HINCRBY is limited to 64 bit signed integers.
	* @return Integer reply: the value at field after the increment operation.
	*/
	public function hincrBy($key, $field, $increment=1)
	{
		if($this->_client == null) return false;
        return $this->_client->hashIncrementBy($this->generateUniqueKey($key), $field, $increment);
    }

    /**
	* Returns the number of fields contained in the hash stored at key.
    * For every field that does not exist in the hash, a nil value is returned.
    * Because a non-existing keys are treated as empty hashes, running HMGET against a non-existing key will return a list of nil values
    * @param string $key
	* @return Array reply: list of values associated with the given fields, in the same order as they are requested.
	*/
	public function hmget($key, $field)
	{
		if($this->_client == null) return false;
		if(!is_array($field)) {
			$fields[] = $field;
		} else {
			$fields = $field;
		}
		$result = $this->_client->hmget($this->generateUniqueKey($key),$fields);

		if(!is_array($field)) {
			return $result[$field];
		} else {
			return $result;
		}

	}

	/**
	* Returns the value associated with field in the hash stored at key.
    * @param string $key
	* @return the value associated with field, or nil when field is not present in the hash or key does not exist.
	*/
	public function hget($key, $field)
	{
		if($this->_client == null) return false;
		return $this->_client->hget($this->generateUniqueKey($key),$field);
	}


    /**
	* Returns the number of fields contained in the hash stored at key.
    * @param string $key
	* @return Integer reply: number of fields in the hash, or 0 when key does not exist.
	*/
	public function hlen($key)
	{
		if($this->_client == null) return false;
		return $this->_client->hlen($this->generateUniqueKey($key));
	}

	/**
	* Removes the specified fields from the hash stored at key. Specified fields that do not exist within this hash are ignored. If key does not exist, it is treated as an empty hash and this command returns 0.
	* @return Return value
	* Integer reply: the number of fields that were removed from the hash, not including specified but non existing fields.
	* History
	* >= 2.4: Accepts multiple field arguments. Redis versions older than 2.4 can only remove a field per call.
	* To remove multiple fields from a hash in an atomic fashion in earlier versions, use a MULTI / EXEC block.
	*/
	public function hdel($key, $field)
	{
		if($this->_client == null) return false;
		return $this->_client->hdel($this->generateUniqueKey($key),$field);
	}


	/**
	* Set key to hold the string value. If key already holds a value, it is overwritten, regardless of its type.
	* Any previous time to live associated with the key is discarded on successful SET operation.
	*	Options
	*	Starting with Redis 2.6.12 SET supports a set of options that modify its behavior:
	*	EX seconds -- Set the specified expire time, in seconds.
	*	PX milliseconds -- Set the specified expire time, in milliseconds.
	*	NX -- Only set the key if it does not already exist.
	*	XX -- Only set the key if it already exist.
	*	Note: Since the SET command options can replace SETNX, SETEX, PSETEX,
	*	it is possible that in future versions of Redis these three commands will be deprecated and finally removed.
	* @return Simple string reply: OK if SET was executed correctly.
	*		Null reply: a Null Bulk Reply is returned if the SET operation was not performed because the user specified the NX or XX option but the condition was not met.
	*/
	public function set($key, $value, $expire=3600)
	{
		if($this->_client == null) return false;
		$this->setExpireTime($key, $expire);
		return $this->_client->set($this->generateUniqueKey($key),$value,$expire);
    }

	/**
	* Get the value of key. If the key does not exist the special value nil is returned.
	* An error is returned if the value stored at key is not a string, because GET only handles string values.
	* @return Bulk string reply: the value of key, or nil when key does not exist.
	*/
    public function get($key)
    {
    	if($this->_client == null) return false;
        return $this->_client->get($this->generateUniqueKey($key));
    }

	/**
	* Returns the values associated with the specified fields in the hash stored at key.
    * For every field that does not exist in the hash, a nil value is returned. Because a non-existing keys are treated as empty hashes,
    * running HMGET against a non-existing key will return a list of nil values
    * @param string $key
	* @return Array reply: list of values associated with the given fields, in the same order as they are requested.
	*/
    public function setExpireTime($key, $time)
    {
    	if($this->_client == null) return false;
        return $this->_client->expire($this->generateUniqueKey($key), $time);
    }

	/**
	* Removes the specified keys. A key is ignored if it does not exist.
    * @param string $key
	* @return Integer reply: The number of keys that were removed.
	*/
    public function del($key)
    {
    	if($this->_client == null) return false;
        return $this->_client->del($this->generateUniqueKey($key));
    }

    /**
    * Returns if key exists.
	* Since Redis 3.0.3 it is possible to specify multiple keys instead of a single one. In such a case, it returns the total number of keys existing.
	* Note that returning 1 or 0 for a single key is just a special case of the variadic usage, so the command is completely backward compatible.
	* The user should be aware that if the same existing key is mentioned in the arguments multiple times, it will be counted multiple times.
	* So if somekey exists, EXISTS somekey somekey will return 2.
    * @param string $key
	* @return Array reply: list of values associated with the given fields, in the same order as they are requested.
	*/
    public function exists($key)
    {
    	if($this->_client == null) return false;
        return $this->_client->exists($this->generateUniqueKey($key));
    }

    /**
	* Return Simple string reply: always OK.
    * Marks the start of a transaction block. Subsequent commands will be queued for atomic execution using EXEC.
    */
    public function multi()
    {
    	if($this->_client == null) return false;
        return $this->_client->multi();
    }
    /*
    * Return Simple string reply: always OK.
    * Flushes all previously queued commands in a transaction and restores the connection state to normal.
	* If WATCH was used, DISCARD unwatches all keys watched by the connection.
    */
    public function discard()
    {
    	if($this->_client == null) return false;
        return $this->_client->discard();
    }
    /*
    * Array reply: each element being the reply to each of the commands in the atomic transaction.
	* When using WATCH, EXEC can return a Null reply if the execution was aborted.
    * Executes all previously queued commands in a transaction and restores the connection state to normal.
	* When using WATCH, EXEC will execute commands only if the watched keys were not modified, allowing for a check-and-set mechanism.
    */
    public function exec()
    {
    	if($this->_client == null) return false;
        return $this->_client->exec();
    }
    /*
    * Return value: Simple string reply: always OK.
    * Marks the given keys to be watched for conditional execution of a transaction.
    */
    public function watch()
    {
    	if($this->_client == null) return false;
        return $this->_client->watch();
    }

    /*
    * return Simple string reply: always OK.
    * Flushes all the previously watched keys for a transaction.
    * If you call EXEC or DISCARD, there's no need to manually call UNWATCH.
    */
    public function unwatch()
    {
    	if($this->_client == null) return false;
        return $this->_client->unwatch();
    }

}
