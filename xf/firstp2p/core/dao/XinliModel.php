<?php
namespace core\dao;

use libs\db\Db;
class XinliModel extends BaseModel {

    public function __construct(){
        $this->db = \libs\db\Db::getInstance('vip');
    }

    /**
     * 获取某天所有活跃度总数
     */
    public function getActivityTotalByDate($log_date)
    {
        $sql_id = "select (max(id)-2000000) max_id from firstp2p_vip_point_log";
        $id_max = Db::getInstance('vip', 'slave')->getOne($sql_id);
        $sql = "select sum((case source_type when '1' then floor(source_amount*600/10000) when '2' then floor(source_amount*400/10000) when '9' then floor(source_amount*500/10000) when '5' then 10 when '4' then 1000 else 0 end)) sum_point ";
        $sql .= " from firstp2p_vip_point_log where id>{$id_max} and source_type in (1,2,4,5,9) and status=1 and from_unixtime(create_time, '%Y-%m-%d')='{$log_date}' ";
        $result = Db::getInstance('vip', 'slave')->getOne($sql);
        return $result;
    }

}
