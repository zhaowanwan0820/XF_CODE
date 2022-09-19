<?php
namespace core\dao\report;

use core\dao\BaseModel;

class ReportBaseModel extends BaseModel{
    public function saveData($data){
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }

        $this->update_time = time();

        return $this->insert() ? $this->db->insert_id() : false;
    }
    public function saveOrUpdate($data){

        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
        $this->update_time = time();

        return $this->save() ;
    }
}