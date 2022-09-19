<?php
/**
 * DealLoanRepayService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service;

use core\dao\DealLoanRepayModel;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\service\DtDealService;
use core\dao\JobsModel;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\DealLoanRepayEvent;
use libs\utils\Finance;
use core\service\DealService;
use core\service\partner\PartnerService;
use core\service\ProjectRepayListService;

/**
 * 订单回款计划
 *
 * Class DealLoanRepayService
 * @package core\service
 */
class DealLoanRepayService extends BaseService {

    /**
     * 生成回款计划
     */
    public function create($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $principal, $is_last)
    {
        \libs\utils\Logger::info('DealLoanRepayCreate. params:'.json_encode(func_get_args()));
        $event = new DealLoanRepayEvent($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $principal, $is_last);
        return $event->execute();
    }

    /**
     * 根据投资id获取回款列表
     *
     * @param $deal_load_id
     * @return mixed
     */
    public function getLoanRepayListByLoanId($deal_load_id) {
        $list = DealLoanRepayModel::instance()->getLoanRepayListByLoanId($deal_load_id);

        if(empty($list)){
            return array();
        }
        foreach( $list as &$one){
            if( $one['money_type'] == '利息' && !isDealP2P($one['deal_type'])){
                $one['money_type'] = '收益';
            }
            $isDealZx = (new DealService())->isDealEx($one['deal_type']);
            if( $one['money_type'] == '提前还款利息' && $isDealZx){
                $one['money_type'] = '提前还款收益';
            }
        }
        return $list;
    }

    /**
     * 放款收尾任务
     * @param $deal_id
     * @return bool
     */
    public function finishDealLoans($deal_id) {
        $deal = DealModel::instance()->find($deal_id);
        $dealService = new DealService();

        $GLOBALS['db']->startTrans();
        try {
            if ($deal->deal_type == DealModel::DEAL_TYPE_COMPOUND || $deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_CROWDFUNDING']) {
                // 通知贷和公益标直接返回true
                if ($deal->changeLoansStatus($deal_id, 1) == false) {
                    throw new \Exception("更新已打款状态失败");
                }
                $GLOBALS['db']->commit();
                \libs\utils\Monitor::add('DEAL_MAKE_LOANS');
                return true;
            }

            $repay_times = $deal->getRepayTimes();

            $deal_ext = \core\dao\DealExtModel::instance()->getInfoByDeal($deal_id, false);
            $loantype_tmp = array(
                $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'],
                $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY'],
            );
            if ($deal_ext['first_repay_interest_day'] && in_array($deal->loantype, $loantype_tmp)) {
                // 如果是第一期还款日并且是按月、按季还款，还款期数加一期
                $repay_times++;
            }

            $total = $deal->buy_count * $repay_times;

            $count = DealLoanRepayModel::instance()->getCountByDealId($deal_id, DealLoanRepayModel::MONEY_INTREST);
            if ($count < $total) {
                // 数据库中的count比理论值小代表放款未完成
                throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
            }

            $dr_model = new DealRepayModel();

            $arr_sum = DealLoanRepayModel::instance()->getSumByDealId($deal_id);
            foreach ($arr_sum as $k => $v) {
                $repay_id = $k;
                $deal_repay = $dr_model->find($repay_id);

                if ($deal_repay) {
                    $deal_repay->principal = $v['principal'];
                    $deal_repay->interest = $v['interest'];

                    $repay_money_arr = array(
                        $v['principal'],
                        $v['interest'],
                        $deal_repay['loan_fee'],
                        $deal_repay['consult_fee'],
                        $deal_repay['guarantee_fee'],
                        $deal_repay['pay_fee'],
                        $deal_repay['management_fee'],
                        $deal_repay['canal_fee'],
                    );

                    $deal_repay->repay_money = Finance::addition($repay_money_arr);

                    if ($deal_repay->save() === false) {
                        throw new \Exception("save deal repay fail");
                    }
                }
            }

            if ($deal->changeLoansStatus($deal_id, 1) == false) {
                throw new \Exception("更新已打款状态失败");
            }

            // 生成项目还款列表
            if (ProjectRepayListService::isOKForCreateProjectRepayList($deal->project_id)) {
                $jobs_obj = new JobsModel();
                $jobs_obj->priority = JobsModel::PRIORITY_CREATE_PROJECT_REPAY_LIST;
                $function = '\core\service\ProjectRepayListService::createProjectRepayList';
                $param = array('project_id' => $deal->project_id);
                if (false === $jobs_obj->addJob($function, $param)) {
                    throw new \Exception(sprintf('add jobs %s fail,deal_id:%d', $function, $deal->id));
                }
            }

            //如果是智多鑫p2p标的，放款完成通知智多鑫
            if($dealService->isDealDT($deal_id)) {
                $jobs_model = new JobsModel();
                $jobs_model->priority = 84;
                $param = array(
                    'deal_id' => $deal_id,
                );
                $r = $jobs_model->addJob('\core\service\DtDealService::p2pDealHasLoansNotify', $param);
                if ($r === false) {
                    throw new \Exception("Add DT Jobs Fail");
                }
            }

            // 向第三方推送还款计划
            $partner_project_name = PartnerService::getPartnerProjectName($deal->type_id);
            if (!empty($partner_project_name)) {
                $jobs_model = new JobsModel();
                $jobs_model->priority = JobsModel::PRIORITY_NOTICE_PARTNER;
                $param = array(
                    'deal_id' => $deal_id,
                    'partner_project_name' => $partner_project_name,
                );
                if (false === $jobs_model->addJob('\core\service\partner\PartnerService::noticeDealRepayList', $param)) {
                    throw new \Exception("Add partner Jobs Fail");
                }
            }

            $mq_job_model = new JobsModel();
            $mq_param = array('dealId'=>$deal_id);
            $mq_job_model->priority = JobsModel::PRIORITY_MESSAGE_QUEUE_LOAN;
            $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::loan', array('param' => $mq_param), false, 90);
            if ($mq_res === false) {
                throw new \Exception("Add MqService loan Jobs Fail");
            }


            $GLOBALS['db']->commit();
            \libs\utils\Monitor::add('DEAL_MAKE_LOANS');
            return true;
        } catch (\Exception $e) {
            \libs\utils\Logger::error($e->getMessage());
            $GLOBALS['db']->rollback();
            throw $e;
        }

    }

