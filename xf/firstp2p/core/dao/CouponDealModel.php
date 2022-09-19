<?php
/**
 * CouponDealModel.php.
 *
 * @date 2015-03-12
 *
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

use core\service\CouponLogService;
use libs\utils\Logger;

class CouponDealModel extends CouponBaseModel
{
    const PAY_TYPE_FANGKUAN = '0'; // 优惠码结算时间选项 放款时结算
    const PAY_TYPE_HUANQING = '1'; // 优惠码结算时间选项 还清时结算
    const PAY_AUTO_YES = '1'; // 优惠码结算方式 自动结算
    const PAY_AUTO_NO = '2'; // 优惠码结算方式 手工结算
    const IS_PAID_NO = '0'; // 优惠码结算状态 未结算
    const IS_PAID_YES = '1'; // 优惠码结算状态 已结算
    const IS_REBATE_YES = '1';//是否有返利 有返利
    const IS_REBATE_NO = '2';//是否有返利 无返利

    /**
     * 更新所有标的结清状态
     */
    public function updatePaidDeals()
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $sql = 'update '.DB_PREFIX."coupon_deal c set c.is_paid='1' where c.is_paid='0'
                and exists (select 1 from firstp2p_coupon_log a where c.deal_id=a.deal_id and a.pay_status in ('1','2'))
                and not exists (select 1 from firstp2p_coupon_log b where c.deal_id=b.deal_id and b.pay_status in ('0','3','4','5'))";
        $rs = $this->execute($sql);
        Logger::info(implode(' | ', array_merge($log_info, array('done', $rs))));

        return $rs;
    }

    /**
     * 更新标的结清状态为已结算.
     */
    public function updatePaidDeal($deal_id)
    {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $deal_id);
        Logger::info(implode(' | ', array_merge($log_info, array('start'))));
        $sql = "update ".$this->tableName()." set is_paid= '".CouponDealModel::IS_PAID_YES."' where deal_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_id));
        $rs = $this->execute($sql);
        Logger::info(implode(' | ', array_merge($log_info, array('done', $rs))));

        return $rs;
    }

    /**
     * 获取未结清的标.
     *
     * @return array
     */
    public function getUnPaidList()
    {
        $size = app_conf('COUPON_JOBS_SIZE_GETUNPAIDLIST');
        $size = empty($size) ? 10000 : $size;
        $sql = 'select c.deal_id from  '.DB_PREFIX.'coupon_deal c, '.DB_PREFIX."deal d
                where c.deal_id=d.id and c.is_paid='0' and d.is_delete='0' and d.deal_status in ('4','5') limit {$size}";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取需要处理的自动结算的标.
     *
     * @return array
     */
    public function getAutoPayList()
    {
        $size = app_conf('COUPON_JOBS_SIZE_GETAUTOPAYLIST');
        $size = empty($size) ? 10000 : $size;
        $sql = 'select c.deal_id from  '.DB_PREFIX.'coupon_deal c,  '.DB_PREFIX."deal d
                where c.deal_id=d.id and c.pay_auto='1' and c.is_paid='0' and d.deal_type!='".CouponLogService::DEAL_TYPE_COMPOUND."' and d.is_delete='0'  and c.start_pay_time<='".get_gmtime()."'
                and ((c.pay_type='0' and d.deal_status in ('4','5')) or (c.pay_type='1' and d.deal_status='5')) limit {$size}";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取需要处理的通知贷的标.
     *
     * @return array
     */
    public function getCompoundPayList()
    {
        $sql = 'select c.deal_id from  '.DB_PREFIX.'coupon_deal c,  '.DB_PREFIX."deal d
                where c.deal_id=d.id and c.is_paid='0' and d.deal_type='1' and d.is_delete='0'
                and c.pay_type='0' and d.deal_status in ('4','5')";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取需要处理的多投宝的标.
     *
     * @return array
     */
    public function getAutoPayDuotouList()
    {
        $sql = 'select distinct l.deal_id from '.DB_PREFIX."coupon_log_duotou l
                where l.type='2' and l.deal_type='1' and l.pay_status in ('0','5') ";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取需要处理的黄金定期的标.
     *
     * @return array
     */
    public function getAutoPayGoldList()
    {
        $sql = 'select distinct l.deal_id from '.DB_PREFIX."coupon_log_gold l
                where l.deal_type='0' and l.pay_status in ('0','5') ";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取需要处理的黄金活期的标.
     *
     * @return array
     */
    public function getAutoPayGoldcList()
    {
        $sql = 'select distinct l.deal_id from '.DB_PREFIX."coupon_log_goldc l
                where l.deal_type='0' and l.pay_status in ('0','5') ";
        $list = $this->findAllBySql($sql, true, array(), true);

        return $list;
    }

    /**
     * 获取没有coupon_deal信息的标的列表.
     */
    public function getDealListNotExists()
    {
        $sql = 'select d.id, d.repay_time, d.loantype, d.deal_type, e.coupon_pay_type
                from '.DB_PREFIX.'deal d left join '.DB_PREFIX."deal_ext e on d.id=e.deal_id
                where d.is_delete='0' and not exists (select 1 from ".DB_PREFIX.'coupon_deal c where c.deal_id=d.id)';
        $list = $this->findAllBySql($sql, true, array());

        return $list;
    }
}
