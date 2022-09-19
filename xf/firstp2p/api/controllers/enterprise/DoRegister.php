<?php

/**
 * DoRegister.php
 * 企业用户注册时  校验手机号和邀请码
 * @date 2016-10-30
 * @author yanjun <yanjun5@ucfgroup.com>
 */

namespace api\controllers\enterprise;

use libs\web\Form;
use api\controllers\BaseAction;
use core\service\user\BOFactory;
use core\service\LogRegLoginService;
use core\service\H5UnionService;
use core\service\AdunionDealService;
use core\service\UserService;
use core\service\UserTokenService;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use libs\utils\Logger;
use core\dao\EnterpriseModel;
use core\dao\UserModel;
use libs\lock\LockFactory;

class DoRegister extends BaseAction {
    protected $_isHaveCountyCode = false;
    protected $prefix_key = "ENTERPRISE_VERIFY_REGISTER_MOBILE_CODE_KEY_";

    public function init() {
        parent::init();

        $this->logRegLoginService = new LogRegLoginService();

        $this->form = new Form("post");
        $this->form->rules = array(
            // 企业会员登录名
            'user_name' => array('filter' => 'reg', 'message' => '请输入4-20位字母、数字、下划线、横线，首位只能为字母', "option" => array("regexp" => "/^([A-Za-z])[\w-]{3,19}$/", 'optional' => true)),
            // 密码
            'password' => array('filter' => 'length', 'message' => '密码应为6-20位数字/字母/标点', "option" => array("min" => 6, "max" => 20)),
            // 接受短信手机号
            'sms_phone' => array(
                'filter' => 'reg',
                "message" => "接收短信通知手机号码应为7-11为数字",
                "option" => array("regexp" => "/^1[3456789]\d{9}$/")
            ),
            // 接收短信号码国别码
            'sms_country_code' => array('filter' => 'string'),
            // 推荐人姓名
            'inviter_name' => array('filter' => 'string'),
            // 推荐人邀请码
            'invite' => array('filter'=>'string'),
            // 短信验证码
            'code' => array('filter' => 'required', 'message' => '短信验证码不能为空'),

            //注册协议
            'agreement' => array('filter' => 'string'),
            // 来源
            'from_platform' => array('filter' => 'string'),
            // ？？？
            'type' => array('filter' => 'string'),
            // ???
            'src' => array('filter' => 'string'),
            // ？？？
            'isAjax' => array('filter' => 'int'),
            // 分站id
            "euid" => array("filter" => 'string', "option" => array("optional"=>true)),
            // ？？？
            'event_cn_hidden' => array('filter' => 'int'),
        );

        if (!empty($_REQUEST['sms_country_code']) && isset($GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]) && $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['is_show']){
            $this->form->rules['sms_phone'] =  array(
                'filter' => 'reg',
                "message" => "手机格式错误",
                "option" => array("regexp" => "/{$GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['regex']}/")
            );
            $this->_isHaveCountyCode = true;
        }

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        if (app_conf("TURN_ON_FIRSTLOGIN") == 2) {
            $this->setErr('ERR_SYSTEM', "系统正在升级，暂停注册，预计时间0:00到4:00");
            return false;
        }

        if (!$this->checkRegister()) {
            return false;
        }

        $params = $this->form->data;
        // 是否开启验证码效验，方便测试
        if (!isset($GLOBALS['sys_config']['IS_REGISTER_VERIFY']) || $GLOBALS['sys_config']['IS_REGISTER_VERIFY']) {
            //app4.1，判断验证码是否过期
            if ($this->app_version >= 410) {
                $redis = \SiteApp::init()->dataCache->getRedisInstance();
                $vcode = $redis->get($this->prefix_key. $params['sms_phone']);
                if ($vcode == $params['code']) {
                    $redis->del($this->prefix_key. $params['sms_phone']);
                }else{
                    $this->setErr('ERR_SIGNUP_CODE');
                    return false;
                }
            }
        }

