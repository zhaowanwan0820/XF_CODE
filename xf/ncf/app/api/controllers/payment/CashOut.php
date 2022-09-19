<?php

namespace api\controllers\payment;

use libs\web\Form;
use api\conf\ConstDefine;
use api\controllers\AppBaseAction;
use libs\utils\Logger;
use libs\utils\PaymentApi;
use core\dao\PaymentNoticeModel;
use core\service\risk\RiskServiceFactory;
use libs\utils\Risk;
/**
 * CashOut
 * 提现
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author Tobias Schlitt <toby@php.net>
 * @license PHP Version 4 & 5 {@link http://www.php.net/license/3_01.txt}
 * 增加用户锁
 * @userLock
 */
class CashOut extends AppBaseAction {

    public function init() {
        parent::init();
        if (app_conf('API_CASHOUT_OPEN') === '0') {
            $this->setErr(ERR_SYSTEM, '提现接口关闭，暂时不可使用，请与系统管理员联系！');
        }

        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'money' => array('filter' => 'int'),
            'os' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'string', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->user;

        RiskServiceFactory::instance(
            Risk::BC_WITHDRAW_CASH,
            Risk::PF_API,
            Risk::getDevice($_SERVER['HTTP_OS']
        ))->check($loginUser, Risk::ASYNC, $data);

        $money = $data['money'];
        $money = number_format($money / 100, 2, '.', ''); // 转换成元
        if (($money - $loginUser['money']) > 0) {
            $this->setErr('ERR_MANUAL_REASON', '您的账户余额不足');
        }
        //最低提现金额更改为0.01元
        if ($money < 0.01) {
            $this->setErr('ERR_MANUAL_REASON', '最少提取0.01元');
        }
        $canWithdraw = $this->rpc->local('UserCarryService\canWithdrawAmount', array($loginUser['id'], $money));
        if (!$canWithdraw) {
            $this->setErr('ERR_MANUAL_REASON', $GLOBALS['lang']['CARRY_LIMIT_ERR']);
        }
        $canWithdraw = $this->rpc->local('UserCarryService\canWithdraw', array($loginUser['id'], $money));
        if (!$canWithdraw['result']) {
            $this->setErr('ERR_MANUAL_REASON', $canWithdraw['reason']);
        }

        $os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
        if ($os == 'Android') {
            $osId = PaymentNoticeModel::PLATFORM_ANDROID;
        } else {
            $osId = PaymentNoticeModel::PLATFORM_IOS;
        }
        $userCarryId = $this->rpc->local('PaymentService\cashOut', array($loginUser['id'], $money, $osId));
        if ($userCarryId !== false) {
            // 记录申请提现金额记录
            //$os = isset($_SERVER['HTTP_OS']) ? $_SERVER['HTTP_OS'] : "iOS";
            $channel = isset($_SERVER['HTTP_CHANNEL']) ? $_SERVER['HTTP_CHANNEL'] : 'NO_CHANNEL';
            $apiLog = array(
                'time' => date('Y-m-d H:i:s'),
                'userId' => $loginUser['id'],
                'ip' => get_real_ip(),
                'userCarryId' => $userCarryId,
                'money' => $money,
                'os' => $os,
                'channel' => $channel,
            );
            logger::wLog("API_CASHOUT_APPLY:" . json_encode($apiLog));
            PaymentApi::log("API_CASHOUT_APPLY:" . json_encode($apiLog), Logger::INFO);

            $ret = array("success" => ConstDefine::RESULT_SUCCESS, "msg" => "申请提现成功，预计1个工作日内到账");
            RiskServiceFactory::instance(Risk::BC_WITHDRAW_CASH,Risk::PF_API)->notify();
        } else {
            $ret = array("success" => ConstDefine::RESULT_FAILURE, "msg" => "申请提现失败，请重试");
        }
        $this->json_data = $ret;
        return true;
    }

}
