<?php
namespace core\dao\duotou;

use core\dao\BaseModel;

class DuotouEntranceModel extends BaseModel {
    public function getLockDayList()
    {
        $sql = 'select DISTINCT lock_day from '.$this->tableName();
        return DuotouEntranceModel::instance()->findAllBySqlViaSlave($sql, true);
    }
}