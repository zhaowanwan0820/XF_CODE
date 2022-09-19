<?php

namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\controllers\BaseAction;
use api\conf\ConstDefine;
use core\service\PaymentService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\service\BonusService;
use core\service\CouponService;
use core\service\MsgBoxService;
use core\service\BankService;

/**
 *
 * 用户注册接口
 *
 * @uses BaseAction
 * @package
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author wangqunqiang@ucfgroup.com
 */
class ModifyBank extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form('post');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'bankcard' => array('filter' => 'required'),
            'bank_id' => array('filter' => 'required'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->errorCode = "-4";
            $this->errorMsg = \libs\utils\PaymentApi::maintainMessage();
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        // 用户ID
        $userId = $loginUser['id'];
        // 用户总资产是否为0
        $accountInfo = user_statics($userId);
        if (intval($accountInfo['load_count']) > 0) {
            $this->errorCode = '-4';
            $this->errorMsg = '暂时无法换卡，请前往网页端更换';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        $userTotal = bcadd($loginUser['money'], $loginUser['lock_money'], 2);
        $userTotal = bcadd($userTotal, $accountInfo['principal'], 2);
        if (bccomp($userTotal, '0.00', 2) > 0) {
            $this->errorCode = '-4';
            $this->errorMsg = '无法修改银行卡，总资产不为零';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        //查询有无修改正在审核中
        $accountService = new \core\service\AccountService();
        $userBankcard = $accountService->getUserBankInfo($userId);
        if ($userBankcard['is_audit'] == 1) {
            $this->errorCode = '-2';
            $this->errorMsg = '您已提交了一次修改申请，不能重复提交，请耐心等待审核结果！';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        // 必要参数校验
        if (empty($data['bank_id'])) {
            $this->errorCode = "-2";
            $this->errorMsg = "更换银行卡失败,请核对信息后重试";
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        if (empty($data['bankcard'])) {
            $this->errorCode = "-4";
            $this->errorMsg = $GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE'];
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        //去除空格
        $data['bankcard'] = str_replace(" ", "", $data['bankcard']);
        if (!in_array(strlen($data['bankcard']), array(12, 15, 16, 17, 18, 19))) {
            $this->errorCode = "-4";
            $this->errorMsg = "更换银行卡失败,请核对信息后重试";
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }
        // 银行卡是否可以被当前用户绑定
        $bankService = new BankService();
        $canBind = $bankService->checkBankCardCanBind($data['bankcard'], $loginUser['id']);
        if (!$canBind) {
            $this->errorCode = '-4';
            $this->errorMsg = '更换银行卡失败,请核对信息后重试';
            $this->json_data = array('success' => '01', 'msg' => $this->errorMsg);
            return false;
        }

        // 收集银行卡信息
        $_bankInfo['bank_id'] = intval($data['bank_id']);
        $_bankInfo['bankcard'] = str_replace(' ', '', $data['bankcard']);
        $_bankInfo['card_name'] = $loginUser['real_name'];
        $_bankInfo['create_time'] = get_gmtime();
        $_bankInfo['status'] = 1;
        $_bankInfo['user_id'] = $userId;

        // 银行短码
        $bankInfo = $bankService->getBank($data['bank_id']);
        $bankCode = '';
        if(!empty($bankInfo)) {
            $bankCode = $bankInfo->short_name;
        }
        $paymentService = new PaymentService();
        try {
            $GLOBALS['db']->startTrans();
            // 支付注册绑卡接口
            $bankcard_id = $userBankcard ? $userBankcard->id : 0;
            if (empty($bankcard_id)) {
                throw new \Exception('您尚未绑定银行卡，请先绑定银行卡。');
            }

            if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                $paymentService = new PaymentService();
                //$bankcardInfo = $paymentService->getBankcardInfo($data, $isNew, 0, $userId);
                // 发送请求
                $changeCard = array('userId' => $userId, 'merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'], 'bankCardNo' => $_bankInfo['bankcard'], 'bankCode' => $bankCode);
                $paymentService->changeCard($changeCard);
                //$paymentService->bankcardSync($userId, $bankcardInfo);
            }

            $saveResult = $bankService->modifyBankcard($_bankInfo, $bankcard_id);
            if (!$saveResult) {
                throw new \Exception ('编辑银行卡失败！');
            }

            $event = new \core\event\BonusEvent('bindCard', $userId, $loginUser['invite_code']);
            $task_obj = new GTaskService();
            $task_id = $task_obj->doBackground($event, 20);
            if (!$task_id) {
                Logger::wLog('绑卡添加返利失败|' .$userId. PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH ."send_bonus_event" . date('Ymd') .'.log');
            }
            new \core\service\DigService('bindBankCard', array(
                'bankCardNo' => $bankcardInfo['cardNo'],
                'bankcardSn' => $bankcardInfo['bankCode'],
                'id' => $userId,
                'mobile' => $loginUser['mobile'],
                'cardName' => $loginUser['real_name'],
                'cn' => $loginUser['invite_code'],
            ));

            $bonusService = new BonusService();
            $bonusService->transCashBonus($userId);
            $GLOBALS['db']->commit();
            $ret = array('success' => '00', 'msg' => '已更换银行卡');
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $ret = array('success' => '01', 'msg' => $e->getMessage());
            $this->errorCode = '-4';
            $this->errorMsg = $e->getMessage();
            $this->json_data = $ret;
            return false;
        }
        $this->json_data = $ret;
        return true;
    }
}

