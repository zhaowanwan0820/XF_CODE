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
class AdunionChannelModel extends BaseModel
{

    /**
     * get publisher list
     */
    public function getChannels($condition='') {
        $result = $this->findAll($condition." AND is_delete = 0" );
        return $result;
    }

    public function insertChannel($data){

        if(empty($data)){
            return false;
        }

        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->pub_id = $data['pub_id'];
        $this->link_coupon = $data['link_coupon'];

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function updateByChannelId($data,$channelId)
    {
        $condition = sprintf("`id` = '%d'",$this->escape($channelId));
        return $this->updateAll($data,$condition);
    }

} // END class BankModel extends BaseModel
