<?php
/**
 * 清洗 project_repay_list 数据及其审核信息
 */
require_once dirname(__FILE__).'/../app/init.php';

use core\service\ProjectRepayListService;

use core\dao\DealProjectModel;
use core\dao\DealModel;
use core\dao\ServiceAuditModel;
use core\dao\ProjectRepayListModel;
use core\dao\DealRepayModel;

use libs\utils\Logger;

set_time_limit(0);
ini_set('memory_limit', '2048M');

try {
    $op_func = $argv[1];
    $executor = new FlushExecutor();
    if (call_user_func_array(array($executor, $op_func), array_slice($argv, 1))) {
        Logger::info(sprintf('call success; params:%s, [%s:%s]', json_encode($argv), __FILE__, __LINE__));
    } else {
        throw new \Exception(sprintf('call fail,params:%s', json_encode($argv)));
    }
} catch (\Exception $e) {
    Logger::error(sprintf('fail, err-msg:%s [%s:%s]', $e->getMessage(), __FILE__, __LINE__));
}

/**
 * 具体操作
 */
class FlushExecutor
{
    /**
     * 将 所有1.75项目 标的层还款计划 汇总到项目还款计划列表中 并更新 审核记录
     * @return boolean | throw
     */
    public function flushAll()
    {
        $this->repayAllIntoProjectRepay();
        $this->flushAllAuditData();
    }

    /**
     * 将 所有1.75项目 标的层还款计划 汇总到项目还款计划列表中
     * @return boolean | throw
     */
    public function repayAllIntoProjectRepay()
    {
        $project_list = $this->getEntrustProjectList();
        if (empty($project_list)) {
            throw new \Exception('empty entrust_project_list');
        }
        $success_count = 0;
        foreach ($project_list as $project) {
            try {
                ProjectRepayListService::createProjectRepayList($project['id']);
                Logger::info(sprintf('success: create one project_repay_list,project_id:%d [%s:%s]', $project['id'], __FILE__, __LINE__));
                ++$success_count;
            } catch (\Exception $e) {
                Logger::error(sprintf('fail: create one project_repay_list,project_id:%d [%s:%s]', $project['id'], __FILE__, __LINE__));
            }
        }
        Logger::info(sprintf('done: function:%s, sum_count:%s, success_count:%d [%s:%s]', count($project_list), $success_count, __FUNCTION__, __FILE__, __LINE__));
        return true;
    }

    /**
     * 获取 1.75 项目列表
     * @return array $entrust_project_list
     */
    private function getEntrustProjectList()
    {
        $sql_get_entrust_pro = sprintf(' SELECT `id` FROM %s WHERE `fixed_value_date` > 0 AND `deal_type` = %d ', DealProjectModel::instance()->tableName(), DealModel::DEAL_TYPE_EXCLUSIVE);
        return DealProjectModel::instance()->findAllBySqlViaSlave($sql_get_entrust_pro, true);
    }

    /**
     * 将 指定的1.75项目的 标的层还款计划 汇总到项目还款计划列表中
     * @params int $project_id
     * @return boolean
     */
    public function repayOneIntoProjectRepay($project_id)
    {
        if (is_null($project_id)) {
            Logger::error(sprintf('fail: param is null, function:%s [%s:%s]', __FUNCTION__, __FILE__, __LINE__));
            return false;
        }


        if (ProjectRepayListService::createProjectRepayList($project_id)) {
            Logger::info(sprintf('success: create one project_repay_list,project_id:%d [%s:%s]', $project['id'], __FILE__, __LINE__));
        } else {
            Logger::error(sprintf('fail: create one project_repay_list,project_id:%d [%s:%s]', $project['id'], __FILE__, __LINE__));
        }
        return true;
    }

