<?php
/**
 * @date 2015-12-01
 **/

namespace core\service;

use core\dao\WeixinInfoModel;
use libs\utils\Aes;
use libs\weixin\Weixin;
use core\service\WeixinInfoService;
use libs\utils\PaymentApi;
use core\dao\WeixinBindModel;

class WeiXinService extends BaseService
{
    // 公益活动
    const TYPE_PUBLIC_WELFARE = 1;
    // 和红包获取微信cookie 用一个
    const TYPE_MARKETING = 2;

    public static $type_prefix_aes = array(
                                        1 => 'gy',
                                        2 => 'marketing',
                                        );
    public static $type_prefix_cookie = array(
                                        1 => 'gyc',
                                        2 => 'marketingc'
                                        );

    // aes的基础key
    const BASE_KEY_AES = 'cbcd09bcce19e3948285d977229750d2';

    // cookie的基础key
    const BASE_KEY_COOKIE = '747a4d700ea2103b82a1b70c0cf0d820';

    //红包的cookie的name
    const HONGBAO_COOKIE_NAME = 'a4b3b934bb3d9c72bdfd68b8e2b1ac9c';

    // 红包的AES KEY (base64加密)
    const HONGBAO_KEY_AES = 'aGpocyYqNzMqKEAqI0BRKQ==';

    // cookie 的信息
    public static $wxCache = array();

    // 微信本地信息
    public static $wxInfo = array();

    // 微信认证的appid
    public static $appId = '';
    // 微信认证的
    public static $secret = '';

    // 用户id
    public static $userId = '';

    // 用户openid
    public static $openId = '';

    // callback
    public static $callBack = '';

    // 静态obj
    public static $winXinObj = '';

    // 验签绑定盐
    const BIND_SALT = '9865d0dd9d';

    const STATUS_UNBIND = 0; //未绑定
    const STATUS_BINDED_SELF = 1; //已绑定
    const STATUS_BINDED_OTHER_USERID = 3; //USERID已绑定
    const STATUS_BINDED_OTHER_OPENID = 2; //OPENID已绑定

    const BIND_FAILED = 0; // 绑定失败
    const BIND_SUCCESS = 1; // 绑定成功
    const BIND_OTHER_USERID = 3; // USERID已绑定
    const BIND_OTHER_OPENID = 2; // OPENID已绑定

    public function __construct(){
        self::$appId = app_conf('WEIXIN_APPID');
        self::$secret = app_conf('WEIXIN_SECRET');
    }
    /**
     * 清理cookie 防止存满cookie
     * @param int $winxin_activity_type
     */
    public function clearCookie($winxin_activity_type){
        // 防止手机存满cookie
        foreach ($_COOKIE as $key => $value) {
            if ($key != $this->getCookieName($winxin_activity_type) && $key != 'PHPSESSID' && stripos($key, 'hm_l') === false && stripos($key, '_ncf') === false) {
                setcookie($key, '');
            }
        }
    }
    /**
     * 获取winxin对象
     */
    public function  getWinXinObj(){
        $options = array(
            'appid' => self::$appId,
            'appsecret' => self::$secret,
        );
        if (empty(self::$winXinObj)){
            self::$winXinObj = new Weixin($options);
        }

        return self::$winXinObj;
    }
    /**
     * 获取aes的key
     * @param string $winxin_activity_type
     * @return string
     */
    public function getAesKey($winxin_activity_type){

        if (!isset(self::$type_prefix_aes[$winxin_activity_type])){
            return '';
        }
        switch($winxin_activity_type){
            case self::TYPE_MARKETING;
                return self::HONGBAO_KEY_AES;
                break;
        }
        $md5 = md5(self::$type_prefix_aes[$winxin_activity_type].self::BASE_KEY_AES);
        return base64_encode($md5);
    }

    /**
     * 获取cookie的name
     * @param string $winxin_activity_type
     * @return string
     */
    public function getCookieName($winxin_activity_type){

        if (!isset(self::$type_prefix_cookie[$winxin_activity_type])){
            return '';
        }
        switch($winxin_activity_type){
            case self::TYPE_MARKETING;
                return self::HONGBAO_COOKIE_NAME;
                break;
        }
        return md5(self::$type_prefix_cookie[$winxin_activity_type].self::BASE_KEY_COOKIE);
    }

    /**
     * set cookie
     * @param $name
     * @param $value
     * @param int $winxin_activity_type
     * @return bool
     */
    public function setCookie($winxin_activity_type, $value) {

        $name = $this->getCookieName($winxin_activity_type);

        if (empty($winxin_activity_type) || empty($name)){
            return false;
        }
        if (!$value) {
            setcookie($name, '');
            return true;
        }
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        $aes_base64 = $this->getAesKey($winxin_activity_type);
        $value = Aes::encode($value, base64_decode($aes_base64));
        setcookie($name, $value, time() +3600 * 24 * 30, $path, $domain, $secure, true);
        // 红包的设置cookie路径为当前的路径，为了能统一获取值
        setcookie($name, $value, time() +3600 * 24 * 30, '/hongbao/', $domain, $secure, true);
        return true;
    }

