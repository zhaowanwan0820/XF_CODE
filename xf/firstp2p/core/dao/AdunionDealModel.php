<?php
/**
 * UnionModel class file.
 *
 * @author wangshijie@ucfgroup.com
 */

namespace core\dao;

/**
 * 广告联盟
 *
 * @author wangshijie@ucfgroup.com
 */
class AdunionDealModel extends BaseModel
{
    private $fields = array('id', 'cn', 'euid', 'mid', 'order_sn', 'order_time', 'order_channel',
                                'is_new_custom', 'goods_id', 'goods_name', 'goods_ta', 'goods_price', 'total_price',
                                'commission', 'commission_type', 'goods_cate', 'goods_cate_name',
                                'created_at', 'updated_at', 'uid', 'days', 'track_id');

    /**
     +------------------------------------------------------
     * @desc get order info
     +------------------------------------------------------
     * @param int $data
     +------------------------------------------------------
     * @return bool $result
     +------------------------------------------------------
     */
    public function update_order($data, $id = '') {
        if (empty($id)) {
            $result = $this->db->autoExecute(DB_PREFIX.'adunion_deal', $data, "INSERT");
        } else {
            $result = $this->db->autoExecute(DB_PREFIX.'adunion_deal', $data, "UPDATE", "id=$id");
        }
        return $result;
    }

    /**
     +------------------------------------------------------
     * @desc get order info
     +------------------------------------------------------
     * @param string $union_id
     * @param string $euid
     * @param int $id
     +------------------------------------------------------
     * @return bool $result
     +------------------------------------------------------
     */
    public function get_order_by_cn($cn, $stime, $etime, $sn) {
        $result = array();
        $condition = '(cn=":cn" OR goods_cn=":cn")  AND order_sn!=""';
        $param = array(':cn' => $cn);
        if ($stime) {
            $condition .= ' AND order_time>=":stime"';
            $param[':stime'] = $stime;
        }
        if ($etime) {
            $condition .= ' AND order_time<=":etime"';
            $param[':etime'] = $etime;
        }
        if ($sn) {
            $condition .= ' AND order_sn=":order_sn"';
            $param[':order_sn'] = $sn;
        }
        $result = $this->findAllViaSlave($condition, true, $this->get_query_fields(), $param);
        return $result;
    }

    /**
     +------------------------------------------------------
     * @desc 获取查询字段
     +------------------------------------------------------
     * @return 返回查询字段
     +------------------------------------------------------
     */
    private function get_query_fields() {
        $fields = 'cn, euid, order_sn, order_time, order_channel, is_new_custom, goods_id, goods_name, goods_ta, goods_price';
        $fields .= ', total_price, days, goods_cn, goods_type, track_id, uid';
        return $fields;
    }
}
