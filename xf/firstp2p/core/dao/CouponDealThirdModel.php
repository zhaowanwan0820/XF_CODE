<?php

namespace core\dao;

class CouponDealThirdModel extends CouponDealModel
{
    public function getAutoPayList()
    {
        $size = app_conf('COUPON_JOBS_SIZE_GETAUTOPAYLIST');
        $size = empty($size) ? 10000 : $size;
        $sql = 'select deal_id from '.$this->tableName()." where   pay_auto='1' and is_paid='0'
        and ((pay_type='0' and deal_status in ('4','5')) or (pay_type='1' and deal_status='5')) and start_pay_time<='".get_gmtime()."' limit {$size}";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    //工具系数的标id
    public function getAutoPayThirdpList()
    {
        $sql = 'select distinct l.deal_id from '.DB_PREFIX."coupon_log_third l
                where l.deal_type='2' and l.pay_status in ('0','5') ";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }
}
