<?php

namespace openapi\controllers\user;

use libs\web\Form;
use libs\utils\Logger;
use openapi\controllers\BaseAction;

use core\service\AccountService;
use core\service\PaymentService;
use core\service\BonusService;
use core\service\CouponService;
use core\service\MsgBoxService;
use core\service\BankService;
use core\service\DigService;
use core\service\UserService;

use core\event\BonusEvent;
use NCFGroup\Task\Services\TaskService;

class DoModifyBank extends BaseAction {

    public function init() {
        parent::init();

        $this->form = new Form();
        $this->form->rules = array(
            'asgn'    => array("filter" => "required", "message" => "asgn is required"),
            "bank_id" => array("filter" => "reg", "option" => array("regexp" => '/^\d+$/'),    "message" => "选择的银行不存在"),
            "bank_no" => array("filter" => "reg", "option" => array("regexp" => '/^\d[\d\s]{4,}$/'), "message" => "银行卡号格式错误"),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        $asgn = \es_session::get('openapi_cr_asgn');
        if ($asgn != $this->form->data['asgn']) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }

        $token = \es_session::get('openapi_cr_token');
        if (empty($token)){
            $this->setErr("ERR_PARAMS_ERROR");
            return false;
        } else {
            $this->form->data['oauth_token'] = $token;
        }
    }

    public function invoke() {

        //待完善存管换卡功能,暂时封换卡
        $this->setErr('ERR_MANUAL_REASON', '抱歉，该功能暂时关闭，请前往PC端完成换卡');
        return false;

        //检查是否登录
        $userInfo = $this->getUserByAccessToken();
        if (!$userInfo) {
            $this->setErr('ERR_TOKEN_ERROR');
            return false;
        }

        // 支付降级
        if (\libs\utils\PaymentApi::isServiceDown())
        {
            $this->setErr('ERR_MANUAL_REASON', \libs\utils\PaymentApi::maintainMessage());
            return false;
        }

        //银行卡号长度验证
        $data = $this->form->data;
        $data['bank_no'] = preg_replace("/\s/", "", $data['bank_no']);
        if (!in_array(strlen($data['bank_no']), array(12, 15, 16, 17, 18, 19))) {
            $this->setErr('ERR_MANUAL_REASON', "请输入正确的银行卡号");
            return false;
        }

        //检查资产
        $chkRes = $this->checkUserAsset($userInfo);
        if (0 !== $chkRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $chkRes['msg']);
            return false;
        }

