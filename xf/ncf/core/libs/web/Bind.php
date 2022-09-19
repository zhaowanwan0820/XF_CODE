<?php
namespace libs\web;

use \libs\utils\Logger;
use core\service\user\UserService;

class Bind {

    const CHECK_MOBILE_PAGE = 1;
    const SHOW_ERROR_PAGE = 2;

    public static $pages = array(
        'web' => array('1' => 'web/views/v3/bind/check_mobile.html', '2' => 'web/views/v3/bind/error.html'),
        'wap' => array('1' => 'web/views/v3/bind/check_mobile_wap.html', '2' => 'web/views/v3/bind/error_wap.html'),
    );

    public static function genBindSign($sign) {
        $bindSign = self::getBindSign();
        $bindSign[] = $sign;
        $bindSign = array_splice(array_unique($bindSign), -4); //保留4个key
        return serialize($bindSign);
    }

    public static function getBindSign() {
        $bindSign = trim(\es_cookie::get('bind_sign'));
        if (empty($bindSign)) {
            return array();
        }

        $bindSign = unserialize($bindSign);
        if (empty($bindSign)) {
            return array();
        }

        return $bindSign;
    }

    public static function setBindSign($sign) {
        \es_cookie::set('bind_sign', self::genBindSign($sign), 30 * 24 * 60 * 60); //30天有效
    }

    public static function unSetBindSign() {
        \es_cookie::delete('bind_sign');
    }


    public static function getOpenBind() {
        $params   = array(
           'client_id'      => $_REQUEST['client_id'],
           'client_token'   => $_REQUEST['client_token'],
           'timestamp'      => $_REQUEST['timestamp'],
           'sign'           => $_REQUEST['sign'],
           'open_client_id' => $_REQUEST['open_client_id'],
           'bind_sign'      => $_REQUEST['bind_sign'],
           'device'         => $_REQUEST['device'],
           'back_url'       => $_REQUEST['back_url'],
        );

        $userInfo = self::isWapDevice() ? array() : (empty($GLOBALS['user_info']) ? array() : $GLOBALS['user_info']);
        $options  = array("bind_sign" => self::getBindSign());

        $service  = new \core\service\UserBindService();
        Logger::info("查询绑定授权, 输入:" . json_encode($params));

        return $service->checkUserBind($params, $userInfo, $options);
    }

    public static function getTplRet($execute, $type, $data) {
        $device = self::isWapDevice($data) ? 'wap' : 'web';
        return array('execute' => $execute, 'template' => self::$pages[$device][$type], 'data' => $data, 'device' => $device, 'type' => $type);
    }

    //已绑定
    public static function bindStrategy($data) {
        //正常用户
        if ($data['dataIsNormal']) {
            $sessRes = self::createSession($data);
            if ($sessRes['code']) {
                $data['errmsg'] = '用户授权登录失败,请稍后重试';
                return self::getTplRet(false, self::SHOW_ERROR_PAGE, $data);
            }

            $data['sess_data'] = $sessRes['data'];
            return self::getTplRet(true, self::CHECK_MOBILE_PAGE, $data);
        }

        //非正常用户
        return self::unbindStrategy($data);
    }

    //未绑定
    public static function unbindStrategy($data) {
        \es_session::set("user_bind", true);
        \es_session::set('bind_data', $data);
        return self::getTplRet(false, self::CHECK_MOBILE_PAGE, $data);
    }

    public static function checkBindInfo() {
        $chkBindRes = self::getOpenBind();

        //存在问题,授权失败
        if ($chkBindRes['code']) {
            Logger::error("查询绑定授权失败, 结果:" . json_encode($chkBindRes));
            $data['errmsg'] = '绑定授权用户失败！';
            $data['errmsg'] .= empty($chkBindRes['msg']) ? '' : '原因：'.$chkBindRes['msg'];
            return self::getTplRet(false, self::SHOW_ERROR_PAGE, $data);
        }

        $data = $chkBindRes['data'];
        return $data['isUserBind'] ? self::bindStrategy($data) : self::unbindStrategy($data);
    }


