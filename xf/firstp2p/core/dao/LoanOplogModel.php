<?php
/**
 * @author <wangjiantong@ucfgroup.com>
 **/

namespace core\dao;

/**
 * LoanOplogModel class
 *
 * @author <wangjiantong@ucfgroup.com>
 **/
class LoanOplogModel extends BaseModel {

    const OP_TYPE_MAKE_LOAN = 0; // 放款操作
    const OP_TYPE_AUTO_MAKE_LOAN = 3; // 自动放款

    public function getDealIdByTime($startTime,$endTime,$pageNum = 1,$pageSize = 30) {
        $condition = "op_time BETWEEN '".$startTime."' AND '".$endTime."'";
        $res = $this->findAllViaSlave($condition, true);
        return $res;
    }
}
