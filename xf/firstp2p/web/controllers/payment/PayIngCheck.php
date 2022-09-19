<?php
/**
 * 定时检查用户是否有网信未完成的充值订单
 */
namespace web\controllers\payment;

use web\controllers\BaseAction;
use core\service\ChargeService;

class PayIngCheck extends BaseAction {
    public function init() {
    }

    public function invoke() {
        if(!$this->check_login()) {
            return ajax_return(array('code'=>-1, 'msg'=>'您尚未登录，请登录后重试'));
        }

        $userInfo = $GLOBALS['user_info'];
        $orderInfo = ChargeService::getAppToPcChargeOrder($userInfo['id']);
        if (empty($orderInfo)) {
            // 判断用户如果是从大额充值的扫一扫登录的，则跳转到PC充值聚合页面
            $isPcTogetherUrl = ChargeService::getUserPcTogetherChargeUrl($userInfo['id']);
            if (!empty($isPcTogetherUrl)) {
                return ajax_return(array('code'=>0, 'msg'=>'您当前没有未支付订单', 'url'=>$isPcTogetherUrl));
            }
            return ajax_return(array('code'=>-2, 'msg'=>'您暂无未支付订单'));
        }

        return ajax_return(array('code'=>0, 'msg'=>'您当前有未支付订单', 'url'=>ChargeService::APP_TO_PC_CHARGE_URI));
    }
}