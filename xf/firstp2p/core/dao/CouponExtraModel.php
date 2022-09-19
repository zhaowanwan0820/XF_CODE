<?php
/**
 * CouponExtraModel.php
 * 附加优惠码信息
 * @date 2014-09-18
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class CouponExtraModel extends BaseModel {

    const TYPE_FIRST = 11;// 首标

    const TYPE_LAST = 12; // 最后一笔

    const TYPE_MAX_AMOUNT = 13; // 最高金额
    
    const TYPE_DEAL_TAG = 9; // 标tag
    
    const TYPE_USER_TAG = 8; // 用户tag
    /**
     * 查询附件优惠码返利信息
     *
     * @param $source_type 投资来源
     * @param $deal_id 标id
     * @return \libs\db\Model
     */
    public function getBySourceType($source_type, $deal_id = 0) {
        $sql = "`deal_id` = '%d' and `source_type` = '%d' and `is_effect` = 1 ";
        $sql = sprintf($sql, intval($this->escape($deal_id)), intval($this->escape($source_type)));
        return $this->findAllViaSlave($sql, true);
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
            $fields = "`source_type`,`tags`,`rebate_amount`,`rebate_ratio`,`referer_rebate_amount`,`referer_rebate_ratio`,`agency_rebate_amount`,`agency_rebate_ratio`,
            `remark`,`is_effect`";
            $sql = "insert " . $this->tableName() . " (`deal_id`,`create_time`, {$fields}) select '%d', '%s' ,{$fields} from " . $this->tableName() . " where deal_id='%d'";
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
