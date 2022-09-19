<?php
/**
 * CouponLevelRebateModel.php
 *
 * 会员等级返利规则信息
 *
 * @date 2014-05-28
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

class CouponLevelRebateModel extends BaseModel {

    /**
     * 查询优惠码信息
     *
     * @param $deal_id 标id
     * @param $level_id 等级id
     * @param $prefix 前缀
     * @return array
     */
    public function queryCoupon($deal_id, $level_id, $prefix) {
        $sql = "select * from " . DB_PREFIX . "coupon_level_rebate where deal_id='%d' and level_id='%d' and prefix='%s'";
        $sql = sprintf($sql, intval($this->escape($deal_id)), intval($this->escape($level_id)), $this->escape($prefix));
        $result = $this->findBySqlViaSlave($sql);
        if (empty($result)) {
            return false;
        } else {
            return $result->getRow();
        }
    }

    /**
     * 获取所有会员等级返利规则
     * 第一级key值为等级id，第二级key值为优惠码前缀
     * 有排序，第一个为后台配置的前缀（CURRENT_COUPON_PREFIX）,其它按字母排序
     *
     * @return array
     */
    public function getAll() {
        $sql = "select * from " . DB_PREFIX . "coupon_level_rebate where deal_id=0 and is_effect=1 order by prefix";
        $list = $this->findAllBySqlViaSlave($sql, true);
        $result = array();
        $default_prefix = app_conf('CURRENT_COUPON_PREFIX');
        foreach ($list as $item) {
            if (!empty($default_prefix) && $default_prefix == $item['prefix'] && !empty($result[$item['level_id']])) {
                $result[$item['level_id']] = array_merge(array($item['prefix'] => $item), $result[$item['level_id']]);
            }
            $result[$item['level_id']][$item['prefix']] = $item;
        }
        return $result;
    }

    /**
     * 获取普通绑定优惠码列表
     *
     * @deprecate 效率低，没有跟标走
     */
    public function getCouponsFixed() {
        $now = get_gmtime();
        $sql = "select u.id, c.prefix, c.fixed_days from " . DB_PREFIX . "user u, " . DB_PREFIX . "coupon_level_rebate c
         where u.coupon_level_id=c.level_id and u.is_delete=0 and c.deal_id=0 and c.fixed_days>0 and c.valid_begin<{$now} and c.valid_end>{$now}";
        $list = $this->findAllBySql($sql, true);
        $result = array();
        foreach ($list as $item) {
            $short_alias = $item['prefix'] . \core\service\CouponService::userIdToHex($item['id']);
            $short_alias = strtoupper($short_alias);
            $item['short_alias'] = $short_alias;
            $result[$short_alias] = $item;
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
            $fields = "`level_id`,`prefix`,`fixed_days`,`rebate_amount`,`rebate_ratio`,`referer_rebate_amount`,`referer_rebate_ratio`,`agency_rebate_amount`,`agency_rebate_ratio`,`remark`,`valid_begin`,`valid_end`,`is_effect`";
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
