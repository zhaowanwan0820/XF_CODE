<?php
namespace libs\utils;

//require APP_ROOT_PATH . '/libs/caching/predis/src/Autoloader.php';
//\Predis\Autoloader::register();
/**
 * RemoteTag
 * tag服务
 */
class RedisTag
{
    // 前缀
    const PRE_KEY = 'NEW_TAG_';

    // set类型KEY_VALUE分隔符
    const SET_SPLIT = "^o^";

    // tagDict的类型，set类型的KEY
    const TYPE_SET = 'set';
    // tagDict的类型，kv类型的KEY
    const TYPE_KV = 'string';

    const TAG_KEY_ATTR = 'KEY_ATTR';

    // 三种操作
    // 设置
    const CMD_SET = 'set';

    // 删除
    const CMD_DEL = 'del';

    // 获取
    const CMD_GET = 'get';

    // 搜索
    const CMD_SEARCH = 'search';

    // 批量执行过程中失败的用户
    public static $failedUsers = array();

    // 返回的错误信息
    public static $error = '';

    public static $redisInstance = NULL;

    public static function getRedisCluster() {
        // Redis 3.0 Cluster, No support pipeline and transaction
        // Predis also not support sentinel transaction while phpredis can
        if (!empty(self::$redisInstance)) {
            return self::$redisInstance;
        }

        $parameters = $GLOBALS['components_config']['components']['tagRedisCluster']['nodes'];
        if ($GLOBALS['components_config']['components']['tagRedisCluster']['auth']) {
            foreach($parameters as &$node) {
                $node .= '&password=' . $GLOBALS['components_config']['components']['tagRedisCluster']['password'];
            }
        }
        $options = ['cluster' => 'redis'];
        self::$redisInstance = new \Predis\Client($parameters, $options);
        $defaultParams = ['timeout' => 1, 'persistent' => true];
        if ($GLOBALS['components_config']['components']['tagRedisCluster']['auth']) {
            $defaultParams['password'] = $GLOBALS['components_config']['components']['tagRedisCluster']['password'];
        }
        self::$redisInstance->getConnection()->setDefaultParameters($defaultParams);

        return self::$redisInstance;
    }

    public static function request($params) {

        $result = NULL;
        $time = microtime(true);
        $msg = 'params:'.json_encode($params, JSON_UNESCAPED_UNICODE);

        try {
            $redis = self::getRedisCluster();
            switch ($params['cmd']) {
            case self::CMD_SET:
                $setRes = $redis->hmset(self::PRE_KEY . $params['key'], $params['data']);
                $result = $setRes ? true : false;
                if (!empty($params['index'])) {
                    foreach ($params['data'] as $tagKey => $tagValue) {
                        $redis->sadd(self::PRE_KEY . $tagKey, $params['key']);
                        if (strpos($tagKey, self::SET_SPLIT) !== false) {
                            list($setKey, $setValue) = explode(self::SET_SPLIT, $tagKey);
                            $addIndexKeys[$setKey] = $setKey;
                        } else {
                            $addIndexKeys[$tagKey . self::SET_SPLIT . $tagValue] = $tagKey . self::SET_SPLIT . $tagValue;
                        }
                    }

                    foreach ($addIndexKeys as $setKey) {
                        $redis->sadd(self::PRE_KEY . $setKey, $params['key']);
                    }
                }
                break;
            case self::CMD_GET:
                if (empty($params['columns'])) {
                    $result = $redis->hgetall(self::PRE_KEY . $params['key']);
                } else {
                    $result = $redis->hmget(self::PRE_KEY . $params['key'], $params['columns']);
                    $result = array_combine($params['columns'], $result);
                    $effectResult = false;
                    foreach ($result as $key => $value) {
                        if ($value !== NULL) {
                            $effectResult = true;
                        }
                    }

                    $result = $effectResult ? $result : array();
                }
                break;
            case self::CMD_DEL:
                foreach ($params['columns'] as $field) {
                    $delRes = $redis->hdel(self::PRE_KEY . $params['key'], $field);
                }
                $result = $delRes ? true : false;
                if (!empty($params['index'])) {
                    foreach ($params['columns'] as $tagKey) {
                        $redis->srem(self::PRE_KEY . $tagKey, $params['key']);
                        if (strpos($tagKey, self::SET_SPLIT) !== false) {
                            list($setKey, $setValue) = explode(self::SET_SPLIT, $tagKey);
                            $delIndexKeys[$setKey] = $setKey;
                        } else {
                            $delIndexKeys[$tagKey . self::SET_SPLIT . $tagValue] = $tagKey . self::SET_SPLIT . $tagValue;
                        }
                    }

                    foreach ($delIndexKeys as $setKey) {
                        $redis->srem(self::PRE_KEY . $setKey, $params['key']);
                    }
                }
                break;
            case self::CMD_SEARCH:
                if (empty($params['value'])) {
                    $result = $redis->srandmember(self::PRE_KEY . $params['key'], 100);
                } else {
                    $result = $redis->srandmember(self::PRE_KEY . $params['key'] . self::SET_SPLIT . $params['value'], 100);
                }
                break;
            default:
                break;
            }
        } catch (\Exception $e) {
            $msg .= '|message:'.$e->getMessage();
            if (!$result){
                \libs\utils\Alarm::push('remotetag', '远程tag Redis异常', $msg);
                \libs\utils\PaymentApi::log('TAG_OPERATE_FAIL|'.$msg);
            } else {
                \libs\utils\PaymentApi::log('TAG_OPERATE_INDEX_FAIL|'.$msg);
            }
        }

        $timeCost = round((microtime(true) - $time) * 1000, 2);
        if ($result) {
            \libs\utils\PaymentApi::log('TAG_OPERATE_SUCCESS|' . $msg . '|cost:' . $timeCost);
        }

        return $result;
    }

