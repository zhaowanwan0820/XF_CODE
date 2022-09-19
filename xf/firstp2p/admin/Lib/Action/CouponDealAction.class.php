<?php
/**
 * CouponDealAction.class.php
 *
 * @date 2015-03-12
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

use core\service\CouponLogService;
use core\service\CouponDealService;
use core\service\CouponJobsService;
use core\dao\CouponDealModel;

class CouponDealAction extends CommonAction {

    /**
     * 根据标ID执行优惠码周期返利结算
     *
     * @return bool
     */
    public function doAutoPay() {
        set_time_limit(300);
        $module = isset($_REQUEST['module']) ? $_REQUEST['module'] : CouponLogService::MODULE_TYPE_P2P;
        $deal_id = intval($_REQUEST['deal_id']);
        if (empty($deal_id)) {
            echo 'error deal id';
            return false;
        }
        $coupon_log_service = new CouponLogService($module);
        $rs = $coupon_log_service->payForDeal($deal_id);
        echo "结算标ID[$deal_id]优惠码返利完毕：" . (empty($rs) ? '失败' : '成功') . "<br/>" . date('H:i:s');
    }

    /**
     * 根据标ID执行优惠码返利天数更新
     *
     * @return bool
     */
    public function doUpdateRebateDays() {
        set_time_limit(300);
        $module = isset($_REQUEST['module']) ? $_REQUEST['module'] : CouponLogService::MODULE_TYPE_P2P;
        $deal_id = intval($_REQUEST['deal_id']);
        if (empty($deal_id)) {
            echo 'error deal id';
            return false;
        }
        $coupon_log_service = new CouponLogService($module);
        $rs = $coupon_log_service->updateRebateDaysForDeal($deal_id);
        echo "更新标ID[$deal_id]的优惠码返利天数完毕：" . (empty($rs) ? '失败' : '成功') . "<br/>" . date('H:i:s');
    }

    /**
     * 更新所有标的结清状态
     */
    public function doUpdatePaidDeal() {
        $deal_id = intval($_REQUEST['deal_id']);
        if (empty($deal_id)) {
            echo 'error deal id';
            return false;
        }
        $coupon_deal_service = new CouponDealService();
        $rs = $coupon_deal_service->updatePaidDeal($deal_id);
        echo "更新标ID[$deal_id]的结清状态完毕：" . (empty($rs) ? '失败' : '成功') . "<br/>" . date('H:i:s');
    }

    /**
     * 补充旧数据的优惠码结算配置信息
     */
    public function initCouponDealData() {
        $coupon_deal_service = new CouponDealService();
        $rs = $coupon_deal_service->initCouponDealData();
        echo "补充旧数据的优惠码结算配置信息完毕:" . (empty($rs) ? '失败' : '成功') . "<br/>" . date('H:i:s');
    }

}