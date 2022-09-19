<?php
namespace core\dao\third;

class ThirdDealLoadModel extends ThirdBaseModel
{
    const MAX_ID_OFFSET = 5000000;

    public function getDealLoadById($id)
    {
        $sql = ' select id,deal_id,money,user_id,client_id,update_time from ' .$this->tableName(). ' where id= '.$id;
        return $this->db->getRow($sql);
    }

    public function getDealLoadIds($startId, $size)
    {
        $dealLoadIds = array();
        $sql = 'select id from ' .$this->tableName(). ' where id >= '.$startId.' limit '.$size;
        return $this->db->getAll($sql,true);
    }

    public function getStartId($startTime)
    {
        $sql = 'select id from ' .$this->tableName(). ' where update_time <= '.$startTime.'  order by id desc limit 1';
        return $this->db->getOne($sql);
    }

    public function getEndId($endTime)
    {
        $sql = 'select id from ' .$this->tableName(). ' where update_time <= ' . $endTime . '  order by id desc limit 1';
        return $this->db->getOne($sql);
    }

}
