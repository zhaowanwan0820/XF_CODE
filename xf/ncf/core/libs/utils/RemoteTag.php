<?php
namespace libs\utils;
use libs\utils\RedisTag;

/**
 * RemoteTag
 * tag服务
 */
class RemoteTag
{
    // 生成接口token对应的salt
    const PRE_TOKEN = 'o2o';

    // tagDict的类型，set类型的KEY
    const TYPE_SET = 'set';
    // tagDict的类型，kv类型的KEY
    const TYPE_KV = 'string';

    // 设置用户kv类型的tag接口地址
    const URL_SET_KV_TAG = 'user_kv_set.html';
    // 获取用户kv类型的tag接口地址
    const URL_GET_KV_TAG = 'user_kv_get.html';
    // 删除用户kv类型的tag接口地址
    const URL_DEL_KV_TAG = 'user_kv_del.html';
    // 设置单个tag的属性接口地址
    const URL_SET_TAG_ATTR = 'keyattr_set.html';
    // 获取单个tag的属性接口地址
    const URL_GET_TAG_ATTR = 'keyattr_get.html';
    // 获取单个tag对应属性的接口地址
    const URL_DEL_TAG_ATTR = 'keyattr_del.html';
    // 获取所有tag及相应属性接口地址
    const URL_GET_ALL_TAG_ATTRS = 'keyattr_getall.html';
    // 往set类型的tag中追加标签接口地址
    const URL_APPEND_SET = 'user_sadd.html';
    // 在set类型的tag中删除指定标签接口地址
    const URL_SUBTRACT_SET = 'user_sdel.html';
    // 判断set中是否存在指定标签接口地址
    const URL_EXIST_IN_SET = 'user_sexist.html';
    // 获取一个set下所有tag的接口地址
    const URL_GET_SET  = 'user_sgetall.html';
    // 删除一个set下所有tag接口名称的接口地址
    const URL_CLEAR_SET = 'user_sclear.html';

    // 获取一个用户下的所有tag
    const URL_GET_USER_ALL = 'user_getall.html';

    // 获取一个tag下的所有用户
    const URL_GET_USER_BY_TAG = 'get_all_user_by_key.html';

    // 批量执行过程中失败的用户
    public static $failedUsers = array();

    // 返回的错误信息
    public static $error = '';

    /**
     * getToken
     * 获取请求的token
     *
     * @static
     * @access public
     * @return string
     */
    public static function getToken() {
        return md5(self::PRE_TOKEN . date("mdH"));
    }

    /**
     * getFailedUsers
     * 获取删除或更新失败的用户id
     *
     * @static
     * @access public
     * @return array()
     */
    public static function getFailedUsers() {
        return self::$failedUsers;
    }

