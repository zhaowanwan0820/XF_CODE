<?php
/**
 * StockTransferModel class file.
 *
 * @author luzhengshuai@ucfgroup.com
 */

namespace core\dao;

use libs\utils\Logger;

class StockTransferModel extends BaseModel
{

    /**
     * 插入数据
     */
    public function insertData($data){

        if(empty($data)){
            return false;
        }

        foreach ($data as $field => $value) {
            if ($value !== NULL && $value !== '') {
                $this->$field = $this->escape($value);
            }
        }

        $this->create_time = time();

        if($this->insert()){
            return $this->db->insert_id();
        }else{
            return false;
        }
    }
}
