<?php
/**
 * 用于修复  标的放款操作记录表 中的错误数据
 **/

require_once dirname(__FILE__).'/../app/init.php';
\FP::import("libs.utils.logger");

set_time_limit(0);

use core\dao\LoanOplogModel;
use core\dao\DealProjectModel;
use core\dao\DealModel;

use libs\utils\Logger;

$fix_project_id_arr = explode(',', $argv[1]);

// 记录 sql-log 最终不提交事务
$sql_log_arr = array();
foreach ($fix_project_id_arr as $project_id) {
    try {
        $GLOBALS['db']->startTrans();
        $project = DealProjectModel::instance()->find($project_id);
        $pro_oplog_1 = LoanOplogModel::instance()->findBy(sprintf('`deal_id` = %d AND `op_type` = 1 ', $project_id)); // 取出 提交 的放款操作记录
        if (empty($pro_oplog_1)) {
            throw new \Exception("没有此项目的放款提交操作记录！");
        }

        $deal_arr = DealModel::instance()->getDealByProId($project_id);
        foreach ($deal_arr as $deal) {
            $loan_oplog_model = new LoanOplogModel();
            $loan_oplog_model->op_type         = 1;
            $loan_oplog_model->loan_batch_no   = '';
            $loan_oplog_model->deal_id         = $deal['id'];
            $loan_oplog_model->deal_name       = $deal['name'];
            $loan_oplog_model->borrow_amount   = $deal['borrow_amount'];
            $loan_oplog_model->repay_time      = $deal['repay_time'];
            $loan_oplog_model->loan_type       = $deal['loantype'];
            $loan_oplog_model->borrow_user_id  = $deal['user_id'];
            $loan_oplog_model->op_user_id      = $pro_oplog_1['op_user_id'];
            $loan_oplog_model->loan_money_type = $project['loan_money_type'];
            $loan_oplog_model->op_time         = $pro_oplog_1['op_time'];
            $loan_oplog_model->loan_money      = $deal['borrow_amount'];
            $loan_oplog_model->return_type     = 0;
            $loan_oplog_model->return_reason   = '';
            $loan_oplog_model->submit_uid      = $pro_oplog_1['submit_uid'];
            if(!$loan_oplog_model->save()){
                throw new \Exception("保存放款操作记录失败");
            } else {
                Logger::info(sprintf('保存放款操作记录成功！标的id：%d，file:%s,line:%s', $deal['id'], __FILE__, __LINE__));
            }
        }


        // 删除项目的记录
        if ($pro_oplog_1->remove()) {
            Logger::info(sprintf('提交操作记录删除成功！项目id：%d，file:%s,line:%s', $project_id, __FILE__, __LINE__));
        } else {
            Logger::info(sprintf('提交操作记录删除失败！项目id：%d，file:%s,line:%s', $project_id, __FILE__, __LINE__));
        }
        $pro_oplog_2 = LoanOplogModel::instance()->findBy(sprintf('`deal_id` = %d AND `op_type` = 2 ', $project_id)); // 取出 退回 的放款操作记录
        if (empty($pro_oplog_2)) {
            Logger::info(sprintf('没有此项目的退回操作记录！项目id：%d，file:%s,line:%s', $project_id, __FILE__, __LINE__));
        } else {
            if ($pro_oplog_2->remove()) {
                Logger::info(sprintf('退回操作记录删除成功！项目id：%d，file:%s,line:%s', $project_id, __FILE__, __LINE__));
            } else {
                Logger::info(sprintf('退回操作记录删除失败！项目id：%d，file:%s,line:%s', $project_id, __FILE__, __LINE__));
            }
        }
        $GLOBALS['db']->rollback();
        $sql_log_arr[$project_id] = implode(';', LoanOplogModel::instance()->db->queryLog);
        Logger::info(sprintf('sql记录成功，事务回滚！sql-log:%s; file:%s', implode('; ', LoanOplogModel::instance()->db->queryLog), __FILE__));
    } catch (\Exception $e) {
        $GLOBALS['db']->rollback();
        Logger::info(sprintf('操作失败，事务回滚！项目id：%d，错误信息：%s，file:%s,line:%s', $project_id, $e->getMessage(), __FILE__, __LINE__));
    }
}

// 最后输出汇总的 sql-log (以项目 id 为组)
foreach ($sql_log_arr as $key => $sql_log) {
    Logger::info(sprintf('********************  project_id:%d, file:%s ********************', $key, __FILE__));
    $sql_string = implode(';', $sql_log_arr);
    Logger::info(sprintf('sql-log:%s; file:%s', $sql_string, __FILE__));

    echo $sql_string . ";\n";
}
Logger::info(sprintf('===============  over; file:%s ===============', __FILE__));