    /**
     * httpPost
     * 请求方法，添加接口统计
     *
     * @param string $url
     * @param array $params
     * @param int $timeOut
     * @static
     * @access public
     * @return void
     */
    public static function httpPost($url, $params, $timeOut = 300, $retryTimes = 3) {

        // send request
        $ch = curl_init();
        $url = $GLOBALS['components_config']['remotetag']['host'] . $url;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeOut);
        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (substr($url, 0, 5) === 'https')
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  //信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //检查证书中是否设置域名
        }
        // 添加请求时间记录
        $timeStart = microtime(true);
        $i = 0;
        $errno = -1;
        while ($i < $retryTimes && $errno !== 0) {
            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            ++$i;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $timeCost = round((microtime(true) - $timeStart) * 1000, 2);

        // 统计下接口成功和失败，看下失败概率
        if ($errno !== 0 || $httpCode != 200) {
            $error = curl_error($ch);
            //TODO ADD LOG
            \libs\utils\Alarm::push('remotetag', '远程tag服务不可用', $url . '|'. json_encode($params, JSON_UNESCAPED_UNICODE). "|$errno|$error|$httpCode|$result");
            PaymentApi::log("RemoteTagCurlError|$url|" .json_encode($params, JSON_UNESCAPED_UNICODE). "|$errno|$error|$httpCode|$result|$timeCost");
            return false;
        } else {
            PaymentApi::log("RemoteTagCurlSuccess|$url|" .json_encode($params, JSON_UNESCAPED_UNICODE)."|$timeCost");
            $result = json_decode($result, true);
            if (isset($result['status']) && $result['status'] != 'success') {
                PaymentApi::log('RemoteTagProcessFail|' . $url . '|' .json_encode($params, JSON_UNESCAPED_UNICODE) .'|' . json_encode($result, JSON_UNESCAPED_UNICODE));
                self::$error = $result['status'];
                if (isset($result['failed_user'])) {
                    self::$failedUsers = explode(',', $result['falied_user']);
                }
                return false;
            }
        }
        curl_close($ch);

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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'col' => base64_encode(json_encode($tags)),
        );

        $result = self::httpPost(self::URL_SET_KV_TAG, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::setKvTag($userId, $tags);
        return true;
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

        $userIds = array(strval($userId));
        $postParams = array(
            'token' => self::getToken(),
            'user' => base64_encode(json_encode($userIds)),
        );

        if (!empty($tags)) {
            $postParams['col'] = base64_encode(json_encode($tags));
        }

        $result = self::httpPost(self::URL_GET_KV_TAG, $postParams);

        if (empty($result)) {
            return false;
        }

        return $result['result'][$userId];
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'col' => base64_encode(json_encode($tags)),
        );

        $result = self::httpPost(self::URL_DEL_KV_TAG, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::delKvTag($userId, $tags);

        return true;
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

        $postParams = array(
            'token' => self::getToken(),
            'key' => $tagKey,
        );

        $result = self::httpPost(self::URL_GET_TAG_ATTR, $postParams);

        if (empty($result)) {
            return false;
        }

        return $result['result'];
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

        $postParams = array(
            'token' => self::getToken(),
        );

        $result = self::httpPost(self::URL_GET_ALL_TAG_ATTRS, $postParams);

        if (empty($result)) {
            return false;
        }

        return $result['result'];
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

        $postParams = array(
            'token' => self::getToken(),
            'key' => $tagKey,
            'chn' => $tagAttr['chn'],
            'type' => $tagAttr['type'],
        );

        $result = self::httpPost(self::URL_SET_TAG_ATTR, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::setTagAttr($tagKey, $tagAttr);
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

        $postParams = array(
            'token' => self::getToken(),
            'key' => $tagKey,
        );

        $result = self::httpPost(self::URL_DEL_TAG_ATTR, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::delTagAttr($tagKey);
        return true;
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'space' => $tagKey,
            'tag'  => $tagName,
        );

        $result = self::httpPost(self::URL_APPEND_SET, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::appendSetTag($userId, $tagKey, $tagName);
        return true;
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'space' => $tagKey,
            'tag'  => $tagName,
        );

        $result = self::httpPost(self::URL_SUBTRACT_SET, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::subtractSetTag($userId, $tagKey, $tagName);
        return true;
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'space' => $tagKey,
            'tag'  => $tagName,
        );

        $result = self::httpPost(self::URL_EXIST_IN_SET, $postParams);

        if (empty($result)) {
            return false;
        }
        return $result['result'];
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'space' => $tagKey,
        );

        $result = self::httpPost(self::URL_GET_SET, $postParams);

        if (empty($result)) {
            return false;
        }
        return $result['result'];
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
            'space' => $tagKey,
        );

        $result = self::httpPost(self::URL_CLEAR_SET, $postParams);

        if (empty($result)) {
            return false;
        }

        RedisTag::delSetTag($userId, $tagKey);
        return true;
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

        $postParams = array(
            'token' => self::getToken(),
            'user' => $userId,
        );

        $result = self::httpPost(self::URL_GET_USER_ALL, $postParams);

        if (empty($result)) {
            return false;
        }
        return $result['result'];
    }

    // 根据tag获取下面的用户
    public static function getUserByTag($tagKey, $tagValue = '') {
        $postParams = array(
            'token' => self::getToken(),
            'space' => $tagKey,
        );

        if (!empty($tagValue)) {
            $postParams['tag'] = $tagValue;
        }

        $result = self::httpPost(self::URL_GET_USER_BY_TAG, $postParams);

        if (empty($result)) {
            return false;
        }
        PaymentApi::log("RemoteTagCurlSuccess|allUser|" .json_encode($result, JSON_UNESCAPED_UNICODE));
        return $result['result'];
    }
}
