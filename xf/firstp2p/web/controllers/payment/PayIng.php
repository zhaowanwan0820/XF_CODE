<?php
/**
 * 网信未完成的充值订单
 */
namespace web\controllers\payment;

use libs\web\Url;
use web\controllers\BaseAction;
use core\service\ChargeService;
use core\service\QrCodeService;
use core\dao\PaymentNoticeModel;

class PayIng extends BaseAction {
    public function init() {
    }

    public function invoke() {
        if(!$this->check_login()) {
            return $this->show_error('您尚未登录，请登录后重试', '', 0, 0, '/user/login');
        }

        $userInfo = $GLOBALS['user_info'];
        $orderInfo = ChargeService::getAppToPcChargeOrder($userInfo['id']);
        if (empty($orderInfo)) {
            // 判断用户如果是从大额充值的扫一扫登录的，则跳转到PC充值聚合页面
            $isPcTogetherUrl = ChargeService::getUserPcTogetherChargeUrl($userInfo['id']);
            if (!empty($isPcTogetherUrl)) {
                app_redirect($isPcTogetherUrl);
                exit;
            }
            app_redirect('/');
            exit;
        }
        // 清空APPTOPC的充值信息
        ChargeService::clearAppToPcChargeOrder($userInfo['id']);
        // 销毁扫码来源标识信息
        QrCodeService::clearQrRefInfo($userInfo['id']);

        // 查询该笔充值订单
        $paymentNotice = PaymentNoticeModel::instance()->find($orderInfo['id']);
        if (empty($paymentNotice) || $paymentNotice['user_id'] != $userInfo['id']) {
            ChargeService::clearAppToPcChargeOrder($userInfo['id']);
            return $this->show_error('当前访问发生问题，请稍后再试');
        }
        if (in_array($paymentNotice['is_paid'], [PaymentNoticeModel::IS_PAID_SUCCESS, PaymentNoticeModel::IS_PAID_FAIL])) {
            ChargeService::clearAppToPcChargeOrder($userInfo['id']);
            return $this->show_error('您暂无待支付订单', '', 0, 0, '/');
        }

        $chargeAccountName = '';
        if (empty($orderInfo['channel']) || $orderInfo['channel'] == 'wx') {
            $chargeAccountName = '网信账户';
        }
        // 生成跳转支付的充值地址
        $paymentAction= Url::gene('payment', 'startpay', array('id'=>$paymentNotice['id'], 'site'=>$GLOBALS['sys_config']['APP_SITE']), 1);
        $this->tpl->assign('paymentAction', $paymentAction);
        $actionUrl = Url::gene('payment','payCheck',array('id'=>$paymentNotice['id'], 'check'=>1));
        $this->tpl->assign('actionUrl', $actionUrl);
        $this->tpl->assign("reUrl", Url::gene('account', 'charge'));
        // 充值账户名
        $this->tpl->assign('chargeAccountName', $chargeAccountName);
        $this->tpl->assign('chargeAmount', $orderInfo['amount'] . '元'); // 充值金额，单位元
        $this->tpl->assign('chargeId', $orderInfo['id']); // 充值记录自增ID
        $this->tpl->assign('outOrderId', $orderInfo['outOrderId']);
    }
}