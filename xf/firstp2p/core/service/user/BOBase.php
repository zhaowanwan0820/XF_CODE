<?php
namespace core\service\user;

use system\libs\oauth;
use libs\utils\Logger;

class BOBase
{
    /**
     * 用户数据
     */
    protected $userData = array();
    private static $_passKey = 'AEXJIEJSIFKELFDILEKFDI';

    protected $conf = array(

    );

    public function getData() {
        if (!empty($this->userData)) {
            return $this->userData;
        }
        return null;
    }

    public function setData($userData) {
        if (is_array($userData)) {
            $this->userData = array_merge($this->userData, $userData);
        }
    }

    public function getUserInfo($code) {
        $o=new OAuth($GLOBALS['sys_config']['NEW_OAUTH_API_URL'].'oauthserver_firstp2p', $GLOBALS['sys_config']["NEW_OAUTH_CLIENT_ID"]);
        $retInfo = $o->getUserInfo($code);
        if (is_array($retInfo) && !empty($retInfo['username'])) {
            return array(
                'id' => $retInfo['id'],
                'user_login_name' => $retInfo['username'],
                'passport_id' => $retInfo['passportid'],
                'user_email' => $retInfo['email'],
                'mobile' => $retInfo['telephone'],
            );
        } else {
            // log 失败信息
            $log = array(
                'type' => 'oauth-login-fail',
                'result' => 'getuserinfo-fail',
                'url' => 'user-callback',
                'info' => $reinfo,
                'path' =>  __FILE__,
                'function' => 'getUserInfo',
                'msg' => '获取用户信息失败.',
                'time' => time(),
            );
            Logger::wLog($log);
            session_unset();
            session_destroy();
            return false;
        }
        return false;
    }

    public function addInfo($key, $value) {
        if (isset($this->userData[$key])) {
            trigger_error("BO attribute {$key} is already exsit", E_USER_NOTICE);
        }
        $this->userData[$key] = $value;
    }

    public function getConf($key) {
        if (isset($this->conf[$key])) {
            return $this->conf[$key];
        }
        return null;
    }

    /**
     * 同步退出
     *
     * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
     * @since      File available since Release 1.0 -- 2012-11-13 下午01:30:55
     * @author	   Zheng Yi Wireless
     */
    public function oauthLogout($url, $return = 0)
    {
        if(stripos($url, 'oauth.9888.com') !== false){
            $url = '';
        }
        //新OAuth
        $to = $GLOBALS['sys_config']['NEW_OAUTH_LOGOUT_URL'] .'?r='.rand(0,1000).'&response_type=code&client_id='
            .$GLOBALS['sys_config']['NEW_OAUTH_CLIENT_ID'].'&redirect_uri='. $url;

        if($return)
            return $to;

        header("Location:" . $to);
        return false;
    }

    /**
     * 密码编译方法;
     * @access  public
     * @param   string      $pass       需要编译的原始密码
     * @return  string
     */
    public function compilePassword($pass)
    {
        $md5pass = md5(md5(base64_encode(self::$_passKey.$pass)).self::$_passKey);
        return $md5pass;
    }
}
