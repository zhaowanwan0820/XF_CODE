<?php
/**
 * 企业管家站点 转账服务
 *
 * @author wangqunqiang<wangqunqiang@ucfgroup.com>
 */

namespace api\controllers\account;

use libs\web\Form;
use api\controllers\AppBaseAction;
use api\conf\Error;
use core\service\UserLogService;
use core\service\UserService;
use core\service\BwlistService;
use core\service\TransferService;
use core\service\EnterpriseService;
use core\service\MobileCodeService;
use core\dao\UserModel;
use core\dao\FinanceAuditModel;
use libs\utils\PaymentApi;

/**
 * 转账服务
 *
 */
class Transfer extends AppBaseAction {
    private $_message = "转账信息填写错误";

    public function init() {
        parent::init();
        $this->form = new Form("post");
        $this->form->rules = array(
            "token" => array("filter" => "required", "message" => "token is required"),
            "money" => array("filter" => "required", "message" => $this->_message),
            "receiverAccountName" => array("filter" => "required" , "message" => $this->_message),
            "receiverUserId" => array("filter" => "required" , "message" => $this->_message),
            "receiverUserName" => array("filter" => "required" , "message" => $this->_message),
            "vCode" => array("filter" => "required" , "message" => $this->_message),
        );

        if (!$this->form->validate()) {
            PaymentApi::log(__FILE__. ' Params error:'.$this->form->getErrorMsg());
            $this->setErr( "ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        try {
            $user = $this->getUserByToken();
            if (empty($user)) {
                PaymentApi::log(__FILE__. ' 用户不存在');
                throw new \Exception($this->_message);
            }

            // 检查转让和受让方是否在黑白名单
            if ( !BwlistService::inList('ENTERPRISE_TRANSFER_LIST', $user['id']) || !BwlistService::inList('ENTERPRISE_TRANSFER_LIST', $data['receiverUserId'])) {
                PaymentApi::log(__FILE__. ' 付款方'.$user['id'].'或者收款方'.$data['receiverUserId'].'不在白名单');
                throw new \Exception($this->_message);
            }

            // 自己不能给自己转账
            if ($user['id'] == $data['receiverUserId']) {
                PaymentApi::log(__FILE__. ' 不能给自己转账,用户id:'.$user['id']);
                throw new \Exception($this->_message);
            }

            // 检查转让方是否为企业用户二期名单
            $enterpriseSrv = new EnterpriseService();
            $payerInfo = $enterpriseSrv->getInfo($user['id']);

            // 检查受让方信息是否完全匹配
            $checkTargetInfoList = [
                'userName' => '',
                'accountName' => '',
            ];
            $userSrv = new UserService();
            $receiverUserInfo = $userSrv->getUser($data['receiverUserId']);
            if (empty($receiverUserInfo)) {
                PaymentApi::log(__FILE__. ' 收款方'.$data['receiverUserId'].'不存在');
                throw new \Exception($this->_message);
            }
            // 检查收款方状态是否有效
            if ($receiverUserInfo['is_effect'] == 0 || $receiverUserInfo['is_delete'] == 1) {
                PaymentApi::log(__FILE__. ' 收款方'.$data['receiverUserId'].'用户状态无效或者被逻辑删除');
                throw new \Exception($this->_message);
            }
            // 设置待检查的用户名称
            $checkTargetInfoList['userName'] = $receiverUserInfo['user_name'];
            // 设置待检查的用户账户名称 , 如果用户为企业用户
            $checkTargetInfoList['accountName'] = $receiverUserInfo['real_name'];
            // 受让方为企业用户
            if ($receiverUserInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                $receiverInfo = $enterpriseSrv->getInfo($data['receiverUserId']);
                if (empty($receiverInfo['base'])) {
                    PaymentApi::log(__FILE__. ' 收款方'.$data['receiverUserId'].'为企业用户,但是基本信息不存在');
                    throw new \Exception($this->_message);
                }
                $checkTargetInfoList['accountName'] = $receiverInfo['base']['company_name'];
            }
            if ($checkTargetInfoList['userName'] != trim($data['receiverUserName']) || $checkTargetInfoList['accountName'] != trim($data['receiverAccountName'])) {
                PaymentApi::log(__FILE__. ' 收款方'.$data['receiverUserId'].'信息一致性校验失败,上传信息:'.json_encode($data, JSON_UNESCAPED_UNICODE).' 数据库里的信息:'.json_encode($checkTargetInfoList, JSON_UNESCAPED_UNICODE));
                throw new \Exception($this->_message);
            }

            // 检查用户余额是否足够发起转账, 转账金额为负数也提示错误消息
            if (bccomp($data['money'], '0.00', 2) <= 0 || bccomp($data['money'], $user['money'], 2) > 0) {
                PaymentApi::log(__FILE__. ' 付款方'.$user['id'].'转账金额为负数或者付款方金额不足');
                throw new \Exception($this->_message);
            }

            // 检查是否需要过人工审批
            $data['needAudit'] = false;
            if(bccomp(bcdiv($data['money'], 10000, 6), app_conf('ENTERPRISE_TRANSFER_AUDIT_AMOUNT'), 6) >= 0) {
                PaymentApi::log(__FILE__. ' 付款方'.$user['id'].'需要人工审批');
                $data['needAudit'] = true;
            }
            // 检查短信验证码
            $verifyService = new MobileCodeService();
            $payerMsgMobile = $enterpriseSrv->getFirstReceiveMsgPhone($user['id']);
            if (strpos($payerMsgMobile, '-') !== false) {
                $mobileInfo = explode('-', $payerMsgMobile);
                $payerMsgMobile = $mobileInfo[1];
            }
            $vcode = $verifyService->getMobilePhoneTimeVcode($payerMsgMobile, null, 0);
            if($vcode != $data['vCode'])
            {
                PaymentApi::log(__FILE__. ' 付款方'.$user['id'].' 需要人工审批');
                $this->setErr("ERR_SIGNUP_CODE");
                return false;
            }
            // 执行转账数据录入
            $transferSrv = new TransferService();
            $data['payerUserId'] = $user['id'];
            $data['payerUserName'] = $user['user_name'];
            $transferSrv->executeEnterpriseTransfer($data);
            $this->json_data = [
                'result' => 1,
            ];
        } catch (\Exception $e) {
            $this->setErr('ERR_MANMAL_REASON', $e->getMessage());
            return false;
        }
    }

}
