<?php
/**
 * CouponSpecialModel.php
 *
 * 特殊优惠码信息
 *
 * @date 2014-05-31
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class CouponSpecialModel extends BaseModel {

    /**
     * 查询特殊优惠码信息
     *
     * @param $short_alias
     * @param $deal_id 标id
     * @return \libs\db\Model
     */
    public function getByShortAlias($short_alias, $deal_id = 0) {
        $sql = "`short_alias` = '%s' and `deal_id` = '%d' and `is_effect` = 1 ";
        $sql = sprintf($sql, $this->escape($short_alias), intval($this->escape($deal_id)));
        $result = $this->findByViaSlave($sql);
        if (empty($result)) {
            return false;
        } else {
            return $result->getRow();
        }
    }

    /**
     * 获取特殊绑定优惠码列表
     *
     * @deprecate 效率低，没有跟标走
     */
    public function getCouponsFixed() {
        $now = get_gmtime();
        $sql = sprintf("`deal_id`=0 and `fixed_days`>0 and `is_effect` = 1 and valid_begin<{$now} and valid_end>{$now}");
        $list = $this->findAll($sql, true);
        $result = array();
        foreach ($list as $item) {
            $item['short_alias'] = strtoupper($item['short_alias']);
            $result[$item['short_alias']] = $item;
        }
        return $result;
    }

    /**
     * 复制一套返利规则
     *
     * @param int $desc_deal_id 新增规则的标id
     * @param int $src_deal_id 源规则的标id
     */
    public function copyRebate($desc_deal_id, $src_deal_id = 0) {
        if (empty($desc_deal_id)) {
            return false;
        }
        $desc_deal_id = intval($this->escape($desc_deal_id));
        $src_deal_id = intval($this->escape($src_deal_id));
        $GLOBALS['db']->startTrans();
        try {
            $sql = "delete from " . $this->tableName() . " where deal_id='%d'";
            $sql = sprintf($sql, $desc_deal_id);
            $result = $this->execute($sql);
            if (!$result) {
                throw new \Exception("删除原规则失败");
            }
            $fields = "`short_alias`,`rebate_amount`,`rebate_ratio`,`referer_rebate_amount`,`referer_rebate_ratio`,`agency_rebate_amount`,`agency_rebate_ratio`,
            `remark`,`valid_begin`,`valid_end`,`refer_user_id`,`agency_user_id`,`fixed_days`,`is_effect`";
            $sql = "insert " . $this->tableName() . " (`deal_id`,`create_time`,{$fields}) select '%d','%s',{$fields} from " . $this->tableName() . " where deal_id='%d'";
            $sql = sprintf($sql, $desc_deal_id,get_gmtime(), $src_deal_id);
            $result = $this->execute($sql);
            if (!$result) {
                throw new \Exception("复制规则失败");
            }
            $result = $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            return false;
        }
        return $result;
    }

    /**
     * 是否存在标所属的返利规则
     *
     * @param $deal_id
     * @return int
     */
    public function existsDealCoupon($deal_id) {
        $condition = "deal_id = :deal_id";
        $result = $this->count($condition, array(':deal_id' => $deal_id));
        return $result;
    }

}