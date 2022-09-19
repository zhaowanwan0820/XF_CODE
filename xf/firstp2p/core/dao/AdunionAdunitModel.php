<?php
/**
 * AdunionAdunitModel class file.
 *
 * @author daiyuxin@ucfgroup.com
 **/

namespace core\dao;

/**
 * 发布商
 *
 * @author daiyuxin@ucfgroup.com
 **/
class AdunionAdunitModel extends BaseModel
{

    /**
     * get publisher list
     */
    public function getAdList() {
        $result = $this->findAll(" is_delete = 0" );
        return $result;
    }



    public function getAdById($condition='') {

        empty($condition)?$condition = "is_delete = 0":$condition.=" AND is_delete = 0";
        $result = $this->findAll($condition );
        return $result;
    }

    public function insertAd($data){

        if(empty($data)){
            return false;
        }

        $this->ad_id = $data['ad_id'];
        $this->pub_id = $data['pub_id'];
        $this->channel_id = $data['channel_id'];

        $this->name = $data['name'];
        $this->size = $data['size'];
        $this->color = $data['color'];
        $this->rows = $data['rows'];
        $this->code = $data['code'];
        $this->create_time = $data['create_time'];

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function updateById($data,$id)
    {
        $condition = sprintf("`id` = '%d'",$this->escape($id));
        return $this->updateAll($data,$condition);
    }



} // END class BankModel extends BaseModel
