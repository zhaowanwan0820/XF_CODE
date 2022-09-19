<?php
/**
 * coupon_update_user_level.php
 *
 * 更新优惠券等级
 * 20 0 * * * cd /apps/product/nginx/htdocs/firstp2p/scripts/ && /apps/product/php/bin/php coupon_update_user_level.php
 * 
 * @date 2014-06-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../app/init.php');

set_time_limit(0);
$coupon_level_service = new \core\service\CouponLevelService();
$user_model = new \core\dao\UserModel();
$last_user = $user_model->getUserLastId();
$last_user_id = $last_user->id;

for ($user_id = 1; $user_id <= $last_user_id; $user_id++) {
	$coupon_level_service->updateUserLevel($user_id);
}
