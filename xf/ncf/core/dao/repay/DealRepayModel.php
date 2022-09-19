<?php
namespace core\dao\repay;

use core\dao\deal\OrderNotifyModel;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use core\enum\DealRepayEnum;
use core\enum\JobsEnum;
use core\enum\DealDkEnum;
use core\enum\MsgbusEnum;
use core\enum\ThirdpartyDkEnum;
use core\enum\UserAccountEnum;
use core\service\msgbus\MsgbusService;
use core\service\repay\DealRepayMoneyLog;
use core\service\repay\RepayMoneyLogRoute;
use core\service\thirdparty\ThirdpartyDkService;
use core\service\user\UserService;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\utils\Finance;
use core\dao\BaseModel;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\service\deal\DealService;
use core\dao\deal\DealAgencyModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\project\DealProjectModel;
use core\service\project\ProjectService;
use core\service\creditloan\CreditLoanService;
use core\service\repay\DealPartRepayService;


/**
 * 订单还款计划
 *
 * Class DealRepayModel
 * @package core\dao
 */
class DealRepayModel extends BaseModel {

    /**
     * 根据订单id获取订单还款计划列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealRepayListByDealId($dealId) {
        $condition = "deal_id=:deal_id ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    /**
     * 根据订单id获取订单未还还款计划列表
     *
     * @param $dealId
     * @return mixed
     */
    public function getDealUnpaiedRepayListByDealId($dealId) {
        $condition = "`deal_id`=:deal_id AND `status`='0' ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    public function getPayedRepayListByDealId($dealId) {
        $condition = "`deal_id`=:deal_id AND `status`='1' ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(':deal_id' => $dealId));
    }

    /**
     * 执行还款
     *
     * @param boolean $ignore_impose_money 是否执行逾期罚息
     * @param $negative 0 不可扣负 1可扣负
     * @return void
     **/
    public function repay(&$totalRepayMoney = 0,$repayAccountType=0, $orderId = '') {
        if($this->status > 0){
            return false;
        }
        $time = get_gmtime();

        $deal = DealModel::instance()->find($this->deal_id);
        $dealService = new DealService();
        $isPartRepayDealND = $dealService->isPartRepayDealND($this->deal_id,$repayAccountType);
        $repayUserId = $dealService->getRepayUserAccount($this->deal_id,$repayAccountType);

        if(!$repayUserId){
            throw new \Exception('未获取到还款用户ID');
        }

        $repayUser = UserService::getUserById($repayUserId);

        try {
            $this->db->startTrans();

            $sets = array('true_repay_time' => $time, 'repay_type' => $repayAccountType);
            $condition = " id=".$this->id." AND status=0";

            // 部分还款逻辑
            $partRepayInfo = DealPartRepayService::getPartRepayMoneyByOrderId($orderId);
            $partRepayType = $partRepayInfo['partRepayType'];

            if ($partRepayType != DealPartRepayService::REPAY_TYPE_PART) {
                //全部标注准时还款
                $this->status = DealRepayEnum::STATUS_PAIED_ONTIME; //准时
                $sets['status'] = DealRepayEnum::STATUS_PAIED_ONTIME;
            }
            if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
                $sets['part_repay_money'] = $this->part_repay_money = bcadd($this->part_repay_money, $partRepayInfo['totalRepayMoney'], 2);
            }

            $this->true_repay_time = $time;
            $sets['update_time'] = $time;
            $this->updateAll($sets, $condition);
            $effectRow = $this->db->affected_rows();

            if(!$effectRow){
                throw new \Exception('还款单状态修改失败！');
            }

            $deal = DealModel::instance()->getDealInfo($this->deal_id);
            $deal['isDtb'] = 0;
            if ($dealService->isDealDT($this->deal_id) == true) {
                $deal['isDtb'] = 1;
            }

            // 部分还款逻辑
            if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
                $repayMoney = $partRepayInfo['totalRepayMoney'];
            } else {
                $repayMoney = $this->repay_money;
            }

            $deal->repay_money = bcadd($deal->repay_money, $repayMoney, 2);
            $deal->last_repay_time = $time;
            $deal->update_time = $time;
            $nextRepay = $this->getNextRepay();
            if($nextRepay){
                $deal->next_repay_time = $nextRepay['repay_time'];
            }
            $nextRepayId = $nextRepay ? $nextRepay->id : null;