        //上次申请
        $chkRepeatRes = $this->checkRepeatApply($userInfo);
        if (0 !== $chkRepeatRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $chkRepeatRes['msg']);
            return false;
        }

        //银行卡是否可以被当前用户绑定
        $chkRes = $this->checkCardCanBind(array('bank_no' => $data['bank_no'], 'user_id' => $userInfo->getUserId()));
        if (0 !== $chkRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $chkRes['msg']);
            return false;
        }

        $GLOBALS['db']->startTrans();

        //通知支付
        $changeRes = $this->paymentChangeCard($userInfo, $data);
        if (0 !== $changeRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $changeRes['msg']);
            $GLOBALS['db']->rollback();
            return false;
        }

        //本地绑卡
        $changeRes = $this->changeBankCard($userInfo, $data, $chkRepeatRes['data']['card_id']);
        if (0 !== $changeRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $changeRes['msg']);
            $GLOBALS['db']->rollback();
            return false;
        }

        //事件处理
        $handleRes = $this->handleBindCardEvent($userInfo);
        if (0 !== $handleRes['code']) {
            $this->setErr('ERR_MANUAL_REASON', $handleRes['msg']);
            $GLOBALS['db']->rollback();
            return false;
        }

        $GLOBALS['db']->commit();

        $userService = new UserService();
        $user = $userService->getUser($userInfo->getUserId());

        $risk_data['user_id']     = $user['id'];
        $risk_data['mobile']      = $user['mobile'];
        $risk_data['user_name']   = $user['real_name'];
        $risk_data['id_no']       = $user['idno'];
        $risk_data['card_no']     = $data['bank_no'];
        $risk_data['from_source'] = 1;
        
        if('1' == app_conf('RISK_SWITCHS')){
            $this->risk_warning('PAY.SIGNED', $risk_data);
            unset($risk_data, $userService, $user);
        }

        \es_session::clear();
        $this->json_data = array();
        return true;
    }

    //事件的处理
    public function handleBindCardEvent($userInfo) {
        $userId = $userInfo->getUserId();
        $inviteCode = $userInfo->getInviteCode();

        try {
            $event = new BonusEvent('bindCard', $userId, $inviteCode);
            $taskObj = new TaskService();
            $taskId = $taskObj->doBackground($event, 20);
            if (!$taskId) {
                Logger::wLog('绑卡添加返利失败|' . $userId . PHP_EOL, Logger::INFO, Logger::FILE, LOG_PATH . "send_bonus_event" . date('Ymd') . '.log');
            }

            new DigService('bindBankCard', array(
                'bankCardNo' => $bankcardInfo['cardNo'],
                'bankcardSn' => $bankcardInfo['bankCode'],
                'id'         => $userId,
                'mobile'     => $userInfo->getMobile(),
                'cardName'   => $userInfo->getRealName(),
                'cn'         => $inviteCode,
            ));

            $bonusService = new BonusService();
            $bonusService->transCashBonus($userId);
        } catch (\Exception $e) {
           $errmsg = $e->getMessage();
           setLog(array('errmsg' => "处理换卡事件发生错误, 错误: $errmsg  错误码: " . $e->getCode()));
           empty($errmsg) && $errmsg = "系统错误, 更换银行卡失败";
           return $this->getRetPack(7, $errmsg);
        }

        return $this->getRetPack(0);
    }

    //本地变更银行卡
    public function changeBankCard($userInfo, $data, $bankcardId) {
        $bankInfo['bank_id']  = $data['bank_id'];
        $bankInfo['bankcard'] = $data['bank_no'];
        $bankInfo['card_name'] = $userInfo->getRealName();
        $bankInfo['user_id']   = $userInfo->getUserId();
        $bankInfo['create_time'] = get_gmtime();
        $bankInfo['status'] = 1;

        $bankService = new BankService();
        $saveResult = $bankService->modifyBankcard($bankInfo, $bankcardId);
        if (!$saveResult) {
           setLog(array('errmsg' => "更新本地银行卡失败"));
           return $this->getRetPack(6, "系统错误，更换银行卡失败");
        }

        return $this->getRetPack(0);
    }

    //和支付进行交互
    public function paymentChangeCard($userInfo, $data) {
        if (app_conf('PAYMENT_ENABLE') && app_conf('PAYMENT_BIND_ENABLE')) {
            $paymentService = new PaymentService();
            $changeCard = array(
                'userId'     => $userInfo->getUserId(),
                'merchantId' => $GLOBALS['sys_config']['XFZF_PAY_MERCHANT'],
                'bankCardNo' => $data['bank_no'],
                'bankCode'   => $this->getBankCode($data['bank_id']),
            );

            try {
                $paymentService->changeCard($changeCard);
            } catch(\Exception $e) {
                $errmsg = $e->getMessage();
                setLog(array('errmsg' => "支付服务更换银行卡失败,  错误: $errmsg  错误号: " . $e->getCode()));
                empty($errmsg) && $errmsg = "系统错误, 更换银行卡失败";
                return $this->getRetPack(5, $errmsg);
            }
        }

        return $this->getRetPack(0);
    }

    //获得银行短码
    public function getBankCode($bankId) {
        $bankService = new BankService();
        $bankInfo = $bankService->getBank($bankId);
        return !empty($bankInfo) ? $bankInfo->short_name : '';
    }

    //能否绑定
    public function checkCardCanBind($param) {
        $bankService = new BankService();
        $canBind = $bankService->checkBankCardCanBind($param['bank_no'], $param['user_id']);
        if (!$canBind) {
            return $this->getRetPack(4, '更换银行卡失败, 卡号已经被绑定');
        }

        return $this->getRetPack(0);
    }

    //申请检查
    public function checkRepeatApply($userInfo) {
        $accountService = new AccountService();
        $userBankcard = $accountService->getUserBankInfo($userInfo->getUserId());
        if ($userBankcard['is_audit'] == 1) {
            return $this->getRetPack(2, '请耐心等待审核结果, 不要重复提交修改申请');
        }

        $bankcardId = $userBankcard ? $userBankcard->id : 0;
        if (empty($bankcardId)) {
            return $this->getRetPack(3, '您尚未绑定银行卡，请先绑定银行卡');
        }

        return $this->getRetPack(0, '', array('card_id' => $bankcardId));
    }

    //资产检查
    public function checkUserAsset($userInfo) {
        $account = user_statics($userInfo->getUserId());
        if (intval($account['load_count']) > 0) {
            return $this->getRetPack(1, '暂时无法换卡，请前往网页端更换');
        }
        $frozen  = $userInfo->getFrozen();
        $remain  = $userInfo->getRemain();

        $total = bcadd(bcadd($remain, $frozen, 2), $account['principal'], 2);
        if (bccomp($total, '0.00', 2) > 0) {
            return $this->getRetPack(1, '用户总资产不为零, 无法更换银行卡');
        }

        return $this->getRetPack(0);
    }

    //数据打包
    public function getRetPack($code, $msg = '', $data = '') {
        return array('code' => $code, 'msg' => $msg, 'data' => $data);
    }

    //验签名
    public function authCheck() {
        return true;
    }

}