    public static function createCode($data){
        require_once APP_ROOT_PATH . "libs/vendors/oauth2/Server.php";
        $oauth = new \PDOOAuth2(array('user_id' => $data['p2pUserId']));
        $wapClientId = 'db6c30dddd42e4343c82713e'; //wap主站client_id
        $params = array('client_id' => $wapClientId, 'response_type' => 'code');
        $result = $oauth->finishClientAuthorization(true, $params, false);
        if (empty($result)) {
            return dataPack(1, '获取授权码失败');
        }

        self::setBindSign($data['cookBindSign']);
        return dataPack(0, '', array('code' => $result['query']['code']));
    }

    public static function createSession($data) {
        $userInfo = UserService::getUserById($data['p2pUserId'], '*');
        if (empty($userInfo)) {
            Logger::error("创建用户会话失败, 输入:" . json_encode($data));
            return dataPack(1, '用户不存在');
        }

        if (!self::isWapDevice($data)) {
            $GLOBALS['user_info'] = $userInfo;
            \es_session::set("user_info", $userInfo);
            \es_session::set('pass_client_token', $data['openBindData']['userParam']['params']['client_token']);
            self::setBindSign($data['cookBindSign']);
        }

        //如果设备是wap，则创建code，跳转至wap
        return self::isWapDevice() ? self::createCode($data) : dataPack(0);
    }

    public static function createUser($data) {
        $service = new \core\service\UserBindService();
        if (!$data['code']) {
            $userId = $data['data']['user_id'];
            //$rpc = new \libs\rpc\Rpc();
            //$rpc->local('AdunionDealService\triggerAdRecord', array($userId, 1));
        }
        return $service->bindUserRegist($data);
    }

    public static function saveOpenBind($data) {
        $service  = new \core\service\UserBindService();
        $result = $service->doOpenUserBind($data);
        if (!$result['code']) {
            $linkCoupon = $data['openBindData']['appInfo']['inviteCode'];
            \es_cookie::set('link_coupon', $linkCoupon);
            $_COOKIE['link_coupon'] =  $linkCoupon;
            //$rpc = new \libs\rpc\Rpc();
            //$rpc->local('AdunionDealService\triggerAdRecord', array($data['p2pUserId'], 1));
        }
        return $result;
    }

    public static function isWapDevice($data = array()) {
        $device = trim($_REQUEST['device']);
        if (!empty($device)) {
            return strtolower($device) != 'web';
        }

        $device = trim($data['openBindData']['userParam']['params']['device']);
        if (!empty($device)) {
            return strtolower($device) != 'web';
        }

        return true;
    }

    public static function getJumpUrl($data) {
        if (!empty($data['openBindData']['appInfo']['notifyUrl'])) {
            return $data['openBindData']['appInfo']['notifyUrl'];
        }

        $backUrl   = trim($data['openBindData']['userParam']['params']['back_url']);
        $isWapDevice = self::isWapDevice($data);
        if (!$isWapDevice) {
            return empty($backUrl) ? '/index/index' : $backUrl;
        }

        $wapDomain = trim($data['openBindData']['appInfo']['usedWapDomain']);
        $redirectUri = sprintf('http://%s/', $wapDomain);
        $code = $data['sess_data']['code'];
        if (empty($code)) {
            return self::redirectAddEuid($redirectUri);
        }

        $redirectUri = empty($backUrl) ? $redirectUri : $backUrl;
        return self::redirectAddEuid(sprintf('http://%s/oauth?code=%s&back_url=%s', $wapDomain, $code, urlencode($redirectUri)));
    }

    public static function redirectAddEuid($url) {
        $euid = $_COOKIE['euid'];
        if (empty($euid)) {
            return $url;
        }

        if (false != strpos($url, '?')) {
            return rtrim($url, '&') . '&euid=' . $euid;
        }

        return $url . '?euid=' . $euid;
    }

    public static function checkWapJump($appInfo){
        if(self::isWapDevice()){
            $appWapDomain = $appInfo['usedWapDomain'];
            if(empty($appWapDomain)){
                $jump = "/";
            }else{
                $jump = "http://".$appWapDomain."/user/openbind?".$_SERVER['QUERY_STRING'];
            }
            header("Location:" . $jump);
            exit;
        }
    }
}
