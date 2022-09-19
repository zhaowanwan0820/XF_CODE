<?php
/**
 * CouponPayLogModel.php
 *
 * @date 2015-02-03
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class CouponPayLogModel extends CouponBaseModel {

    /**
     * 统计通知贷邀请投资
     *
     * @param $deal_load_id
     * @param $refer_user_id
     * @return array
     */
    public function statByDealLoadId($deal_load_id, $refer_user_id) {
        $sql = "SELECT count(id) count_pay, sum(referer_rebate_amount+referer_rebate_ratio_amount) sum_pay_refer_amount ";
        $sql .= "FROM ".$this->tableName()." WHERE deal_load_id='%d' and refer_user_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_load_id), $this->escape($refer_user_id));
        $result = $this->findBySql($sql, array(), true);
        return $result->getRow();
    }


    /**
     * 统计通知贷已返
     * @param int $refer_user_id
     * @param array $consume_user_ids
     */
    public function refererRebateAmountCompound($refer_user_id,$consume_user_ids,$siteId = null){
        $sql = "SELECT SUM(referer_rebate_amount+referer_rebate_ratio_amount) AS referer_rebate_amount " .
                "FROM ".$this->tableName()." WHERE refer_user_id='%d' ";
        $sql = sprintf($sql, $this->escape($refer_user_id));
        if (!empty($siteId)){
            $sql .= sprintf(" AND site_id = '%d' ", intval($this->escape($siteId)));
        }
        if(!empty($consume_user_ids)){
            $sql .= sprintf(" AND consume_user_id in (%s) ", $this->escape(implode(',', $consume_user_ids)));
        }
        $sql .= $this->setDealLoadIdCond($this->dataType,self::$module_name);
        $result = $this->countBySql($sql, array(), self::$_is_use_slave);
        return $result;
    }


    /**
     * 通过deal_load_id获取统计信息
     * @param int $deal_load_id
     * @return array
     */
    public function statAllByDealLoadId($deal_load_id){
        $sql = "select sum(rebate_ratio_amount) rebate_ratio_amount,sum(referer_rebate_ratio_amount) referer_rebate_ratio_amount,sum(agency_rebate_ratio_amount) agency_rebate_ratio_amount,sum(rebate_days) rebate_days from ".$this->tableName()." where deal_load_id='%d'";
        $sql = sprintf($sql, $this->escape($deal_load_id));
        $result = $this->findBySqlViaSlave($sql);
        return $result;
    }

}