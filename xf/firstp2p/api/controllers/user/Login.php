<?php

/**
 * 登录接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\user;

use api\controllers\AppBaseAction;

use libs\web\Form;
use libs\utils\Block;
use libs\utils\Risk;
use libs\utils\Logger;
use libs\utils\PaymentApi;

use core\service\risk\RiskServiceFactory;
use core\service\UserTrackService;
use core\service\UserVerifyService;
use core\service\UserTokenService;
use core\service\LogRegLoginService;
use core\service\PassportService;
use core\service\UserAccessLogService;
use NCFGroup\Protos\Ptp\Enum\UserAccessLogEnum;
use NCFGroup\Protos\Ptp\Enum\DeviceEnum;

class Login extends AppBaseAction {
    protected $must_verify_sign = true;

    public function init() {
        parent::init();

        $this->form = new Form("post");
        $this->form->rules = array(
            "account" => array(
                "filter" => "required",
                "message" => 'ERR_USERNAME_ILLEGAL'
            ),
            "password" => array(
                "filter" => "reg",
                "message" => 'ERR_PASSWORD_ILLEGAL',
                "option" => array("regexp" => "/^.{5,25}$/")
            ),
            "verify" => array("filter" => "string"),
            "country_code" => array("filter" => "string"),
        );

        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $ret = RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API, Risk::getDevice($_SERVER['HTTP_OS']))
            ->check($data, Risk::SYNC);

        if ($ret === false) {
            $this->setErr('ERR_MANUAL_REASON', '网络连接失败，请稍后再试');
            return false;
        }

        $logRegLoginService = new LogRegLoginService();
        $loginFrom = UserTokenService::LOGIN_FROM_WX_APP;
        $logRegLoginService->insert($data['account'], '', 0, 3, $loginFrom);

        // ip限制
        $this->ipIsLimited(true);

        // 验证码判断
        $this->verifyCode($data['account'], $data['verify']);

        $country_code = !empty($data['country_code']) ? $data['country_code'] : "cn";

        // 登陆来源
        $result = $this->rpc->local("UserService\apiNewLogin", array(
            $data['account'],
            $data['password'],
            true,
            $loginFrom,
            $country_code
        ));

        if (empty($result['success'])) {
            // 通行证
            if ($result['code'] == '10001') {
                $this->json_data = ['code' => 1, 'ppID' => $result['ppID']];
                return true;
            }

            if ($result['code'] == '10002') {
                \SiteApp::init()->cache->set("passport_need_verify_" . $result['ppID'], 1, 600);
                $this->json_data = ['code' => 2, 'ppID' => $result['ppID']];
                return true;
            }

            // 通行证密码已经修改，做二次验证
            if ($result['code'] == '10003') {
                \SiteApp::init()->cache->set("passport_need_verify_" . $data['account'], 1, 600);
                $this->json_data = ['code' => 3];
                return true;
            }

            $logRegLoginService->insert($data['account'], '', 0, 2, $loginFrom, '', $result['reason']);

            // 登录失败则向频次险种中插入记录
            if (!empty($result['code']) && $result['code'] == '20007') {
                $this->setErr('ERR_ENTERPRISE_ABANDON');
            }

            if (!empty($result['code']) && $result['code'] == '20006') {
                $this->setErr('ERR_LOGIN_FAILED', $result['reason']);
            }

            if (!empty($result['code']) && $result['code'] == '-33') {
                $this->setErr('ERR_FAILED_RESETPWD', $result['reason']);
            }

            // 记录ip频次
            $this->ipIsLimited();

            // 需要显示自定义的错误提示
            $verifyError = '';
            // 用户名和密码错误
            if ($result['code'] = '20003' || $result['code'] = '20004') {
                $verifyError = "用户名或密码错误";
            }

            $this->needVerify($data['account'], $verifyError);
            $this->setErr('ERR_AUTH_FAIL');
        }

        // 记录用户登录站点
        $userTrackService = new UserTrackService();
        $userTrackService->setLoginSite($result['user_id']);

        //生产用户访问日志
        UserAccessLogService::produceLog($result['user_id'], UserAccessLogEnum::TYPE_LOGIN, '登陆成功', $data, '', UserAccessLogService::getDevice($_SERVER['HTTP_OS']));

        // 企业用户登录判断
        $logRegLoginService->insert($data['account'], $result['user_id'], 0, 1, $loginFrom);
        RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API)->notify(array('userId'=>$result['user_id']));

        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserTokenService\getUserByToken", array($token));
        if (!empty($info['code'])) {
            // 获取oauth用户信息失败
            $this->setErr('ERR_GET_USER_FAIL');
        }

        if (empty($info['status'])) {
            // 获取本地用户数据失败
            $this->setErr('ERR_LOGIN_FAIL');
        }

        $passportService = new PassportService();
        if ($info['user']['ppID'] && $passportService->isThirdPassport($info['user']['mobile'], false)) {
            $force_new_passwd = 0;
        } else {
            $force_new_passwd = $info['user']['force_new_passwd'];
        }

        $bankcard = $this->rpc->local("UserBankcardService\getBankcard", array("user_id" => $info['user']['id']));
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
        // 电商生活开关
        $wxLifeOpen =  $this->rpc->local("ApiConfService\isWhiteList", ['wxLifeOpen']);

        // 信宝开关
        $candyOpen = \libs\utils\ABControl::getInstance()->hit('candy');

        $this->rpc->local("UserTokenService\kickOutForLogin", array($info['user']['id'], $token));

        //合规用户黑名单
        $mobile = $info['user']['mobile'] ? $info['user']['mobile'] : 0;
        $isCompliantUser = intval($this->rpc->local("BwlistService\inList", array('COMPLIANCE_BLACK', $info['user']['id'])) || $this->rpc->local("BwlistService\inList", array('COMPLIANCE_BLACK', $mobile)));

        $this->json_data = array(
            'code' => 0,
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
            'force_new_password' => $force_new_passwd,
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $info['user']['isSeller'],
            'couponUrl' => $info['user']['couponUrl'],
            'isO2oUser' => $info['user']['isO2oUser'],
            'showO2O' => $info['user']['showO2O'],
            // } END

            'bind_bank' => $bind_bank,
            'ppID' => $info['user']['ppID'],
            'wxLifeOpen' => $wxLifeOpen,
            'candyOpen' => $candyOpen,
            'isCompliantUser' => $isCompliantUser,
        );
        return true;
    }

    //IP地址限制
    private function ipIsLimited($checkOnly = false) {
        $ip = get_real_ip();
        if ($ip != 'unknown') {
            if (Block::check('USER_LOGIN_IP_LIMIT', $ip, $checkOnly) === false) {
                $this->setErr('ERR_IP_LIMIT');
            }
        }
    }

    //需要验证
    private function needVerify($account, $verifyError = '') {
        $hasVerified = $this->rpc->local('UserVerifyService\hasVerified');
        if (Block::check('LOGIN_USERNAME', $account) === false && !$hasVerified) {
            $verifyMode = $this->rpc->local('UserVerifyService\needVerify', array($this->app_version));
            $this->verifyErrorNo($verifyMode, $verifyError);
        }
    }

    //校验验证码
    private function verifyCode($account, $verify) {
        if (Block::check('LOGIN_USERNAME', $account, true) === false) {
            $verifyMode = $this->rpc->local('UserVerifyService\checkVerify', array($verify, $account, $this->app_version));
            if ($verifyMode > 0) {
                $verifyError = '';
                if ($verifyMode == UserVerifyService::VERIFY_CODE && $verify) {
                    $verifyError = '验证码有误';
                }

                $this->verifyErrorNo($verifyMode, $verifyError);
            }
        }
    }

    // 验证模式
    private function verifyErrorNo($verifyMode, $verifyError = '') {
        switch ($verifyMode) {
        //普通验证码
        case UserVerifyService::VERIFY_CODE:
            $this->setErr('ERR_VERIFY', $verifyError);
            break;
        //人脸验证
        case UserVerifyService::VERIFY_FACE:
            $this->setErr('ERR_FACE_VERIFY', $verifyError);
            break;
        //短信验证
        case UserVerifyService::VERIFY_SMS:
            $this->setErr('ERR_SMS_VERIFY', $verifyError);
            break;
        //投篮验证
        default:
            $this->setErr('ERR_SHOOT_VERIFY', $verifyError);
            break;
        }
    }

}
