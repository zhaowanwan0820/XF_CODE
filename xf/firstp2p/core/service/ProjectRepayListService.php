<?php
namespace core\service;

use core\service\DealProjectService;

use core\dao\DealModel;
use core\dao\DealProjectModel;
use core\dao\DealRepayModel;
use core\dao\ProjectRepayListModel;

use libs\utils\Finance;
use libs\utils\Logger;
use libs\utils\Alarm;

class ProjectRepayListService extends BaseService
{
    /**
     * 判断项目是否满足生成项目还款列表的条件
     * @params int $project_id
     * @return boolean
     */
    static public function isOKForCreateProjectRepayList($project_id)
    {
        $project_id = intval($project_id);
        $project_service = new DealProjectService();

        // 是否专享 1.75
        if (!$project_service->isProjectEntrustZX($project_id)) {
            return false;
        }

        // 还款中标的数 是否等于 已放款的标的数
        $deal_list = DealModel::instance()->getDealByProId($project_id);
        $statistics['repaying'] = $statistics['has_loans'] = 0;
        foreach ($deal_list as $deal) {
            // 还款中
            if (DealModel::$DEAL_STATUS['repaying'] == $deal['deal_status']) {
                $statistics['repaying'] += 1;
            }

            // 已放款
            if (1 == $deal['is_has_loans']) {
                $statistics['has_loans'] += 1;
            }
        }

        return ($statistics['repaying'] === $statistics['has_loans']);
    }

    /**
     * 生成项目还款
     * @params int $project_id
     * @return boolean | throw \Exception
     */
    static public function createProjectRepayList($project_id)
    {
        $project_id = intval($project_id);
        $project_info = DealProjectModel::instance()->findViaSlave($project_id);
        $deal_list = DealModel::instance()->findAllViaSlave(sprintf('`project_id` = %d', $project_id), false, 'id');
        if (empty($deal_list)) {
            throw new \Exception('no deal data');
        }

        $id_arr = array();
        foreach ($deal_list as $one_deal) {
            $id_arr[] = $one_deal->id;
        }

        $deal_repay_list = DealRepayModel::instance()->findAll(sprintf('`deal_id` IN (%s)', implode(',', $id_arr)));
        $statistics_project_repay = array();
        $statistics_fields = array('repay_money', 'manage_money', 'impose_money', 'principal', 'interest', 'loan_fee', 'consult_fee', 'guarantee_fee', 'pay_fee', 'management_fee', 'canal_fee');
        foreach ($deal_repay_list as $one_deal_repay) {
            // 汇总字段值
            foreach ($statistics_fields as $field) {
                @$statistics_project_repay[$one_deal_repay->repay_time][$field] = Finance::addition(array($statistics_project_repay[$one_deal_repay->repay_time][$field], $one_deal_repay->$field));
            }
        }

        try {
            $GLOBALS['db']->startTrans();
            // 保存记录
            foreach ($statistics_project_repay as $repay_time => $one_project_repay) {
                $project_repay_obj = new ProjectRepayListModel();
                $project_repay_obj->project_id = $project_id;
                $project_repay_obj->user_id = $project_info->user_id;
                $project_repay_obj->repay_time = $repay_time;
                $project_repay_obj->create_time = time();
                $project_repay_obj->update_time = time();
                foreach ($statistics_fields as $field) {
                    $project_repay_obj->$field = $one_project_repay[$field];
                }

                if (!$project_repay_obj->save()) {
                    throw new \Exception('save project_repay_list fail');
                }
                unset($project_repay_obj);
            }
            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:save project_repay_list;project_id:%d, repay_data:%s [%s:%s]', $project_id, json_encode($statistics_project_repay), __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Alarm::push('entrust_project', '项目还款计划', sprintf('fail:save project_repay_list;project_id:%d, repay_data:%s, error:%s [%s:%s]', $project_id, json_encode($statistics_project_repay), $e->getMessage(), __FILE__, __LINE__));
            throw $e;
        }
    }
}
