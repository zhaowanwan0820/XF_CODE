<?php

/**
 * Signup.php
 *
 * @date 2014-05-04
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\LogRegLoginService;
use libs\utils\Logger;
use libs\utils\Site;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use core\service\AdunionDealService;
use libs\utils\Risk;
use libs\utils\Monitor;
use libs\lock\LockFactory;
use core\service\UserService;
use core\service\UserTokenService;
use core\service\OpenService;
use libs\web\Open;
use NCFGroup\Common\Library\SignatureLib;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\WeiXinService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

/**
 * 注册添加用户
 *
 * Class Signup
 * @package api\controllers\user
 */
class Signup extends AppBaseAction {

    protected $must_verify_sign = true;
    protected  $prefix_key = "VERIFY_REGISTER_MOBILE_CODE_KEY_";

    public function init() {
        parent::init();
        $this->logRegLoginService = new LogRegLoginService();
        $this->form = new Form("post");
        $this->form->rules = array(
            'username' => array('filter' => 'string',),
            'password' => array("filter" => 'required'),
            'email' => array("filter" => 'string'),
            'phone' => array("filter" => 'required'),
            'code' => array("filter" => 'required'),
            'invite' => array("filter" => 'string'), // 邀请码
            'site_id' => array("filter" => 'string'), //分站标示
            'euid' => array("filter" => 'string'), //分站标示
            'country_code' => array("filter" => 'string'),
            'wxId' => array('filter' => "string", 'option' => array('optional' => true)),
            'openId' => array('filter' => "string", 'option' => array('optional' => true)),
            'sign' => array('filter' => "string", 'option' => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }

        if (!empty($this->form->data['username'])) {
            if (!$this->check_username())
                return false;
        }
        if (!empty($this->form->data['email'])) {
            if (!$this->check_email())
                return false;
        }

        if (!$this->check_password() || !$this->check_code()) {
            return false;
        }

        if(!$this->check_phone()){
            return false;
        }

        if (!empty($this->form->data['invite']) && !$this->check_invite()) {
            $this->logRegLoginService->insert($this->form->data['username'], '', 2, 0, 2, $this->form->data['invite']);
            return false;
        }
    }

    public function invoke() {
        if (app_conf("TURN_ON_FIRSTLOGIN") == 2) {
            $this->setErr('ERR_SYSTEM', "系统正在升级，暂停注册，预计时间0:00到4:00");
            return false;
        }

        $params = $this->form->data;

        // 验证绑定签名
        $isBind = false;
        if (isset($params['wxId']) && isset($params['openId']) && isset($params['sign'])) {
            $isBind = true;
            $data = [
                'wxId' => $params['wxId'],
                'openId' => $params['openId'],
                'sign' => $params['sign'],
            ];
            //验证签名
            if(!SignatureLib::verify($data, WeiXinService::BIND_SALT)) {  //不通过
                $this->setErr('ERR_SIGNATURE_FAIL');
                return false;
            }

            $bindCode = (new WeiXinService)->isBinded($params['openId'], $params['phone'], $params['wxId']);
            if ($bindCode == WeiXinService::STATUS_BINDED_OTHER_USERID ||
                $bindCode == WeiXinService::STATUS_BINDED_OTHER_OPENID)
            {

                $jsonData['isBind'] = true;
                $jsonData['bindRes'] = $bindCode == WeiXinService::STATUS_BINDED_OTHER_OPENID ?
                                       WeiXinService::BIND_OTHER_OPENID :
                                       WeiXinService::BIND_OTHER_USERID;
                $this->json_data = $jsonData;
                return true;
            }
        }

        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_API,Risk::getDevice($_SERVER['HTTP_OS']))->check($params,Risk::SYNC);
        if ($ret === false) {
            $this->setErr('ERR_RISK_DEVICE_BLACKLIST', '注册异常');
            Monitor::add('REGISTER_FAIL');
            return false;
        }

        if (isset($params['site_id']) && !empty($params['site_id'])) {
            $site_key = array_search($params['site_id'], $GLOBALS['sys_config']['TEMPLATE_LIST']);
            $GLOBALS['sys_config']['APP_SITE'] = ($site_key === false) ? 'firstp2p' : $site_key;
        }
        if (!empty($params['invite'])) {
            $params['invite'] = str_replace(' ', '', $params['invite']);
        }

        //app 3.5版本 增加弱密码校验
        $mobile = $params['phone'];
        $password = $params['password'];
        //获取密码黑名单
        \FP::import("libs.common.dict");
        $blacklist = \dict::get("PASSWORD_BLACKLIST");
        //基本规则判断
        $base_rule_result = login_pwd_base_rule(strlen($password), $mobile, $password);
        if ($base_rule_result) {
            $this->setErr('ERR_PASS_RULE', $base_rule_result['errorMsg']);
            Monitor::add('REGISTER_FAIL');
            return false;
        }
        //黑名单判断,禁用密码判断
        $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
        if ($forbid_black_result) {
            $this->setErr('ERR_PASS_BLACKLIST', $forbid_black_result['errorMsg']);
            Monitor::add('REGISTER_FAIL');
            return false;
        }
        //密码校验结束

        $use_mobile_code = true;
        //是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $vcode = $redis->get($this->prefix_key. $params['phone']);
            if($vcode == $params['code']){
                $use_mobile_code = false;
                $redis->del($this->prefix_key. $params['phone']);
            }else{
                $this->setErr('ERR_SIGNUP_CODE');
                return false;
            }
        }

