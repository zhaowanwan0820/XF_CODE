<?php

namespace api\controllers;

use libs\rpc\Rpc;
use api\conf\Error;
use api\conf\ConstDefine;
use api\controllers\BaseAction;
use libs\utils\Aes;
use libs\web\Form;
use core\dao\BaseModel;

use libs\utils\Logger;
use libs\utils\PaymentApi;

require_once APP_ROOT_PATH.'system/libs/CryptRc4.php';

/**
 * AppBaseAction
 * APP api，安全验证部分
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @author Pine wangjiansong@ucfgroup.com
 */
class AppBaseAction extends BaseAction {

    public $is_firstp2p;

    protected $app_version = 100;
    protected $verify_sign = true;
    protected $must_verify_sign = false;
    protected $view_dir = '_v10';
    protected $playback_action_list = array(
        'Bid', //投标
        'CashOut', //提现
    );

    public function __construct()
    {
        parent::__construct();

        $site_id = \libs\utils\Site::getId();
        $this->is_firstp2p = $site_id == 100 ? true : false;

        // wap调用不参与验签
        if ($this->isWapCall()) {
            $this->must_verify_sign = false;
        }
    }


    public function _before_invoke() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datas = $_POST;
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $datas = $_GET;
            $datas = array_diff_key($datas, array('act' => '', 'city' => '', 'ctl' => '', '1' => '', '2' => ''));
        } else {
            $this->setErr('ERR_SIGNATURE_FAIL'); // 签名不正确
            return false;
        }
        $apiVersion = isset($_SERVER['HTTP_APIVERSION']) ? $_SERVER['HTTP_APIVERSION'] : 0;

        try {
            $this->app_version = $this->getAppVersion();
            //app版本小于300的,服务端接口直接拒绝(2.x.x版本客户端泄露紧急修复)
            if ($this->app_version < 300) {
                throw new \Exception(Error::getMsg('ERR_VERSION'), Error::getCode('ERR_VERSION'));
            }
            $this->authCheck($datas, $apiVersion);
            $this->setAutoViewDir();

            $userInfo = $this->getUserByToken(false);
            $userId = isset($userInfo['id']) ? $userInfo['id'] : 0;
            //特殊用户处理
            if (\libs\utils\Block::isSpecialUser($userId)) {
                define('SPECIAL_USER_ACCESS', true);
                if (\libs\utils\Block::checkAccessLimit($userId) === false) {
                    throw new \Exception('刷新过于频繁，请稍后再试', SHOW_EXCEPTION_MESSAGE_CODE);
                }
            }
        } catch (\Exception $e) {
            $this->errno = $e->getCode();
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * api鉴权验证
     * @param type $data  参数数组
     * @param type $apiVersion  api版本(不同于APP版本)
     * @return boolean
     */
    public function authCheck($data, $apiVersion) {
        $class = get_called_class();
        if (($class::IS_H5 === false && $apiVersion >= 1) || $this->must_verify_sign) {
            //进行时间戳的验证
            if ($apiVersion >= 2) {
                $timestamp = intval($data['timestamp']);
                if (strlen(strval($timestamp)) > 10) {
                    $timestamp = intval(substr(strval($timestamp), 0, 10));
                }
                if (empty($timestamp) || abs($timestamp - time()) > 10 * 60) {
                    throw new \Exception(Error::getMsg('ERR_SYSTEM_TIME'), Error::getCode('ERR_SYSTEM_TIME'));
                }
                //防止回访请求验证
                if (isset($data['orderId'])) {
                    if ($this->playbackCheck($data['orderId']) === false) {
                        $log['function'] = 'playback';
                        $log['api'] = end(explode('\\', get_class($this)));
                        $log['data'] = 'data : ' . json_encode($data);
                        $log['msg'] = Error::getMsg('ERR_PLAYBACK');
                        \libs\utils\Logger::info(implode(" | ", $log));
                        throw new \Exception(Error::getMsg('ERR_PLAYBACK'), Error::getCode('ERR_PLAYBACK'));
                    }
                }
            }
            if (\libs\utils\Signature::verify($data, "&key=" . $this->getSignSecret(), 'signature') === false) {
                throw new \Exception(Error::getMsg('ERR_SIGNATURE_FAIL'), Error::getCode('ERR_SIGNATURE_FAIL'));
            }
        }
        return true;
    }

    /**
     * 请求回放验证
     * @param $uniqOrder 防重放唯一token
     * @return boolean ture|false
     */
    public function playbackCheck($uniqOrder) {
        if (empty($uniqOrder)) {
            return true;
        }
        $className = explode('\\', get_class($this));
        if (in_array(end($className), $this->playback_action_list)) {
            //cache check
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            if ($redis) {
                $isExist = $redis->exists("APP_PLAYBACK_{$uniqOrder}");
                if ($isExist) {
                    return false;
                }
                $redis->setex("APP_PLAYBACK_{$uniqOrder}", 1200, 1);
            }
        }

        return true;
    }

    public function _after_invoke() {
        $this->setAutoViewDir();
        $this->afterInvoke();
        parent::_after_invoke();
    }

    /**
     * 设置view下的版本目录
     * @author longbo
     */
    public function setViewVersion($view_dir = '_v10') {
        $this->view_version = $view_dir;
        $this->template = $this->getTemplate();
        return;
    }

    /**
     * 根据app版本号自动匹配模板目录, 找不到向上个版本目录找
     * @author longbo
     */
    public function setAutoViewDir() {
        $is_mached = 0;
        $version_dir = array_reverse(ConstDefine::$version_dir, true);
        foreach ($version_dir as $ver_num => $value) {
            if ($ver_num > 0 && $ver_num <= $this->app_version) {
                if (is_array($value)) {
                    $this->view_version = $value[0];
                    $class_dir_arr = explode('/', str_replace('\\', '/', get_class($this)));
                    $class_name = array_pop($class_dir_arr);
                    $class_key = end($class_dir_arr) . '/' . $class_name;
                    if (isset($value[$class_key])) {
                        $this->template = $this->getTemplate($value[$class_key]);
                        $is_mached = 1;
                        break;
                    }
                } else {
                    $this->view_version = $value;
                    if (!empty($this->template)) {
                        $tplPath = explode('/', $this->template);
                        $tplName = str_replace('.html', '', array_pop($tplPath));
                        $this->template = $this->getTemplate($tplName);
                    } else {
                        $this->template = $this->getTemplate();
                    }
                    if (@is_file(APP_ROOT_PATH . $this->template)) {
                        $is_mached = 1;
                        break;
                    }
                }
            }
        }
        if (!$is_mached) {
            $this->setViewVersion();
        }
        return;
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
    protected function getHost()
    {
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        return $http . $_SERVER['HTTP_HOST'];
    }

    /**
     * 获取客户端系统
     */
    protected function getOs()
    {
        $platform = 0;
        $str = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : $_SERVER['HTTP_USER_AGENT'];
        if (stripos($str, 'ios') !== false) {
            $platform = 1;
        } else if (stripos($str, 'android') !== false) {
            $platform = 2;
        }

        return $platform;
    }

    public function replaceObjByRow($data) {
        $result = ($data instanceof BaseModel) ? $data->getRow() : $data;
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $value = ($value instanceof BaseModel) ? $value->getRow() : $value;
                $value = $this->replaceObjByRow($value);
                $result[$key] = $value ;
            }
        }

        return $result;
    }

    public function afterInvoke() {
        if ($this->isWapCall()) {
            $data = $this->tpl->get_template_vars();
            unset($data['APP_HOST']);
            unset($data['STATIC_PATH']);
            unset($data['STATIC_SITE']);
            unset($data['data']['token']);
            unset($data['token']);
            $data = $this->replaceObjByRow($data);
            $arrResult = array();
            if ($this->errno == 0) {
                $arrResult["errno"] = 0;
                $arrResult["error"] = "";
                $arrResult["data"] = empty($this->json_data) ? $data : $this->json_data;
            } else {
                $arrResult["errno"] = $this->errno;
                $arrResult["error"] = $this->error;
                $arrResult["timestamp"] = time();
                $arrResult["data"] = "";
            }
            header('Content-type: application/json;charset=UTF-8');
            echo json_encode($arrResult, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public function getRetUserInfo($userInfo){

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $userInfo['id']));

        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:'.$GLOBALS['sys_config']['STATIC_HOST'].'/'.$attachment['attachment'];
            $bind_bank = 1;
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        // 记录日志
        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
        $apiLog = array(
            'time' => date('Y-m-d H:i:s'),
            'userId' => $info['user']['id'],
            'ip' => get_real_ip(),
            'os' => $os,
            'channel' => $channel,
        );
        logger::wLog("API_LOGIN:".json_encode($apiLog));
        PaymentApi::log("API_LOGIN:".json_encode($apiLog), Logger::INFO);

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($info['user']['id']));

        return array(
            "uid" => $userInfo['id'],
            "username" => $userInfo['user_name'],
            "name" => $userInfo['real_name'] ? $userInfo['real_name'] : "无",
            "money" => number_format($userInfo['money'], 2),
            "idno" => $userInfo['idno'],
            "idcard_passed" => $userInfo['idcardpassed'],
            "photo_passed" => $userInfo['photo_passed'],
            "mobile" => !empty($userInfo['mobile']) ? moblieFormat($userInfo['mobile']) : '无',
            "email" => !empty($userInfo['email']) ? mailFormat($userInfo['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            'force_new_password' => $userInfo['force_new_passwd'],
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $userInfo['isSeller'],
            'couponUrl' => $userInfo['couponUrl'],
            'isO2oUser' => $userInfo['isO2oUser'],
            'showO2O' => $userInfo['showO2O'],
            // } END

            'bind_bank' => $bind_bank,
            // 增加用户编号
            'user_num' => numTo32($userInfo['id'], $userInfo['is_enterprise_user']),
        );
    }

    //判断用户是否首投
    public function isBid($userId) {
        return $this->rpc->local('OpenService\isBid', array($userId));
    }

    //wap端调用api接口
    public function isWapCall() {
        return isset($_REQUEST['format']) && 'json' == $_REQUEST['format'];
    }

    protected function getSignSecret() {
        return ConstDefine::APP_SEC_KEY_2;
    }
}
