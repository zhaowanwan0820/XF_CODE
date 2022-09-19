<?php
class UserP2pAction extends CommonAction
{
    public function run(){
        $coupon_stat_service = new \core\service\CouponStatService();
        $result = $coupon_stat_service->makeCouponLoadData();
        echo implode("\r\n", $result['msg']);
    }
}
?>