        // 加锁，防用户注册重复提交
        $lockKey = "enterprise-register-user-".$params['sms_phone'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey)) {
            $this->setErr('ERR_SIGNUP_PHONE_UNIQUE');
            return false;
        }

        $userInfoExtra = array(
            'usertype' => UserModel::USER_TYPE_ENTERPRISE,
            'user_purpose' => EnterpriseModel::COMPANY_PURPOSE_INVESTMENT
        );

        if ($this->_isHaveCountyCode) {
            $userInfoExtra['country_code'] = trim($_REQUEST['sms_country_code']);
            $userInfoExtra['mobile_code'] = $GLOBALS['dict']['MOBILE_CODE'][$_REQUEST['sms_country_code']]['code'];
        }

        // 不管主站/分站，会员所属网站都读取配置[主站-平台-企业投资户]
        $enterpriseDefaultGroupId = (int)app_conf('ENTERPRISE_DEFAULT_GROUPID');
        if (!empty($enterpriseDefaultGroupId)) {
            $userInfoExtra['group_id'] = $enterpriseDefaultGroupId;
        }

        $result = $this->rpc->local('UserService\Newsignup', array(
            $params['user_name'],
            $params['password'],
            '',
            '',
            $params['code'],
            $params['invite'],
            $userInfoExtra,
            false
        ));
        $lock->releaseLock($lockKey);

        if (!empty($result) && !isset($result['code'])) {
            // 注册成功日志
            $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';

            $userId = $result['user_id'];
            $apiLog = array(
                'time' => date('Y-m-d H:i:s'),
                'userId' => $userId,
                'ip' => get_real_ip(),
                'os' => $os,
                'channel' => $channel,
            );

            logger::wLog("API_REG:" . json_encode($apiLog));
            // $this->registAdd2Adunion($result['user_id'], $params); //广告联盟埋点，记录用户注册

            // 企业证件号码是否长期有效
            $isPermanent = intval($params['is_permanent']);
            if ($isPermanent === 1) {
                $credentialsExpireAt = EnterpriseModel::$credentialsExpireAtDefault;
            } else {
                $credentialsExpireAt = addslashes($params['credentials_expire_at']);
            }

            $consigneePhone = isset($params['consignee_phone'])
                ? addslashes($params['consignee_phone'])
                : addslashes($params['sms_phone']);

            $consigneeCountryCode = isset($params['consignee_country_code'])
                ? addslashes($GLOBALS['dict']['MOBILE_CODE'][$params['consignee_country_code']]['code'])
                : addslashes($GLOBALS['dict']['MOBILE_CODE'][$params['sms_country_code']]['code']);

            // 企业用户注册相关
            $enterpriseRegisterData = [
                'user_id' => intval($userId),
                'credentials_type' => intval($params['credentials_type']),
                'credentials_expire_date' => addslashes($params['credentials_expire_date']),
                'credentials_expire_at' => $credentialsExpireAt,
                'credentials_no' => addslashes($params['credentials_no']),
                'name' => addslashes($params['name']),
                'user_name' => addslashes($params['user_name']),
                'consignee_phone' => $consigneePhone,
                'consignee_country_code' => $consigneeCountryCode,
                'sms_phone' => addslashes($params['sms_phone']),
                'sms_country_code' => addslashes($GLOBALS['dict']['MOBILE_CODE'][$params['sms_country_code']]['code']),
                'inviter_name' => addslashes($params['inviter_name']),
                'inviter_country_code' => '',
                'inviter_phone' => '',
                'create_time' => get_gmtime(),
            ];

            // 企业用户基础相关
            $enterpriseBaseData = [
                'company_name' => addslashes($params['name']),
                'company_purpose' => '1',
                'user_id' => intval($userId),
                'credentials_type' => intval($params['credentials_type']),
                'credentials_expire_date' => addslashes($params['credentials_expire_date']),
                'credentials_expire_at' => $credentialsExpireAt,
                'is_permanent' => $isPermanent,
                'credentials_no' => addslashes($params['credentials_no']),
                'create_time' => get_gmtime(),
            ];

            // 企业联系人相关
            $contactInfo = [
                'user_id' => intval($userId),
                'receive_msg_mobile' => $GLOBALS['dict']['MOBILE_CODE'][$params['sms_country_code']]['code'].'-'.$params['sms_phone'],
                'consignee_phone' => $consigneePhone,
                'consignee_phone_code' => $consigneeCountryCode,
                'inviter_name' => addslashes($params['inviter_name']),
                'inviter_country_code' => '',
                'inviter_phone' => '',
            ];

            $enterpriseParams = array($enterpriseRegisterData, $enterpriseBaseData, $contactInfo);
            $this->rpc->local('EnterpriseService\registerSimpleData', $enterpriseParams);

            // 企业用户，用户名登陆
            $this->login($params['user_name'], $params['password'], $userId);
            RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_API)
                ->notify(array('userId'=>$userId), $params);
        } else {
            $this->logRegLoginService->insert(
                $params['user_name'],
                '',
                2,
                0,
                UserTokenService::LOGIN_FROM_QIYE_APP,
                $params['invite'],
                $result['reason']
            );

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
    public function login($username, $password, $user_id = '') {
        $data = $this->form->data;

        // 调用oauth接口进行登录验证
        $result = $this->rpc->local("UserService\apiNewLogin", array(
            $username,
            $password,
            false,
            UserTokenService::LOGIN_FROM_QIYE_APP
        ));

        if ($result['success'] !== true) {
            $this->logRegLoginService->insert($username, $user_id, 1, 2, UserTokenService::LOGIN_FROM_QIYE_APP, $data['invite']);
            // 登录失败则向频次险种中插入记录
            if (\libs\utils\Block::check('ENTERPRISE_LOGIN_USERNAME', $username) === false) {
                // 如果超过限制，则提示需要填写验证码
                $this->setErr('ERR_VERIFY', "登录认证失败");
            } else {
                // 未超过限制泽提示登录失败
                $this->setErr('ERR_AUTH_FAIL');
            }
        }

        $this->logRegLoginService->insert($username, $user_id, 1, 1, UserTokenService::LOGIN_FROM_QIYE_APP, $data['invite']);
        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserService\getUserByCode", array($token));
        if ($info['code']) {
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
        );
    }

    /**
     * 注册时，添加到adunion_deal表
     */
    public function registAdd2Adunion($userId, $data){
        $adService = new AdunionDealService();
        $adService->euid = !empty($data['euid']) ? $data['euid'] : '';
        $adService->triggerAdRecord($userId, 1, 0, 0, 0, 0, $data['invite']);
    }

    protected function checkRegister() {
        $params = $this->form->data;
        $ret = RiskServiceFactory::instance(Risk::BC_REGISTER, Risk::PF_WEB, DeviceEnum::DEVICE_WAP)
            ->check($params, Risk::SYNC);

        if ($ret === false) {
            Monitor::add('REGISTER_FAIL');
            $this->setErr('ERR_MANUAL_REASON', '注册异常');
            return false;
        }

        if (!$this->check_phone($params['sms_phone'])) {
            Monitor::add('REGISTER_FAIL');
            return false;
        }

        if (!$this->check_username($params['user_name'])) {
            Monitor::add('REGISTER_FAIL');
            return false;
        }

        if (!$this->check_password($params['password'])) {
            Monitor::add('REGISTER_FAIL');
            return false;
        }

        if ($params['agreement'] != '1') {
            Monitor::add('REGISTER_FAIL');
            $this->setErr('ERR_MANUAL_REASON', '不同意注册协议无法完成注册');
            return false;
        }

        // 密码检查
        // 基本规则判断
        if ($GLOBALS['sys_config']['TEMPLATE_LIST'][$GLOBALS['sys_config']['APP_SITE']] == 1) {
            $len = strlen($params['password']);
            $mobile = $params['sms_phone'];
            $password = $params['password'];
            $password = stripslashes($password);

            // 获取密码黑名单
            \FP::import("libs.common.dict");
            $blacklist = \dict::get("PASSWORD_BLACKLIST");
            $base_rule_result = login_pwd_base_rule($len, $mobile, $password);
            if ($base_rule_result){
                Monitor::add('REGISTER_FAIL');
                $this->setErr('REGISTER_FAIL', $base_rule_result['errorMsg']);
                return false;
            }

            // 黑名单判断,禁用密码判断
            $forbid_black_result = login_pwd_forbid_blacklist($password, $blacklist, $mobile);
            if ($forbid_black_result) {
                Monitor::add('REGISTER_FAIL');
                $this->setErr('REGISTER_FAIL', $forbid_black_result['errorMsg']);
                return false;
            }
        }

        return true;
    }

    /**
     * 校验用户名
     * 4-16个字符，支持英文或英文与数字，下划线，横线组合
     *
     * @return bool
     */
    public function check_username($username) {
        $reg = "/^([A-Za-z])[\w-]{3,15}$/";
        if (preg_match($reg, $username)) {
            $isUsernameExist = $this->rpc->local('UserService\isUserExistsByUsername', array($username));
            if ($isUsernameExist) {
                $this->setErr('ERR_SIGNUP_PARAM_USERNAME', '用户名已存在');
                return false;
            }

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
    public function check_password($password) {
        $reg = "/^.{6,20}$/";
        $password_trim = trim($password);
        if (!empty($password_trim) && preg_match($reg, $password)) {
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
    public function check_phone($phone) {
        $reg = "/^1[3456789]\d{9}$/";
        if (preg_match($reg, $phone)) {
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
    public function check_email($email) {
        if (check_email($email)) {
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
    public function check_code($code) {
        $reg = "/^\w{4,20}$/";
        if (preg_match($reg, $code)) {
            return true;
        } else {
            $this->setErr('ERR_SIGNUP_PARAM_CODE');
            return false;
        }
    }


    /**
     * 校验邀请码
     * @return bool
     */
    public function check_invite($invite, $invite_name) {
        $invite = trim($invite);
        $res = array(
            'inviter_country_code' => '',
            'inviter_phone' => ''
        );

        if (!empty($invite)) {
            $coupon = $this->rpc->local('CouponService\checkCoupon', array($invite));
            // 如果邀请码不正确
            if ($coupon === FALSE || $coupon['coupon_disable']) {
                $this->setErr('ERR_COUPON_APP_ERROR', $GLOBALS['lang']['COUPON_DISABLE']);
                return false;
            }

            if ($coupon['refer_user_id']) {
                $referUser = $this->rpc->local('UserService\getUserArray', array($coupon['refer_user_id'], 'mobile, real_name'));
                if (empty($referUser) || (!empty($invite_name) && $referUser['real_name'] != $invite_name)) {
                    $this->setErr('ERR_COUPON_APP_ERROR', '输入的邀请人姓名与邀请码不符，请核对后重新填写');
                    return false;
                }
            }

            $res['inviter_country_code'] = $referUser['mobile_code'];
            $res['inviter_phone'] = $referUser['mobile'];
        }

        return $res;
    }

}
