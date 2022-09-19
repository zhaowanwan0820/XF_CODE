<?php
/**
 * 盈嘉项目还款操作记录
 */

namespace core\dao;

class ProjectYjRepayOplogModel extends BaseModel {

    const OPERATION_TYPE_CHARGED        = 1; //1确认充值
    const OPERATION_TYPE_REPAY_CALC     = 2; //2线下当期还款 还款计算完成
    const OPERATION_TYPE_REPAYED        = 3 ;//3确认代发
    const OPERATION_TYPE_CHANGE_STATUS  = 4 ;//4更改还款状态

}
