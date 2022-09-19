<?php

/**
 * @abstract openapi  绑定银行卡
 *
 */

namespace openapi\controllers\account;

use libs\web\Form;
use openapi\controllers\BaseAction;
use core\service\PaymentService;
use NCFGroup\Task\Services\TaskService AS GTaskService;

/**
 * 绑定银行卡
 *
 * Class FinancialRecord
 * @package openapi\controllers\account
 */
class SaveBank extends BaseAction {

    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'bank_id' => array("filter" => 'int'),
            'card_name' => array("filter" => 'string'),
            'region_lv1' => array("filter" => 'int'),
            'region_lv2' => array("filter" => 'int'),
            'region_lv3' => array("filter" => "int"),
            'bankzone' => array("filter" => 'string'),
            'bankcard' => array("filter" => 'string'),
            "oauth_token" => array("filter" => "required", "message" => "oauth_token is required"),
        );
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        /**
         * 参数验证
         */
        if (empty($data['region_lv3'])) {
            $this->errorCode = "-1";
            $this->errorMsg = "请选择所在地区！";
            return false;
        }

        if (empty($data['bank_id'])) {
            $this->errorCode = "-2";
            $this->errorMsg = "缺少银行信息！";
            return false;
        }
        if (empty($data['bankzone'])) {
            $this->errorCode = "-3";
            $this->errorMsg = "请选择开户行所在地！";
            return false;
        }
        if (empty($data['bankcard'])) {
            $this->errorCode = "-4";
            $this->errorMsg = $GLOBALS['lang']['PLASE_ENTER_CARRY_BANK_CODE'];
            return false;
        }
        //去除空格
        $data['bankcard'] = str_replace(" ", "", $data['bankcard']);
        if (!in_array(strlen($data['bankcard']), array(12, 15, 16, 17, 18, 19))) {
            $this->errorCode = "-4";
            $this->errorMsg = "银行卡号长度不正确";
            return false;
        }

        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }
        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->errorCode = 1;
            $this->errorMsg = \libs\utils\PaymentApi::maintainMessage();
            return false;
        }

        $user_id = $userInfo->userId;
        //查询有无修改正在审核中
        $bankcard = $this->rpc->local('AccountService\getUserBankInfo', array($user_id));
        if ($bankcard['is_audit'] == 1) {
            $this->errorCode = 1;
            $this->errorMsg = "您已提交了一次修改申请，不能重复提交，请耐心等待审核结果!";
            return false;
        }

        //新增或重置
        $is_new = $data['isnew'];

        //查询银行卡已绑定的信息
        $can_bind = $this->rpc->local('BankService\checkBankCardCanBind', array($data['bankcard'], $user_id));

        if (!$can_bind) {
            $this->errorCode = 2;
            $this->errorMsg = "该银行卡已被其他用户绑定，请重新设置提现银行卡。";
            return false;
        }

        $data['card_name'] = htmlspecialchars($data['card_name']);
        $data['bankcard'] = htmlspecialchars(trim($data['bankcard']));
        $data['bankzone'] = htmlspecialchars(trim($data['bankzone']));
        $data['create_time'] = get_gmtime();
        $data['status'] = 1;    //审核中
        $data['user_id'] = $GLOBALS['user_info']['id'];
        unset($data['isnew']);

        if ($bankcard['bankcard'] && $bankcard['status'] == 1) {
            $this->errorCode = 3;
            $this->errorMsg = "您已有绑定银行卡，不能重复添加，若想变更，请去修改！";
            return false;
        } else {
            try {
                if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
                    // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                    $paymentService = new PaymentService();
                    $user['userId'] = $userInfo->userId;
                    $user['groupId'] = $userInfo->groupId;
                    $bankcardInfo = $paymentService->getBankcardInfo($data, true, 0, $user);
                    // 发送请求
                    $paymentService->bankcardSync($user_id, $bankcardInfo);
                }
                $rs = $this->rpc->local('BankService\saveBank', array($data, true));
                new \core\service\DigService('bindBankCard', array(
                    'bankCardNo' => $bankcardInfo['cardNo'],
                    'bankcardSn' => $bankcardInfo['bankCode'],
                    'id' => $userInfo->userId,
                    'mobile' => $userInfo->mobile,
                    'cardName' => $userInfo->realName,
                    'cn' => $userInfo->inviteCode,
                ));
            } catch (\Exception $e) {
                \libs\utils\Logger::debug("openapi saveBank" . "|" . $e->getMessage());
                $this->errorCode = 4;
                $this->errorMsg = "绑定银行卡失败！";
                return false;
            }
        }
        //$this->rpc->local('AdunionDealService\triggerAdRecord', array($user_id, 3)); //广告联盟
        $event = new \core\event\BonusEvent('bindCard', $user_id, $userInfo->inviteCode);
        $task_obj = new GTaskService();
        $task_id = $task_obj->doBackground($event, 20);
        if (!$task_id) {
            Logger::wLog('绑卡添加返利失败|' . $user_id . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH . "send_bonus_event" . date('Ymd') . '.log');
        }
        $ret['success'] = '银行卡信息修改成功';
        $this->json_data = $ret;
    }

}
