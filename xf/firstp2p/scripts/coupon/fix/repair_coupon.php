<?php

ini_set('memory_limit', '2048M');
require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';
//error_reporting(E_ALL);ini_set('display_errors', 1);


/**
 * 优惠码注册用户数据修复
 * changlu
 * Class RepairCoupon
 */
class RepairCoupon
{
    /**
     * 修复优惠码老数据
     */
    function repair()
    {
        echo "\n\n" . 'start at :' . date('Y-m-d H:i:s') . "\n\n";
        //注册用户
        $sql_user = "SELECT id,`invite_code`,`refer_user_id`,`is_rebate`,`create_time` FROM `firstp2p_user`  WHERE  `idcardpassed_time` = '' AND `is_rebate` = 0 AND `invite_code` !='' ";
        $user_list = $GLOBALS['db']->getAll($sql_user);
        echo "注册未认证用户" . count($user_list) . "人\n";
        $this->_insert_coupon($user_list, -2);
        unset($user_list);

        //实名认证用户 没有绑卡
        $sql_user_real = "SELECT u.id AS id ,u.`invite_code` AS invite_code,u.`refer_user_id` AS refer_user_id,u.`is_rebate` AS is_rebate,u.`create_time` AS create_time FROM `firstp2p_user` AS u
         LEFT JOIN `firstp2p_user_bankcard` AS b  ON u.id = b.user_id WHERE (b.bankcard IS NULL OR b.bankcard = '' ) AND  u.`idcardpassed_time` != '' AND u.`is_rebate` = 0 AND u.`invite_code` !='' ";
        $user_real_list = $GLOBALS['db']->getAll($sql_user_real);
        echo "注册已认证用户" . count($user_real_list) . "人\n";
        $this->_insert_coupon($user_real_list, -1);
        unset($user_real_list);
        echo 'end at :' . date('Y-m-d H:i:s') . "\n\n";
    }

    /**
     * 插入数据到数据库
     * @param $user_list
     */
    private function _insert_coupon($user_list, $status)
    {
        $sql_conpon_insert_f = "insert into `firstp2p_coupon_log`
        (`type`, `consume_user_id`, `refer_user_id`, `short_alias`, `remark`, `pay_status`, `create_time`)
          values('1','%s','%s','%s','注册返利','%s','%s');";
        if ($user_list) {
            $count = 0;
            foreach ($user_list as $k => $v) {
                if ($this->_check_coupon($v)) {
                    $count++;
                    $sql_conpon_insert = sprintf($sql_conpon_insert_f, $v['id'], $v['refer_user_id'], $v['invite_code'], $status, $v['create_time']);
                    $GLOBALS['db']->query($sql_conpon_insert);
                }
            }
            echo '成功插入' . $count . "人\n", '失败插入' . (count($user_list) - $count) . "人,已经有这条记录\n\n";
        }
    }

    /**
     * 检查是否可以插入
     * @param $v
     * @return bool
     */
    private function _check_coupon($v)
    {
        $sql = "SELECT id FROM `firstp2p_coupon_log` WHERE consume_user_id =%s AND deal_load_id = 0 AND is_delete = 0 ";
        $sql = sprintf($sql, $v['id']);
        $rs = $GLOBALS['db']->getrow($sql);
        if ($rs) {
            return false;
        }
        return true;
    }
}

$ew = new RepairCoupon();
$ew->repair();
