<?php
/**
 * CQueue class file.
 *
 */

/**
 * Queuq is the base class for queue classes with different queue storage implementation.
 *
 * CQueue implements the interface {@link IQueue} with the following methods:
 * <ul>
 * <li>{@link lpush} : left push value with a key into list</li>
 * <li>{@link rpush} : right push value with a key into list</li>
 * <li>{@link lpop} : left pop value with a key into list</li>
 * <li>{@link rpop} : delete the value with the specified key from queue</li>
 * <li>{@link llen} : length with the key from list</li>
 * <li>{@link lindex} : retrieve the value with a key (if any) from list</li>
 * <li>{@link lset} : update the value with a key into list</li>
 * <li>{@link lrem} :  remove one or many the value with the key from list</li>
 * <li>{@link lrange} : retrieve the values with the  key and (start,end) from list</li>
 * </ul>
 *
 * Child classes must implement the following methods:
 * <ul>
 * <li>{@link lpushValue}</li>
 * <li>{@link rpushValue}</li>
 * <li>{@link lpopValue}</li>
 * <li>{@link rpopValue}</li>
 * <li>{@link llenValue} </li>
 * <li>{@link lindexValue} </li>
 * <li>{@link lsetValue} </li>
 * <li>{@link lremValue} </li>
 * <li>{@link lrangeValue} </li>
 * </ul>
 *
 * CCache also implements ArrayAccess so that it can be used like an array.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.caching
 * @since 1.0
 */
abstract class CQueue extends CApplicationComponent implements IQueue
{
	/**
	 * @var string a string prefixed to every cache key so that it is unique. Defaults to null which means
	 * to use the {@link CApplication::getId() application ID}. If different applications need to access the same
	 * pool of cached data, the same prefix should be set for each of the applications explicitly.
	 */
	public $keyPrefix;
	/**
	 * @var boolean whether to md5-hash the cache key for normalization purposes. Defaults to true. Setting this property to false makes sure the cache
	 * key will not be tampered when calling the relevant methods {@link get()}, {@link set()}, {@link add()} and {@link delete()}. This is useful if a Yii
	 * application as well as an external application need to access the same cache pool (also see description of {@link keyPrefix} regarding this use case).
	 * However, without normalization you should make sure the affected cache backend does support the structure (charset, length, etc.) of all the provided
	 * cache keys, otherwise there might be unexpected behavior.
	 * @since 1.1.11
	 **/
	public $hashKey=true;


	/**
	 * Initializes the application component.
	 * This method overrides the parent implementation by setting default queue key prefix.
	 */
	public function init()
	{
		parent::init();
		if($this->keyPrefix===null)
			$this->keyPrefix=Yii::app()->getId();
	}


	/**
	 * @param string $key a key identifying a value to be cached
	 * @return string a key generated from the provided key which ensures the uniqueness across applications
	 */
	protected function generateUniqueKey($key)
	{
		if(empty($key)) {
			throw new CException(Yii::t('yii','key is must not null.',
			array('{className}'=>get_class($this))));
		}
		return $this->hashKey ? md5($this->keyPrefix.$key) : $this->keyPrefix.$key;
	}

