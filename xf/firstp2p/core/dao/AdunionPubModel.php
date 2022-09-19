<?php
/**
 * AdunionPubModel class file.
 *
 * @author daiyuxin@ucfgroup.com
 **/

namespace core\dao;

/**
 * 发布商
 *
 * @author daiyuxin@ucfgroup.com
 **/
class AdunionPubModel extends BaseModel
{

    /**
     * get publisher list
     */
    public function getPubList() {
        $result = $this->findAll(" is_delete = 0" );
        return $result;
    }
    public function getPubByPubId($condition='') {
        $result = $this->findAll($condition );
        return $result;
    }
    public function insertPub($data){

        if(empty($data)){
            return false;
        }

        $this->name = $data['name'];
        $this->admin = $data['admin'];
        $this->phone = $data['phone'];
        $this->email = $data['email'];
        $this->site = $data['site'];
        $this->create_time = $data['create_time'];

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }



    public function updateByPubId($data,$pubId)
    {
        $condition = sprintf("`id` = '%d'",$this->escape($pubId));
        return $this->updateAll($data,$condition);
    }




} // END class BankModel extends BaseModel
