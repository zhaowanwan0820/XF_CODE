<?php

namespace core\dao;

class DiscountRateModel extends BaseModel
{
    public function addRecord($data)
    {
        $this->setRow($data);
        $this->_isNew = true;
        $result = $this->save();
        if (!$result) {
            return false;
        }

        return $this->id;
    }

    public function updateRecord($data) {
        if (!isset($data['id']) || empty($data['id'])){
            return false;
        }
        $condition = "id = {$data['id']}";
        unset($data['id']);
        $res = $this->updateAll($data, $condition);
        return $this->db->affected_rows();
    }
}
