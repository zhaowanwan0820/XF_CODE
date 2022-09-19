<?php

namespace api\controllers;

use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Logger;
use core\service\user\UserService;

/**
 * AppBaseAction
 * APP api，安全验证部分
 */
class AppBaseAction extends BaseAction {
    public $is_firstp2p;

    // 对于原有的app的h5页面对应的wap页面，如果可以跳转，尝试跳转，否则更改对应的路由
    // TIPS 这个变量取消默认赋值，方便维护的时候判断是否是H5
    //protected $redirectWapUrl = '';
    protected $app_version = 100;

    public function __construct() {
        parent::__construct();
        $this->is_firstp2p = true;
    }

    public function _before_invoke() {
        parent::_before_invoke();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datas = $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $datas = $_GET;
            $datas = array_diff_key($datas, array('act' => '', 'city' => '', 'ctl' => '', '1' => '', '2' => ''));
        } else {
            // 签名不正确
            $this->setErr('ERR_SIGNATURE_FAIL');
            return false;
        }

        try {
            if (!$this->isWapCall()) {
                // 对于app的api的处理
                $this->app_version = $this->getAppVersion();
                // 对于app的请求，需要进行sign鉴权
                $this->apiAuthCheck($datas);
                // 跳转对应的wap页面
                if (!empty($this->redirectWapUrl) && $this->redirectBlacklist() == false) {
                    if (substr($this->redirectWapUrl, 0, 1) == '/') {
                        $url = app_conf('NCFPH_WAP_URL').$this->redirectWapUrl;
                    } else {
                        $url = $this->redirectWapUrl;
                    }

                    if (strpos($url, '?') === false) {
                        // 去除没有用的字段
                        unset($datas['signature']);
                        $url .= '?'.http_build_query($datas, '', '&');
                    }

                    return app_redirect($url);
                }
            } else {
                $this->app_version = 99999;
            }

            $userInfo = $this->user;
            $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
            // 特殊用户处理
            if (\libs\utils\Block::isSpecialUser($userId)) {
                define('SPECIAL_USER_ACCESS', true);
                if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                    throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
                }
            }
        } catch (\Exception $e) {
            $this->errorCode = $e->getCode();
            $this->errorMsg = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * api鉴权验证
     * @param type $data  参数数组
     * @return boolean
     */
    public function apiAuthCheck($data = []) {
        $class = get_called_class();
        // 时间戳验证
        $timestamp = intval($data['timestamp']);
        if (strlen(strval($timestamp)) > 10) {
            $timestamp = intval(substr(strval($timestamp), 0, 10));
        }

        // 时间戳判断
        if (empty($timestamp) || abs($timestamp - time()) > 10 * 60) {
            throw new \Exception(Error::getMsg('ERR_SYSTEM_TIME'), Error::getCode('ERR_SYSTEM_TIME'));
        }

        if (\libs\utils\Signature::verify($data, "&key=" . $this->getSignSecret(), 'signature') === false) {
            // 签名不正确
           throw new \Exception(Error::getMsg('ERR_SIGNATURE_FAIL'), Error::getCode('ERR_SIGNATURE_FAIL'));
        }

        return true;
    }

    /**
     * 获取APP的VERSION
     */
    protected function getAppVersion($initVersion = 100) {
        $appVersion = isset($_SERVER['HTTP_VERSION']) ? intval($_SERVER['HTTP_VERSION']) : 0;
        // HEADER里的VERSION大于100才写入cookie
        $appVersion > $initVersion && \es_cookie::set('appVersion', $appVersion);
        // HEADER里读不到时，从cookie获取
        $appVersion <= 0 && $appVersion = \es_cookie::get('appVersion');
        return max($initVersion, intval($appVersion));
    }

    /**
     * 获取Api域名
     */
    protected function getHost() {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

    /**
     * 获取客户端系统
     */
    protected function getOs() {
        $platform = 0;
        $str = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : $_SERVER['HTTP_USER_AGENT'];
        if (stripos($str, 'ios') !== false) {
            $platform = 1;
        } else if (stripos($str, 'android') !== false) {
            $platform = 2;
        }

        return $platform;
    }

    protected function redirectBlacklist()
    {
        $blacklist = ['account/deal_load_detail' => 0, 'deals/detail' => 0];
        if (isset($_SERVER['HTTP_PLATFORM']) && $_SERVER['HTTP_PLATFORM'] == 'wxapp') {
            list($requestUri, ) = explode("?", $_SERVER['REQUEST_URI']);
            if (isset($blacklist[trim($requestUri, '/')])) {
                return true;
            }
        }

        return false;
    }

    protected function getSignSecret() {
        if ($this->app_version >= 494) {
            return ConstDefine::APP_SEC_KEY_2;
        } else {
            return ConstDefine::APP_SEC_KEY;
        }
    }

    public function isShowO2o() {
        if (!empty($this->user)) {
            return $this->user['isFromWxlc'] ? true : false;
        }

        // 1 from wxapp弹; 2 phapp 不弹 ;3 wap根据登陆站点判断
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'wxph') !== false) {
            $isShowO2o = false;
        } elseif (isset($_SERVER['HTTP_PLATFORM']) && $_SERVER['HTTP_PLATFORM'] == 'wxapp') {
            $isShowO2o = true;
        } elseif (isset($_SERVER['HTTP_CLIENT']) && ($_SERVER['HTTP_CLIENT'] == 'app')) {
            $isShowO2o = false;
        } else {
            $isShowO2o = false;
        }

        return $isShowO2o;
    }
}
