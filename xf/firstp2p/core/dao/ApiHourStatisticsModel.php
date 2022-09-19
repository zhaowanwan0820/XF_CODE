<?php
/**
 * 统计每小时的接口请求记录
 * @date 2018-12-5
 * @author zhanyao <zhangyao1@ucfgroup.com>
 */

namespace core\dao;

class ApiHourStatisticsModel extends BaseModel
{
    public function __construct()
    {
        $this->db = \libs\db\Db::getInstance('itil');
        parent::__construct();
    }

    public function tableName()
    {
        return 'api_hour_statistics';
    }

    public function getLastTime($data)
    {
        if(empty($data)){
            return false;
        }

        $condition = "api_id={$data['api_id']} AND type = {$data['type']}";
        if(isset($data['value'])){
            $condition .= " AND value={$data['value']}";
        }
        $condition .= " ORDER BY count_time DESC LIMIT 1";

        $res = $this->findBy($condition, 'count_time,id');
        if($res){
            return $res;
        }
        return false;
    }

    public function updateLogById($params)
    {
        if(empty($params)){
            return false;
        }

        $sql = sprintf("UPDATE api_hour_statistics SET request_num = request_num + 1, update_time = %d WHERE id = %d", time(), $params['id']);
        $res = $this->db->query($sql);
        if(!$res || !$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    public function insertLog($data)
    {
        if(empty($data)){
            return false;
        }

        $data['create_time'] = time();
        $data['request_num'] = 1;
        $this->setRow($data);

        if ($this->insert()) {
            return $this->db->insert_id();
        }

        return false;
    }

}
