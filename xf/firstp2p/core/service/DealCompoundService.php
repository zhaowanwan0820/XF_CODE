<?php

 /**
 * DealCompound class file.
 * 利滚利业务逻辑
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace core\service;

use core\dao\CompoundRedemptionApplyModel;
use core\data\DealData;
use core\dao\DealModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanRepayModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\DealCompoundModel;
use core\dao\FinanceQueueModel;
use core\service\DealService;
use core\service\DealGroupService;
use core\service\AdunionDealService;
use core\service\DealLoadService;
use core\service\DealTagService;
use core\service\CouponService;
use core\service\jifu\JfTransferService;
use libs\lock\LockFactory;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\DealCompoundMsgEvent;
use core\event\DealCompoundNoticeEvent;
use core\dao\ThirdpartyOrderModel;
use core\service\UserCarryService;
use core\service\O2OService;
use core\service\UserLoanRepayStatisticsService;
use core\service\DealLoanRepayCalendarService;
use core\service\UserProfileService;
use core\service\DealProjectRiskAssessmentService;
use NCFGroup\Protos\O2O\Enum\CouponGroupEnum;

/**
 * DealCompound service
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/
class DealCompoundService extends BaseService
{

    private $_syncRemoteData;

    /**
     * getDayRateByYearRate
     * 根据年利率获得日利率 5位小数 后面要跟% 方便显示用,不得直接用于计算
     *
     * @param mixed $year_rate
     * @param int $redemption_period 赎回周期
     * @access public
     * @return void
     */
    public static function getDayRateByYearRate($year_rate, $redemption_period) {
        $day_rate = DealModel::instance()->convertRateYearToDay($year_rate, $redemption_period);
        return bcmul($day_rate, '100', 5);
    }
    /**
     * 将日利率转为年利率
     * @param float $rate_day
     * @param float
     */
    public static function convertRateDayToYear($rate_day)
    {
        return DealModel::instance()->convertRateDayToYear($rate_day);
    }

    /**
     * 根据deal_id获取日率
     * @param int $deal_id
     * @return float|false
     */
    public function getDayRateByDealId($deal_id) {
        $deal_id = intval($deal_id);
        if (!$deal_id) {
            return false;
        }

        $deal = DealModel::instance()->find($deal_id);
        if (!$deal) {
            return false;
        }

        $rate_year = $deal['rate'];
        $compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);
        if (!$compound) {
            return false;
        }

        $redemption_period = $compound['redemption_period'];
        return self::convertRateYearToDay($rate_year, $redemption_period);
    }


    /**
     * 将年利率转为日利率
     * @param float $rate_year
     * @param int $redemption_period 赎回周期
     * @param bool only_display
     * @return float
     */
    public static function convertRateYearToDay($rate_year, $redemption_period, $only_display = false)
    {
        $day_rate = DealModel::instance()->convertRateYearToDay($rate_year, $redemption_period);
        if ($only_display === true ) {
            return bcmul($day_rate, '100', 5);
        } else {
            return $day_rate;
        }
    }

    /**
     * 根据日利率计算期间本息和
     * @param  float $principal
     * @param  float $rate_day
     * @param  int   $day
     * @return float
     */
    public static function getTotalMoneyByDayRate($principal, $rate_day, $day=360)
    {
        $money = $principal * pow(1+$rate_day, $day);

        return DealModel::instance()->floorfix($money);
    }

    /**
     * 根据年利率计算期间本息和
     * @param  float $principal
     * @param  float $rate_year
     * @param  int   $redemption_period 赎回周期
     * @param  int   $day
     * @return float
     */
    public static function getTotalMoneyByYearRate($principal, $rate_year, $redemption_period, $day=360)
    {
        $rate_day = self::convertRateYearToDay($rate_year, $redemption_period);
        $money = self::getTotalMoneyByDayRate($principal, $rate_day, $day);

        return $money;
    }

    /**
     * 根据年利率计算期间利息
     * @param float $principal
     * @param float $rate_year
     * @param int $day
     * @param float
     */
    public static function getInterestByYearRate($principal, $rate_year, $redemption_period, $day=360)
    {
        $total = self::getTotalMoneyByYearRate($principal, $rate_year, $redemption_period, $day);
        return bcsub($total, $principal, 2);
    }

    /**
     * 根据deal_id获取第一回款日
     * 目前的计算是起息日+锁定期+回款周期
     * TODO 没有加赎回周期
     * @param  int $deal_id
     * @return int 到帐日时间戳
     */
    public function getFirstRepayDay($deal_id)
    {
        $deal = DealModel::instance()->find($deal_id);
        $deal_compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);
        $first_repay_day = $deal['repay_start_time'] + $deal_compound['lock_period'] * 86400 + $deal_compound['redemption_period'] * 86400;

        return $first_repay_day;
    }

    /**
     * 根据deal_id获取最后一期回款日
     * 起息日+借款周期
     * TODO 目前只支持按天一次性
     * @param  int $deal_id
     * @return int 到账日时间戳
     */
    public function getLastRepayDay($deal_id)
    {
        $deal = DealModel::instance()->find($deal_id);
        $last_repay_day = $deal['repay_start_time'] + $deal['repay_time'] * 86400;

        return $last_repay_day;
    }

    /**
     * 根据deal_id获取第一可申请赎回日期
     * 起息日+锁定期
     * @param  int $deal_id
     * @return int 可申请赎回日时间戳
     */
    public function getFirstApplyDay($deal_id)
    {
        $deal = DealModel::instance()->find($deal_id);
        $deal_compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);
        $first_apply_day = $deal['repay_start_time'] + $deal_compound['lock_period'] * 86400;

        return $first_apply_day;
    }

    /**
     * 根据deal_id获取最后可申请赎回日期
     * 起息日+借款周期-回款周期
     * TODO 没有加赎回周期
     * @param  int $deal_id
     * @return int 最后可申请赎回日时间戳
     */
    public function getLastApplyDay($deal_id)
    {
        $deal = DealModel::instance()->find($deal_id);
        $deal_compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);
        $last_apply_day = $deal['repay_start_time'] + $deal['repay_time'] * 86400 - $deal_compound['redemption_period'] * 86400;

        return $last_apply_day;
    }

    /**
     * 根据deal_id获取最近回款日
     * 在第一回款日和最后一期回款日之间
     * @param int $deal_id
     * @return int 最近回款日时间戳
     */
    public function getLatestRepayDay($deal_id) {
        $now = to_timespan(date('Y-m-d'));
        $deal = DealModel::instance()->find($deal_id);
        $deal_compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);

        $first_repay_day = $deal['repay_start_time'] + $deal_compound['lock_period'] * 86400 + $deal_compound['redemption_period'] * 86400; //第一回款日
        $current_repay_day = $now + $deal_compound['redemption_period'] * 86400; //当前日期过赎回周期后的最近回款日
        $latest_repay_day = max($first_repay_day, $current_repay_day); //和第一回款日比较，得出最近回款日

        $is_holiday = false;
        // 考虑节假日顺延
        while ($this->checkIsHoliday(to_date($latest_repay_day, 'Y-m-d'))) {
            $latest_repay_day += 86400; //叠加顺延时间
            $is_holiday = true;
        }

        $last_repay_day = $deal['repay_start_time'] + $deal['repay_time'] * 86400; // 最晚回款日
        $latest_repay_day = min($latest_repay_day, $last_repay_day); // 与最晚回款日比较，得出最终结果
        return array('is_holiday'=>$is_holiday,'repay_time'=>$latest_repay_day);
    }

    /**
     * 根据deal_id获取已赎回本金
     * @param  int   $deal_id
     * @return float
     */
    public function getPayedCompoundPrincipal($deal_id)
    {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeDealId($deal_id, DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL, DealLoanRepayModel::STATUS_ISPAYED);

        return empty($rs) ? 0 : $rs;
    }

    /**
     * 根据deal_id获取未赎回本金
     * @param  int   $deal_id
     * @return float
     */
    public function getUnpayedCompoundPrincipal($deal_id)
    {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeDealId($deal_id, DealLoanRepayModel::MONEY_COMPOUND_PRINCIPAL, DealLoanRepayModel::STATUS_NOTPAYED);

        return empty($rs) ? 0 : $rs;
    }

    /**
     * 根据deal_id获取已赎回利息
     * @param  int   $deal_id
     * @return float
     */
    public function getPayedCompoundInterest($deal_id)
    {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeDealId($deal_id, DealLoanRepayModel::MONEY_COMPOUND_INTEREST, DealLoanRepayModel::STATUS_ISPAYED);

        return empty($rs) ? 0 : $rs;
    }

    /**
     * 根据deal_id获取未赎回利息
     * @param  int   $deal_id
     * @return float
     */
    public function getUnpayedCompoundInterest($deal_id)
    {
        $rs = DealLoanRepayModel::instance()->getTotalMoneyByTypeDealId($deal_id, DealLoanRepayModel::MONEY_COMPOUND_INTEREST, DealLoanRepayModel::STATUS_NOTPAYED);

        return empty($rs) ? 0 : $rs;
    }

    /**
     * 根据投资id获取计息日期
     * @param int $deal_loan_id
     * @return int|false 计息日期
     */
    public function getPeriodDay($deal_loan_id)
    {
        $apply = CompoundRedemptionApplyModel::instance()->getApplyByDealLoanId($deal_loan_id);
        if (!$apply) {
            // 如果未申请赎回，则返回false
            return false;
        }
        $deal = DealModel::instance()->find($apply['deal_id']);

        $day = $apply['repay_time'] - $deal['repay_start_time'];
        return $day / 86400;
    }

    /**
     * 获取用户个人中心 利滚利相关内容
     * pengchanglu@ucfgroup.com
     * @param $uid
     * @param $time
     * @return mixed
     */
    public function getUserCompoundMoney($uid ,$time = false)
    {
        $list = DealLoadModel::instance()->getUserCompoundList($uid);
        $compound_money = 0;//利滚利金额
        $repay_money = 0;//可赎回金额
        $interest = 0;//利息
        if (!$list) {
            return array('compound_money' =>0, 'repay_money'=>0, 'interest'=>0);
        }
        $deal_model = new DealModel();
        $compound_model = new DealCompoundModel();
        foreach ($list as $k => $v) {
            $deal = $deal_model->findViaSlave($v['deal_id']);
            $deal_compound = $compound_model->getDealCompoundByDealId($v['deal_id']);
            if ($deal['deal_status'] == 4) {//已经开始回款
                if ($time) {
                    $day = ceil(($time-$deal->repay_start_time)/86400);
                } else {
                    $day = ceil((get_gmtime()-$deal->repay_start_time)/86400);
                }
                $temp_money = self::getTotalMoneyByYearRate($v['money'], $deal->income_fee_rate, $deal_compound['redemption_period'], $day);
                $repay_money = bcadd($repay_money, $temp_money, 2);
                $interest = bcadd($interest, bcsub($temp_money, $v['money'], 2), 2);
            }
            if($deal['deal_status'] !=3){//流标
                $compound_money = $compound_money + $v['money'];
            }
        }
        return array('compound_money' =>$compound_money, 'repay_money'=>$repay_money, 'interest'=>$interest);
    }

    /**
     * 获取利滚利标的本息和利息
     * @param $deal_load_id
     * @param int $time
     */
    public function getCompoundMoneyByDealLoadId($deal_load_id, $time = 0){
        $deal_load_service = new DealLoadService();
        $deal_load = $deal_load_service->getDealLoadDetail($deal_load_id);
        if(!$deal_load['deal']['deal_type']){
            return 0;
        }
        if(!$time){
            $rs = $this->getLatestRepayDay($deal_load['deal_id']);
            $time = $rs['repay_time'];
        }
        $moeny = $deal_load['money'];
        $day = ceil(($time-$deal_load['deal']['repay_start_time'])/86400);
        $rate = trim($deal_load['deal']['int_rate'],'%');
        $redemption_period = $deal_load['deal']['redemption_period'];
        $sum = self::getTotalMoneyByYearRate($moeny, $rate, $redemption_period, $day);
        return $sum;
    }

    /**
     * 利滚利投资方法
     * @param int    $user_id
     * @param int    $deal_id
     * @param float  $money
     * @param string $coupon_id   优惠码
     * @param int    $source_type 默认为0-web投资
     * @param int    $site_id     默认为1-主站
     */
    public function bid($user_id, $deal_id, $money, $coupon_id, $source_type=0, $site_id=1, $order_id = false)
    {
        try {
            // 记录log
            $arr_log = array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $site_id, $coupon_id,"start");
            Logger::info(implode(" | ", $arr_log));
            $this->_startBid($user_id, $deal_id, $money, $source_type, $site_id);
            // 限制投资
            $userCarryService = new UserCarryService();
            $user_money_limit = $userCarryService->canWithdrawAmount($user_id, $money);
            if ($user_money_limit === false){
                throw new \Exception($GLOBALS['lang']['FORBID_BID']);
            }
            $deal_model = new DealModel();
            $deal = $deal_model->find($deal_id);

            if (!$deal) {
                throw new \Exception($GLOBALS['lang']['PLEASE_SPEC_DEAL']);
            }

            $deal = $deal_model->handleDeal($deal);
            $user_model = new UserModel();
            $user = $user_model->find($user_id);

            // 检查是否能投资
            $coupon_id = ($coupon_id == CouponService::SHORT_ALIAS_DEFAULT) ? '' : $coupon_id;
            $result = $this->_checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id);
            $bonus = $result['bonus'];

            // 投标操作和记录优惠码加到一个事务中
            $GLOBALS['db']->startTrans();
            try {
                $deal_service = new DealService();
                $isJF = $deal_service->isDealJF($site_id);
                if ($isJF === true) {
                    $bid_transfer_id = $deal_service->transferBidJF($user, $money, $deal_id, $order_id);
                    if ( $bid_transfer_id === false) {
                        throw new \Exception("投资转账失败，请稍后再试");
                    }
                }

                $deal_load_id = $deal->bidNew($user, $money, $source_type, $site_id, $bonus, $coupon_id);
                if ($deal_load_id === false) {
                    throw new \Exception("投资处理失败，请稍后再试");
                }


                $jobsModel = new JobsModel();
                $contract_function = '\core\service\DealLoadService::sendContract';
                $contract_param = array(
                    'deal_id' => $deal_id,
                    'load_id' => $deal_load_id,
                    'is_full' => false,
                    'create_time' => time(),
                );
                $jobsModel->priority = 123;
                $contract_ret = $jobsModel->addJob($contract_function, array('param' => $contract_param)); //不重试
                if ($contract_ret === false) {
                    throw new \Exception('load:'.$deal_load_id.'合同任务插入注册失败');
                }

                if ($isJF === true) {
                    if (empty($order_id)) {
                        throw new \Exception("投资订单缺失，请稍后再试");
                    } else {
                        $tpo_model = new ThirdpartyOrderModel();
                        $res = $tpo_model->createOrderRecord($site_id, $order_id, $user['id'], $user['mobile'], $deal_id, $money,$deal_load_id, $bid_transfer_id);
                        if ($res == ThirdpartyOrderModel::ORDER_ALREADY_EXISTED) {
                            throw new \Exception("投资订单已经存在，请稍后重试");
                        } elseif ($res == ThirdpartyOrderModel::ORDER_CREATED_FAILED) {
                            throw new \Exception("投资订单创建失败，请稍后重试");
                        } elseif ($res != ThirdpartyOrderModel::ORDER_CREATED_SUCCESS) {
                            throw new \Exception("投资订单创建异常，请稍后重试");
                        }
                    }
                }

                //重新获取 投标信息（重要，否则无法进行下面的操作。）
                $deal = $deal_model->find($deal_id);
                //判断是否已经满标
                $is_deal_full = ($deal['deal_status'] == 2)?true:false;
                //满标操作
                if($is_deal_full){
                    $deal['deal_status'] = 2;

                    $arr_deal = $deal->getRow();//var_dump($arr_deal);
                    $state_manager = new \core\service\deal\StateManager();
                    $state_manager->setDeal($arr_deal);
                    $state_manager->work();

                    if(!empty($deal['contract_tpl_type'])){
                        $contract_function = '\core\service\DealLoadService::sendContract';
                        $contract_param = array(
                            'deal_id' => $deal_id,
                            'load_id' => 0,
                            'is_full' => true,
                            'create_time' => time(),
                        );
                        $jobsModel->priority = 123;
                        $contract_ret = $jobsModel->addJob($contract_function, array('param' => $contract_param)); //不重试
                        if ($contract_ret === false) {
                            throw new \Exception('满标合同任务插入注册失败');
                        }

                        $full_ckeck_function = '\core\service\DealLoadService::fullCheck';
                        $full_ckeck_param = array(
                            'deal_id' => $deal_id,
                        );
                        $jobsModel->priority = 122;
                        $full_check_ret = $jobsModel->addJob($full_ckeck_function, array('param' => $full_ckeck_param), get_gmtime() + 1800); //不重试
                        if ($full_check_ret === false) {
                            throw new \Exception('检测标的合同任务注册失败');
                        }
                    }

                    //更新项目信息
                    $deal_pro_service = new DealProjectService();
                    $deal_pro_service->updateProLoaned($deal['project_id']);
                }

                //用户投资次数相关，打tag
                $user_service = new UserService();
                $user_service->makeUserBidTag($user_id, $money, $coupon_id, $deal_load_id, true, NULL, true);

                //邀请码使用，邀请码可为空
                $coupon = new CouponService();
                $consume_result = $coupon->consume($deal_load_id);
                if (false === $consume_result) {
                    throw new \Exception('优惠码消费失败，请稍后再试');
                }
                $rs = $GLOBALS['db']->commit();
                if ($rs === false) {
                    throw new \Exception('投资失败，请稍后再试');
                }
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                throw $e;
            }
            $this->_finishBid($user_id, $deal, $money, $source_type, $site_id, true, $deal_load_id, false, $coupon_id);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->_finishBid($user_id, $deal, $money, $source_type, $site_id, false, false, $msg, $coupon_id);

            return array("error" => true, "msg" => $msg);
        }

        $deal_status = isset($arr_deal)?$arr_deal['deal_status']:$deal['deal_status'];

        //用户通知贷投资统计埋点
        $userProfileService = new UserProfileService();
        $userProfileService->bidProfile($user_id, $deal_id, $money);


        return array("error" => false, "load_id" => $deal_load_id, "deal_status" => $deal_status);
    }

    /**
     * 封装投资开始的方法，包括：加悲观锁、记录一条投资开始的日志
     */
    private function _startBid($user_id, $deal_id, $money, $source_type, $site_id)
    {
        \libs\utils\Monitor::add('DOBID_COMPOUND_START');

        $arr_log = array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $site_id, "start");
        Logger::info(implode(" | ", $arr_log));

        $deal_data = new DealData();
        if ($deal_data->enterPool($deal_id) === false) {
            \libs\utils\Monitor::add('DOBID_COMPOUND_FAILED_LOCKED');
            throw new \Exception("抢标人数过多，请稍后再试");
        }
    }

    /**
     * 检查用户是否可以投资该标的
     * 忽略了新手标和达人标
     * @param $coupon_id 优惠码
     */
    private function _checkCanBid($deal, $user, $money, $source_type, $coupon_id, $site_id)
    {
        $result = array();
        if ($deal['user_id'] == $user['id']) {
            throw new \Exception($GLOBALS['lang']['CANT_BID_BY_YOURSELF']);
        }
        if ($deal['is_visible'] != 1) {
            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }
        if (bccomp(floatval($deal['progess_point']), 100) != -1) {
            throw new \Exception($GLOBALS['lang']['DEAL_BID_FULL']);
        }
        if (($source_type!=\app\models\dao\DealLoad::$SOURCE_TYPE['appointment'] && floatval($deal['deal_status']) != 1) || ($source_type==\app\models\dao\DealLoad::$SOURCE_TYPE['appointment'] && !in_array($deal['deal_status'], array(0,1))) ) {
            throw new \Exception($GLOBALS['lang']['DEAL_FAILD_OPEN']);
        }
        // 定时标
        if ($deal['start_loan_time'] && $deal['start_loan_time']>get_gmtime()) {
            throw new \Exception("该项目将于" . to_date($deal['start_loan_time'], "Y-m-d H点m分") . "开始，请稍后再试");
        }
        //18岁以上投资限制
        $deal_service = new DealService();
        $age_check = $deal_service->allowedBidByCheckAge($user);
        if ($age_check['error'] == true) {
            throw new \Exception($age_check['msg']);
        }
        // 手机专享标
        $ret = $deal_service->allowedBidBySourceType($source_type, $deal['deal_crowd'], $user);
        if ($ret['error'] == true) {
            throw new \Exception($ret['msg']);
        }
        // 判断用户已经发起过投资
        $deal_already_load_money = DealLoadModel::instance()->getUserLoadMoneyByDealid($user['id'], $deal['id']);
        /* if ($deal_already_load_money) {
            throw new \Exception("不可重复投资");
        } */
        // 判断是否超过最高可投金额
        //if ($deal['max_loan_money'] > 0 && bccomp($money, $deal['max_loan_money']) == 1) {
        if (
            $deal['max_loan_money']>0
            && ($deal_already_load_money+$money)>$deal['max_loan_money']
            && ($deal['need_money_decimal']-$money)>=$deal['min_loan_money']
            || ($deal['max_loan_money']>0 && $money>=($deal['min_loan_money']+$deal['max_loan_money']))
        ){
            throw new \Exception("抱歉，当前标的最高累计投资{$deal['max_loan_money']}元");
        }
        // 判断是否低于最低可投金额
        if (bccomp($money, $deal['min_loan_money'], 2) == -1) {
            throw new \Exception("最低投资金额为{$deal['min_loan_money']}元");
        }
        //特定用户组
        if ($deal['deal_crowd'] == '2') {
            $deal_group_service = new DealGroupService();
            $group_check = $deal_group_service->checkUserDealGroup($deal['id'], $user['id']);
            if (!$group_check) {
                throw new \Exception("专享标为平台为特定用户推荐的优惠项目，只有特定用户才可以投资。");
            }
        }
        // 检查项目风险承受能力
        $deal_project_risk_service = new DealProjectRiskAssessmentService();
        $deal_project_risk_ret = $deal_project_risk_service->checkRiskBid($deal['project_id'],$user['id']);
        if (isset($deal_project_risk_ret['result']) && $deal_project_risk_ret['result'] == false){
            throw new \Exception("当前您的投资风险承受能力为 ".$deal_project_risk_ret['user_risk_assessment'].'型');
        }
        $isJF = $deal_service->isDealJF($site_id);
        //即富用户不验证用户余额
        if ($isJF !== true) {
            // 获取红包金额
            $bonus_service = new \core\service\BonusService();
            $bonus = $bonus_service->get_useable_money($user['id'], $money, true, '', '', true);
            if (bccomp($money, bcadd($user['money'], $bonus['money'], 2), 2) == 1) {
                throw new \Exception($GLOBALS['lang']['MONEY_NOT_ENOUGHT']);
            }
            $result['bonus'] = $bonus;
        }
        //判断所投的钱是否超过了剩余投标额度
        $need = bcsub($deal['borrow_amount'], $deal['load_money'], 2);
        if (bccomp($money, $need, 2) == 1) {
            throw new \Exception(sprintf($GLOBALS['lang']['DEAL_LOAN_NOT_ENOUGHT'],format_price($deal['borrow_amount'] - $deal['load_money'])));
        }
        // 最后一口
        $min_left = bcsub($deal['need_money_decimal'], $money, 2);
        if ($min_left > 0 && bccomp($min_left, $deal['min_loan_money'], 2) == -1) {
            throw new \Exception(sprintf($GLOBALS['lang']['LAST_BID_AT_ONCE_NOT_TRUE'], $deal['need_money']));
        }

        //验证优惠码有效性
        $couponService = new CouponService();
        if ($coupon_id) {
            $coupon = $couponService->queryCoupon($coupon_id, true);
            \logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $deal['id'], $user['id'], $money, $coupon_id, "coupon result", json_encode($coupon))));
            if (!empty($coupon)) {
                if (!$coupon['is_effect']) {
                    throw new \Exception("您使用的优惠码不适应此项目，请输入有效的优惠码，谢谢。");
                }
            } else {
                throw new \Exception("优惠码有误，请重新输入。");
            }
        }
        //如果存在绑定优惠码，必须填绑定的优惠码，防止修改表单 20150303
        $coupon_latest = $couponService->getCouponLatest($user['id']);
        if (!empty($coupon_latest) && $coupon_latest['is_fixed'] && $coupon_id != $coupon_latest['short_alias']) {
            throw new \Exception("您使用的优惠码不正确，请与客服联系，谢谢。");
        }

        return $result;
    }

    /**
     * 封装投资结束的方法，包括：解悲观锁、记录一条投资逻辑结束的日志、向广告联盟推送数据
     */
    private function _finishBid($user_id, $deal, $money, $source_type, $site_id, $is_succ=false, $deal_load_id=false, $err_msg=false, $coupon_id)
    {
        $deal_id = $deal['id'];
        $deal_data = new DealData();
        $deal_data->leavePool($deal_id);
        $dealService = new DealService();
        $dealService->dealEvent($user_id, $money, $coupon_id, $deal_load_id, true);
        $arr_log = array(__CLASS__, __FUNCTION__, APP, $deal_id, $user_id, $money, $site_id);
        if ($is_succ === true) {
            \libs\utils\Monitor::add('DOBID_COMPOUND_SUCCESS');
            $arr_log[] = "succ";
            $arr_log[] = $deal_load_id;
            // 广告联盟
            //$adunionDealService = new AdunionDealService();
            //$adunionDealService->triggerAdRecord($user_id, 4, $deal_id, $deal_load_id, $money);
            //投标完成 发送邮件和短信等通知  caolong 2013-12-25
            //send_tender_deal_message($deal,'tender',number_format($money, 2),$deal_load_id);
        } else {
            $arr_log[] = "fail";
            $arr_log[] = $err_msg;
        }
        Logger::info(implode(" | ", $arr_log));
    }

    /**
     * 获取扩展数据
     * @param unknown $deal_id
     */
    public function getDealCompound($deal_id)
    {
        return DealCompoundModel::instance()->findBy('`deal_id`=":deal_id"', '*', array(':deal_id' => intval($deal_id)), true);
    }

    /**
     * 批量获取扩展数据
     * @param unknown $deal_ids
     */
    public function getInfoByDealIds($deal_ids)
    {
        return DealCompoundModel::instance()->findAll("`deal_id` in (:deal_ids)", true, '*', array(':deal_ids' => implode(',', $deal_ids)));
    }

    /**
     * 根据投资记录id生产利滚利回款计划
     * @param  int         $deal_loan_id
     * @param  int         $repay_time         到账日
     * @return float|false 返回本息和或false
     */
    public function createDealCompoundLoanRepay($deal_loan_id, $repay_time)
    {
        $deal_load_model = new DealLoadModel();
        $deal_load = $deal_load_model->find($deal_loan_id);
        if (!$deal_load || $deal_load['deal_type'] != 1) {
            return false;
        }

        $deal_model = new DealModel();
        $deal = $deal_model->find($deal_load['deal_id']);

        $compound_model = new DealCompoundModel();
        $deal_compound = $compound_model->getDealCompoundByDealId($deal['id']);

        $day = ceil(($repay_time - $deal['repay_start_time'])/86400);

        $principal = $deal_load['money'];
        $interest = self::getInterestByYearRate($principal, $deal['rate'], $deal_compound['redemption_period'], $day);
        $income_interest = self::getInterestByYearRate($principal, $deal['income_fee_rate'], $deal_compound['redemption_period'], $day);
        $manage_money = bcsub($interest, $income_interest, 2);

        $deal_loan_repay = new DealLoanRepayModel();
        $row = DealLoanRepayModel::instance()->findBy("`deal_loan_id`='{$deal_loan_id}'");
        if ($row) {
            $deal_loan_repay = $row;
        }

        $deal_loan_repay->deal_id = $deal['id'];
        $deal_loan_repay->deal_repay_id = 0;
        $deal_loan_repay->deal_loan_id = $deal_loan_id;
        $deal_loan_repay->loan_user_id = $deal_load['user_id'];
        $deal_loan_repay->borrow_user_id = $deal['user_id'];
        $deal_loan_repay->status = 0;
        $deal_loan_repay->create_time = get_gmtime();
        $deal_loan_repay->update_time = get_gmtime();
        $deal_loan_repay->time = $deal['repay_start_time'] + $day*86400;

        $GLOBALS['db']->startTrans();
        try {
            // 生成回款本金
            $deal_loan_repay->money = $principal;
            $deal_loan_repay->type = 8;
            $dlr_id = $deal_loan_repay->id;
            if (!$dlr_id) {
                throw new \Exception("放款尚未完成");
            }
            $r = $deal_loan_repay->updateAll($deal_loan_repay->getRow(), "`id`='{$dlr_id}'", true);
            if (!$r) {
                throw new \Exception("update deal loan repay error");
            }

            // 生成回款利息
            $deal_loan_repay->money = $income_interest;
            $deal_loan_repay->type = 9;
            if ($deal_loan_repay->insert() === false) {
                throw new \Exception("insert deal loan repay error");
            }

            // 生成管理费
            if ($manage_money) {
                $deal_loan_repay->money = $manage_money;
                $deal_loan_repay->type = 6;
                if ($deal_loan_repay->insert() === false) {
                    throw new \Exception("insert deal loan repay error");
                }
            }

            $GLOBALS['db']->commit();

            $arr_log = array(__CLASS__, __FUNCTION__, $deal['id'], $deal_load['user_id'], $deal_loan_id, "succ");
            Logger::info(implode(" | ", $arr_log));

            return $principal + $interest;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();

            $arr_log = array(__CLASS__, __FUNCTION__, $deal['id'], $deal_load['user_id'], $deal_loan_id, "fail", $e->getMessage());
            Logger::info(implode(" | ", $arr_log));

            return false;
        }
    }

    /**
     * 根据deal_id获取已经在回款计划中的投资id，并进行回款
     * @param  int  $deal_id
     * @param  int  $time
     * @return bool
     */
    public function repayCompound($deal_id, $time = 0)
    {
        $arr_log = array(__CLASS__, __FUNCTION__, $deal_id, "start");
        Logger::info(implode(" | ", $arr_log));
        // 悲观锁
        $lockKey = __CLASS__ . "-" . __FUNCTION__ . "-" . $deal_id ;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 1200)) { // 20分钟
            return false;
        }

        $GLOBALS['db']->startTrans();
        try {
            $this->_syncRemoteData = array();
            $deal = DealModel::instance()->find($deal_id, 'id,user_id,name,deal_type');

            $loan_repay_list = DealCompoundModel::instance()->getDealCompoundLoadByDealId($deal_id, $time);
            $arr_deal_loan_id = array();
            foreach ($loan_repay_list as $loan_repay) {
                $jobs_model = new JobsModel();
                $function = '\core\service\DealCompoundService::repayCompoundJobs';
                $param = array(
                    "deal_loan_id" => $loan_repay['deal_loan_id'],
                    "time" => $time,
                );
                $jobs_model->priority = 83;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("Add Jobs Fail");
                }
                $arr_deal_loan_id[] = $loan_repay['deal_loan_id'];
            }

            $repay_money = $this->_getTotalMoneyByDealLoanIds($arr_deal_loan_id);

            // 将借款人的资金操作放在前面
            if ($this->_changeMoneyBorrowUser($deal, $repay_money, $time) === false) {
                throw new \Exception("fail to change money of borrow user");
            }

            $jobs_model = new JobsModel();
            $function = '\core\service\DealCompoundService::finishRepayCompound';
            $param = array(
                'deal_id' => $deal_id,
                'time' => $time,
                'repay_money' => $repay_money,
            );
            $jobs_model->priority = 83;
            $r = $jobs_model->addJob($function, array('param' => $param), false, 30);
            if ($r === false) {
                throw new \Exception("Add Finish Jobs Fail");
            }

            $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); // 解锁

            $arr_log = array(__CLASS__, __FUNCTION__, $deal_id, "succ");
            Logger::info(implode(" | ", $arr_log));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); // 解锁

            $arr_log = array(__CLASS__, __FUNCTION__, $deal_id, "fail", $e->getMessage());
            Logger::info(implode(" | ", $arr_log));

            return false;
        }

        return true;
    }

    public function finishRepayCompound($param) {
        $deal_id = $param['deal_id'];
        $time = $param['time'];
        $repay_money = $param['repay_money'];

        // 检查开始
        $loan_repay_list = DealCompoundModel::instance()->getDealCompoundLoadByDealId($deal_id, $time);
        if ($loan_repay_list) {
            throw new \Exception(JobsModel::ERRORMSG_NEEDDELAY, JobsModel::ERRORCODE_NEEDDELAY);
        }

        try {
            $GLOBALS['db']->startTrans();

            $deal = DealModel::instance()->find($deal_id);

            $deal->is_during_repay = 0;
            $deal->update_time = get_gmtime();
            $deal->last_repay_time = get_gmtime();
            if (!$deal->save()) {
                throw new \Exception("update deal error");
            }

            // 检查是否全部还清
            $is_finish = false;
            if ($this->_checkRepayFinish($deal_id) === true) {
                if ($deal->repayCompleted() === false) {
                    throw new \Exception("deal repay completed error");
                }
                $is_finish = true;
            }

            // 判断是否将回款记录同步到即付宝
            $jobs_model = new JobsModel();
            $param = array(
                'deal_id' => $deal_id,
                'real_time' => $time
            );
            $r = $jobs_model->addJob('\core\service\jifu\JfLoanRepayService::syncCompoundToJf', $param);
            $jobs_model->priority = 83;
            if ($r === false) {
                throw new \Exception("Add Jobs Fail");
            }

            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $time, $repay_money, "succ")));
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_id, $time, $repay_money, "fail", $e->getMessage(), $e->getLine())));
            $GLOBALS['db']->rollback();
            throw $e;
        }

        /**
         * 借款人消息通知
         * 如果是即付宝的话暂时屏蔽发送消息通知
         */
        $deal_service = new DealService();
        $isJF = $deal_service->isDealJF(false,$deal_id);

        if ($repay_money && !$isJF) {
            $obj = new GTaskService();
            $event = new DealCompoundNoticeEvent($deal['id'], $repay_money, $is_finish);
            $obj->doBackground($event, 1);
        }

        \libs\utils\Monitor::add('DEAL_REPAY_COMPOUND');
        return true;
    }

    /**
     * 更新借款人账户
     * @param array $deal
     * @param float $repay_money
     * @param int $time
     * @return bool
     */
    private function _changeMoneyBorrowUser($deal, $money, $time=0) {
        if (!$money) {
            return true;
        }

        $date = $time == 0 ? to_date(get_gmtime(), 'Y-m-d') : to_date($time, 'Y-m-d');
        $user = UserModel::instance()->find($deal['user_id']);
        $user->changeMoneyAsyn = true;
        $user->changeMoneyDealType = $deal['deal_type'];

        $bizToken = [
            'dealId' => $deal['id'],
        ];
        $rs = $user->changeMoney(-$money, "偿还本息", "编号{$deal['id']} {$deal['name']},日期{$date}", 0, 0, 0, 0, $bizToken);
        if ($rs === false) {
            return false;
        }
        return true;
    }

    /**
     * 根据投资id更新还款记录和赎回申请状态
     * @param array $arr_deal_loan_id
     * @return bool
     */
    private function _updateDealLoanStatus($arr_deal_loan_id, $time=0) {
        if (empty($arr_deal_loan_id)) {
            return true;
        }

        // 更新回款状态
        if (DealLoanRepayModel::instance()->repayCompoundByDealLoanId($arr_deal_loan_id, $time) === false) {
            return false;
        }

        // 更新赎回状态
        if (CompoundRedemptionApplyModel::instance()->updateApplyStatusByDealLoanId($arr_deal_loan_id) === false) {
            return false;
        }
    }

    /**
     * 根据投资id获取回款计划总额
     * @param array $arr_deal_loan_id
     * @return float
     */
    private function _getTotalMoneyByDealLoanIds($arr_deal_loan_id) {
        return DealLoanRepayModel::instance()->getTotalRepayMoneyByDealLoanIds($arr_deal_loan_id);
    }

    /**
     * 向借款人发送消息，包括站内信和短信
     * @param object $deal
     * @param float $repay_money 还款金额
     * @param bool $is_finish 是否已经还清
     * @return bool
     */
    public function repayNotice($deal_id, $repay_money, $is_finish = false)
    {
        $deal = DealModel::instance()->find($deal_id);
        $user = UserModel::instance()->find($deal->user_id, 'id,real_name,mobile');

        // 站内信
        $deal['share_url'] = \core\dao\DealRepayModel::instance()->getShareUrl($deal->id);
        $content = "您好，您在".app_conf("SHOP_TITLE")."的融资项目“<a href=\"".$deal['share_url']."\">".$deal['name']."</a>”成功还款" . format_price($repay_money, 0) . "元";
        if ($is_finish === true) {
            $content .= "，本融资项目已还款完毕！";
        }
        send_user_msg("", $content, 0, $user['id'], get_gmtime(), 0, true, 8);

        // 短信
        if(app_conf("SMS_ON")==1 && app_conf('SMS_SEND_REPAY') == 1){
            $notice = array(
                "site_name" => app_conf('SHOP_TITLE'),
                "real_name" => $user['real_name'],
                "repay"     => $repay_money,
            );

            /*author:liuzhenpeng, modify:系统触发短信签名, date:2015-10-28*/
            $user['site_id'] = empty($user['site_id']) ? 1 : $user['site_id'];
            // SMSSend 还款短信
            \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_DEAL_LOAD_REPAY_SMS', $notice, $user['id'], $user['site_id']);
        }

        return true;
    }

    /**
     * 检查通知贷的标的是否已经还清
     * @param  int  $deal_id
     * @return bool
     */
    private function _checkRepayFinish($deal_id)
    {
        $deal_loan_list = DealLoadModel::instance()->getDealLoanList($deal_id);
        $deal_loan_cnt = count($deal_loan_list);

        // 找出已经赎回完成的申请
        $apply_cnt = CompoundRedemptionApplyModel::instance()->getApplyCountByDealId($deal_id, CompoundRedemptionApplyModel::STATUS_DONE);
        if ($deal_loan_cnt <= $apply_cnt) {
            return true;
        } else {
            return false;
        }
    }

    public function repayCompoundJobs($param) {

        $deal_loan_id = $param['deal_loan_id'];
        $time = to_timespan(to_date($param['time'],'Y-m-d')); // 真实还款时间 转换为零点
        $timeNo8diff = strtotime(to_date($param['time'],'Y-m-d')); // 坑爹的8小时

        $deal_load = DealLoadModel::instance()->find($deal_loan_id);
        $deal = DealModel::instance()->find($deal_load['deal_id']);
        $loan_repay_list = DealLoanRepayModel::instance()->getLoanRepayListByLoanId($deal_loan_id);
        $user_model = new UserModel();
        $user = $user_model->find($deal_load['user_id'], 'id,user_name,mobile,email');
        $jfRepayMoney = 0;
        $calInfo = array();
        $user->changeMoneyDealType = $deal['deal_type'];

        try {
            $GLOBALS['db']->startTrans();

            if (!$loan_repay_list || !is_array($loan_repay_list) || count($loan_repay_list) <= 0) {
                throw new \Exception("loan repay list empty");
            }

            $arr_deal_loan = array($deal_loan_id);
            if ($this->_updateDealLoanStatus($arr_deal_loan, $time) === false) {
                throw new \Exception("update loan status error");
            }

            foreach ($loan_repay_list as $loan_repay) {
                if ($loan_repay['status'] != 0) {
                    continue;
                }
                $preTimeNoDiff = strtotime(to_date($loan_repay['time'],'Y-m-d'));

                $bizToken = [
                    'dealId' => $loan_repay['deal_id'],
                    'dealRepayId' => $loan_repay['id'],
                    'dealLoadId' => $deal_loan_id,
                ];

                if ($loan_repay['type'] == 8) {
                    if ($loan_repay['money'] != 0) {
                        $user->changeMoney($loan_repay['money'], "还本", "编号{$loan_repay['deal_id']} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                        $money_info['principal'] = $loan_repay['money'];
                        $jfRepayMoney+=$loan_repay['money'];

                        $statistics_info[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY] = $loan_repay['money'];
                        $statistics_info[UserLoanRepayStatisticsService::NOREPAY_PRINCIPAL] = -$loan_repay['money'];

                        // 考虑通知贷预期 今日已还本金增加 原计划时间的已还本金减少
                        $calInfo[$timeNo8diff][DealLoanRepayCalendarService::REPAY_PRINCIPAL] = $loan_repay['money'];
                        $calInfo[$preTimeNoDiff][DealLoanRepayCalendarService::NOREPAY_PRINCIPAL] = -$loan_repay['money'];
                    }
                } elseif ($loan_repay['type'] == 9) {
                    $user->changeMoney($loan_repay['money'], "付息", "编号{$loan_repay['deal_id']} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                    if ($loan_repay['money'] > 0) {
                        $money_info['interest'] = $loan_repay['money'];
                        $jfRepayMoney+=$loan_repay['money'];

                        $statistics_info[UserLoanRepayStatisticsService::LOAD_EARNINGS] = $loan_repay['money'];
                        $statistics_info[UserLoanRepayStatisticsService::NOREPAY_INTEREST] = -$loan_repay['money'];
                        $statistics_info[UserLoanRepayStatisticsService::LOAD_REPAY_MONEY]+= $loan_repay['money'];

                        $calInfo[$timeNo8diff][DealLoanRepayCalendarService::REPAY_INTEREST] = $loan_repay['money'];
                        $calInfo[$preTimeNoDiff][DealLoanRepayCalendarService::NOREPAY_INTEREST] = -$loan_repay['money'];
                    }
                } elseif ($loan_repay['type'] == 6) {
                    $platform_user_id = app_conf('MANAGE_FEE_USER_ID');
                    $platform_user = $user_model->find($platform_user_id);
                    $platform_user->changeMoneyAsyn = true;
                    $platform_user->changeMoneyDealType = $deal['deal_type'];
                    if (!empty($platform_user)) {
                        if ($loan_repay['money'] > 0) {
                            $platform_user->changeMoney($loan_repay['money'], "平台管理费", "编号{$loan_repay['deal_id']} {$deal['name']} 投资记录ID{$deal_loan_id}", 0, 0, 0, 0, $bizToken);
                            $manage_money = $loan_repay['money'];
                        }
                    }
                } else {
                    continue;
                }
            }

            if (!empty($statistics_info)) {
                if (UserLoanRepayStatisticsService::updateUserAssets($deal_load['user_id'], $statistics_info) === false) {
                    throw new \Exception("user loan repay statistic error");
                }
            }

            if (!empty($calInfo)) {
                foreach($calInfo as $prepayTime=>$val) {
                    if (DealLoanRepayCalendarService::collect($deal_load['user_id'],$prepayTime, $val,$prepayTime) === false) {
                        throw new \Exception("save calendar error");
                    }
                }
            }

            $repay_money = array_sum($money_info);

            if(!JfTransferService::instance()->repayTransferToJf($user,$deal_load['deal_id'],$deal_loan_id,$jfRepayMoney)) {
                throw new \Exception("JfTransferService error");
            }


            $syncRemoteData = array();
            if (bccomp($repay_money, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'DEALCOMPOUNDREPAY' . $deal_loan_id,
                    'payerId' => $loan_repay['borrow_user_id'],
                    'receiverId' => $loan_repay['loan_user_id'],
                    'repaymentAmount' => bcmul($repay_money, 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $loan_repay['deal_id'],
                );
            }
            if (bccomp($manage_money, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'DEALCOMPOUNDREPAY' . $deal_loan_id,
                    'payerId' => $loan_repay['borrow_user_id'],
                    'receiverId' => app_conf('MANAGE_FEE_USER_ID'),
                    'repaymentAmount' => bcmul($manage_money, 100),
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $loan_repay['deal_id'],
                );
            }
            if (FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH) === false) {
                throw new \Exception("sync finance queue error");
            }

            $GLOBALS['db']->commit();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_loan_id, "succ")));
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, $deal_loan_id, "fail", $e->getMessage(), $e->getLine())));
            throw $e;
        }

        if ($repay_money) {
            $money_info['repay_money'] = $repay_money;
            $money_info['manage_money'] = $manage_money;

            $obj = new GTaskService();
            $event = new DealCompoundMsgEvent($user['id'], $deal['id'], $money_info);
            $obj->doBackground($event, 1);
        }
        return true;
    }

    /**
     * 实现利滚利标的赎回后进行回款
     * @param  int  $deal_loan_id
     * @return bool
     */
    private function _repayCompoundByDealLoadId($deal_loan_id, $deal)
    {
        $loan_repay_list = DealLoanRepayModel::instance()->getLoanRepayListByLoanId($deal_loan_id);
        $user_model = new UserModel();

        $money_info = array();
        if (!$loan_repay_list || !is_array($loan_repay_list) || count($loan_repay_list) <= 0) {
            return false;
        }
        foreach ($loan_repay_list as $loan_repay) {
            if ($loan_repay['status'] != 0) {
                continue;
            }

            $user = $user_model->find($loan_repay['loan_user_id'], 'id,user_name,mobile,email');
            $user->changeMoneyAsyn = true;
            $user->changeMoneyDealType = $deal['deal_type'];

            $bizToken = [
                'dealId' => $loan_repay['deal_id'],
                'dealRepayId' => $loan_repay['id'],
                'dealLoadId' => $deal_loan_id,
            ];

            if ($loan_repay['type'] == 8) {
                if ($loan_repay['money'] != 0) {
                    $user->changeMoney($loan_repay['money'], "还本", "编号{$loan_repay['deal_id']} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                    $money_info['principal'] = $loan_repay['money'];
                }
            } elseif ($loan_repay['type'] == 9) {
                $user->changeMoney($loan_repay['money'], "付息", "编号{$loan_repay['deal_id']} {$deal['name']}", 0, 0, 0, 0, $bizToken);
                if ($loan_repay['money'] > 0) {
                    $money_info['interest'] = $loan_repay['money'];
                }
            } elseif ($loan_repay['type'] == 6) {
                $platform_user_id = app_conf('MANAGE_FEE_USER_ID');
                $platform_user = $user_model->find($platform_user_id);
                $platform_user->changeMoneyAsyn = true;
                if (!empty($platform_user)) {
                    if ($loan_repay['money'] > 0) {
                        $platform_user->changeMoney($loan_repay['money'], "平台管理费", "编号{$loan_repay['deal_id']} {$deal['name']} 投资记录ID{$deal_loan_id}", 0, 0, 0, 0, $bizToken);
                        $manage_money = $loan_repay['money'];
                    }
                }
            } else {
                continue;
            }
        }


        $repay_money = array_sum($money_info);
        $money = bcadd($manage_money, $repay_money, 2);

        if ($repay_money) {
            $money_info['repay_money'] = $repay_money;
            $money_info['manage_money'] = $manage_money;

            /*
            $obj = new GTaskService();
            $event = new DealCompoundMsgEvent($user['id'], $deal['id'], $money_info);
            $obj->doBackground($event, 1);
            */

            return $money;
        }

        return false;
    }

    /**
     * 申请赎回利滚利标
     *
     * @param $deal_load_id 订单ID
     * @param $repay_time 申请到账时间
     * @param  bool $user_id 申请赎回的前台用户ID，前台调用必填，用于校验前台用户本人操作
     * @return bool
     */
    public function redeem($deal_loan_id, $user_id) {
        $log_info = array(__CLASS__, __FUNCTION__, APP, $deal_loan_id, $user_id);
        Logger::info(implode(" | ", array_merge($log_info, array('start'))));

        //参数校验
        if (empty($deal_loan_id) || empty($user_id)) {
            Logger::info(implode(" | ", array_merge($log_info, array('传入参数错误'))));
            return false;
        }

        //获取订单信息
        $deal_load_model = new DealLoadModel();
        $deal_load = $deal_load_model->find($deal_loan_id);
        if (empty($deal_load)) {
            Logger::info(implode(" | ", array_merge($log_info, array('deal_load_id 错误'))));
            return false;
        }
        $log_info[] = json_encode($deal_load->getRow());

        //检查用户ID
        if ($deal_load['user_id'] != $user_id) {
            Logger::info(implode(" | ", array_merge($log_info, array('user_id 不符'))));
            return false;
        }

        //获取标的信息
        $deal_model = new DealModel();
        $deal = $deal_model->find($deal_load['deal_id'], 'id,user_id,deal_status,deal_type');
        if (empty($deal)) {
            Logger::info(implode(" | ", array_merge($log_info, array('deal_id 错误'))));
            return false;
        }
        $log_info[] = json_encode($deal->getRow());

        //检查订单状态
        if (!$this->isRedeemable($deal)) {
            Logger::info(implode(" | ", array_merge($log_info, array('订单不可赎回'))));
            return false;
        }

        //赎回到账时间
        $repay_time = $this->getLatestRepayDay($deal['id']);
        $repay_time = $repay_time['repay_time'];
        $log_info[] = $repay_time;

        // 悲观锁，以id为锁的键名
        $lockKey = "DealCompoundRedemptionService_redeem_" . $deal_loan_id;
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 300)) {
            return false;
        }
        $GLOBALS['db']->startTrans();

        try {
            //检查是否存在赎回申请
            $compoundRedemptionApplyModel = new CompoundRedemptionApplyModel();
            $compoundRedemptionApplyModel = $compoundRedemptionApplyModel->getApplyByDealLoanId($deal_loan_id);
            if (!empty($compoundRedemptionApplyModel)) {
                throw new \Exception('已存在赎回申请记录');
            }

            //新增赎回申请
            $compoundRedemptionApply = new CompoundRedemptionApplyModel();
            $rs = $compoundRedemptionApply->saveApply($deal_load, $repay_time);
            $log_info[] = json_encode($compoundRedemptionApply->getRow());
            if (empty($rs)) {
                throw new \Exception('新增赎回申请记录失败');
            }

            //新增回款计划记录
            $rs = $this->createDealCompoundLoanRepay($deal_load['id'], $repay_time);
            $sum = $rs;
            if (empty($rs)) {
                throw new \Exception('新增回款计划记录失败');
            }

            //优惠码处理
            $coupon_log_service = new CouponLogService();
            $rs = $coupon_log_service->redeem($deal_load['id']);
            if (empty($rs)) {
                throw new \Exception('处理优惠码返利记录失败');
            }

            O2OService::triggerO2OOrder($user_id, CouponGroupEnum::TRIGGER_REPEAT_DOBID, $deal_load['id']);

            $money_info = array(
                UserLoanRepayStatisticsService::NOREPAY_INTEREST => bcsub($sum, $deal_load['money'], 2),
            );
            if (UserLoanRepayStatisticsService::updateUserAssets($user_id,$money_info) === false) {
                throw new \Exception('更新资产总额失败');
            }

            // 回款日历开始
            $calInfo = array(
                DealLoanRepayCalendarService::NOREPAY_PRINCIPAL => $deal_load['money'],
                DealLoanRepayCalendarService::NOREPAY_INTEREST => bcsub($sum, $deal_load['money'], 2),
            );
            if (!DealLoanRepayCalendarService::collect($user_id,strtotime(to_date($repay_time,'Y-m-d')),$calInfo)) {
                throw new \Exception('更新回款日历失败');
            }

            $rs = $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey); //正常结束，解锁
            Logger::info(implode(" | ", array_merge($log_info, array("success:{$rs}"))));
            return $rs;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey); //异常结束，解锁
            Logger::info(implode(" | ", array_merge($log_info, array("exception:" . $e->getMessage()))));
            return false;
        }
    }

    /**
     * 标的还款计划
     * @param $deal_id
     * @return array
     */
    public function getRepaySchedule($deal_id) {
        $repay_day_start = to_timespan(date('Y-m-d')) + 86400; // 统计起始日，第二天

        $deal = DealModel::instance()->find($deal_id);
        $deal_compound = DealCompoundModel::instance()->getDealCompoundByDealId($deal_id);
        $repay_day_end = $repay_day_start + ($deal_compound['redemption_period'] - 1) * 86400; //赎回周期-1的日期
        $last_repay_day = $deal['repay_start_time'] + $deal['repay_time'] * 86400; // 最晚回款日
        $repay_day_end = min($repay_day_end, $last_repay_day); // 统计截止日

        //修复最后一天显示不出来的问题。
        if(($repay_day_end - $repay_day_start) < ($deal_compound['redemption_period'] - 1) * 86400){
            $repay_day_end += 1;
        }

        $compound_redemption_apply_model = new CompoundRedemptionApplyModel();
        $apply_stat_list = $compound_redemption_apply_model->getRepayScheduleByDealId($deal_id, $repay_day_start, $repay_day_end);
        $result = array();
        for ($repay_day = $repay_day_start; $repay_day < $repay_day_end; $repay_day += 86400) {
            $repay_day_str = to_date($repay_day, $format = 'Y-m-d');
            $repay_money = (empty($apply_stat_list) || empty($apply_stat_list[$repay_day_str])) ? 0 : $apply_stat_list[$repay_day_str];
            $result[] = array('day' => $repay_day_str, 'money' => $repay_money);
        }
        return $result;
    }

    /**
     * 校验标是否可赎回
     *
     * @param $deal
     * @return bool
     */
    public function isRedeemable($deal) {
        return $deal['deal_status'] == '4' && $deal['deal_type'] == '1';
    }

    /**
     * 判断一个时间是否节假日
     * @param  string  $date
     * @return boolean
     */
    public function checkIsHoliday($date)
    {
        \FP::import("libs.common.dict");
        $holidays = \dict::get('REDEEM_HOLIDAYS');
        if (empty($date) || empty($holidays) || !is_array($holidays)) {
            return false;
        }

        return in_array($date, $holidays);
    }

    public function getDelayList($start = 0, $limit = 20)
    {
        $model_deal_loan_repay = new DealLoanRepayModel();
        $list = $model_deal_loan_repay->getLGLDelayList($start, $limit);

        return $list;
    }

    public function getDelayCount()
    {
        $model_deal_loan_repay = new DealLoanRepayModel();
        $cnt = $model_deal_loan_repay->getLGLDelayCount();
        if ($cnt >= 0) {
            return $cnt;
        }

        return 0;

    }

    /**
     * 获取下个还款日
     * @return number
     */
    public function getNextRepayDate()
    {
        $time_gmt = to_timespan(format_date(strtotime('+1 day'), 'Y-m-d'));
        $condition = "`status` = 0 AND `type` IN (8,9) AND `time` = '%d'";
        do {
            /*$have_repay = DealLoanRepayModel::instance()->findByViaSlave(sprintf($condition, $time_gmt));
            if ($have_repay || !$this->checkIsHoliday(to_date($time_gmt, 'Y-m-d'))) {
                break;
            }*/
            // 这里太耗性能了，改掉
            if (!$this->checkIsHoliday(to_date($time_gmt, 'Y-m-d'))) {
                break;
            }
            $time_gmt += 86400;
        } while (true);
        return $time_gmt;
    }

    /**
     * 获取截止到某个时间 金额不足还款的借款人id
     * @param unknown $time
     */
    public function getMoneyLessBorrower($time)
    {
        $user_list = DealCompoundModel::instance()->getMoneyLessBorrower(intval($time));
        $res = array();
        if ($user_list) {
            foreach ($user_list as $user) {
                $res[$user['id']] = $user;
            }
        }
        return $res;
    }
}
