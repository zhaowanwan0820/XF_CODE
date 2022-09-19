<?php
/**
 * @author <wangjiantong@ucfgroup.com>
 **/

namespace core\dao\dealloan;

use core\dao\BaseModel;

/**
 * LoanOplogModel class
 *
 * @author <wangjiantong@ucfgroup.com>
 **/
class LoanOplogModel extends BaseModel {

    public function getDealIdByTime($startTime,$endTime,$pageNum = 1,$pageSize = 30) {
        $condition = "op_time BETWEEN '".$startTime."' AND '".$endTime."'";
        $res = $this->findAllViaSlave($condition, true);
        return $res;
    }
}
