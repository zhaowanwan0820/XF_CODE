<?php
/**
 * 多投宝优惠码检查页面
 * Couponcheck.php
 * @author wangchuanlu@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use libs\utils\Logger;
use web\controllers\BaseAction;
use core\service\coupon\couponService;

/**
 * 校验优惠券
 */
class Couponcheck extends BaseAction {

    const CODE_TYPE_COUPON = 1;
    const CODE_TYPE_MOBILE = 2;

    public function init() {
        if(!$this->check_login()) return false;
        $this->form = new Form("post");
        $this->form->rules = array(
            'coupon_id' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    /**
     * status: 2:优惠码为空；1:正常返回
     */
    public function invoke() {
        $log_info = array(__CLASS__, __FUNCTION__, APP, __LINE__);
        $params = $this->form->data;
        \FP::import("libs.utils.logger");
        Logger::info(implode(" | ", array_merge($log_info, array(json_encode($params)))));
        $shortAlias = trim($params['coupon_id']);

        $result = couponService::queryCoupon($shortAlias);

        $couponInfo = $result['data'];
        if (($result['resCode'] == 0) && !empty($couponInfo)) {
            if (!$couponInfo['is_effect']) {
                $error_msg = "您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢。";
                $data = array('errno' => 3, 'error' => $error_msg, 'data' => $couponInfo);
            } else if ($couponInfo['coupon_disable']) {
                $error_msg = "您使用的".$GLOBALS['lang']['COUPON_DISABLE']."，请输入有效的优惠码，谢谢。";
                $data = array('errno' => 4, 'error' => $error_msg, 'data' => $couponInfo);
            } else {
                $data = array('errno' => 0, 'error' => '', 'data' => $couponInfo);
            }
        } else {
            $couponInfo = array('short_alias'=>$shortAlias);
            $error_msg = "优惠码有误，请重新输入。";
            $data = array('errno' => 1, 'error' => $error_msg, 'data' => $couponInfo);
        }

        Logger::info(implode(" | ", array_merge($log_info, array('result', json_encode($params), json_encode($data)))));
        return ajax_return($data);
    }
}
