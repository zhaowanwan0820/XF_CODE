<?php
/**
 * WorldCupModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 **/

namespace core\dao;

/**
 * 世界杯活动数据操作类
 *
 * @author luzhengshuai@ucfgroup.com
 **/
class WorldCupModel extends BaseModel {

    /**
     * 插入一条数据
     * @param $data array 数据数组
     * @return float
     */
    public function insertData($data){

        if(empty($data)){
            return false;
        }

        $this->name = $data['name'];
        $this->mobile = $data['mobile'];
        $this->team = $data['team'];
        $this->player = $data['player'];
        $this->create_time = get_gmtime();

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

} // END class WorldCupModel extends BaseModel
