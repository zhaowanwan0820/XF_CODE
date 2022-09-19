<?php
namespace core\service;
use libs\utils\RemoteTag;
use libs\utils\RedisTag;
use libs\utils\FileCache;

/**
 * Class RemoteTagService
 * @package core\service
 */
class RemoteTagService extends BaseService {

    const TAG_DICTS_FILE_CACHE_KEY = 'file_cache_for_tag_dicts';
    const TAG_DITS_UPDATE_TIME = 'tag_dicts_update_time';

    public static $tagAttrs = array();

    public static $tagAttrTypeEnum = array(
        RedisTag::TYPE_KV,
        RedisTag::TYPE_SET,
    );
    /**
     * getTagAttrs
     * 获取所有tag的描述,存文件cache,新增编辑时添加
     *
     * @access public
     * @return array
     */
    public function getTagAttrs($noCache = false) {
        if (!$noCache && !empty(self::$tagAttrs)) {
            return self::$tagAttrs;
        }
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        if($redis) {
            $lastUpdateTime = $redis->get(self::TAG_DITS_UPDATE_TIME);
        }
        if(!$lastUpdateTime) {
            $lastUpdateTime = 0;//初始状态redis可能没有TAG_DITS_UPDATE_TIME,防止lastUpdateTime被当做null处理
        }
        $fileCache = FileCache::getInstance();
        $cacheTime = $fileCache->get(self::TAG_DITS_UPDATE_TIME);
        if ($noCache || empty($cacheTime) || $cacheTime['update_time'] != $lastUpdateTime) {
            //未设置过缓存更新时间，或者缓存更新时间的标志和redis不一致，则读取原始数据并设置新的file缓存，并设置cacheTime和lastUpdateTime一致
            self::$tagAttrs = RedisTag::getAllTagAttr();
            if (empty(self::$tagAttrs)) {
                return false;
            }
            self::updateAttrCache($lastUpdateTime, self::$tagAttrs);
            return self::$tagAttrs;
        } else {
            self::$tagAttrs = $fileCache->get(self::TAG_DICTS_FILE_CACHE_KEY);
            if (!empty(self::$tagAttrs)) {
                return self::$tagAttrs;
            }

            self::$tagAttrs = RedisTag::getAllTagAttr();
            if (empty(self::$tagAttrs)) {
                return false;
            }
            $fileCache->set(self::TAG_DICTS_FILE_CACHE_KEY, self::$tagAttrs, 3600 * 24);
            return self::$tagAttrs;
        }
    }

    /**
     * 更新tag attr缓存
     */
    public static function updateAttrCache($time, $tagAttrs) {

        $fileCache = FileCache::getInstance();
        $redis = \SiteApp::init()->dataCache->getRedisInstance();

        $fileCache->set(self::TAG_DICTS_FILE_CACHE_KEY, $tagAttrs, 3600 * 24);
        $cacheTimeData = array('update_time' => $time);
        $fileCache->set(self::TAG_DITS_UPDATE_TIME, $cacheTimeData, 3600*24);
        if($redis) {
            $redis->set(self::TAG_DITS_UPDATE_TIME, $time);
        }
        return true;
    }

    /**
     * getTagAttr
     * 获取一个tag对应的属性
     *
     * @param mixed $tagKey
     * @access public
     * @return void
     */
    public function getTagAttr($tagKey) {

        $tagAttrs = $this->getTagAttrs();
        if (empty($tagAttrs)) {
            throw new \Exception('Tag服务不可用');
        }

        if (isset($tagAttrs[$tagKey])) {
            $tagAttr = $tagAttrs[$tagKey];
        } else {
            $tagAttr = RedisTag::getTagAttr($tagKey);
        }

        if (empty($tagAttr)) {
            throw new \Exception('该tag不存在，请先添加tag');
        }

        return $tagAttr;
    }

    /**
     * 设置一个tag的属性
     */
    public function setTagAttr($tagKey, $tagAttr) {

        if (!in_array($tagAttr['type'], self::$tagAttrTypeEnum)) {
            throw new \Exception('tag类型错误');
        }
        $result = RedisTag::setTagAttr($tagKey, $tagAttr);
        if (!$result) {
            return false;
        }
        self::$tagAttrs = RedisTag::getAllTagAttr();
        self::updateAttrCache(time(), self::$tagAttrs);

        return $result;
    }

    /**
     * 删除一个tag的属性
     */
    public function delTagAttr($tagKey) {

        $result = RedisTag::delTagAttr($tagKey);
        if (!$result) {
            return false;
        }
        self::$tagAttrs = RedisTag::getAllTagAttr();
        self::updateAttrCache(time(), self::$tagAttrs);
        return $result;
    }

    /**
     * 添加tag
     */
    public function addUserTag($userId, $tagKey, $value) {

        $tagAttr = $this->getTagAttr($tagKey);

        if ($tagAttr['type'] == RedisTag::TYPE_KV) {
            return RedisTag::setKvTag($userId, array($tagKey => strval($value)));
        }

        if ($tagAttr['type'] == RedisTag::TYPE_SET) {
            return RedisTag::appendSetTag($userId, $tagKey, $value);
        }

        return false;
    }

    /**
     * 获取某个kv tag对应的value
     */
    public function getUserTag($userId, $tagKey) {

        $tagAttr = $this->getTagAttr($tagKey);

        if ($tagAttr['type'] == RedisTag::TYPE_KV) {
            $result =  RedisTag::getKvTag($userId, array($tagKey));
            if (!empty($result)) {
                return $result[$tagKey];
            }
        }

        if ($tagAttr['type'] == RedisTag::TYPE_SET) {
            $result = RedisTag::getSetTag($userId, $tagKey);
            if (!$result) {
                return false;
            }
            return $result;
        }
        return false;
    }

    /**
     *  删除某个tag或set类型tag中的值
     */
    public function delUserTag($userId, $tagKey, $value = '') {

        $tagAttr = $this->getTagAttr($tagKey);
        if ($tagAttr['type'] == RedisTag::TYPE_KV) {
            return RedisTag::delKvTag($userId, array($tagKey));
        }

        if ($tagAttr['type'] == RedisTag::TYPE_SET) {
            return $value !== '' ? RedisTag::subtractSetTag($userId, $tagKey, $value) : RedisTag::delSetTag($userId, $tagKey);
        }

        return false;
    }

    /**
     * 获取一个用户下的所有tag，kv和set类型全部
     */
    public function getUserAllTag($userId) {
        return RedisTag::getUserAllTag($userId);
    }

    /**
     * 判断用户tag中是否存在一个tag
     */
    public function existUserTag($userId, $tagKey, $value = '') {

        $result = false;
        $tagAttr = $this->getTagAttr($tagKey);

        if ($tagAttr['type'] == RedisTag::TYPE_KV) {
            $tag = RedisTag::getKvTag($userId, array($tagKey));
            $result = empty($tag) ? false : true;
        }

        if ($tagAttr['type'] == RedisTag::TYPE_SET) {
            $tag = ($value !== '') ? RedisTag::existInSetTag($userId, $tagKey, $value) : RedisTag::getSetTag($userId, $tagKey);
            $result = empty($tag) ? false : true;
        }
        return $result;
    }

    public function getUserByTag($tagKey, $tagValue = '') {

        return RedisTag::getUserByTag($tagKey, $tagValue);
    }
}
