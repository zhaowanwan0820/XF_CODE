<?php
/**
 *
 *
 * @date 2018-06-28
 * @author <wangzhen3@ucfgroup.com>
 */

require_once(dirname(__FILE__) . '/../../../app/init.php');
use libs\utils\Logger;
use core\dao\UserModel;

set_time_limit(0);
ini_set('memory_limit', '2048M');


class updateInviteUser
{
    public function run($argv)
    {
        $log_info = array(__CLASS__, __FUNCTION__);
        Logger::info(implode(" | ", array_merge($log_info, array('script start'))));

        $sql = "select user_id,refer_user_id,invite_user_id from firstp2p_coupon_bind where invite_user_id != 0 and invite_user_group_id = 0";

        $result = $GLOBALS['db']->get_slave()->getAll($sql);
        if (empty($result)) {
            Logger::info(implode(" | ", array_merge($log_info, array('no data for update'))));
        } else {
            foreach ($result as $value) {
                $userInfo = UserModel::instance()->find($value['invite_user_id'], 'group_id');
                //$sqlUpdate = "update firstp2p_coupon_bind set invite_user_id=refer_user_id,invite_user_name=refer_user_name,invite_code=short_alias,invite_user_group_id=".intval($userInfo['group_id'])." where user_id = ".$value['user_id'];
                $sqlUpdate = "update firstp2p_coupon_bind set invite_user_group_id=".intval($userInfo['group_id'])." where user_id = ".$value['user_id'];
                $res = $GLOBALS['db']->query($sqlUpdate);
                if (empty($res)) {
                    Logger::error(implode(" | ", array_merge($log_info, array($sqlUpdate))));
                }
            }
        }

        Logger::info(implode(" | ", array_merge($log_info, array('script end'))));
    }
}
$initCouponBindObj = new updateInviteUser();
$initCouponBindObj->run($argv);
exit;