    /**
     * @param int $winxin_activity_type
     * @return array|\mix|mixed|\stdClass|string
     */
    public function getCookie( $winxin_activity_type) {

        $name = $this->getCookieName($winxin_activity_type);

        if (empty($name) || !$_COOKIE[$name] || empty($winxin_activity_type)) {
            return;
        }
        $aes_base64 = $this->getAesKey($winxin_activity_type);
        $result = Aes::decode($_COOKIE[$name], base64_decode($aes_base64));
        if (!$result) {
            return $result;
        }
        return json_decode($result, true);
    }


    /**
     * 获取 user agent
     * @return array
     */
    public function getUserAgent() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $from = "";
        if (strpos($userAgent, 'MicroMessenger') !== false) {
            $from = "weixin";
        }

        $os = "";
        if (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = "ios";
        }

        if (preg_match('/Android|Linux/', $userAgent)) {
            $os = "android";
        }
        return array("from" => $from, 'os' => $os);
    }

    /**
     * 移除emoji
     * @param $text
     * @return mixed
     */
    public function removeEmoji($text) {

        return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }
    /**
     * 获取微信信息
     * @param string $open_id
     * @param bool $refreshCache
     * @return array | false
     */
    public function getWinXinInfo($open_id, $refreshCache = false){
        if (empty($open_id)){
            return false;
        }

        $winxinInfoService = new WeixinInfoService();

        return $winxinInfoService->getWeixinInfo($open_id, $refreshCache);
    }

    /**
     * 保存微信信息(如果存在update否的话insert)
     * @param $openid
     * @param array $tokenInfo
     * @param array $userInfo
     * @param string $userId
     * @return bool
     */
    public function saveWinXin($openid, $tokenInfo = array(), $userInfo = array(), $userId = ''){

        if (empty($openid)){
            return false;
        }
        $winxinInfoService = new WeixinInfoService();

        return $winxinInfoService->saveWeixinInfo($openid, $tokenInfo, $userInfo, $userId);

    }

    /**
     * check 是否为微信浏览器
     */
    public function isWinXin(){
        $uaInfo = $this->getUserAgent();
        if ($uaInfo['from'] == "weixin" && self::$appId && self::$secret){
            return true;
        }else{
            return false;
        }
    }
    /**
     * 微信网页授权
     * @param string $callBack
     * @param bool  $returnUrl
     * @return 去微信授权
     */
    public function grantAuthorization($callBack,$returnUrl = false){
        if (empty($callBack)){
            return false;
        }
        $winXinObj = $this->getWinXinObj();
        $jumpTo = $winXinObj->getOauthRedirect($callBack);
        if ($returnUrl) {
           return $returnUrl;
        }
        header('Location:' . $jumpTo);
        exit;
    }

    /**
     * 微信callback
     * @param string $code
     * @return array $ret
     */
    public function winXinCallback($code, $type = self::TYPE_PUBLIC_WELFARE){

        $ret = array('err_code' => 0,'err_msg' => '');

        $isWinXin = $this->isWinXin();
        if ($isWinXin === false){
            $ret['err_code'] = -1;
            $ret['err_msg'] = '请在手机微信中打开此链接';
            return $ret;
        }

        if (empty($code)){
            $ret['err_code'] = -2;
            $ret['err_msg'] = '参数错误';
            return $ret;
        }
        $weiXinObj = $this->getWinXinObj();
        $wxinfoService = new WeixinInfoService();
        // sdk 为get获取值, 防止post code
        $_GET['code'] = $code;
        // 获取微信信息
        $tokenInfo = $weiXinObj->getOauthAccessToken();
        if (!$tokenInfo) {
            $ret['err_code'] = -3;
            $ret['err_msg'] = '微信忙不过来鸟';
            $data = array($tokenInfo, $weiXinObj->errCode, $weiXinObj->errMsg);
            PaymentApi::log("WeixinCallback getOauthAccessToken Error." . json_encode($data, JSON_UNESCAPED_UNICODE));
            return $ret;
        }
        $userInfo = $weiXinObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
        if (!$userInfo) {
            $ret['err_code'] = -4;
            $ret['err_msg'] = '微信忙不过来鸟';
            $data = array($tokenInfo, $userInfo, $weiXinObj->errCode, $weiXinObj->errMsg);
            PaymentApi::log("Weixin getOauthUserinfo Error." . json_encode($data, JSON_UNESCAPED_UNICODE));
            return $ret;
        }

        // 存储用户信息
        $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
        $userInfo['headimgurl'] = substr($userInfo['headimgurl'], 0, strrpos($userInfo['headimgurl'], "/")) . '/96';
        $tokenInfo['time'] = $userInfo['time'] = time();
        self::$openId = $tokenInfo['openid'];
        $wxinfoService->saveWeixinInfo(self::$openId, $tokenInfo, $userInfo);
        $this->wxInfo = array('token_info' => $tokenInfo, 'user_info' => $userInfo);
        // 种下当前用户的openid cookie
        $this->setCookie($type, array('openid' => $tokenInfo['openid']));

        return true;

    }

    /**
     * 已授权处理
     * @param string $callback 防止获取信息失败，重新授权
     * @return bool false 为须在微信浏览器打开
     */
    public function hasAuthorized($callBack,$type = self::TYPE_PUBLIC_WELFARE)
    {
        $isWinXin = $this->isWinXin();
        if ($isWinXin === false) {
            return false;
        }
        self::$wxCache = $this->getCookie($type);
        if (empty(self::$wxCache['openid'])) {
            $this->grantAuthorization($callBack);
        }
        self::$openId = self::$wxCache['openid'];
        // 读取微信信息从本地
        $wxInfoService = new WeixinInfoService();
        self::$wxInfo = $wxInfoService->getWeixinInfo(self::$openId);
        if (empty(self::$wxInfo)) {
            $this->grantAuthorization($callBack);
        }
        $userInfo = self::$wxInfo['user_info'];
        $tokenInfo = self::$wxInfo['token_info'];
        $weiXinObj = $this->getWinXinObj();
        // 时间大于一天更新用户信息
        if (time() - $userInfo['time'] > 3600 * 24) {
            // 更新token信息
            if (time() - $tokenInfo['time'] > 7000) {
                // 刷新token
                $tokenInfo = $weiXinObj->getOauthRefreshToken($tokenInfo['refresh_token']);
            }
            if (empty($tokenInfo)) {
                $this->grantAuthorization($callBack);
            }
            $userInfo = $weiXinObj->getOauthUserinfo($tokenInfo['access_token'], $tokenInfo['openid']);
            $tokenInfo['time'] = $userInfo['time'] = time();
            $userInfo['nickname'] = $this->removeEmoji($userInfo['nickname']);
            // 存储
            $wxInfoService->saveWeixinInfo($this->openid, $tokenInfo, $userInfo);
            $this->setCookie($type, array('openid' => $tokenInfo['openid']));
            if (self::$wxInfo['user_info']['headimgurl']) {
                self::$wxInfo['user_info']['headimgurl'] = substr(self::$wxInfo['user_info']['headimgurl'], 0, strrpos(self::$wxInfo['user_info']['headimgurl'], "/")) . '/96';
            }
        }

        return true;
    }

    /**
     * 批量获取用户ID
     * @param  [array] $uids 用户id数组
     * @return [array] [uid1 => [openid1, openid2], uid2 => [openid]]
     */
    public function getOpenIdByUids($uids)
    {
            if(empty($uids)){
                return false;
            }
             $weixinBindModel = new WeixinBindModel();
             $lists = $weixinBindModel->getOpenidByUids($uids,'user_id,openid');
             foreach ($lists as $list){
              $arr[$list['user_id']][]=$list['openid'];
             }
             return $arr;
    }

    /**
     * 检查用户绑定
     */
    public function isBinded($openId, $mobile, $wxId)
    {
        $userInfo = (new \core\service\UserService)->getByMobile($mobile);
        $userId = $userInfo['id'];
        if (empty($userId)) return self::STATUS_UNBIND;

        $model = new WeixinBindModel;
        $bindInfo = $model->getByOpenid($openId);
        if ($bindInfo && $bindInfo['user_id'] != $userId) return self::STATUS_BINDED_OTHER_USERID;

        $bindInfo = $model->getByUserid($userId, $wxId);
        if ($bindInfo && $bindInfo['openid'] != $openId) return self::STATUS_BINDED_OTHER_OPENID;

        return self::STATUS_UNBIND;

    }

    public function getListBinded($wxId, $openId = '', $userId = '', $page = 1, $size = 20)
    {
        return (new WeixinBindModel)->getList($wxId, $openId, $userId, $page, $size);
    }

        /**
         * 查询绑定关系
         * @param string $openid
         */
        public function getByOpenid($openid, $fields='*'){
            if (empty($openid)){
                return false;
            }

            $weixinBindModel = new WeixinBindModel();

            return $weixinBindModel->getByOpenid($openid,$fields);
        }

       /**
         * 解除绑定关系
         * @param string $openid
         */
         public function delByOpenid($openid){
            if (empty($openid)){
                return false;
            }

            $weixinBindModel = new WeixinBindModel();

            return $weixinBindModel->delByOpenid($openid);
        }
         /**
         * 解除绑定关系
         * @param string $user_id
         */
         public function delByUserid($user_id){
            if (empty($user_id)){
                return false;
            }
            $weixinBindModel = new WeixinBindModel();

            return $weixinBindModel->delByUserid($user_id);
        }

        /**
         * openid是否绑定
         * @param string $openid
         */
         public function isBindOpenid($openid){
              if (empty($openid)){
                return false;
            }
            $weixinBindModel = new WeixinBindModel();
            $result = $weixinBindModel->getByOpenid($openid);
            //空则没有绑定，不空则绑定
            return empty($result) ? FALSE : TRUE;
        }
        /**
         * 写入绑定关系
         * @param array $data
         * $data = array('openid' => 'xx','weixin_id'=>'xx','user_id'=>xxx);
         */
        public function insertWeixinBind($data){
            if (empty($data)){
                return false;
            }
           if($this->isBindOpenid($data["openid"])){
                return true;
            }
            $weixinBindModel = new WeixinBindModel();
            $result = $weixinBindModel->getDelData($data["user_id"], $data["openid"]);
            if(!empty($result)){
              return  $weixinBindModel->updateStatus($data["user_id"], $data["openid"]);
            }else{
              return $weixinBindModel->insertData($data);
            }
        }
        /**
         *  设置加密连接
         * @param int $winxin_activity_type 类型
         * @param array $value
         * @return  bool | string
         */
        public function setAesValue($winxin_activity_type, $value){
            if (empty($winxin_activity_type) || empty($value)){
                return false;
            }
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            $aesKey = $this->getAesKey($winxin_activity_type);

            return Aes::encode($value, base64_decode($aesKey));
        }
        /**
         * 获取解密后的链接
         * @param string $key
         * @param string $value
         */
        public function getAesValue($winxin_activity_type, $value){
            if (empty($winxin_activity_type) || empty($value)){
                return false;
            }
            $aesKey = $this->getAesKey($winxin_activity_type);
            $result = Aes::decode($value, base64_decode($aesKey));
            if (empty($result)){
                return false;
            }

            return json_decode($result,true);
        }
        /**
         * 获取js apiSignature
         * @return array
         * <code>
         * array( appid,timeStamp,nonceStr,signature
         * </code>
         */
        public function getJsApiSignature($url=''){
            $this->getWinXinObj();
            $isHttps = false;
            if (isset($_SERVER['HTTP_XHTTPS']) && 1 == $_SERVER['HTTP_XHTTPS']) {
                $isHttps = true;
            } else {
                $isHttps = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? true : false;
            }
            if(empty($url)) {
                $url = $isHttps ? 'https://' : 'http://';
                if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
                    $url .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
                } else {
                    $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                }
            }
            $nonceStr = md5(time());
            $timeStamp = time();
            $signature = self::$winXinObj->getJsSign($url, $timeStamp, $nonceStr);
            return array(
                'appid' => self::$appId,
                'timeStamp' => $timeStamp,
                'nonceStr' => $nonceStr,
                'signature' => $signature,
                'url'=>$url,
                'timeStamp'=>$timeStamp,
                'nonceStr'=>$nonceStr,
                'jsTicket'=>self::$winXinObj->getJsTicket(),
                'errCode'=>self::$winXinObj->errCode,
                'errMsg'=>self::$winXinObj->errMsg
            );
        }


    /**
     * 同步绑定关系给呼叫中心
     * @param  string $type add/delete
     */
    public static function syncBind2CallCenter($userId, $openId, $type = 'add')
    {
        $url = app_conf('API_CALL_CENTER');
        $api = $url . '/dm/vip/realTimeSyn';
        $params = [
            'custId' => $userId,
            'openId' => $openId,
            'type' => $type,
        ];
        $api = $api . '?' . http_build_query($params);
        \libs\utils\Logger::info(implode('|', [__METHOD__, $api]));
        $res = \libs\utils\Curl::get($api);
        \libs\utils\Logger::info(implode('|', [__METHOD__, $res]));
        $res = json_decode($res, true);
        if ($res['code'] == 1000) {
            return true;
        }
        return false;

    }

    /**
     * 通知weiservice，绑定成功
     */
    public function bindSuccessCallback($openId)
    {
        $api = app_conf('WEI_HOST');
        $api .= '/xiaoneng/bindsuccess';
        $data = [
            'openId' => $openId,
            'timestamp' => time(),
        ];
        $data['sign'] = \NCFGroup\Common\Library\SignatureLib::generate($data, WeiXinService::BIND_SALT);
        \libs\utils\Logger::info(implode('|', [__METHOD__, $api, json_encode($data)]));
        $res = \libs\utils\Curl::post($api, $data);
        \libs\utils\Logger::info(implode('|', [__METHOD__, $openId, $res]));
    }

}
// END class
