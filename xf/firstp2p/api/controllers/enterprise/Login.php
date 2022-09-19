<?php

/**
 * 登录接口
 * @author 王一鸣<wangyiming@ucfgroup.com>
 */

namespace api\controllers\enterprise;

use libs\web\Form;
use api\controllers\AppBaseAction;
use libs\utils\Block;
use core\service\LogRegLoginService;
use core\service\UserTokenService;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
use libs\utils\Monitor;
use core\service\BwlistService;
use core\dao\UserModel;


class Login extends AppBaseAction {

    const SITE_TYPE_NORMAL = 0; // 企业站
    const SITE_TYPE_MANAGER = 1; // 企业管家站点登陆

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "account" => array("filter" => "required", "message" => 'ERR_USERNAME_ILLEGAL'),
            "password" => array("filter" => "reg", "message" => 'ERR_PASSWORD_ILLEGAL', "option" => array("regexp" => "/^.{5,25}$/")),
            "verify" => array("filter" => "reg", "message" => 'ERR_VERIFY_ILLEGAL', "option" => array("regexp" => "/^[0-9a-zA-Z]{0,4}$/", 'optional' => true)),
            "siteType" => array("filter" => "int", "option" => array('optional' => true)),
        );

        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_VERIFY_FAIL', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;

        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : '';
        RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API, Risk::getDevice($os))->check($data, Risk::SYNC);

        $loginFrom = UserTokenService::LOGIN_FROM_QIYE_APP;
        $logRegLoginService = new LogRegLoginService();
        $logRegLoginService->insert($data['account'], '', 0, 3, $loginFrom);

        // 校验验证码
        if (!empty($data['verify'])) {
            $redis = \SiteApp::init()->cache;
            $verify = $redis->get("verify_" . md5($data['account']));
            $redis->delete("verify_" . md5($data['account']));

            $data['verify'] = strtolower($data['verify']);
            if ($verify != md5($data['verify'])) {
                Monitor::add('LOGIN_FAIL');
                $logRegLoginService->insert($data['account'], '', 0, 2, $loginFrom);

                $this->setErr('ERR_VERIFY_ILLEGAL');
            }
        }
        // 企业管家站点登陆之前验证用户是否在黑白名单里
        if ($data['siteType'] == self::SITE_TYPE_MANAGER) {
            // 获取当前用户名对应的用户id
            $userInfo = UserModel::instance()->getInfoByName($data['account'], 'id,payment_user_id', true);
            if (empty($userInfo['id'])) {
                $this->setErr('ERR_LOGIN_FAIL', '用户名或者密码错误');
            }
            // 判断企业管家站点要登录的用户是否在白名单里
            if (!BwlistService::inList('ENTERPRISE_TRANSFER_LIST', $userInfo['id'])) {
                $this->setErr('ERR_LOGIN_FAIL', '用户名或者密码错误');
            }

            // 必须是开通先锋支付账户的用户才能登陆
            if ($userInfo['payment_user_id'] != $userInfo['id']) {
                $this->setErr('ERR_LOGIN_FAIL', '用户名或者密码错误');
            }
        }

        // 调用oauth接口进行登录验证
        $result = $this->rpc->local("UserService\apiNewLogin", array(
            $data['account'],
            $data['password'],
            false,
            $loginFrom
        ));

        if (!isset($result['success']) || $result['success'] !== true) {
            $logRegLoginService->insert($data['account'], '', 0, 2, $loginFrom, $result['reason']);
            // 登录失败则向频次险种中插入记录
            if (!empty($result['code']) && $result['code'] == '20007') {
                $this->setErr('ERR_ENTERPRISE_ABANDON');
            }

            if (!empty($result['code']) && $result['code'] == '20006') {
                $this->setErr('ERR_LOGIN_FAILED', $result['reason']);
            }

            $times = Block::check('ENTERPRISE_LOGIN_USERNAME', $data['account'], false, true);
            $totalTimes = 5;
            if ($times >= $totalTimes) {
                // 冻结用户帐号
                $this->rpc->local('UserService\freezeUserAccount', array($data['account']));
                $this->setErr('ERR_VERIFY', "账号已被冻结，24小时内无法登陆");
            } else {
                // 未超过限制泽提示登录失败
                $this->setErr('ERR_VERIFY', '密码不符合规则，您还有'.($totalTimes - $times).'次尝试机会');
            }
        }

        $logRegLoginService->insert($data['account'], $result['user_id'], 0, 1, $loginFrom);
        RiskServiceFactory::instance(Risk::BC_LOGIN, Risk::PF_API)->notify(array('userId'=>$result['user_id']));

        $token = $result['code'];
        // 调用oauth接口获取用户信息
        $info = $this->rpc->local("UserService\getUserByCode", array($token));
        if ($info['code']) {
            // 获取oauth用户信息失败
            $this->setErr('ERR_GET_USER_FAIL');
        }

        if ($info['status'] == 0) {
            // 获取本地用户数据失败
            $this->setErr('ERR_LOGIN_FAIL');
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
        $this->json_data = array(
            "token" => $token,
            "uid" => $info['user']['id'],
            "username" => $info['user']['user_name'],
            "name" => $info['user']['real_name'] ? $info['user']['real_name'] : "无",
            "money" => number_format($info['user']['money'], 2),
            "mobile" => !empty($info['user']['mobile']) ? moblieFormat($info['user']['mobile']) : '无',
            "email" => !empty($info['user']['email']) ? mailFormat($info['user']['email']) : '无',
            "bank_no" => $bank_no,
            "bank" => $bank_name,
            "bank_icon" => $bank_icon,
            'bonus' => format_price($bonus['money'], false),
            'force_new_password' => $info['user']['force_new_passwd'],
            // BEGIN { 增加用户是否商家参数
            'isSeller' => $info['user']['isSeller'],
            'couponUrl' => $info['user']['couponUrl'],
            'isO2oUser' => $info['user']['isO2oUser'],
            'showO2O' => $info['user']['showO2O'],
            // } END

            'bind_bank' => $bind_bank,
        );
        return true;
    }

}