        // 加锁，防用户注册重复提交
        $lockKey = "register-user-".$params['phone'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey)) {
            $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
            return ;
        }

        $appInfo = array();
        $site_id = empty($params['site_id']) ? 1 : $params['site_id'];
        $userInfoExtra = array('site_id' => $site_id);
        //分站添加用户组id
        if($site_id != 1) {
            // 分站优惠购活动
            //$appInfo = Open::getAppBySiteId($site_id);
            //$ticketInfo  = OpenService::toCheckTicket($appInfo, $params['euid']);
            //if ($ticketInfo['status'] != 0) {
            //    $this->setErr('ERR_PARAMS_VERIFY_FAIL', $ticketInfo['msg']);
            //    return false;
            //}

            $site_key = $appInfo['appShortName'];
            $group_id = isset($GLOBALS['sys_config']['SITE_USER_GROUP'][$site_key]) ? $GLOBALS['sys_config']['SITE_USER_GROUP'][$site_key] : 1;
            $userInfoExtra = array_merge($userInfoExtra, array('group_id' => $group_id));

            //分站优惠购活动
            //if (isset($ticketInfo['data']['actType']) && $ticketInfo['data']['actType'] == 1) {
            //    $userInfoExtra['open_ticket'] = $ticketInfo['data'];
            //}
        }

        // 添加渠道信息
        $euid = Site::getEuid();
        if (!empty($euid)) {
            $userInfoExtra['euid'] = $euid;
        }

        //国别号
        $country_code = !empty($params['country_code']) ? $params['country_code'] : "cn";
        $result = $this->rpc->local('UserService\Newsignup', array($params['username'], $params['password'], $params['email'], $params['phone'], $params['code'], $params['invite'], $userInfoExtra, $use_mobile_code,$country_code));
        $lock->releaseLock($lockKey);
        if (!empty($result) && !isset($result['code'])) {

            //生产用户访问日志
            UserAccessLogService::produceLog($result['user_id'], UserAccessLogEnum::TYPE_REGISTER, '注册成功', $params, '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));

            // 注册成功日志
            $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
            $apiLog = array(
                'time' => date('Y-m-d H:i:s'),
                'userId' => $result['user_id'],
                'ip' => get_real_ip(),
                'os' => $os,
                'channel' => $channel,
            );
            logger::wLog("API_REG:" . json_encode($apiLog));
            PaymentApi::log("API_REG:" . json_encode($apiLog), Logger::INFO);

            //$this->registAdd2Adunion($result['user_id'], $params); //广告联盟埋点，记录用户注册
            //OpenService::setTicketStatus($appInfo, $params['euid'], $result['user_id']);

            $this->login($params['phone'], $params['password'], $result['user_id'],$country_code);
            RiskServiceFactory::instance(Risk::BC_REGISTER,Risk::PF_API)->notify(array('userId'=>$result['user_id']), $params);
            // todo
            $this->json_data['isBind'] = $isBind;
            if ($isBind) {
                // 做微信用户绑定
                $bindRes = WeiXinService::BIND_FAILED;
                if ($bindCode == WeiXinService::STATUS_UNBIND) {
                    $taskId = (new GTaskService())->doBackground((new \core\event\WeixinBindEvent($params['wxId'], $params['openId'], $result['user_id'])), 20);
                    Logger::info(implode('|', [__METHOD__, $taskId, $params['wxId'], $params['openId'], $result['user_id']]));
                    if ($taskId) $bindRes = WeiXinService::BIND_SUCCESS;
                } else if ($bindCode == WeiXinService::STATUS_BINDED_SELF) {
                    $bindRes = WeiXinService::BIND_SUCCESS;
                }
                $this->json_data['bindRes'] = $bindRes;
            }

        } else {
            $this->logRegLoginService->insert($this->form->data['phone'], '', 2, 0, 2, $this->form->data['invite']);
            switch ($result['code']) {
                case '303':
                    $this->setErr('ERR_SIGNUP_USERNAME_UNIQUE');
                    break;
                case '304':
                    $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
                    break;
                case '305':
                    $this->setErr('ERR_SIGNUP_EMAIL_UNIQUE');
                    break;
                case '319':
                    $this->setErr('ERR_SIGNUP_CODE');
                    break;
                case '320':
                    $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
                    break;
                default:
                    $this->setErr('ERR_SIGNUP', $result['reason']);
            }
        }
    }

    /**
     * 完成p2p这边的用户添加和登录
     *
     * @param $username
     * @param $password
     * @return bool
     */
    public function login($username, $password, $user_id = '', $country_code = 'cn') {
        // 调用oauth接口进行登录验证
        $loginFrom = $this->isWapCall() ? UserTokenService::LOGIN_FROM_WX_WAP : UserTokenService::LOGIN_FROM_WX_APP;
        $result = $this->rpc->local("UserService\apiNewLogin", array(
            $username,
            $password,
            false,
            $loginFrom,
            $country_code
        ));

        if ($result['success'] !== true) {
            $this->logRegLoginService->insert($this->form->data['username'], $user_id, 1, 0, 2, $this->form->data['invite']);
            // 登录失败则向频次险种中插入记录
            if (\libs\utils\Block::check('LOGIN_USERNAME', $username) === false) {
                // 如果超过限制，则提示需要填写验证码
                $this->setErr('ERR_VERIFY', "登录认证失败");
                return false;
            } else {
                // 未超过限制泽提示登录失败
                $this->setErr('ERR_AUTH_FAIL');
                return false;
            }
        }
        $this->logRegLoginService->insert($this->form->data['username'], $user_id, 1, 1, 2, $this->form->data['invite']);
        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserService\getUserByCode", array($token));
        if (!empty($info['code'])) {
            // 获取oauth用户信息失败
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        if ($info['status'] == 0) {
            // 获取本地用户数据失败
            $this->setErr('ERR_LOGIN_FAIL');
            return false;
        }
        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $info['user']['id']));
        if (!empty($bankcard)) {
            $bank = $this->rpc->local("BankService\getBank", array('bank_id' => $bankcard['bank_id']));
            $bank_no = !empty($bankcard['bankcard']) ? formatBankcard($bankcard['bankcard']) : '无';
            $bank_name = $bank['name'];
            $attachment = $this->rpc->local("AttachmentService\getAttachment", array('id' => $bank['img']));
            $bank_icon = empty($attachment['attachment']) ? "" : 'http:' . $GLOBALS['sys_config']['STATIC_HOST'] . '/' . $attachment['attachment'];
            $bind_bank = 1;
        } else {
            $bank_no = '无';
            $bank_name = '';
            $bank_icon = '';
            $bind_bank = 0;
        }

        $bonus = $this->rpc->local('BonusService\get_useable_money', array($info['user']['id']));
        $this->json_data = array(
            "token" => $token,
            "uid" => $info['user']['id'],
            "username" => $info['user']['user_name'],
            "name" => $info['user']['real_name'] ? $info['user']['real_name'] : "无",
            "money" => number_format($info['user']['money'], 2),
            "idno" => $info['user']['idno'],
            "idcard_passed" => $info['user']['idcardpassed'],
            "photo_passed" => $info['user']['photo_passed'],
            "mobile" => !empty($info['user']['mobile']) ? moblieFormat($info['user']['mobile']) : '无',
            "email" => !empty($info['user']['email']) ? mailFormat($info['user']['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $info['user']['isSeller'],
            'couponUrl' => $info['user']['couponUrl'],
            'isO2oUser' => $info['user']['isO2oUser'],
            'showO2O' => $info['user']['showO2O'],
            // } END
            'bind_bank' => $bind_bank,
            'tokenExpireTime' => (time() + UserTokenService::API_TOKEN_EXPIRE),
            'isBid' => false, //是否首投
        );
    }

    /**
     * 校验用户名
     * 4-16个字符，支持英文或英文与数字，下划线，横线组合
     *
     * @return bool
     */
    public function check_username() {
        $reg = "/^([A-Za-z])[\w-]{3,15}$/";
        if (preg_match($reg, $this->form->data['username'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_USERNAME');
            return false;
        }
    }

    /**
     * 校验密码
     * 5-25个字符，任意字符组成的非空字符串
     *
     * @return bool
     */
    public function check_password() {
        $reg = "/^.{6,20}$/";
        $password_trim = trim($this->form->data['password']);
        if (!empty($password_trim) && preg_match($reg, $this->form->data['password'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_PASSWORD');
            return false;
        }
    }

    /**
     * 校验手机号
     *
     * @return bool
     */
    public function check_phone() {

        $countryCode = 'cn';
        if((isset($this->form->data['country_code'])) && (!empty($this->form->data['country_code']))){
            $countryCode = $this->form->data['country_code'];
        }

        $mobileCode  = $GLOBALS['dict']['MOBILE_CODE'];
        $mobileReg = $mobileCode[$countryCode]['regex'];

        if(empty($mobileReg)){
            return false;
        }

        $reg = "/".$mobileReg."/";
        if (preg_match($reg, $this->form->data['phone'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_PHONE');
            return false;
        }
    }

    /**
     * 校验email
     *
     * @return bool
     */
    public function check_email() {
        if (check_email($this->form->data['email'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_EMAIL');
            return false;
        }
    }

    /**
     * 校验手机验证码
     *
     * @return bool
     */
    public function check_code() {
        $reg = "/^\w{4,20}$/";
        if (preg_match($reg, $this->form->data['code'])) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_CODE');
            return false;
        }
    }

    /**
     * 校验邀请码
     * @return boolean
     * @author zhanglei5@ucfgroup.com
     */
    public function check_invite() {
        $turn_on_invite = 1; //app_conf('TURN_ON_INVITE');  //@这个在哪儿定义的
        if (!empty($this->form->data['invite'])) {
            $invite = trim($this->form->data['invite']); //echo $this->form->data['invite'];
            $ret = $this->rpc->local('CouponService\checkCoupon', array($invite));

            if ($ret === FALSE || $ret['coupon_disable'] || $ret['short_alias'] != strtoupper($invite)) {    //  如果验证码不正确
                $log = array(
                    'type' => 'invite_code_error',
                    'host' => $_SERVER['HTTP_HOST'],
                    'code' => $this->form->data['invite'],
                    'path' => __FILE__,
                    'function' => 'SignupCheck',
                    'time' => time(),
                );
                $destination = APP_ROOT_PATH . "log/logger/invite_code_error-" . date('y_m') . ".log";
                Logger::wLog(var_export($log, TRUE), Logger::INFO, Logger::FILE, $destination);

                $this->setErr($ret['coupon_disable']==0?'ERR_COUPON_EFFECT':'ERR_COUPON_DISABLE');

                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * 注册时，添加到adunion_deal表
     */
    public function registAdd2Adunion($userId, $data){
        $adService = new AdunionDealService();
        $adService->euid = !empty($data['euid']) ? $data['euid'] : '';
        $adService->triggerAdRecord($userId, 1, 0, 0, 0, 0, $data['invite']);
    }

}
