<?php
/**
 * ICache interface file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 **/

namespace libs\caching;

/**
 * ICache is the interface that must be implemented by cache components.
 *
 * This interface must be implemented by classes supporting caching feature.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
interface ICache
{
        /**
         * Retrieves a value from cache with a specified key.
         *
         * @param string $id a key identifying the cached value
         *
         * @return mixed the value stored in cache, false if the value is not in the cache or expired.
         */
        public function get($id);

        /**
         * Retrieves multiple values from cache with the specified keys.
         *
         * Some caches (such as memcache, apc) allow retrieving multiple cached values at one time,
         * which may improve the performance since it reduces the communication cost.
         * In case a cache doesn't support this feature natively, it will be simulated by this method.
         *
         * @param array $ids list of keys identifying the cached values
         *
         * @return array list of cached values corresponding to the specified keys. The array
         * is returned in terms of (key,value) pairs.
         * If a value is not cached or expired, the corresponding array value will be false.
         */
        public function mget($ids);

        /**
         * Stores a value identified by a key into cache.
         * If the cache already contains such a key, the existing value and
         * expiration time will be replaced with the new ones.
         *
         * @param string $id the key identifying the value to be cached
         * @param mixed $value the value to be cached
         * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
         * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
         *
         * @return boolean true if the value is successfully stored into cache, false otherwise
         */
        public function set($id,$value,$expire=0,$dependency=null);

        /**
         * Stores a value identified by a key into cache if the cache does not contain this key.
         *
         * Nothing will be done if the cache already contains the key.
         *
         * @param string $id the key identifying the value to be cached
         * @param mixed $value the value to be cached
         * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
         * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
         *
         * @return boolean true if the value is successfully stored into cache, false otherwise
         */
        public function add($id,$value,$expire=0,$dependency=null);

        /**
         * Deletes a value with the specified key from cache
         *
         * @param string $id the key of the value to be deleted
         *
         * @return boolean whether the deletion is successful
         */
        public function delete($id);

        /**
         * Deletes all values from cache.
         *
         * Be careful of performing this operation if the cache is shared by multiple applications.
         *
         * @return boolean whether the flush operation was successful.
         */
        public function flush();

        public function incr($id);

        public function decr($id);
}