    /*
	public function get_repay_list($where,$limit=''){
		$rs = DealLoanRepayModel::instance()->getList($where,$limit,true);
		$deal = DealModel::instance();

		$repay_status_all = array(0 => '未还', 1 => '已还', 2 => '因提前还款而取消');
		$money_type_all = array(1 => '本金', 2 => '利息', 3 => '提前还款', 4 => '提前还款补偿金', 5 => '逾期罚息', 7 => '提前还款利息');

		$arr = array();
		$counts = $rs['counts'];
		unset($rs['counts']);
		foreach($rs as $k=>$v){
			$deal_info = $deal->find($v['deal_id'],"name");
			$v['deal_name'] = $deal_info["name"];
			$v['time'] = to_date($v['time'],'Y-m-d');
			$v['real_time'] = $v['real_time'] ? to_date($v['real_time'],'Y-m-d') : '-';
			$v['is_timeout'] = $v['real_time'] > $v['time'] && $v['time'] > 0 ? 1 : 0;
			$v['repay_status'] = $repay_status_all[$v['status']];
			$v['money_type'] = $money_type_all[$v['type']];
 			$arr[$k] = $v;
		}
		return array('list'=>$arr,'counts'=>$counts,'status'=>$repay_status_all,'type'=>$money_type_all);
	}
    */
    /**
     * 获取用户回款列表
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @param $limit array(0,10)
     * @param string $type web api new api
     * @param null $money_type
     * @param null $repay_status
     * @param int | string $deal_type
     * @return array
     */
    public function getRepayList($user_id,$start_time,$end_time,$limit,$type='web',$money_type=null,$repay_status=null, $deal_type = false){
        $rs = DealLoanRepayModel::instance()->getLoanList($user_id,$start_time,$end_time,$limit,$type,$money_type,$repay_status, $deal_type);
        $deal = DealModel::instance();
        $repay_status_all = array(''=>'全部',0 => '未还', 1 => '已还', 2 => '因提前还款而取消');
        $money_type_all = array(1 => '本金', 2 => '收益/利息', 3 => '提前还款', 4 => '提前还款补偿金', 5 => '逾期罚息', 7 => '提前还款利息',8 =>'通知贷赎回本金' ,9 =>'通知贷赎回利息');

        $arr = array();
        $counts = $rs['counts'];
        unset($rs['counts']);
        foreach($rs as $k=>$v){
            $deal_info = $deal->findViaSlave($v['deal_id'],"name");
            $v['deal_name'] = $deal_info["name"];
            $v['time'] = to_date($v['time'],'Y-m-d');
            $v['real_time'] = $v['real_time'] ? to_date($v['real_time'],'Y-m-d') : '-';
            $v['is_timeout'] = $v['real_time'] > $v['time'] && $v['time'] > 0 ? 1 : 0;
            $v['repay_status'] = $repay_status_all[$v['status']];
            $v['money_type'] = isDealP2P($v['deal_type']) ? str_replace('收益/利息', '利息', $money_type_all[$v['type']]) : str_replace('收益/利息', '收益', $money_type_all[$v['type']]);
            // JIRA#5410
            $isDealZx = (new DealService())->isDealEx($v['deal_type']);
            if($v['type'] == 7 && $isDealZx){
                $v['money_type'] = '提前还款收益';
            }
            $arr[$k] = $v;
        }
        return array('list'=>$arr,'counts'=>$counts,'status'=>$repay_status_all,'type'=>$money_type_all);
    }

