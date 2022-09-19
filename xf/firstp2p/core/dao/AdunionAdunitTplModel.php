<?php
/**
 * AdunionAdunitTplModel class file.
 *
 * @author daiyuxin@ucfgroup.com
 **/

namespace core\dao;

/**
 * 发布商
 *
 * @author daiyuxin@ucfgroup.com
 **/
class AdunionAdunitTplModel extends BaseModel
{

    /**
     * get publisher list
     */
    public function getTplList() {
        $result = $this->findAll(" is_delete = 0" );
        return $result;
    }

    public function getAdTypes(){
        $sql = "SELECT id, name  FROM " . DB_PREFIX . "adunion_adunit_tpl WHERE is_delete = 0";
        return $this->db->getAll($sql);
    }

    public function getAdSizeColorByTplId($tplId){
        $sql = sprintf("SELECT size, color, rows FROM " . DB_PREFIX . "adunion_adunit_tpl WHERE id = '%d'  ", $tplId) ;
        return $this->db->getAll($sql);
    }

    public function getTplById($condition='') {
        $result = $this->findAll($condition );
        return $result;
    }

    public function getAdContentByTplId($tplId){
        $sql = sprintf("SELECT content FROM " . DB_PREFIX . "adunion_adunit_tpl WHERE id = '%d'  ", $tplId) ;
        return $this->db->getAll($sql);
    }

    public function insertTpl($data){

        if(empty($data)){
            return false;
        }

        $this->name = $data['name'];
        $this->size = $data['size'];
        $this->color = $data['color'];
        $this->rows = $data['rows'];
        $this->content = $data['content'];
        $this->create_time = $data['create_time'];

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }

    public function updateByAdId($data,$adId)
    {
        $condition = sprintf("`id` = '%d'",$this->escape($adId));
        return $this->updateAll($data,$condition);
    }



} // END class BankModel extends BaseModel