            if (!$deal->save()) {
                throw new \Exception('订单还款额修改失败！');
            }

            $repayType = $isPartRepayDealND ? DealRepayEnum::DEAL_REPAY_PART : DealRepayEnum::DEAL_REPAY_NORMAL;

//            if($nextRepayId == null){
//                $dealRepayRes = $deal->repayCompleted(true);
//                if($dealRepayRes === false){
//                    throw new \Exception("还有未完成还款，不能更改标的未已还清状态");
//                }
//            }

            // 部分还款逻辑
            if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
                RepayMoneyLogRoute::handleMoneyLog($deal,$this,DealRepayEnum::DEAL_REPAY_NORMAL_PART,$repayAccountType,$partRepayInfo);
            } else {
                RepayMoneyLogRoute::handleMoneyLog($deal,$this,$repayType,$repayAccountType);
            }

            $result = DealLoanRepayModel::instance()->repayDealLoan($this->id, $nextRepayId, $repayUserId, $repayAccountType, $orderId);
            //计算总共还款扣除的钱
            if ($partRepayType != DealPartRepayService::REPAY_TYPE_NORMAL) {
                // 部分还款金额
                $totalRepayMoney = $partRepayInfo['totalRepayMoney'];

            } else {

                $totalRepayMoney = $this->principal + $this->interest + $this->loan_fee + $this->guarantee_fee + $this->consult_fee + $this->pay_fee + $this->canal_fee;
            }

            // 加入还款结束检查
            $jobModel = new JobsModel();
            $function = '\core\dao\repay\DealRepayModel::finishRepay';
            $param = array(
                'deal_id' => $this->deal_id,
                'user_id' => $this->user_id,
                'deal_repay_id' => $this->id,
                'next_repay_id' => $nextRepayId,
                'repayUserId' => $repayUserId,
                'orderId' => $orderId,
                'partRepayType' => $partRepayInfo['partRepayType'],
            );
            $jobModel->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
            $r = $jobModel->addJob($function, array('param' => $param), false, 90);
            if ($r === false) {
                throw new \Exception('add \core\dao\repay\DealRepayModel::finishRepay error');
            }

            $save_res = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);

            if(!$save_res){
                throw new \Exception('修改标的还款状态失败！');
            }
            $this->db->commit();
        } catch (\Exception $e) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $this->id, $this->deal_id, $e->getMessage())));
            $this->db->rollback();
            return false;
        }
        return true;
    }

    //获取还款url地址
    public function getShareUrl($dealId='') {
        $durl = '';
        if(!empty($dealId)) {
            $durl = \libs\web\Url::gene("d", "", Aes::encryptForDeal($dealId), false, true);
            if($GLOBALS['user_info']){
                $durl .= "?r=".base64_encode(intval($GLOBALS['user_info']['id']));
            }
        }
        return get_domain().$durl;
    }

    /**
     * 获取逾期的借款id
     * @return array
     */
    public function getDelayRepayList() {
        $time = get_gmtime();
        $condition = sprintf("`status`='0' AND `repay_time`<'%d'", $this->escape($time));
        $result = $this->findAll($condition, true, 'deal_id');
        $arr = array();
        foreach ($result as $v) {
            $arr[] = $v['deal_id'];
        }
        return array_unique($arr);
    }

    /**
     * 获取逾期的借款列表
     * @return array
     */
    public function getDelayDealList() {
        $deal_ids = $this->getDelayRepayList();
        if (!$deal_ids) {
            return array();
        }
        $deal_str = implode(",", $deal_ids);
        $condition = sprintf("`deal_status`='4' AND `deal_type` = 0 AND `is_delete`='0' AND `id` IN (%s)", $this->escape($deal_str));
        $result = DealModel::instance()->findAll($condition);
        return $result;
    }

    /**
     * 获取逾期的借款数目
     * @return int
     */
    public function getDelayDealCount() {
        $deal_ids = $this->getDelayRepayList();
        if (!$deal_ids) {
            return 0;
        }
        $deal_str = implode(",", $deal_ids);
        $condition = sprintf("`deal_status`='4' AND `is_delete`='0' AND `id` IN (%s)", $this->escape($deal_str));
        $count = DealModel::instance()->count($condition);
        return $count;
    }

    /**
     * 获取下次还款
     *
     * @return DealRepay or null
     **/
    public function getNextRepay(){
        return $this->findBy("deal_id=$this->deal_id and id>$this->id limit 1");
    }

    public function getNextRepayByRepayId($dealId,$repayId){
        return $this->findBy("deal_id={$dealId} and id>$repayId limit 1");
    }

    /**
     * 根据deal_id获取下次还款
     * @param int deal_id
     * @return obj
     */
    public function getNextRepayByDealId($deal_id) {
        $deal_id = intval($deal_id);
        $condition = sprintf("`deal_id`='%d' AND `status`='0' ORDER BY `id`", $deal_id);
        return $this->findByViaSlave($condition);
    }

    /**
     * 根据deal_id获取上期还款
     * @param int deal_id
     * @return obj
     */
    public function getPrevRepayByDealId($deal_id) {
        $deal_id = intval($deal_id);
        $condition = sprintf("`deal_id`='%d' AND `status`>'0' ORDER BY `id` DESC ", $deal_id);
        return $this->findByViaSlave($condition);
    }

    /**
     * 计算是否能够还款
     *
     * @return boolean
     **/
    public function canRepay()
    {
        $day_of_ahead_repay = $GLOBALS['dict']['DAY_OF_AHEAD_REPAY'];
        if($this->status == 0 && (int)$this->repay_time <= (get_gmtime() + $day_of_ahead_repay * 24 * 3600)){
            return true;
        }
        return false;
    }

    /**
     *  检查是否已经逾期
     *
     * @return void
     **/
    public function isOverdue()
    {
        //http://jira.corp.ncfgroup.com/browse/WXPH-209 没有逾期
        return false;
        return to_date(get_gmtime(), "Y-m-d") >= to_date($this->repay_time, "Y-m-d");
    }

    /**
     * 逾期天数
     *
     * @return integer
     **/
    public function daysOfOverdue()
    {
        if($this->status == 0 && $this->isOverdue()){
            return floor((get_gmtime() - $this->repay_time)/(24 * 60 * 60));
        } else {
            return 0;
        }
    }

    /**
     * 逾期费用
     *
     * @return float
     **/
    public function feeOfOverdue()
    {
        //http://jira.corp.ncfgroup.com/browse/WXPH-209 没有逾期
        return 0;

        $deal = DealModel::instance()->find($this->deal_id);
        $principal = $this->principal;
        //对于按月付息，本金单独计算
        if($deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'] || $deal->loantype == $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']){
            $repay_times = $deal->getRepayTimes();
            $principal = $deal->borrow_amount / $repay_times; //计算每期正常情况下应还本金
        }
        return Finance::overdue($principal, $this->daysOfOverdue(), floatval($deal->rate)/100, floatval($deal->overdue_rate));
    }

    /**
     * 提前还款时，将未还的还款计划设置为取消
     * @param int $deal_id
     * @param int $prepay_time
     * @return bool
     */
    public function cancelDealRepay($deal_id, $prepay_time) {
        $params = array(":deal_id" => $deal_id);
        $list = $this->findAll("`deal_id`=':deal_id' AND `status`='0'", false, "*", $params);
        if (!$list) {
            return false;
        }

        foreach ($list as $v) {
            $v['status'] = DealRepayEnum::STATUS_PREPAID;
            $v['update_time'] = $prepay_time;
            $v['true_repay_time'] = $prepay_time;
            if ($v->save() === false) {
                throw new \Exception("update repay status fail");
            }
        }

        return true;
    }

    /**
    * 还款结束检查
    */
    public function finishRepay($param){
        //检查这次还款的数量如果还有，那就等着
        $count = DealLoanRepayModel::instance()->getRepayCountByDealRepayId($param['deal_repay_id']);

        if($count > 0){
            if ($param['partRepayType'] == DealPartRepayService::REPAY_TYPE_PART) {
                // return true;
                // pass
            } else {
                throw new \Exception(JobsEnum::ERRORMSG_NEEDDELAY, JobsEnum::ERRORCODE_NEEDDELAY);
            }
        }
        $next_repay_id = $param['next_repay_id'];
        $repayUserId = intval($param['repayUserId']);//还款用户ID

        try{
            $this->db->startTrans();

            //修改标状态
            $deal = DealModel::instance()->find($param['deal_id']);
            $save_res = $deal->changeRepayStatus(DealEnum::DEAL_NOT_DURING_REPAY);
            if(!$save_res){
                throw new \Exception('修改标的还款状态失败！');
            }

            if (!isset($param['partRepayType']) || $param['partRepayType'] != DealPartRepayService::REPAY_TYPE_PART) {
                if(!$next_repay_id){
                    $repayRes = $deal->repayCompleted();
                    if(!$repayRes){
                        throw new \Exception('更改还款完成状态失败');
                    }
                    $message = array('dealId' => $deal['id']);
                    MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_REPAY_OVER,$message);
                }

                $thirdPartyOrder = ThirdpartyDkService::getThirdPartyByOrderId($param['orderId']);
                if (!empty($thirdPartyOrder)) {
                    $outerOrderRecord = ThirdpartyDkModel::instance()->find($thirdPartyOrder['id']);
                    $outerOrderRecord->status = ThirdpartyDkEnum::REQUEST_STATUS_SUCCESS;
                    $outerOrderRecord->update_time = time();
                    $updateOrderRes = $outerOrderRecord->save();
                    if (!$updateOrderRes) {
                        throw new \Exception("更新Dk状态失败");
                    }
                }
                //接口异步回调通知
                if ($thirdPartyOrder['notify_url'] != '') {
                    $orderNotifyInfo = OrderNotifyModel::instance()->findViaOrderId($thirdPartyOrder['client_id'], $thirdPartyOrder['order_id']);
                    if (empty($orderNotifyInfo)) {
                        // 回调时，应该将outer_order_id和结果放到回调参数中的
                        $insertOrderNotifyData = [
                            'client_id'     => $thirdPartyOrder['client_id'],
                            'order_id'      => $thirdPartyOrder['order_id'],
                            'notify_url'    => $thirdPartyOrder['notify_url'],
                            'notify_params' => ['out_order_id'=>$thirdPartyOrder['outer_order_id'],'status'=>DealDkEnum::DK_STATUS_SUCC],
                        ];
                        $orderNotifyRes = OrderNotifyModel::instance()->insertData($insertOrderNotifyData);
                        if (!$orderNotifyRes) {
                            throw new \Exception("插入接口异步通知回调失败");
                        }
                    }
                }
                $message = array('dealId'=>$deal->id,'repayId'=>$param['deal_repay_id'],'nextRepayId' => $next_repay_id);
                MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_REPAY_FINISH,$message);

                $deal_service = new DealService();
                $is_dtv3 = $deal_service->isDealDTV3($param['deal_id']);
                if ($is_dtv3 === false) {
                    $jobs_model = new JobsModel();
                    $credit_loan_service = new CreditLoanService();
                    if($credit_loan_service->isCreditingDeal($param['deal_id']) && empty($next_repay_id)) {
                        $jobs_model->priority = JobsEnum::REPAY_CREDIT_LOAN;
                        $creditParam = array(
                                'deal_id' => $deal['id'],
                                'repay_type' => 2,// 1:网信提前还款2:正常还款3:逾期还款
                                );
                        $r = $jobs_model->addJob('\core\service\creditloan\CreditLoanService::dealCreditAfterRepay', $creditParam);
                        if ($r === false) {
                            throw new \Exception("Add CreditAfterRepay Jobs Fail");
                        }
                    }
                }
                $mq_job_model = new JobsModel();
                $mq_param = array('repayId'=>$param['deal_repay_id']);
                $mq_job_model->priority = JobsEnum::PRIORITY_MESSAGE_QUEUE_REPAY;
                $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::repay', array('param' => $mq_param), false, 90);
                if ($mq_res === false) {
                    throw new \Exception("Add MqService repay Jobs Fail");
                }
            }
            $this->db->commit();
        }catch (\Exception $ex){
            $this->db->rollback();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,$ex->getMessage(),"deal_id:".$param['deal_id'],"repay_id:".$param['deal_repay_id'])));
            return false;
        }
        \libs\utils\Monitor::add('PH_DEAL_REPAY');
        return true;
    }

    /**
     * 根据标的ids查出所有还款成功的列表
     *
     * @param $begin
     * @param $end
     * @return mixed
     */
    public function getRepayListByDealIds($dealIds) {
        $dealIdsStr = implode(',',$dealIds);
        $condition = sprintf('`status`!=0 AND `deal_id` IN (%s) ORDER BY `create_time` ASC',$dealIdsStr);
        $ret = $this->findAll($condition, false, '`deal_id`,`status`,`impose_money`,`interest`');
        return $ret;
    }


    /**
    * 获取标的所有正常还款和逾期还款的标
    */
    public function getRepaysByTime($start,$end){
        $condition = sprintf('`status`!=0 AND `status`!=4 AND `true_repay_time`>=%s AND `true_repay_time`<%s GROUP BY `deal_id`,`status` ORDER BY `deal_id`, `status` ASC',$start,$end);
        $ret = $this->findAllViaSlave($condition, false, 'deal_id,status');
        if (is_array($ret) && count($ret) > 0) {
            return $ret;
        } else {
            return array();
        }
    }


    /**
     * 取得标的的最大还款时间
     * @param $deal_id
     * @param array $status
     * @return \libs\db\Model
     */
    public function getMaxRepayTimeByDealId($deal_id, $status = array(1,2)) {
        $str_status = implode(',', $status);
        $res = $this->findBy("deal_id={$deal_id} and status in ({$str_status})","max(repay_time) as repay_time");
        return $res;
    }

    /**
     * 取得标的的最后一次还款时间
     * @param $deal_id
     * @return \libs\db\Model
     */
    public function getLastRepayTimeByDealId($deal_id) {
        $res = $this->findBy("deal_id={$deal_id}","max(repay_time) as repay_time");
        return $res;
    }

    /**
     * 取得预期还款汇总清单
     * @param $deal_id 标ID
     */
    public function getExpectRepayStat($deal_id) {
        $sql = sprintf('SELECT sum(repay_money) as repay_money,sum(loan_fee) as loan_fee,sum(consult_fee) as consult_fee,
                sum(guarantee_fee) as guarantee_fee,sum(pay_fee) as pay_fee,sum(management_fee) as management_fee,
                max(repay_time) as last_repay_time,sum(principal) as principal,sum(interest) as interest
                FROM `firstp2p_deal_repay` where deal_id=%s and status=%s',$deal_id,0);
        $res = $this->findBySql($sql);
        return $res;
    }

    /**
     * 根据标ID获取最后一期还款时间
     * @param $deal_id 标ID
     */
    public function getFinalRepayTimeByDealId($deal_id) {
        $sql = 'SELECT MAX(repay_time) as final_repay_time FROM `firstp2p_deal_repay` WHERE deal_id = '.$deal_id;
        $res = $this->findBySql($sql);
        if($res) {
            return $res->final_repay_time;
        }
        return 0;
    }

    /**
     * 提前还款情况下 取得标的截止某天的未收取费用
     * 手续费,咨询费,担保费,支付服务费
     * 每项费用计算公式 = 费用天数 * 借款金额(borrow_amount) * 费率 / 360 - 已收费用
     * @param $deal
     * @param $day 还款日期
     */
    public function getNoPayFees($deal,$deal_ext,$day) {
        $return = array('loan_fee'=>0,'consult_fee'=>0,'guarantee_fee'=>0,'pay_fee'=>0,'management_fee'=>0,'canal_fee'=>0);

        $has_pay_fees = 0; // 已经收取费用

        //续费后收 对于后收的标的，已收费用为零 对于分期收的标的，已收费用为已收各期费用之和
        $fee_days = ceil((to_timespan($day) - $deal->repay_start_time)/86400);
        $management_fee_column = "";
        if ($deal['isDtb'] == 1) {
            $management_fee_column = ",sum(management_fee) as management_fee " ;
        }

        // 已收取费用
        $sql = sprintf('SELECT `status`,sum(loan_fee) as loan_fee,sum(consult_fee) as consult_fee,sum(guarantee_fee) as guarantee_fee,sum(pay_fee) as pay_fee,sum(canal_fee) as canal_fee %s FROM `firstp2p_deal_repay` where deal_id = %s and `status` = 1',$management_fee_column,$deal['id']);
        $ret = $this->findBySql($sql);

        $totalMoney = $fee_days * $deal->borrow_amount;

        $return['loan_fee'] = 0;
        $has_pay_loan_fee = $ret['loan_fee'] ? $ret['loan_fee'] : 0;
        if(DealExtEnum::FEE_RATE_TYPE_PROXY == $deal_ext['loan_fee_rate_type']) { // 手续费代销分期,足额收取最后一期的手续费
            $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
            $return['loan_fee'] = array_pop($loan_fee_arr);
        } elseif (DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE == $deal_ext['loan_fee_rate_type']) { // 固定比例分期收，足额收取剩下的平台手续费
            $loan_fee_arr = json_decode($deal_ext['loan_fee_ext'], true);
            $return['loan_fee'] = array_sum($loan_fee_arr) - $has_pay_loan_fee;
        } else {
            if (in_array($deal_ext['loan_fee_rate_type'], array(DealExtEnum::FEE_RATE_TYPE_BEFORE, DealExtEnum::FEE_RATE_TYPE_FIXED_BEFORE))) { // 如果是前收  或者 固定比例前收
                $return['loan_fee'] = 0;
            } else {
                if (DealExtEnum::FEE_RATE_TYPE_FIXED_BEHIND == $deal_ext['loan_fee_rate_type']) { // 固定比例后收
                    $loan_fee_rate = $deal->loan_fee_rate / 100.0;
                    $loan_money = $deal->borrow_amount;
                } else { // 正常后收
                    $loan_fee_rate = $deal->loan_fee_rate /(100 * 360);
                    $loan_money = $totalMoney;
                }
                $loan_fee = floorfix($loan_money * $loan_fee_rate);
                $return['loan_fee'] = $loan_fee - $has_pay_loan_fee;
            }
        }

        if($deal_ext['consult_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['consult_fee'] = 0;
        } else {
            $consult_fee = floorfix($totalMoney * $deal->consult_fee_rate /(100 * 360));
            $has_pay_consult_fee = $ret['consult_fee'] ? $ret['consult_fee'] : 0;
            $return['consult_fee'] = $consult_fee - $has_pay_consult_fee;
        }

        if($deal_ext['guarantee_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['guarantee_fee'] = 0;
        } else {
            $guarantee_fee = floorfix($totalMoney * $deal->guarantee_fee_rate /(100 * 360));
            $has_pay_guarantee_fee = $ret['guarantee_fee'] ? $ret['guarantee_fee'] : 0;
            $return['guarantee_fee'] = $guarantee_fee - $has_pay_guarantee_fee;
        }

        if($deal_ext['pay_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['pay_fee'] = 0;
        } else {
            $pay_fee = floorfix($totalMoney * $deal->pay_fee_rate /(100 * 360));
            $has_pay_pay_fee = $ret['pay_fee'] ? $ret['pay_fee'] : 0;
            $return['pay_fee'] = $pay_fee - $has_pay_pay_fee;
        }

        if($deal_ext['canal_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
            $return['canal_fee'] = 0;
        } else {
            $canal_fee = floorfix($totalMoney * $deal->canal_fee_rate /(100 * 360));
            $has_pay_canal_fee = $ret['canal_fee'] ? $ret['canal_fee'] : 0;
            $return['canal_fee'] = $canal_fee - $has_pay_canal_fee;
        }

        if ($deal['isDtb'] == 1) {
            if($deal_ext['management_fee_rate_type'] == 1) { // 手续费前收 还款时需收取费用为零
                $return['management_fee'] = 0;
            } else {
                $management_fee = $deal->floorfix($totalMoney * $deal->management_fee_rate /(100 * 360));
                $has_pay_management_fee = $ret['management_fee'] ? $ret['management_fee'] : 0;
                $return['management_fee'] = $management_fee - $has_pay_management_fee;
            }
        }

        return $return;
    }

   /**
    * 根据status统计标的利息
    * @param $status
    * @string $deal_types 标的类型
    */
   public function getRepayDealInterestByStatus($status = 0 ,$deal_types = '') {
       $condition = "WHERE `status` = $status";
       if($status != 0){
          $condition = sprintf("WHERE `status` IN (%s)", $status);
       }

       $deal_type_cond = '';
       if(!empty($deal_types)) {
           $deal_type_cond = ' AND deal_type IN ('. $deal_types .') ';
       }

       $sql = sprintf("SELECT SUM(`interest`+`impose_money`) as `sum` FROM %s %s %s ",$this->tableName(),$condition,$deal_type_cond);
       $result = $this->findAllBySqlViaSlave($sql,true);
       return $result['0']['sum'];
   }

    /**
     * 根据deal_id获取等额本息还款方式的最后一期本金
     * @param array $deal
     * @param int $deal_repay_id
     * @return float
     */
    public function getFixPrincipalByDeal($deal, $deal_repay_id) {
        $sql = "SELECT SUM(`principal`) AS `m` FROM %s WHERE `deal_id` = '%d' AND `id` != '%d'";
        $sql = sprintf($sql, $this->tableName(), $deal['id'], $deal_repay_id);

        $row = $this->findBySql($sql);

        if (!$row) {
            return false;
        } else {
            return bcsub($deal['borrow_amount'], $row['m'], 2);
        }
    }

    /**
     * 根据deal_id数据获取最后一期还款日
     * @param array $deal_ids
     * @return array
     */
    public function getMaxRepayTimeByDealIds($deal_ids) {
        $sql = 'SELECT `deal_id`, MAX(`repay_time`) AS `final_repay_time` FROM %s WHERE `deal_id` IN (%s) GROUP BY `deal_id`';
        $sql = sprintf($sql, $this->tableName(), $deal_ids);

        $res = $this->findAllBySql($sql, true, array(), true);

        $result = array();
        foreach ($res as $row) {
            $result[$row['deal_id']] = $row['final_repay_time'];
        }

        return $result;
    }



    /**
     * 获取标的总还款期数
     * @param int $deal_id
     * @param int $user_id
     * @return int $periods_sum
     */
    public function getDealRepayPeriodsSumByUserId($deal_id, $user_id)
    {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d', $deal_id, $user_id);
        return $this->count($condition);
    }

    /**
     * 本次还款所属期数
     * @param int $deal_id
     * @param int $user_id
     * @return int $periods_order
     */
    public function getDealRepayPeriodsOrderByUserId($deal_id, $user_id)
    {
        $condition = sprintf('`deal_id` = %d AND `user_id` = %d AND `status` != %d', $deal_id, $user_id, DealRepayEnum::STATUS_WAITING);
        return $this->count($condition);
    }

    /**
     * 部分还款拆分原回款计划前，备份回款计划数据，发生在当期第一次部分还款时
     * 可多次重复调用，不用判断是否第一次还款
     *
     * @param $deal_repay_id 还款计划ID
     * @return boolean
     */
    public function backupByRepayId($deal_repay_id)
    {
        try {
            $deal_repay_id = intval($deal_repay_id);

            // 已经备份的回款计划记录数
            $sql_count_bak = sprintf("select count(*) as id_count from firstp2p_deal_loan_repay_bak where deal_repay_id = '%d'", $deal_repay_id);
            $count_bak  = $this->db->getOne($sql_count_bak);
            if ($count_bak) { // 已存在备份数据，不进行备份，同时数据库有主键唯一约束
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '已存在备份数据')));
                return true;
            }

            // 已经发生部分还款的回款计划记录数
            $sql_count_repaid = sprintf("select count(*) as id_count from firstp2p_deal_loan_repay where deal_repay_id = '%d' and status = '1'", $deal_repay_id);
            $count_repaid  = $this->db->getOne($sql_count_repaid);
            if ($count_repaid) { // 已经发生部分还款, 原始数据有可能已修改，不再进行数据备份
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '错误: 已经发生部分还款，但不存在备份')));
                return false;
            }

            $sql_backup = sprintf("insert into firstp2p_deal_loan_repay_bak (select * from firstp2p_deal_loan_repay where deal_repay_id = '%d')", $deal_repay_id);
            $rs = $this->db->query($sql_backup);
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '备份回款计划数据:' . (empty($rs) ? '失败' : '成功'))));
            return $rs;
        } catch (\Exception $e){
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, $e->getMessage())));
            return false;
        }
    }

}
