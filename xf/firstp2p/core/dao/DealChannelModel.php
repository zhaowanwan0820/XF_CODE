<?php
/**
 * DealChannelModel.php
 * 
 * @date 2014-04-22
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\dao;

use core\dao\UserModel;

class DealChannelModel extends BaseModel {

    /**
     * 获取更新渠道信息
     * 根据渠道号获取渠道信息，当渠道号为有效user_id时，若渠道记录不存在，则自动添加
     *
     * @param $channel_value 渠道号
     * @return 处理结果
     */
    public function add_deal_channel($channel_value) {
        //判断channel类型，channel_value为字符串，当网站处理；channel_value为int且为user_id有效值，否则无效
        $channel_type = 1;
        if (is_numeric($channel_value)) { // 会员类型
            $channel_type = 0;
            $channel_value = intval($channel_value);
            $advisor_info = UserModel::instance()->find($channel_value);
            if (empty($advisor_info) || $advisor_info['is_effect'] == 0 || $advisor_info['is_delete'] == 1) {
                return false;
            }
        }

        //获取渠道ID，如果是新渠道，则新增
        $sql_channel_exist = "SELECT id FROM " . DB_PREFIX . "deal_channel WHERE channel_type='%d' AND channel_value='%s'";
        $sql_channel_exist = sprintf($sql_channel_exist, $this->escape($channel_type), $this->escape($channel_value));
        $channel_exist = $this->findBySql($sql_channel_exist);
        if (empty($channel_exist)) {
            if ($channel_type == 0) {
                $this->channel_value = $advisor_info['id'];
                $this->channel_type = $channel_type;
                $this->name = $advisor_info['user_name'];
                $this->create_time = get_gmtime();
                $this->update_time = get_gmtime();
                if ($this->insert() !== false) {
                    $channel_id = $this->db->insert_id();
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $channel_id = $channel_exist->id;
        }

        return $channel_id;
    }

    public function getIdByTypeAndValue($channel_type, $channel_value) {
        //获取渠道ID，如果是新渠道，则新增
        $condition = "channel_type=:channel_type AND channel_value=:channel_value";
        return $this->count($condition, array(
                                ':channel_type' => $channel_type,
                                ':channel_value' => $channel_value,
                            )
                        );
    }

}
