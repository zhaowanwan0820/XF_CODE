<?php

/**
 * 易宝-充值结果轮询
 *
 * @author 王群强<wangqunqiang@ucfgroup.com>
 */

namespace api\controllers\payment;

use libs\web\Form;
use api\controllers\YeepayBaseAction;
use core\dao\PaymentNoticeModel;
use core\service\YeepayPaymentService;

/**
 * 易宝-充值结果轮询
 *
 */
class YeepayResultQuery extends YeepayBaseAction {

    public function init() {

        parent::init();
        $this->form = new Form();
        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate())
        {
            $this->setErr('ERR_PARAMS_ERROR', $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $userInfo = $this->getUserBaseInfo();
        if (empty($userInfo))
        {
            // 登录token失效，跳到App登录页
            header('Location:' . $this->getAppScheme('native', array('name'=>'login')));
            return false;
        }
        // 检查用户是否已在先锋支付开户
        if ($userInfo['payment_user_id'] <= 0)
        {
            $this->setErr('ERR_MANUAL_REASON', '您尚未开户无法进行充值，请稍后再试');
            return false;
        }

        $data = $this->form->data;

        // 绑卡成功后，获取redis中的充值订单号、充值金额等
        $userOrderInfo = $this->getUserRedisOrderInfo();
        if (empty($userOrderInfo) || !isset($userOrderInfo['orderId']) || empty($userOrderInfo['orderId']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }

        // 用户身份标识
        $userClientKey = $data['userClientKey'];
        // 用户ID
        $userId = $userInfo['id'];
        // 订单ID
        $orderId = $userOrderInfo['orderId'];
        // 充值完成后，要跳转的页面
        $returnSuccessUrl = !empty($userOrderInfo['returnSuccessUrl']) ? $userOrderInfo['returnSuccessUrl'] : $this->getAppScheme('closeall');
        // 获取订单数据
        $paymentNotice = PaymentNoticeModel::instance()->getInfoByUserIdNoticeSn($userId, $orderId);
        if (empty($paymentNotice) || empty($paymentNotice['notice_sn']))
        {
            $this->setErr('ERR_MANUAL_REASON', '充值订单不存在，请重新发起充值');
            return false;
        }
        // 查询易宝订单充值结果
        $yeepayService = new YeepayPaymentService();
        $result = $yeepayService->queryOrder(YeepayPaymentService::SEARCH_TYPE_BINDPAY, $orderId);
        $orderStatus = 2;
        $errmsg = '';
        // 如果错误消息不为空,则返回错误消息
        if (!empty($result['respMsg']))
        {
            $errmsg = $result['respMsg'];
        }
        // 判断订单状态,并修改orderStatus
        if (isset($result['data']['status']))
        {
            switch($result['data']['status'])
            {
                case YeepayPaymentService::YBPAY_STATUS_SUCCESS:
                    $orderStatus = 1;
                    break;
                case YeepayPaymentService::YBPAY_STATUS_ING:
                case YeepayPaymentService::YBPAY_STATUS_ACCECPT:
                    $orderStatus = 0;
                    break;
                default:
                    $orderStatus = 2;
            }
        }
        // 载入支付完成后的页面
        $this->json_data = ['status' => $orderStatus, 'msg' => $errmsg];
    }
}
