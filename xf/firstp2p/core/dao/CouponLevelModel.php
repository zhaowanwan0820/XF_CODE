<?php
/**
 * CouponLevelModel.php
 *
 * 会员等级信息
 *
 * @date 2014-05-27
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;


class CouponLevelModel extends BaseModel {

    /**
     * 获取所有会员等级及相关信息
     *
     * 数组的key为会员等级ID
     *
     * @param bool $get_rebate 为true时会获取会员等级下所有优惠码规则，并赋予'rebate'键
     * @param bool $group_id 如果有值，则只获取该会员组下的会员等级
     * @return array
     */
    public function getLevels($get_rebate = false, $group_id = false) {
        $sql = "select g.name as group_name, g.agency_user_id as agency_user_id, l.*
         from " . DB_PREFIX . "coupon_level l, " . DB_PREFIX . "user_group g
         where l.group_id=g.id and l.is_effect=1";
        if ($group_id !== false) {
            $sql .= " and l.group_id='%d'";
            $sql = sprintf($sql, $group_id);
        }
        $sql .= " order by l.group_id, l.level";
        $list = $this->findAllBySqlViaSlave($sql, true);
        $result = array();
        if ($get_rebate) {
            $rebate_model = new CouponLevelRebateModel();
            $rebate_list = $rebate_model->getAll();
        }
        foreach ($list as $item) {
            if ($get_rebate) {
                $rebate = isset($rebate_list[$item['id']]) ? $rebate_list[$item['id']] : array();
                $item['rebate'] = $rebate;
                $item['rebate_prefix'] = array_keys($rebate);
            }
            $result[$item['id']] = $item;
        }
        return $result;
    }

}
