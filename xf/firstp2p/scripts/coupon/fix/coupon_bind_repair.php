<?php

require_once dirname(__FILE__).'/../../../app/init.php';
use libs\utils\Logger;
use core\service\CouponService;
use core\dao\CouponBindModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class CouponBindRepair
{
    public function run()
    {
        $sql = "select user_id,refer_user_id from firstp2p_coupon_bind where refer_user_id != 0 and short_alias ='' ";
        $result = $GLOBALS['db']->get_slave()->getAll($sql);
        if (!empty($result)) {
            foreach ($result as $value) {
                $refer_user_id = $value['refer_user_id'];
                $user_id = $value['user_id'];
                $couponService = new CouponService();
                $coupon = $couponService->getOneUserCoupon($refer_user_id, false); //不取缓存
                if (empty($coupon)) {
                    Logger::error(__CLASS__.' | '.__FUNCTION__.' | refer_user_id:'.$refer_user_id.' | 理财师码不可用');
                    continue;
                }
                $couponBindModel = new CouponBindModel();
                $data = array('short_alias' => $coupon['short_alias']);
                $res = $couponBindModel->updateBy($data, ' user_id='.$user_id);
                if (!$res) {
                    Logger::error(__CLASS__.' | '.__FUNCTION__.' | user_id:'.$user_id.' | 更新邀请码失败');
                }
            }
        }
    }
}
$CouponBindRepair = new CouponBindRepair();
$CouponBindRepair->run();
exit;
