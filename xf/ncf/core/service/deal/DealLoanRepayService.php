<?php
/**
 * DealLoanRepayService.php
 *
 * @date 2014-03-20
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace core\service\deal;

use core\enum\DealEnum;
use core\enum\JobsEnum;
use core\enum\MsgbusEnum;
use core\dao\repay\DealLoanRepayModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\deal\DealRepayModel;
use core\dao\jobs\JobsModel;
use libs\utils\Finance;
use libs\utils\Logger;
use core\service\deal\DealService;
use core\service\BaseService;

/**
 * 订单回款计划
 *
 * Class DealLoanRepayService
 * @package core\service
 */
class DealLoanRepayService extends BaseService {


    public $is_history_db = false;
    /**
     * 根据投资id获取回款列表
     *
     * @param $deal_load_id
     * @return mixed
     */
    public function getLoanRepayListByLoanId($deal_load_id) {
        $list = DealLoanRepayModel::instance()->getLoanRepayListByLoanId($deal_load_id);
        return $list;
    }

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

        $dealLoanRepayModel = new DealLoanRepayModel();
        if ($this->is_history_db){
            $dealLoanRepayModel->is_history_db = true;
        }
        $rs = $dealLoanRepayModel->getLoanList($user_id,$start_time,$end_time,$limit,$type,$money_type,$repay_status, $deal_type);
        $this->is_history_db = false;
        $dealLoanRepayModel->is_history_db = false;
        $dealModel = DealModel::instance();
        $repay_status_all = array(''=>'全部',0 => '未还', 1 => '已还', 2 => '因提前还款而取消');
        $money_type_all = array(1 => '本金', 2 => '收益/利息', 3 => '提前还款', 4 => '提前还款补偿金', 5 => '逾期罚息', 7 => '提前还款利息',8 =>'通知贷赎回本金' ,9 =>'通知贷赎回利息');

        $arr = array();
        $counts = $rs['counts'];
        unset($rs['counts']);
        foreach($rs as $k=>$v){
            $deal_info = $dealModel->findViaSlave($v['deal_id'],"name");
            $v['deal_name'] = $deal_info["name"];
            $v['time'] = to_date($v['time'],'Y-m-d');
            $v['real_time'] = $v['real_time'] ? to_date($v['real_time'],'Y-m-d') : '-';
            $v['is_timeout'] = $v['real_time'] > $v['time'] && $v['time'] > 0 ? 1 : 0;
            $v['repay_status'] = $repay_status_all[$v['status']];
            $v['money_type'] = str_replace('收益/利息', '利息', $money_type_all[$v['type']]) ;
            if($v['type'] == 7){
                $v['money_type'] = '提前还款收益';
            }
            $arr[$k] = $v;
        }
        return array('list'=>$arr,'counts'=>$counts,'status'=>$repay_status_all,'type'=>$money_type_all);
    }

    /**
     * 根据投资id获取投资实际收益
     * @param $deal_loan_ids array
     * return array
     */
    public function getRealEarningByDealLoanids($deal_loan_ids) {
        $deal_loan_repay = new DealLoanRepayModel();
        return $deal_loan_repay->getTotalMoneyTypeForUser($deal_loan_ids);
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
        $dtDeals = \core\dao\deal\DealTagModel::instance()->getDealIdsByTagName(\core\service\duotou\DtDealService::TAG_DT);

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
                $maxtime = \core\dao\repay\DealLoanRepayModel::instance()->getMaxPrepayTimeByDealId($row['deal_id']);
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
