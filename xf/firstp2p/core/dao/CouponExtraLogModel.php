<?php
/**
 * CouponExtraLogModel.php
 *
 * @date 2014-10-13
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

class CouponExtraLogModel extends BaseModel {


    /**
     * 根据类型读取列表
     * @param int $type
     */
    function getListByType($type, $offset, $page_size){
        if (!in_array($type,array(CouponExtraModel::TYPE_FIRST,CouponExtraModel::TYPE_LAST,CouponExtraModel::TYPE_MAX_AMOUNT))){
            return false;
        }
        $sql = 'SELECT cel.deal_load_money,cel.type,cel.consume_user_name,deal.name AS deal_name,cel.rebate_ratio,cel.rebate_amount FROM '.self::$prefix.'deal deal, '.self::$prefix.'coupon_extra_log cel WHERE cel.type=%d AND deal.id=cel.deal_id '." ORDER BY cel.id DESC LIMIT $offset,$page_size";
        $sql = sprintf($sql, $this->escape($type));

        return $this->findAllBySql($sql);
    }
}