    /**
     * setKvTag
     * 给用户打tag
     *
     * @param integer $userId 用户ID
     * @param array $tags = array('tagName' => value,)
     * @static
     * @access public
     * @return void
     */
    public static function setKvTag($userId, $tags) {

        $params = [
            'key' => $userId,
            'data' => $tags,
            'cmd' => self::CMD_SET,
            'index' => true
        ];

        return self::request($params);
    }

    /**
     * getKvTag
     * 获取用户的tag
     *
     * @param integer $userId
     * @param array $tags array('name', 'age')
     * @static
     * @access public
     * @return mixed
     */
    public static function getKvTag($userId, $tags = array()) {

        $params = [
            'key' => $userId,
            'columns' => $tags,
            'cmd' => self::CMD_GET,
        ];
        return self::request($params);
    }

    /**
     * delKvTags
     * 获取用户的tag
     *
     * @param integer $userId
     * @param array $tags array('name', 'sex',..);
     * @static
     * @access public
     * @return mixed
     */
    public static function delKvTag($userId, $tags) {

        $params = [
            'key' => $userId,
            'columns' => $tags,
            'cmd' => self::CMD_DEL,
            'index' => true
        ];

        return self::request($params);
    }

    /**
     * getTagAttr
     * 获取单个tag的描述
     *
     * @param string $tagKey
     * @static
     * @access public
     * @return string
     */
    public static function getTagAttr($tagKey) {

        $params = [
            'key' => self::TAG_KEY_ATTR,
            'columns' => array($tagKey),
            'cmd' => self::CMD_GET,
        ];

        return self::request($params);
    }

    /**
     * getAllTagAttr
     * 获取所有tag和其对应的描述
     *
     * @static
     * @access public
     * @return array()
     */
    public static function getAllTagAttr() {

        $result = array();

        $params = [
            'key' => self::TAG_KEY_ATTR,
            'columns' => array(),
            'cmd' => self::CMD_GET,
        ];

        $result = self::request($params);
        if (empty($result)) {
            return array();
        }

        $attrResult = array();
        foreach ($result as $key => $value) {
            list($type, $chn) = explode(self::SET_SPLIT, $value);
            $attrResult[$key] = array('type' => $type, 'chn' => $chn);
        }

        return $attrResult;
    }

    /**
     * setTagAttr
     * 设置tag对应描述
     *
     * @param string $tagKey tag对应键值
     * @param array $tagAttr tag对应属性
     * @static
     * @access public
     * @return void
     */
    public static function setTagAttr($tagKey, $tagAttr) {

        $params = [
            'key' => self::TAG_KEY_ATTR,
            'data' => [
                $tagKey => $tagAttr['type'] . self::SET_SPLIT . $tagAttr['chn']
            ],
            'cmd' => self::CMD_SET,
            'index' => false
        ];

        $result = self::request($params);

        if (empty($result)) {
            return false;
        }
        return true;
    }