	/**
	 * right push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	public function rpush($key, $value)
	{
		Yii::trace('Rpush "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->rpushValue($this->generateUniqueKey($key), $value);
	}
 
 	/**
	 * left push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	public function lpush($key, $value)
	{
		Yii::trace('lpush "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lpushValue($this->generateUniqueKey($key), $value);
	}

	/**
	 * Removes and returns the last element of the list stored at key
	 * @param string $key the key of the value to into save
	 * @return frist list index if the value is  successfully stored into list, false otherwise .
	 */
	public function rpop($key, $value)
	{
		Yii::trace('lpush "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->rpopValue($this->generateUniqueKey($key), $value);
	}

	/**
	 * Removes and returns the first element of the list stored at key
	 * @param string $key the key of the value remove
	 * @return the value of the last element, or nil when key does not exist. .
	 */
	public function lpop($key, $value)
	{
		Yii::trace('lpop "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lpopValue($this->generateUniqueKey($key), $value);
	}

	/**
	 * Removes the first count occurrences of elements equal to value from the list stored at key. The count argument influences the operation in the following ways:
	 * count > 0: Remove elements equal to value moving from head to tail.
	 * count < 0: Remove elements equal to value moving from tail to head.
     * count = 0: Remove all elements equal to value.
     * For example, LREM list -2 "hello" will remove the last two occurrences of "hello" in the list stored at list.
     * Note that non-existing keys are treated like empty lists, so when key does not exist, the command will always return 0.
	 * @param string $key the key int $count of the value remove
	 * @return the number of removed elements.
	 */
	public function lrem($key, $index, $value)
	{
		Yii::trace('lrem "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lremValue($this->generateUniqueKey($key), $index, $value);
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
		Yii::trace('llen "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->llenValue($this->generateUniqueKey($key));
	}

	/**
	 * Sets the list element at index to value. For more information on the index argument, see LINDEX.
	 * An error is returned for out of range indexes.
	 * @param string $key the key 
	 * @return Simple string reply ok
	 */
	public function lset($key, $index, $value)
	{
		Yii::trace('lset "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lsetValue($this->generateUniqueKey($key), $index, $value);
	}

	/**
	 * Returns the specified elements of the list stored at key. The offsets start and stop are zero-based indexes, with 0 being the first element of the list (the head of the list), 1 being the next element and so on.
	 * These offsets can also be negative numbers indicating offsets starting at the end of the list. For example, -1 is the last element of the list, -2 the penultimate, and so on.
     * Consistency with range functions in various programming languages
     * Note that if you have a list of numbers from 0 to 100, LRANGE list 0 10 will return 11 elements, that is, the rightmost item is included. This may or may not be consistent with behavior of range-related functions in your programming language of choice (think Ruby's Range.new, Array#slice or Python's range() function).
     * Out-of-range indexes
     * Out of range indexes will not produce an error. If start is larger than the end of the list, an empty list is returned. If stop is larger than the actual end of the list, Redis will treat it like the last element of the list.
	 * @param string $key 
	 * @return Array reply: list of elements in the specified range. 
	 */
	public function lrange($key, $start=0, $end=-1)
	{
		Yii::trace('lrange "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lrangeValue($this->generateUniqueKey($key), $start, $end);
	}

	/**
	* Returns the element at index index in the list stored at key. The index is zero-based, so 0 means the first element, 1 the second element and so on. Negative indices can be used to designate elements starting at the tail of the list. Here, -1 means the last element, -2 means the penultimate and so forth.
	* When the value at key is not a list, an error is returned.
	* @param string $key 
	* @return Bulk string reply: the requested element, or nil when index is out of range.
	*/
	public function lindex($key, $index)
	{
		Yii::trace('lrange "'.$key.'" to list','system.queue.'.get_class($this));

		return $this->lindexValue($this->generateUniqueKey($key), $index);
	}

	/**
	 * right push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	protected function rpushValue($key, $value)
	{
		throw new CException(Yii::t('yii','{className} does not support rpush() functionality.',
			array('{className}'=>get_class($this))));
	}

 	/**
	 * left push value with a key into list
	 * @param string $key the key of the value to into save
	 * @return last list index if the value is  successfully stored into list, false otherwise .
	 */
	protected function lpushValue($key, $value)
	{
		throw new CException(Yii::t('yii','{className} does not support lpush() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Removes and returns the last element of the list stored at key
	 * @param string $key the key of the value to into save
	 * @return frist list index if the value is  successfully stored into list, false otherwise .
	 */
	protected function rpopValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support rpop() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Removes and returns the first element of the list stored at key
	 * @param string $key the key of the value remove
	 * @return the value of the last element, or nil when key does not exist. .
	 */
	protected function lpopValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support lpop() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Removes the first count occurrences of elements equal to value from the list stored at key. The count argument influences the operation in the following ways:
	 * count > 0: Remove elements equal to value moving from head to tail.
	 * count < 0: Remove elements equal to value moving from tail to head.
     * count = 0: Remove all elements equal to value.
     * For example, LREM list -2 "hello" will remove the last two occurrences of "hello" in the list stored at list.
     * Note that non-existing keys are treated like empty lists, so when key does not exist, the command will always return 0.
	 * @param string $key the key int $count of the value remove
	 * @return the number of removed elements.
	 */
	protected function lremValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support lrem() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Returns the length of the list stored at key. 
	 * If key does not exist, it is interpreted as an empty list and 0 is returned. 
	 * An error is returned when the value stored at key is not a list.
	 * @param string $key the key 
	 * @return the length of the list at key
	 */
	protected function llenValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support llen() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Sets the list element at index to value. For more information on the index argument, see LINDEX.
	 * An error is returned for out of range indexes.
	 * @param string $key the key 
	 * @return Simple string reply ok
	 */
	protected function lsetValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support lset() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	 * Returns the specified elements of the list stored at key. The offsets start and stop are zero-based indexes, with 0 being the first element of the list (the head of the list), 1 being the next element and so on.
	 * These offsets can also be negative numbers indicating offsets starting at the end of the list. For example, -1 is the last element of the list, -2 the penultimate, and so on.
     * Consistency with range functions in various programming languages
     * Note that if you have a list of numbers from 0 to 100, LRANGE list 0 10 will return 11 elements, that is, the rightmost item is included. This may or may not be consistent with behavior of range-related functions in your programming language of choice (think Ruby's Range.new, Array#slice or Python's range() function).
     * Out-of-range indexes
     * Out of range indexes will not produce an error. If start is larger than the end of the list, an empty list is returned. If stop is larger than the actual end of the list, Redis will treat it like the last element of the list.
	 * @param string $key 
	 * @return Array reply: list of elements in the specified range. 
	 */
	protected function lrangeValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support lrange() functionality.',
			array('{className}'=>get_class($this))));
	}

	/**
	* Returns the element at index index in the list stored at key. The index is zero-based, so 0 means the first element, 1 the second element and so on. Negative indices can be used to designate elements starting at the tail of the list. Here, -1 means the last element, -2 means the penultimate and so forth.
	* When the value at key is not a list, an error is returned.
	* @param string $key 
	* @return Bulk string reply: the requested element, or nil when index is out of range.
	*/
	protected function lindexValue($key)
	{
		throw new CException(Yii::t('yii','{className} does not support lindex() functionality.',
			array('{className}'=>get_class($this))));
	}

}