    /**
     * 根据新生成的项目还款, 将原项目还款审核数据
     * @return boolean
     */
    public function flushAllAuditData()
    {
        try {
            // 取出项目 还款/提前还款 审核成功信息
            $audit_cond = '`service_type` IN (7,8)';
            $audit_list = ServiceAuditModel::instance()->findAll($audit_cond);
            if (empty($audit_list)) {
                throw new \Exception('empty audit_list');
            }

            foreach ($audit_list as $audit) {
                $this->createProjectRepayAudit($audit);
            }
            Logger::info(sprintf('success: %s [%s:%s]', __FUNCTION__, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('fail: %s, err-msg:%s [%s:%s]', __FUNCTION__, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 根据项目id 生成新的审核记录 (包括 还款 和 提前还款)
     * @params object $audit
     */
    private function createProjectRepayAudit($audit)
    {
        if (empty($audit)) {
            Logger::error(sprintf('err-msg:empty audit info function:%s, [%s:%s]', __FUNCTION__, __FILE__, __LINE__));
            return;
        }

        try {
            $GLOBALS['db']->startTrans();
            $create_function = (8 == $audit->service_type) ? 'createProjectRepayAuditForPrepay' : 'createProjectRepayAuditForRepay';
            // 生成新的审核记录
            if (!call_user_func_array(array($this, $create_function), array($audit->service_id, $audit->submit_uid, $audit->audit_uid))) {
                throw new \Exception('create new  audit fail');
            }

            // 删除旧的记录
            if (!$audit->remove()) {
                throw new \Exception('delete old audit fail');
            }
            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:%s audit_info:%s [%s:%s]', __FUNCTION__, json_encode($audit), __FILE__, __LINE__));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail:%s audit_info:%s, err-msg:%s [%s:%s]', __FUNCTION__, json_encode($audit), $e->getMessage(), __FILE__, __LINE__));
        }
    }

    /**
     * 根据项目id 生成新的审核记录
     * @params int $project_id
     * @params int $last_submit_uid
     * @params int $last_audit_uid
     * @return boolean | throw
     */
    private function createProjectRepayAuditForRepay($project_id, $last_submit_uid = 0, $last_audit_uid = 0)
    {
        // 取出首标已还款的 max repay_time
        $cond_first_deal = sprintf('`project_id` = %d AND `is_delete` = 0 AND `deal_status` IN (4, 5) LIMIT 1', $project_id); // 只取 还款中 和 已还清
        $first_deal = DealModel::instance()->findByViaSlave($cond_first_deal, 'id');
        if (empty($first_deal)) {
            Logger::error(sprintf('fail: %s, project_id:%d, err-msg:first_deal is empty [%s:%s]', __FUNCTION__, $project_id, __FILE__, __LINE__));
            return false;
        }

        $sql_max_repay_time = sprintf('SELECT max(`repay_time`) AS max_repay_time FROM %s WHERE `status` = %d AND `deal_id` = %d ', DealRepayModel::instance()->tableName(), DealRepayModel::STATUS_PAIED_ONTIME, $first_deal->id); // 只取 正常还款
        $max_repay_time = DealRepayModel::instance()->findBySql($sql_max_repay_time);
        if (empty($max_repay_time['max_repay_time'])) {
            Logger::info(sprintf('notice: %s project_id:%d msg:no repayed, sql:%s [%s:%s]', __FUNCTION__, $project_id, $sql_max_repay_time, __FILE__, __LINE__));
            return true;
        }

        try {
            $GLOBALS['db']->startTrans();
            // 取出正常还款已经操作过还款的计划 即：还款时间小于当天
            $cond = sprintf('`project_id` = %d AND `repay_time` <= %d', $project_id, $max_repay_time['max_repay_time']);
            $project_repay_list = ProjectRepayListModel::instance()->findAll($cond);
            if (empty($project_repay_list)) {
                throw new \Exception('empty project_repay_list');
            }

            $count = count($project_repay_list);
            $now_slot = 0;
            foreach ($project_repay_list as $project_repay) {
                ++$now_slot;

                // 生成新的审核记录
                $audit_obj = new ServiceAuditModel();
                $audit_obj->service_id = $project_repay->id;
                $audit_obj->status = 2;
                $audit_obj->create_time = time();
                $audit_obj->update_time = time();
                $audit_obj->service_type = 7;
                if ($now_slot === $count) { // 最后一条记录，同步现有审核信息内容
                    $audit_obj->submit_uid = $last_submit_uid;
                    $audit_obj->audit_uid = $last_audit_uid;
                } else {
                    $audit_obj->submit_uid = 0; // 此处提交信息已丢失
                    $audit_obj->audit_uid = 0;
                }

                if (!$audit_obj->save()) {
                    throw new \Exception(sprintf('create new  audit fail,project_repay_info:%s', json_encode($project_repay)));
                }
            }
            $GLOBALS['db']->commit();
            Logger::info(sprintf('success: %s project_id:%d [%s:%s]', __FUNCTION__, $project_id, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail: %s, project_id:%d, err-msg:%s [%s:%s]', __FUNCTION__, $project_id, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 根据项目id 提前还款审核
     * @params int $project_id
     * @params int $last_submit_uid
     * @params int $last_audit_uid
     * @return boolean
     */
    private function createProjectRepayAuditForPrepay($project_id, $last_submit_uid = 0, $last_audit_uid = 0)
    {
        try {
            // 获取首标提前还款的记录
            $cond_first_deal = sprintf('`project_id` = %d AND `is_delete` = 0 AND `deal_status` IN (4, 5) LIMIT 1', $project_id); // 只取 还款中 和 已还清
            $first_deal = DealModel::instance()->findByViaSlave($cond_first_deal, 'id');
            if (empty($first_deal)) {
                throw new \Exception('empty first_deal');
            }

            $sql_repay_time = sprintf('SELECT repay_time FROM %s WHERE `status` = %d AND `deal_id` = %d ', DealRepayModel::instance()->tableName(), DealRepayModel::STATUS_PREPAID, $first_deal->id); // 只取 提前还款
            $deal_prepay_info = DealRepayModel::instance()->findBySql($sql_repay_time);
            if (empty($deal_prepay_info['repay_time'])) {
                Logger::info(sprintf('notice: %s project_id:%d msg:no prepayed, sql:%s [%s:%s]', __FUNCTION__, $project_id, $sql_repay_time, __FILE__, __LINE__));
                return true;
            }

            // 取出提前还款这期项目还款信息
            $cond = sprintf('`project_id` = %d AND `repay_time` = %d', $project_id, $deal_prepay_info['repay_time']);
            $project_repay = ProjectRepayListModel::instance()->findBy($cond);
            if (empty($project_repay)) {
                throw new \Exception('empty project_repay_list');
            }
            // 生成新的审核记录
            $audit_obj = new ServiceAuditModel();
            $audit_obj->service_id = $project_repay->id;
            $audit_obj->status = 2;
            $audit_obj->create_time = time();
            $audit_obj->update_time = time();
            $audit_obj->service_type = 8;
            $audit_obj->submit_uid = $last_submit_uid;
            $audit_obj->audit_uid = $last_audit_uid;


            if (!$audit_obj->save()) {
                throw new \Exception(sprintf('create new  audit fail,project_repay_info:%s', json_encode($project_repay)));
            }
            Logger::info(sprintf('success: %s project_id:%d [%s:%s]', __FUNCTION__, $project_id, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('fail: %s, project_id:%d, err-msg:%s [%s:%s]', __FUNCTION__, $project_id, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }

    /**
     * 刷新指定的审核记录
     * @return boolean
     */
    public function flushOneAuditData($audit_id)
    {
        if (is_null($audit_id)) {
            Logger::error(sprintf('fail: param is null, function:%s [%s:%s]', __FUNCTION__, __FILE__, __LINE__));
            return false;
        }

        try {
            $audit = ServiceAuditModel::instance()->find(intval($audit_id));
            if (empty($audit)) {
                throw new \Exception('no audit');
            }
            $this->createProjectRepayAudit($audit);
            Logger::info(sprintf('success: %s [%s:%s]', __FUNCTION__, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            Logger::error(sprintf('fail: %s, err-msg:%s [%s:%s]', __FUNCTION__, $e->getMessage(), __FILE__, __LINE__));
            return false;
        }
    }
}