    /**
     * delTagAttr
     * 删除tag对应描述
     *
     * @param string $tagKey tag对应键值
     * @static
     * @access public
     * @return void
     */
    public static function delTagAttr($tagKey) {

        $params = [
            'key' => self::TAG_KEY_ATTR,
            'columns' => array($tagKey),
            'cmd' => self::CMD_DEL,
            'index' => false
        ];

        return self::request($params);
    }

    /**
     * appendSetTag
     * 像set类型的tag追加tag
     *
     * @param integer $userId
     * @param string  $tagKey
     * @param string  $tagName
     * @static
     * @access public
     * @return void
     */
    public static function appendSetTag($userId, $tagKey, $tagName) {

        $params = [
            'key' => $userId,
            'data' => [
                $tagKey . self::SET_SPLIT . $tagName => 1
            ],
            'cmd' => self::CMD_SET,
            'index' => true
        ];

        return self::request($params);
    }

    /**
     * subtractSetTag
     * 在set类型的tag删掉tag
     *
     * @param integer $userId
     * @param string $tagKey
     * @param string $tagName
     * @static
     * @access public
     * @return void
     */
    public static function subtractSetTag($userId, $tagKey, $tagName) {

        $params = [
            'key' => $userId,
            'columns' => [
                $tagKey . self::SET_SPLIT . $tagName
            ],
            'cmd' => self::CMD_DEL,
            'index' => true
        ];

        return self::request($params);
    }

    /**
     * existInSetTag
     * 判断set中是否存在指定tag
     *
     * @param integer $userId
     * @param string $tagKey
     * @param string $tagName
     * @static
     * @access public
     * @return void
     */
    public static function existInSetTag($userId,  $tagKey, $tagName) {

        $params = [
            'key' => $userId,
            'columns' => [
                $tagKey . self::SET_SPLIT . $tagName
            ],
            'cmd' => self::CMD_GET
        ];

        $result = self::request($params);
        if (empty($result)) {
            return false;
        }
        return true;
    }

    /**
     * getSetAll
     *
     * @param integer $userId
     * @param string $tagKey
     * @static
     * @access public
     * @return void
     */
    public static function getSetTag($userId, $tagKey) {

        $result = array();
        $userAllTags = self::getUserAllTag($userId);
        if (empty($userAllTags) || !isset($userAllTags[$tagKey])) {
            return array();
        }

        return $userAllTags[$tagKey];
    }

    /**
     * delSetTag
     * 删除指定用户对应setkey的tag
     *
     * @param integer $userId
     * @param string $tagKey
     * @static
     * @access public
     * @return void
     */
    public static function delSetTag($userId, $tagKey) {

        $userAllTags = self::getUserAllTag($userId);
        if (empty($userAllTags) || !isset($userAllTags[$tagKey])) {
            return array();
        }

        $delKeys = array();
        foreach ($userAllTags[$tagKey] as $value) {
            $delKeys[] = $tagKey . self::SET_SPLIT . $value;
        }

        $params = [
            'key' => $userId,
            'columns' => $delKeys,
            'cmd' => self::CMD_DEL
        ];

        return self::request($params);
    }

    /**
     * getUserAllTag
     * 获取用户的所有tag
     *
     * @param integer $userId
     * @static
     * @access public
     * @return array
     */
    public static function getUserAllTag($userId) {

        $result = array();

        $params = [
            'key' => $userId,
            'columns' => array(),
            'cmd' => self::CMD_GET
        ];
        $userAllTags = self::request($params);

        if (empty($userAllTags)) {
            return $result;
        }

        foreach ($userAllTags as $key => $value) {
            if (strpos($key, self::SET_SPLIT) === false) {
                $result[$key] = $value;
            } else {
                list($setKey, $setValue) = explode(self::SET_SPLIT, $key);
                $result[$setKey][] = $setValue;
            }
        }

        return $result;

    }

    // 根据tag获取下面的用户
    public static function getUserByTag($tagKey, $tagValue = '') {

        $params = [
            'key' => $tagKey,
            'value' => $tagValue,
            'cmd' => self::CMD_SEARCH
        ];

        return self::request($params);
    }
}
