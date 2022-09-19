<?php
/**
 * 项目还款相关
 */

namespace core\service;

use core\dao\DealModel;
use core\dao\DealLoanTypeModel;

use core\service\DealRepayService;

use libs\utils\Finance;

class DealProjectRepayService extends BaseService
{
    /**
     * 根据项目id 获取还款信息
     * @param int $project_id
     * @return array
     */
    public function getProjectRepayInfo($project_id)
    {
        if (empty($project_id)) {
            return array();
        }

        $deal_list = DealModel::instance()->getDealByProId($project_id);
        $repay_collection = array(
            'repay_list' => array(),
            'applied_prepay' => 0,
            'overdue' => 0,
            'cannot_prepay' => 0,
            'prepay_show' => null,
            'total_repay_money' => 0,
            'deal' => array(),
        );
        $deal_repay_service = new DealRepayService();
        foreach ($deal_list as $deal) {
            $deal_repay_info = $deal_repay_service->getDealRepayInfo($deal['id'], $deal['user_id']);
            // repay_list
            foreach ($deal_repay_info['repay_list'] as $key => $one_repay) {
                if (!isset($repay_collection['repay_list'][$key]['status'])) {
                    $repay_collection['repay_list'][$key]['status'] = $one_repay['status'];
                }
                if (!isset($repay_collection['repay_list'][$key]['repay_time'])) {
                    $repay_collection['repay_list'][$key]['repay_time'] = $one_repay['repay_time'];
                }

                $repay_collection['repay_list'][$key]['repay_money'] = Finance::addition(array($one_repay['repay_money'], $repay_collection['repay_list'][$key]['repay_money']));
                $repay_collection['repay_list'][$key]['principal'] = Finance::addition(array($one_repay['principal'], $repay_collection['repay_list'][$key]['principal']));
                $repay_collection['repay_list'][$key]['interest'] = Finance::addition(array($one_repay['interest'], $repay_collection['repay_list'][$key]['interest']));
                $repay_collection['repay_list'][$key]['consult_fee'] = Finance::addition(array($one_repay['consult_fee'], $repay_collection['repay_list'][$key]['consult_fee']));
                $repay_collection['repay_list'][$key]['guarantee_fee'] = Finance::addition(array($one_repay['guarantee_fee'], $repay_collection['repay_list'][$key]['guarantee_fee']));
                $repay_collection['repay_list'][$key]['loan_fee'] = Finance::addition(array($one_repay['loan_fee'], $repay_collection['repay_list'][$key]['loan_fee']));
                $repay_collection['repay_list'][$key]['pay_fee'] = Finance::addition(array($one_repay['pay_fee'], $repay_collection['repay_list'][$key]['pay_fee']));
                $repay_collection['repay_list'][$key]['fee_of_overdue'] = Finance::addition(array($one_repay['fee_of_overdue'], $repay_collection['repay_list'][$key]['fee_of_overdue']));

                $repay_collection['repay_list'][$key]['can_repay'] = $one_repay['can_repay'];
                $repay_collection['repay_list'][$key]['fee_of_overdue'] = Finance::addition(array($one_repay['fee_of_overdue'], $repay_collection['repay_list'][$key]['fee_of_overdue']));
                $repay_collection['repay_list'][$key]['printerest'] = Finance::addition(array($one_repay['printerest'], $repay_collection['repay_list'][$key]['printerest']));
            }

            // 带着项目的一个标
            if (empty($repay_collection['deal'])) {
                $repay_collection['deal'] = $deal_repay_info['deal'];
            }

            // 总共要还的钱
            $repay_collection['total_repay_money'] = Finance::addition(array($deal_repay_info['deal']['total_repay_money'], $repay_collection['total_repay_money']));

            // 标识已申请或完成提前还款
            $repay_collection['applied_prepay'] = $deal_repay_info['applied_prepay'];

            // 标识逾期
            $repay_collection['overdue'] = $deal_repay_info['overdue'];

            // 标识不能进行提前还款
            $repay_collection['cannot_prepay'] = $deal_repay_info['cannot_prepay'];

            // 标识是否可以提前还款
            if (is_null($repay_collection['prepay_show'])) {
                $type_tag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal_repay_info['deal']->type_id);
                $today = to_timespan(date('Y-m-d'));
                $interest_time =  $deal_repay_service->getLastRepayTimeByDealId($deal_repay_info['deal']);
                $repay_collection['prepay_show'] = !($type_tag == DealLoanTypeModel::TYPE_XFFQ || $deal_repay_info['deal']->deal_type == DealModel::DEAL_TYPE_COMPOUND || $today >= $interest_time);
            }
        }

        return $repay_collection;
    }
}
