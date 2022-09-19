<?php
/**
 * CouponDealModel.php.
 *
 * @date 2018-06-22
 *
 * @author wangzhen <wangzhen3@ucfgroup.com>
 */

namespace core\dao;

use libs\utils\Logger;

class CouponDealNcfphModel extends CouponDealModel
{
    /**
        获取普惠标未结算列表
     */
    public function getAutoPayList()
    {
        $size = app_conf('COUPON_JOBS_SIZE_GETAUTOPAYLIST');
        $size = empty($size) ? 10000 : $size;
        $sql = 'select deal_id from '.$this->tableName()." where   pay_auto='1' and is_paid='0'  
        and ((pay_type='0' and deal_status in ('4','5')) or (pay_type='1' and deal_status='5')) and start_pay_time<='".get_gmtime()."' limit {$size}";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }
    
}
