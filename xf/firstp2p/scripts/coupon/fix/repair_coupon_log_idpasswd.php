<?php

ini_set('memory_limit', '2048M');
require_once(dirname(__FILE__) . '/../app/init.php');
set_time_limit(0);
require_once(dirname(__FILE__) . '/../system/utils/logger.php');
require_once dirname(__FILE__) . '/../libs/common/functions.php';
//error_reporting(E_ALL);ini_set('display_errors', 1);


/**
 * 优惠码已绑身份认证，但状态未更新
 *
 * Class RepairCoupon
 */
class RepairCoupon
{
    /**
     * 修复优惠码老数据
     */
    function repair()
    {
        $log_info = array(__CLASS__,__FUNCTION__);
        $log_msg = implode(' | ',array_merge($log_info,array('start')));
        logger::write($log_msg);
        //已经身份认证，但是优惠码状态未改
        $sql_user = "SELECT u.id AS u_id,cl.id as cl_id
FROM firstp2p_user u
LEFT JOIN firstp2p_coupon_log cl ON u.id = cl.consume_user_id
WHERE u.idcardpassed =1
AND cl.type =1
AND cl.pay_status = -2 ";
        $user_list = $GLOBALS['db']->get_slave()->getAll($sql_user);
        $this->_update_coupon($user_list);
        unset($user_list);

       /* //实名认证用户 没有绑卡
        $sql_user_real = "SELECT u.id AS id ,u.`invite_code` AS invite_code,u.`refer_user_id` AS refer_user_id,u.`is_rebate` AS is_rebate,u.`create_time` AS create_time FROM `firstp2p_user` AS u
         LEFT JOIN `firstp2p_user_bankcard` AS b  ON u.id = b.user_id WHERE (b.bankcard IS NULL OR b.bankcard = '' ) AND  u.`idcardpassed_time` != '' AND u.`is_rebate` = 0 AND u.`invite_code` !='' ";
        $user_real_list = $GLOBALS['db']->getAll($sql_user_real);
        echo "注册已认证用户" . count($user_real_list) . "人\n";
        $this->_update_coupon($user_real_list, -1);
        unset($user_real_list);
        echo 'end at :' . date('Y-m-d H:i:s') . "\n\n";*/
    }

    /**
     * 更新数据状态
     * @param $user_list
     */
    private function _update_coupon($user_list)
    {
        $log_info = array(__CLASS__,__FUNCTION__);
        if ($user_list) {
            foreach ($user_list as $k => $v) {
                if (empty($v['cl_id'])){
                    $log = implode(" | ", array_merge($log_info, array('coupon_special', json_encode($v['cl_id']),$v['cl_id'].'is empty')));
                    logger::write($log,logger::INFO);
                }
                // 查询是否绑定了银行卡

                $pay_status = -1;
                if ($this->_check_bankcard($v['u_id'])){
                    $pay_status = 1;
                }
                $pay_time = get_gmtime();
                $sql_conpon_udpate = "update firstp2p_coupon_log set pay_status=$pay_status,pay_time=$pay_time,update_time=$pay_time  where id='{$v['cl_id']}' and pay_status in (-2,-1) and type=1";
                $ret = $GLOBALS['db']->query($sql_conpon_udpate);
                $msg =  '已处理 用户id为'.$v['u_id'].' coupon_log id 为 '.$v['cl_id'].' result '.$ret;
                $log = implode(" | ", array_merge($log_info, array($msg)));
                logger::write($log);
            }
            $msg =  '共处理 '.count($user_list);
            $log = implode(" | ", array_merge($log_info, array($msg,'end')));
            logger::write($log);
            echo '共处理'.count($user_list)."\n\n";
        }
    }

    /**
     * 是否绑卡
     * @param $v
     * @return bool
     */
    private function _check_bankcard($userid)
    {
        $sql = "SELECT id FROM `firstp2p_user_bankcard` WHERE  user_id='$userid' AND status =1";

        $rs = $GLOBALS['db']->get_slave()->getrow($sql);
        if ($rs) {
            return true;
        }
        return false;
    }
}

$ew = new RepairCoupon();
$ew->repair();