    /**
     * 取得某用户已还或待还的金额按照标的汇总
     * @param $uid
     * @param $time
     */
    public function getRepayDealSumaryByTime($uid,$time) {
        $res = DealLoanRepayModel::instance()->getRepayDealSumaryByTime($uid,$time);
        $data = array();
        $dealsInfo = array();

        // 获取智多鑫标的列表，并加以排除
        $dtDeals = \core\dao\DealTagModel::instance()->getDealIdsByTagName(DtDealService::TAG_DT);

        foreach($res as $k => $row) {
            if (in_array($row['deal_id'], $dtDeals)) {
                continue;
            }

            if($row['type'] == 1 && $row['m'] == 0) {
                continue;
            }
            if(!isset($dealsInfo[$row['deal_id']])) {
                $dealsInfo[$row['deal_id']] = DealModel::instance()->findViaSlave($row['deal_id'],"name");
            }

            $tmpData = array(
                'principal' => 0,
                'interest' => 0,
                'is_prepay' => false,
                'original_date' => '',
                'status' => ($row['status'] == 0) ? 0 : 1, // 1,2均属于已还
            );

            if(!isset($data[$row['deal_id']])) {
                $data[$row['deal_id']] = array(
                    'deal_id' => $row['deal_id'],
                    'deal_name' => $dealsInfo[$row['deal_id']]['name'],
                    'data' => array(
                        'principal' => 0,
                        'interest' => 0,
                        'interest_title' => '', // interest名称 app 文案
                        'damage' => 0, // 违约金
                        'overdue' => 0, // 逾期罚息
                        'is_prepay' => false,
                        'original_date' => '',
                        'status' => ($row['status'] == 0) ? 0 : 1, // 1,2均属于已还
                    ),
                );
            }
            $data[$row['deal_id']]['data']['time'] = $row['time'];
            $data[$row['deal_id']]['data']['real_time'] = $row['real_time'];

            if($row['type'] == 3) {
                $maxtime = DealLoanRepayModel::instance()->getMaxPrepayTimeByDealId($row['deal_id']);
                $data[$row['deal_id']]['data']['is_prepay'] = true;
                $data[$row['deal_id']]['data']['original_date'] = to_date($maxtime,'Y-m-d');
            }
            if($row['type']==2 && $row['real_time'] < $row['time'] && $row['real_time'] > 0) {
                $data[$row['deal_id']]['data']['is_prepay'] = true;
                $data[$row['deal_id']]['data']['original_date'] = to_date($row['time'],'Y-m-d');
            }
            if(in_array($row['type'],array(1,3,8))) {
                $data[$row['deal_id']]['data']['principal'] = bcadd($data[$row['deal_id']]['data']['principal'],$row['m'],2);
            }
            if(in_array($row['type'],array(2,7,9))) {
                $data[$row['deal_id']]['data']['interest'] = bcadd($data[$row['deal_id']]['data']['interest'],$row['m'],2);
                $data[$row['deal_id']]['data']['interest_title'] = isDealP2P($row['deal_type']) ? '利息' : '收益';

            }
            if($row['type'] == 4) {
                $data[$row['deal_id']]['data']['damage'] = bcadd($data[$row['deal_id']]['data']['damage'],$row['m'],2);
            }
            if($row['type'] == 5) {
                $data[$row['deal_id']]['data']['overdue'] = bcadd($data[$row['deal_id']]['data']['overdue'],$row['m'],2);
            }
        }
        return $data;
    }
}
