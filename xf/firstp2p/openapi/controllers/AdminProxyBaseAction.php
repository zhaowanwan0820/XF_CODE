<?php

namespace openapi\controllers;

use libs\utils\Curl;
use libs\utils\Block;
use libs\utils\Logger;
use libs\utils\Alarm;

use core\service\UserService;
use openapi\controllers\BaseAction;
use openapi\conf\OpenAdminConf;

class AdminProxyBaseAction extends BaseAction {

    protected $_apiName = ''; //请求的api名称
    protected $_apiConf = []; //请求的conf配置

    const TIMEDIFF = 28800; //时间差值

    public function __construct() {
        parent::__construct();
        $this->redirectAdmin();
    }

    public function authCheck() {
        return true; // return parent::authCheck();
    }

    protected function _setApiName() {
        $calledClass = str_replace('\\', '_', get_called_class());
        $calledInfo  = explode('_', $calledClass);
        $action      = strtolower(array_pop($calledInfo));
        $controller  = strtolower(array_pop($calledInfo));
        $this->_apiName = sprintf('%s_%s', $controller, $action);
    }

    protected function _setApiConf() {
        $adminProxyConfig = OpenAdminConf::$adminProxyConfig;
        if (isset($adminProxyConfig[$this->_apiName])) {
            $this->_apiConf = $adminProxyConfig[$this->_apiName];
        }
    }

    protected function _isProxyTarget() {
        return $this->_apiConf;
    }

    protected function _checkFrequency() {
        if (Block::check('ADMIN_CHECK_HOUR', $this->_apiName, true) === false || Block::check('ADMIN_CHECK_DAY', $this->_apiName, true) === false) {
            Logger::warn(implode(" | ", array(__CLASS__,  __METHOD__, $this->_apiName, '请求频率超过限制')));
            Alarm::push('call_center',"接口：{$this->_apiName} 请求频率超过限制");
        }
    }

    protected function _shareSession() {
        $redis = \SiteApp::init()->dataCache->getRedisInstance();
        $adm_session['adm_id'] = app_conf("CALL_CENTER_ADMID");
        $adm_session['force_change_pwd'] = 1;
        $adm_session['password_update_time'] = time() - 3600;
        \es_session::set(md5(app_conf("AUTH_KEY")), $adm_session);
        session_write_close();
    }

    protected function redirectAdmin(){
        $this->_setApiName();
        $this->_setApiConf();

        if ($this->_isProxyTarget()) {
            $this->_checkFrequency();
            $this->_shareSession();
        }
    }

    protected function _getAdminDomain() {
        switch ($this->_apiConf['admin_location']) {
            case 'ncfwx' :
                return $GLOBALS['sys_config']['NCFWX_ADMIN_DOMAIN'];
            case 'ncfph' :
                return $this->getDomain("http://".$GLOBALS['sys_config']['NCFPH_ADMIN_DOMAIN']);
            case 'bonus' :
                return $GLOBALS['sys_config']['BONUS_ADMIN_DOMAIN'];
            case 'o2o' :
                return $GLOBALS['sys_config']['O2O_ADMIN_DOMAIN'];
        }
    }

    protected function _getAdminUrl($params) {
        if (!$this->_isProxyTarget()) {
            Logger::error(implode(" | ", array(__CLASS__,  __METHOD__, $this->_apiName, '没有设置配置')));
            throw new \Exception("系统错误");
        }

        $params['from'] = 'callCenter';
        if (isset($this->_apiConf['admin_after_invoke'])) {
            $params['afterInvoke'] = $this->_apiConf['admin_after_invoke'];
        }

        return sprintf($this->_apiConf['admin_url'], $this->_getAdminDomain(), http_build_query($params));
    }

    protected function _getCookieOpt() {
        $cookies['PHPSESSID'] = \es_session::id();

        $tmpArr = array();
        foreach ($cookies as $key => $val) {
            $tmpArr[] = "{$key}={$val}";
        }

        return implode(';', $tmpArr);
    }

    public function revokeAdmin($params, $options = []) {
        $reqUrl = $this->_getAdminUrl($params);
        $cookies = $this->_getCookieOpt();
        $response = Curl::get($reqUrl, false, 10, array('cookie' => $cookies));

        //请求出错
        if (Curl::$httpCode != 200 || !($response = json_decode($response, true))) {
            Logger::error(implode(" | ", array(__CLASS__,  __METHOD__, $this->_apiName, '调用接口失败', $reqUrl, $response)));
            throw new \Exception("系统错误");
        }

        //业务出错
        if ($response['errno']) {
            Logger::error(implode(" | ", array(__CLASS__,  __METHOD__, $this->_apiName, '调用接口失败', $reqUrl, json_encode($response))));
            $error = empty($response['error']) ? '系统错误' : $response['error'];
            throw new \Exception($error);
        }

        return $response['data'];
    }

    public function outputRes($data, $needMoney = false) {
        if(empty($data)){
            return array();
        }

        $outfields = $this->_apiConf['out_fields'];
        $result = array();
        foreach($data as $item){
            foreach($outfields as $field){
                $temp[$field] = $item[$field];

                if($needMoney){
                    if(isset($item['remainingTotalMoney'])){
                        $remainingMoney = empty($item['remainingMoney']) ? 0 : $item['remainingMoney'];
                        $temp['remainingLockMoney'] = sprintf("%.2f", ($item['remainingTotalMoney'] - $remainingMoney));
                    }else{
                        $temp['remaining_lock_money'] = sprintf("%.2f", ($item['remaining_total_money'] - $item['remaining_money']));
                    }
                }
            }
            $result[] = $temp;
        }
        return $result;
    }


    public function getIdByNameOrMObile($params) {
        $userService = new UserService();
        if(!empty($params['mobile'])){
            $userId = $userService->getUserIdByMobile($params['mobile']);
            return $userId;
        }

        if(!empty($params['user_name'])){
            $userInfo = $userService->getUserinfoByUsername($params['user_name']);
            if(!empty($userInfo)){
                $userInfo = $userInfo->getRow();
                return $userInfo['id'];
            }
        }

        return false;
    }

    public function getValueFromStyle($list, $fields){
        foreach($list as $key => $value){
            foreach($fields as $item) {
                $start = strrpos($list[$key][$item],"'>") + 2;
                $len = strrpos($list[$key][$item],"</") - $start;
                $list[$key][$item] = substr($list[$key][$item], $start, $len);
            }
        }
        return $list;
    }

    public function getDomain($url) {
        $arr = parse_url($url);
        return $arr['host'];
    }

}

// END class BaseAction
