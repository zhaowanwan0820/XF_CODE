<?php
/**
 * DealLoanRepay class file.
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/

namespace core\dao\repay;

use core\dao\BaseModel;
use core\dao\deal\DealModel;
use core\dao\jobs\JobsModel;
use core\enum\AccountEnum;
use core\enum\DealEnum;
use core\enum\DealLoanRepayCalendarEnum;
use core\enum\DealLoanRepayEnum;
use core\enum\JobsEnum;
use core\dao\deal\DealLoadModel;
use core\enum\MsgBoxEnum;
use core\enum\PartialRepayEnum;
use core\enum\UserAccountEnum;
use core\enum\UserEnum;
use core\enum\UserLoanRepayStatisticsEnum;
use core\service\account\AccountService;
use core\service\creditloan\CreditLoanService;
use core\service\deal\DealLoanRepayCalendarService;
use core\service\deal\DealService;
use core\service\email\SendEmailService;
use core\service\msgbox\MsgboxService;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\user\UserService;
use libs\db\Db;
use NCFGroup\Common\Library\Idworker;
use libs\utils\Logger;
use core\service\repay\DealPartRepayService;

/**
 * 还款记录,每当满标后进行放款时生成一系列回款记录，也即回款计划
 * @author 王一鸣 <wangyiming@ucfgroup.com>
 **/
class DealLoanRepayModel extends BaseModel {

    const SPLIT_FIELD_DEAL = 'deal_id';
    const SPLIT_FIELD_LOANID = 'deal_loan_id';
    const SPLIT_FIELD_REPAYID = 'deal_repay_id';

    const DB_SOURCE_MOVED = 'history';//冷库
    const DB_SOURCE_HOT = 'hot'; // 热库

    static $splitFields = array(
        self::SPLIT_FIELD_DEAL,
        self::SPLIT_FIELD_LOANID,
        self::SPLIT_FIELD_REPAYID,
    );

    public $is_history_db = false;
    /**
     * 根据投资ID获取用户某一期还款的待还列表
     * @param $userId
     * @param $repayId
     * @param $dealLoanId
     * @return \libs\db\Model
     */
    public function getUserNoRepayList($userId,$repayId,$dealLoanId){
        $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND status = 0";
        $condition = sprintf($condition, $this->escape($repayId), $this->escape($dealLoanId), $this->escape($userId));
        return $this->findAll($condition);
    }

    /**
     * 根据标的id 和 用户id 获取未还款本金
     */
    public function getDealUnpaiedPrincipalByDealIdAndUserId($dealId, $userId) {
        $condition = "`deal_id`=:deal_id AND loan_user_id = :user_id AND `status`='0' AND type = :dealType ORDER BY id ASC";
        return $this->findAll($condition, false, '*', array(
            ':deal_id' => $dealId,
            ':user_id' => $userId,
            ':dealType' => DealLoanRepayEnum::MONEY_PRINCIPAL,
        ));
    }


    public function getDealUnpaiedPrincipalTotalByDealIdAndUserId($dealId, $userId) {
        $list = $this->getDealUnpaiedPrincipalByDealIdAndUserId($dealId, $userId);
        $sum = 0;
        if (is_array($list)) {
            foreach ($list as $data) {
                $sum = bcadd($sum, $data['money'], 2);
            }
        }
        return $sum;
    }


    public function getRepayCountByDealRepayId($deal_repay_id)
    {
        $sql = sprintf("SELECT count(*) AS `all` FROM %s WHERE `deal_repay_id` = '%d' AND `status`=0", $this->tableName(), $this->escape($deal_repay_id));
        $query_ret = $this->db->getOne($sql);
        if ($query_ret === false) {
            throw new \Exception("获取还款数量失败");
        }
        return $query_ret;
    }


    /**
     * 获取等额本息还款方式的最后一期本金
     * @param array $deal_loan
     * @param object $deal
     * @return false|float
     */
    public function getFixPrincipalByLoanId($deal_loan, $deal) {
        $repay_times = $deal->getRepayTimes();
        $sql = "SELECT COUNT(`id`) AS `c`, SUM(`money`) AS `m` FROM %s WHERE `deal_loan_id` = '%d' AND `type`='%d'";
        $sql = sprintf($sql, $this->tableName(), $deal_loan['id'], DealLoanRepayEnum::MONEY_PRINCIPAL);
        $row = $this->findBySql($sql);
        $cnt = $row['c'];
        if ($repay_times != $cnt+1) {
            return false;
        } else {
            return bcsub($deal_loan['money'], $row['m'], 2);
        }
    }

    /**
     * 根据还款id执行正常还款操作
     * @param $deal_repay object
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @param $next_repay_id int|bool 下次还款id false-若为最后一期
     * @return array("total_overdue"=>$totla_overdue) 返回扩展数据，目前只返回逾期罚息金额
     */
    public function repayDealLoan($deal_repay_id, $next_repay_id, $repay_user_id = false,$repayAccountType=0, $orderId = 0){
        $deal_repay_model = new DealRepayModel();
        $dealService = new DealService();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal_id = intval($deal_repay->deal_id);
        $deal = DealModel::instance()->find($deal_id);

        $deal_service = new DealService();
        $is_dtv3 = $deal_service->isDealDTV3($deal_id);

        $GLOBALS['db']->startTrans();
        try {
            if ($is_dtv3 === true) {
                $ydt_user_id = app_conf('DT_YDT');
                $jobs_model = new JobsModel();
                $function = '\core\dao\repay\DealLoanRepayModel::repayDealLoanOne';
                $param = array(
                    'deal_repay_id' => $deal_repay_id,
                    'deal_loan_money' => $deal['borrow_amount'],
                    'deal_loan_id' => 0,
                    'deal_loan_user_id' => $ydt_user_id,
                    'next_repay_id' => $next_repay_id,
                );
                $jobs_model->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
                $r = $jobs_model->addJob($function, array('param' => $param));
                if ($r === false) {
                    throw new \Exception("add prepay by loan id jobs error");
                }
            } else {
                // 变更出借人账户
                $deal_loan_model = new DealLoadModel();
                //根据借款ID获取所有投标记录信息
                $deal_loan_list = $deal_loan_model->getDealLoanList($deal_id);

                $total_overdue = 0;
                foreach ($deal_loan_list as $deal_loan) {
                    //插入队列执行
                    $jobs_model = new JobsModel();
                    $function = '\core\dao\repay\DealLoanRepayModel::repayDealLoanOne';
                    $param = array(
                        'deal_repay_id' => $deal_repay_id,
                        'deal_loan_money' => $deal_loan->money,
                        'deal_loan_id' => $deal_loan->id,
                        'deal_loan_user_id' => $deal_loan->user_id,
                        'next_repay_id' => $next_repay_id,
                        'repayAccountType' => $repayAccountType,
                        'orderId' => $orderId,
                    );
                    $jobs_model->priority = JobsEnum::PRIORITY_REPAY_DEAL_LOAN;
                    $r = $jobs_model->addJob($function, array('param' => $param));
                    if ($r === false) {
                        throw new \Exception("add prepay by loan id jobs error");
                    }
                    $deal_loan_money = $deal_loan->money;
                }
            }
            $rs = $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return true;
    }


    /**
     * 根据dealId获取所有收益表中的已获总收益
     * @param int $dealId 标Id
     * @param int $status  状态
     * @return float
     */
    public function getPayedEarnMoneyByDealId($dealId , $status) {
        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id` = %d AND `status` = %d AND `type` IN (2,4,5,7)";
        $sql = sprintf($sql, $this->tableName(), $dealId ,$status);
        // 已还清要从备份库
        if ($status == DealLoanRepayEnum::STATUS_ISPAYED){
            $mergeArr = $this->getMergeDataFromDiffDb('findBySql',array($sql, array(), true));
            $result['sum'] = bcadd($mergeArr['res1']['sum'],$mergeArr['res2']['sum'],2);
        }else{
            $result = $this->findBySql($sql, array(), true);
        }


        return $result['sum'];
    }

    /**
     * @param $deal_repay_id 还款id
     * @param $deal_loan_money 还款金额
     * @param $deal_loan_id  还款ID
     * @param $deal_loan_user_id 还款用户ID
     * @param bool $ignore_impose_money
     * @return float
     * @throws \Exception
     */
    public function repayDealLoanOne($param) {

        $deal_repay_id = $param['deal_repay_id'];
        $deal_loan_money = $param['deal_loan_money'];
        $deal_loan_id = $param['deal_loan_id'];
        $deal_loan_user_id = $param['deal_loan_user_id'];
        $next_repay_id = $param['next_repay_id'];
        $repayAccountType = $param['repayAccountType'];
        $orderId = $param['orderId'];
        $deal_service = new DealService();

        $moneyInfo = array();
        $calInfo = array();

        $realTime = to_timespan(to_date(get_gmtime(),'Y-m-d'));// 提前还款的真实时间是 今日零点


        $deal_repay_model = new DealRepayModel();
        $deal_repay = $deal_repay_model->find($deal_repay_id);
        $deal_id = intval($deal_repay->deal_id);
        $deal_repay_id = intval($deal_repay->id);
        $deal = DealModel::instance()->find($deal_id);

        // 如果标的属于智多鑫，只更改本金还款状态，不操作账户变更，不变更还款日历，不变更资产总额；利息回款到利息账户
        $isDT = $deal_service->isDealDT($deal_id);
        $isDTV3 = $deal_service->isDealDTV3($deal_id);

        $isPartRepayDealND = $deal_service->isPartRepayDealND($deal_id,$repayAccountType);

        $user = UserService::getUserById($deal_loan_user_id);

        if(!$user){
            throw new \Exception('未获取到用户信息 userId:'.$deal_loan_user_id);
        }

        $credit_loan_service = new CreditLoanService();

        //TODO 走接口
        if(bccomp($deal_repay->principal,'0.00',2) > 0){
            $isNeedFreeze = $credit_loan_service->isNeedFreeze($deal,$deal_loan_user_id,$deal_repay_id,1);
        }else{
            $isNeedFreeze = false; // 还款本金为0时不请求速贷
        }

        $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND status = 0";
        $condition = sprintf($condition, $this->escape($deal_repay_id), $this->escape($deal_loan_id), $this->escape($user['id']));
        //根据还款记录ID，投标记录ID，投资人ID
        $loan_repay_list = $this->findAll($condition);

        if(empty($loan_repay_list)){
            return true;
        }

        // 开始给一个用户还款
        $GLOBALS['db']->startTrans();
        try {
            $repayMoney = 0;

            // 部分还款逻辑
            $partRepayMoneyList = [];
            $isPartRepay = false;
            if ($orderId) {
                $partRepayInfo = DealPartRepayService::getPartRepayMoneyByOrderId($orderId);
                $partRepayType = $partRepayInfo['partRepayType'];
                if ($partRepayType == DealPartRepayService::REPAY_TYPE_PART) {
                    // 由于deal_repay已经更新，part_repay_money字段已经加上当期的部分还款值，所以要减掉再计算
                    $deal_repay->part_repay_money = bcsub($deal_repay->part_repay_money, $partRepayInfo['totalRepayMoney'], 2);
                    $partRepayMoneyInfo = DealPartRepayService::getPartRepayMoney($deal_repay, $partRepayInfo['partRepayMoneyOrg']);
                    $partRepayMoneyList = DealPartRepayService::getPartRepayInfo(
                        $partRepayMoneyInfo['repayMoneyWithoutFee'],
                        $partRepayMoneyInfo['needToRepayInterest'],
                        $partRepayMoneyInfo['needToRepayPrincipal'],
                        $loan_repay_list, "{$orderId}_{$deal_loan_id}");

                    $isPartRepay = true;
                }
            }

            foreach ($loan_repay_list as $loan_repay) {

                if ($isPartRepay) {
                    if (!isset($partRepayMoneyList[$loan_repay['id']])) {
                        continue;
                    } else {
                        // 拆分记录
                        DealPartRepayService::partRepayDealLoanOne($loan_repay, $partRepayMoneyList[$loan_repay['id']]); // 插入一条新的记录
                        $loan_repay->money = $loan_repay['money'] = $partRepayMoneyList[$loan_repay['id']];
                    }
                }

                $loan_repay->real_time = $realTime;
                $loan_repay->update_time = get_gmtime();
                $loan_repay->status = DealLoanRepayEnum::STATUS_ISPAYED;
                if ($loan_repay->save() === false) {
                    throw new \Exception("变更{$loan_repay->id}回款记录状态失败");
                }


                // 暂时屏蔽双账户 智多鑫v3仍然使用 投资户
                $tmpAccId = $isDTV3 ? UserAccountEnum::ACCOUNT_MANAGEMENT : UserAccountEnum::ACCOUNT_INVESTMENT;
               // $accountId = AccountService::getUserAccountId($deal_loan_user_id,$tmpAccId);
                $accountId = $deal_loan_user_id;

                if(!$accountId && !$isDT){
                    throw new \Exception("未获取到账户ID userId:{$deal_loan_user_id}");
                }
                $bizToken = array('dealId' => $deal_id,'dealRepayId' => $deal_repay_id,'dealLoadId' => $loan_repay['deal_loan_id']);
                switch ($loan_repay['type']) {
                    //本金
                    case DealLoanRepayEnum::MONEY_PRINCIPAL :
                        if ($loan_repay['money'] != 0) {
                            if ($isDT === true) {
                                break;
                            }

                            if ($isPartRepayDealND === true) {
                                 $this->addNDRepayMoneyLog($deal_repay_id,$loan_repay['deal_loan_id'],"还本","编号{$deal_id} {$deal['name']}",$user,PartialRepayEnum::FEE_TYPE_PRINCIPAL,$bizToken);
                            } else {

                                if (!AccountService::changeMoney($accountId,$loan_repay['money'], "还本", "编号{$deal_id} {$deal['name']}",AccountEnum::MONEY_TYPE_INCR, false,true, 0,$bizToken)) {
                                    throw new \Exception('还款失败-投资人人账户余额更新失败 userId:'.$deal_loan_user_id);
                                }
                            }
                            $repayMoney += $loan_repay['money'];

                            $calInfo[$realTime][DealLoanRepayCalendarEnum::REPAY_PRINCIPAL] = $loan_repay['money']; // 真实还款日期本金增加

                            if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL])){
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL] = 0;
                            }
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL] -= $loan_repay['money']; // 原有日期本金减少


                            if($credit_loan_service->isCreditingUser($user['id'],$deal_id)){
                                if (!AccountService::changeMoney($accountId,$loan_repay['money'], "贷款冻结", '冻结 "' . $deal['name'] .'" 投资本金',AccountEnum::MONEY_TYPE_LOCK, false, true, 0,$bizToken)) {
                                    throw new \Exception('还款失败-投资人人账户余额更新失败 userId:'.$deal_loan_user_id);
                                }
                            }elseif($isNeedFreeze === true){
                               //如果用户发生过借款 冻结用户本金  $credit_loan_service */
                                if (!AccountService::changeMoney($accountId,$loan_repay['money'], "网信速贷还款冻结", '冻结 "' . $deal['name'] .'" 投资本金',AccountEnum::MONEY_TYPE_LOCK, false, true, 0,$bizToken)) {
                                    throw new \Exception('还款失败-投资人人账户余额更新失败 userId:'.$deal_loan_user_id);
                                }

                                $credit_loan_service->freezeNotifyCreditloan($user['id'],$deal_id,$deal_repay_id,1);
                            }

                            if (!isset($moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL])) {
                                $moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL] = 0;
                            }
                            if (!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL])) {
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL] = 0;
                            }

                            $moneyInfo[UserLoanRepayStatisticsEnum::LOAD_REPAY_MONEY] = $loan_repay['money'];
                            $moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL] = -$loan_repay['money'];

                            if (!isset($moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_PRINCIPAL])) {
                                $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_PRINCIPAL] = 0;
                            }
                            $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_PRINCIPAL] = -$loan_repay['money'];
                        }
                        break;
                    //利息
                    case DealLoanRepayEnum::MONEY_INTREST :
                        if ($isDT === true) {
                            break;
                        } else {
                            if($isPartRepayDealND === true) {
                                $this->addNDRepayMoneyLog($deal_repay_id,$loan_repay['deal_loan_id'],"付息","编号{$deal_id} {$deal['name']}",$user,PartialRepayEnum::FEE_TYPE_INTEREST,$bizToken);
                            } else {
                                if (!AccountService::changeMoney($accountId,$loan_repay['money'], "付息", "编号{$deal_id} {$deal['name']}",AccountEnum::MONEY_TYPE_INCR, false, true, 0,$bizToken)) {
                                    throw new \Exception('还款失败-投资人人账户余额更新失败 userId:'.$deal_loan_user_id);
                                }
                            }

                            // 智多鑫标的不变更回款日历
                            if(!isset($calInfo[$realTime][DealLoanRepayCalendarEnum::REPAY_INTEREST])) {
                                $calInfo[$realTime][DealLoanRepayCalendarEnum::REPAY_INTEREST] = 0;
                            }
                            if(!isset($calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_INTEREST])) {
                                $calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_INTEREST] = 0;
                            }
                            $calInfo[$realTime][DealLoanRepayCalendarEnum::REPAY_INTEREST] += $loan_repay['money']; // 真实还款日期利息增加
                            $calInfo[$loan_repay->time][DealLoanRepayCalendarEnum::NOREPAY_INTEREST]-=$loan_repay['money']; // 原还款日期本金减少
                        }
                        $repayMoney+=$loan_repay['money'];

                        $moneyInfo[UserLoanRepayStatisticsEnum::LOAD_EARNINGS] = $loan_repay['money'];
                        $moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_INTEREST] = -$loan_repay['money'];

                        if(!isset($moneyInfo[UserLoanRepayStatisticsEnum::LOAD_REPAY_MONEY])) {
                            $moneyInfo[UserLoanRepayStatisticsEnum::LOAD_REPAY_MONEY] = 0;
                        }
                        $moneyInfo[UserLoanRepayStatisticsEnum::LOAD_REPAY_MONEY] += $loan_repay['money'];

                        if(!isset($moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_EARNINGS])) {
                            $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_EARNINGS] = 0;
                        }
                        $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_EARNINGS] = -$loan_repay['money'];
                        if(!isset($moneyInfo[UserLoanRepayStatisticsEnum::CG_TOTAL_EARNINGS])) {
                            $moneyInfo[UserLoanRepayStatisticsEnum::CG_TOTAL_EARNINGS] = 0;
                        }
                        $moneyInfo[UserLoanRepayStatisticsEnum::CG_TOTAL_EARNINGS] = + $loan_repay['money'];
                        break;

                    //管理费
                    case DealLoanRepayEnum::MONEY_MANAGE :
                        if($isPartRepayDealND === true) {
                            break;
                        }
                        // 出借人平台管理费转入平台账户
                        $platform_user_id = app_conf('MANAGE_FEE_USER_ID');
                        $platform_user = UserService::getUserById($platform_user_id);

                        if (!empty($platform_user) && $loan_repay['money'] != 0) {
                            $log_note = "编号{$deal_id} {$deal['name']} 投资记录ID{$loan_repay['deal_loan_id']}";

                            $platFromAccountId = AccountService::getUserAccountId($platform_user_id,UserAccountEnum::ACCOUNT_PLATFORM);
                            if (!AccountService::changeMoney($platFromAccountId,$loan_repay['money'], "平台管理费", $log_note,AccountEnum::MONEY_TYPE_INCR, false, true, 0,$bizToken)) {
                                throw new \Exception('还款失败-投资人人账户余额更新失败 userId:'.$deal_loan_user_id);
                            }
                        }
                        break;
                }
            }

            if(!empty($moneyInfo)) {
                if (UserLoanRepayStatisticsService::updateUserAssets($deal_loan_user_id,$moneyInfo) === false) {
                    throw new \Exception("user loan repay statistics error");
                }
            }

            if (!empty($calInfo)) {
                foreach($calInfo as $key=>$cinfo) {
                    $time = strtotime(to_date($key)); // 转为无差别时间
                    if (DealLoanRepayCalendarService::collect($deal_loan_user_id,$time,$cinfo,$time) === false) {
                        throw new \Exception("collect calendar error");
                    }
                }
            }

            $jobs_model = new JobsModel();
            //TODO 判断是否使用了加息券
            $rs = $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw new \Exception($e->getMessage());
        }
        if ($rs === false) {
            throw new \Exception("事务提交失败");
        }
        return true;
    }


    /*
    * 获取单个标的生成的利息回款计划的条数
    * @param int $deal_id
    * @param int $type
    * @return int
    */
    public function getCountByDealId($deal_id, $type) {
        $condition = "`deal_id`=':deal_id' AND `type`=':type'";
        $params = array(
            ':deal_id' => $deal_id,
            ':type' => $type,
        );
        return $this->count($condition, $params);
    }

    /**
     * 根据标的id获取回款计划的总和
     * @param int $deal_id
     * @return array
     */
    public function getSumByDealId($deal_id) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `deal_repay_id` FROM " . $this->tableName() . " WHERE `deal_id`=':deal_id' GROUP BY `type`, `deal_repay_id`";
        $param = array(
            ':deal_id' => $deal_id,
        );
        $sum = $this->findAllBySql($sql, true, $param);

        $result = array();

        foreach ($sum as $k => $v) {
            if ($v['type'] == DealLoanRepayEnum::MONEY_PRINCIPAL) {
                $result[$v['deal_repay_id']]['principal'] += $v['m'];
            } elseif ($v['type'] == DealLoanRepayEnum::MONEY_INTREST) {
                $result[$v['deal_repay_id']]['interest'] += $v['m'];
            }
        }

        return $result;
    }

    /**
     * 获取单笔订单的已经投资的总额
     * @param $deal_id int
     * @return float 已经投资总额
     */
    public function getTotalPrincipalMoney($deal_id) {
        return $this->getTotalMoneyByTypeDealId(DealLoanRepayEnum::MONEY_PRINCIPAL, $deal_id);
    }

    /**
     * 根据订单id和金额类别获取总金额
     * @param $type int 金额类别
     * @param $deal_id int|bool 订单id
     * @return float 总金额
     */
    private function getTotalMoneyByTypeDealId($type, $deal_id) {


        $sql = "SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `type`='{$type}' AND `status`='" . DealLoanRepayEnum::STATUS_ISPAYED . "'";

        $sql .= " AND `deal_id`='{$deal_id}'";
        return  $this->getDataBySepcFields('getOne',array($sql),array('deal_id' => $deal_id ));

    }

    public function getTotalMoneyByTypeStatusLoanId($deal_loan_id, $type, $status,$deal_id=0) {
        $deal_loan_id = intval($deal_loan_id);
        $type = intval($type);

        $sql = "SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_loan_id`='%d' AND `type`='%d' AND `status`='%d'";
        if($deal_id){
            $sql.=" AND deal_id=".$deal_id;
        }
        $sql = sprintf($sql, $this->tableName(), $deal_loan_id, $type,$status);
        if ($status == DealLoanRepayEnum::STATUS_ISPAYED){
            $res = $this->getDataBySepcFields('findBySql',array($sql,array(),true),array('deal_loan_id' => $deal_loan_id));
        }else {
            $res = $this->findBySql($sql, array(), true);
        }
        return $res['sum'];
    }
    /**
     * 获取单笔投资收益总额，既违约金+利息+提前还款利息+提前还款补偿金
     * @param $deal_loan_id int 投资id
     * @return float 受益总额
     */
    public function getTotalIncomeMoney($deal_loan_id) {
        return $this->getTotalImposeMoney($deal_loan_id)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, DealLoanRepayEnum::MONEY_INTREST)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, DealLoanRepayEnum::MONEY_PREPAY_INTREST)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, DealLoanRepayEnum::MONEY_COMPENSATION)
        + $this->getTotalMoneyByTypeLoanId($deal_loan_id, DealLoanRepayEnum::MONEY_COMPOUND_INTEREST);
    }
    /**
     * 获取单笔投资违约金总额
     * @param $deal_loan_id int 投资id
     * @return float 违约金总额
     */
    public function getTotalImposeMoney($deal_loan_id) {
        return $this->getTotalMoneyByTypeLoanId($deal_loan_id, DealLoanRepayEnum::MONEY_IMPOSE);
    }
    /**
     * 根据投资id和金额类别获取总金额
     * @param $deal_loan_id int 投资id
     * @param $type int 金额类别
     * @param $is_payed int 是否已还
     * @return float 总金额
     */
    public function getTotalMoneyByTypeLoanId($deal_loan_id, $type, $is_payed = 1) {
        $sql = "SELECT SUM(`money`) FROM " . $this->tableName() . " WHERE `deal_loan_id`='{$deal_loan_id}' AND `type`='{$type}'";
        if ($is_payed == 1) {
            $sql .= " AND `status`='" . DealLoanRepayEnum::STATUS_ISPAYED . "'";
        }

        return $this->getDataBySepcFields('countBySql',array($sql,array(),true),array('deal_loan_id' => $deal_loan_id));
        //return $this->db->get_slave()->getOne($sql);
    }
    /**
     * 根据投资id获取回款列表
     * @param $deal_loan_id int
     * @return array("count"=>$count, "list"=>$list)
     */
    public function getLoanRepayListByLoanId($deal_loan_id)
    {
        $deal_loan_id = intval($deal_loan_id);
        $condition = "`deal_loan_id`='%d' AND `time`!='0' ORDER BY `time`";
        $condition = sprintf($condition, $this->escape($deal_loan_id));

        //$list = $this->findAll($condition);

        $list = $this->getDataBySepcFields("findAll", array($condition), array('deal_loan_id' => $deal_loan_id));

        if (!$list) {
            return false;
        }

        foreach ($list as $key => &$item) {
            if ($item['type'] == DealLoanRepayEnum::MONEY_MANAGE || $item['money'] == 0) {
                unset($list[$key]);
                continue;
            }
            $item['money_type'] = self::getLoanRepayType($item['type']);
            $item['money_status'] = self::getLoanRepayStatus($item['status']);
            $item['is_delay'] = $item['real_time'] > 0 && to_date($item['real_time'], "Y-m-d") > to_date($item['time']);
        }
        return $list;
    }

    /**
     * 根据回款类型获取回款类型文案
     * @param $type int
     * @return $money_type string
     */
    public static function getLoanRepayType($type) {
        switch ($type) {
            case DealLoanRepayEnum::MONEY_PRINCIPAL: $money_type = "本金";break;
            case DealLoanRepayEnum::MONEY_INTREST: $money_type = "利息";break;
            case DealLoanRepayEnum::MONEY_PREPAY: $money_type = "提前还款本金";break;
            case DealLoanRepayEnum::MONEY_COMPENSATION: $money_type = "提前还款补偿金";break;
            case DealLoanRepayEnum::MONEY_IMPOSE: $money_type = "逾期罚息";break;
            case DealLoanRepayEnum::MONEY_MANAGE: $money_type = "投资管理费";break;
            case DealLoanRepayEnum::MONEY_PREPAY_INTREST : $money_type = "提前还款利息";break;
            default : $money_type = false;
        }
        return $money_type;
    }

    /**
     * 根据回款状态获取回款状态文案
     * @param $status int
     * @return $money_status string
     */
    public static function getLoanRepayStatus($status) {
        switch ($status) {
            case DealLoanRepayEnum::STATUS_NOTPAYED: $money_status = "未还";break;
            case DealLoanRepayEnum::STATUS_ISPAYED: $money_status = "已还";break;
            case DealLoanRepayEnum::STATUS_CANCEL: $money_status = "因提前还款而取消";break;
            default : $money_status = false;
        }
        return $money_status;
    }

    /**
     * 根据投资id和金额类别获取总金额
     * @param $deal_loan_ids array 投资id
     * @param $types array 金额类别
     * @param $is_payed int 是否已还
     * @return array 总金额
     * @author longbo
     */
    public function getTotalMoneyByTypesAndLoanIds($deal_loan_ids = array(), $types = array(), $is_payed = 1) {
        if (!empty($deal_loan_ids) && is_array($deal_loan_ids)) {
            $ids_str = implode(',', $deal_loan_ids);
        } else {
            return array();
        }

        if (!empty($types) && is_array($types)) {
            $type_str = implode(',', $types);
            $type_where = ' AND `type` IN (' . $type_str . ')';
        }
        $sql = "SELECT deal_loan_id, SUM(`money`) as m FROM " . $this->tableName() . " WHERE `deal_loan_id` IN (" . $ids_str . ")" . $type_where;
        if ($is_payed == 1) {
            $sql .= " AND `status`='" . DealLoanRepayEnum::STATUS_ISPAYED . "'";
        }

        $sql .= " GROUP BY deal_loan_id ";
        $mergeArr = $this->getMergeDataFromDiffDb('findAllBySql',array($sql,false,array(),true));

        $res2_arr = array();
        if (!empty($mergeArr['res2'])){
            foreach ($mergeArr['res2'] as $v){
                $res2_arr[$v['deal_loan_id']]  = $v['m'];
            }
        }
        $deal_loan_ids_arr = array();
        if (!empty($mergeArr['res2'])){
            foreach ($mergeArr['res2'] as $v){
                $deal_loan_ids_arr[$v['deal_loan_id']]  = $v['deal_loan_id'];
            }
        }

        foreach($mergeArr['res1'] as $key => $v){
            if ($v['deal_loan_id'] == $deal_loan_ids_arr[$v['deal_loan_id']]){
                $mergeArr['res1'][$key]['m'] = bcadd($mergeArr['res1'][$key]['m'],$res2_arr[$v['deal_loan_id']],2);
            }

        }
        //return $this->db->get_slave()->getAll($sql);

        return $mergeArr['res1'];
    }

    /**
     * 根据投资id和总利息金额
     * @param $deal_loan_ids array 投资id数组
     * @return array 总金额
     * @author longbo
     */
    public function getTotalMoneyTypeForUser($deal_loan_ids) {
        $res = $this->getTotalMoneyByTypesAndLoanIds($deal_loan_ids,
                array(
                    DealLoanRepayEnum::MONEY_IMPOSE,
                    DealLoanRepayEnum::MONEY_INTREST,
                    DealLoanRepayEnum::MONEY_PREPAY_INTREST,
                    DealLoanRepayEnum::MONEY_COMPENSATION,
                    DealLoanRepayEnum::MONEY_COMPOUND_INTEREST,
                    )
                );
        $data = array();
        if (!empty($res)) {
            foreach ($res as $value) {
                $data[$value['deal_loan_id']] = $value['m'];
            }
        }
        return $data;
    }

    /**
     * 提前还款时，将未还的还款计划设置为取消
     * @param int $deal_loan_id
     * @return bool
     */
    public function cancelDealLoanRepay($deal_loan_id) {
        $params = array(
            ":deal_loan_id" => $deal_loan_id,
            ":status" => DealLoanRepayEnum::STATUS_NOTPAYED,
        );

        $list = $this->findAll("`deal_loan_id`=':deal_loan_id' AND `status`=':status'", false, "*", $params);

        if (!$list) {
            return false;
        }
        $dealService = new DealService();
        $isDT = $dealService->isDealDT($list[0]['deal_id']);

        $money_interest = 0;
        $calInfo = array();
        $loan_user_id = 0;

        foreach ($list as $v) {
            $v['status'] = DealLoanRepayEnum::STATUS_CANCEL;
            $v['update_time'] = get_gmtime();
            if ($v->save() === false) {
                throw new \Exception("update deal loan repay fail");
            }
            $calcTime = strtotime(to_date($v['time'],'Y-m-d'));
            $loan_user_id = $v['loan_user_id'];

            if(!isset($calInfo[$calcTime])) {
                $calInfo[$calcTime] = array(
                    DealLoanRepayCalendarEnum::NOREPAY_INTEREST => 0,
                    DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL => 0
                );
            }

            if ($v['type'] == DealLoanRepayEnum::MONEY_INTREST) {
                $money_interest = bcadd($money_interest, $v['money'], 2);
                $calInfo[$calcTime][DealLoanRepayCalendarEnum::NOREPAY_INTEREST]+= -$v['money'];
            }
            if ($v['type'] == DealLoanRepayEnum::MONEY_PRINCIPAL) {
                $calInfo[$calcTime][DealLoanRepayCalendarEnum::NOREPAY_PRINCIPAL]+= -$v['money'];
            }
        }

        if(!$isDT){
            foreach($calInfo as $time=>$val) {
                DealLoanRepayCalendarService::collect($loan_user_id,$time,$val,$time);
            }
        }
        return $money_interest;
    }


    /*
  * 根据user_id整合回款记录，排除预约投资
  * @param int $deal_id
  * @param int $deal_repay_id
  * @return array
  */
    public function getNonReserveListByDealId($deal_id, $deal_repay_id) {
        $deal_id = intval($deal_id);
        $deal_repay_id = intval($deal_repay_id);
        $sql = "SELECT SUM(dlr.`money`) AS `m`, dlr.`loan_user_id`, dlr.`type`, dlr.`deal_loan_id`, COUNT(DISTINCT(dlr.`deal_loan_id`)) AS `c` FROM " . $this->tableName() . " AS dlr
                LEFT JOIN `firstp2p_deal_load` AS dl ON dlr.deal_loan_id = dl.id
                WHERE dlr.`deal_id` = '{$deal_id}' AND dlr.`deal_repay_id` = '{$deal_repay_id}' AND dlr.`status`='" . DealLoanRepayEnum::STATUS_ISPAYED . "' AND dl.`deal_id` = '{$deal_id}' AND dl.`source_type` != '" . DealLoadModel::$SOURCE_TYPE['reservation'] . "'
                GROUP BY dlr.`loan_user_id`, dlr.`type`";
        $result = $this->findAllBySql($sql, true, array());

        $list = array();
        foreach ($result as $val) {
            $list[$val['loan_user_id']]['deal_loan_id'] = $val['deal_loan_id'];
            $list[$val['loan_user_id']]['cnt'] = $val['c'];
            switch($val['type']) {
                case DealLoanRepayEnum::MONEY_PRINCIPAL:
                    $list[$val['loan_user_id']]['principal'] = $val['m'];break;
                case DealLoanRepayEnum::MONEY_INTREST:
                    $list[$val['loan_user_id']]['intrest'] = $val['m'];break;
                case DealLoanRepayEnum::MONEY_PREPAY:
                    $list[$val['loan_user_id']]['prepay'] = $val['m'];break;
                case DealLoanRepayEnum::MONEY_COMPENSATION:
                    $list[$val['loan_user_id']]['compensation'] = $val['m'];break;
                case DealLoanRepayEnum::MONEY_IMPOSE:
                    $list[$val['loan_user_id']]['impose'] = $val['m'];break;
                case DealLoanRepayEnum::MONEY_PREPAY_INTREST:
                    $list[$val['loan_user_id']]['prepayIntrest'] = $val['m'];break;
            }
        }
        return $list;
    }

    /**
     * 根据还款id和用户id获取各项还款金额
     * @param $deal_repay_id int
     * @param $user_id int
     * @param $exclude_reservation boolen 是否排除前台预约投标
     * @param $deal_id int
     * @return array
     */
    public function getChangeMoneyByRepayId($deal_repay_id, $user_id, $exclude_reservation = false, $deal_id = 0) {
        $moneyInfo = array(
            'principal' => '0元', //本金
            'intrest' => '0元', //利息
            'prepay' => '0元', //提前还款
            'compensation' => '0元', //提前还款补偿金
            'impose' => '0元', //逾期罚息
            'prepayIntrest' => '0元', //提前还款利息
        );
        $deal_repay_id = intval($deal_repay_id);
        $user_id = intval($user_id);
        $condition = sprintf('`deal_repay_id`=  %d  AND `loan_user_id`= %d ', $this->escape($deal_repay_id), $this->escape($user_id));
        if ($exclude_reservation) {
            $deal_load_id_arr = DealLoadModel::instance()->getReserveDealLoadIdsByDealId($deal_id);
            $condition .= empty($deal_load_id_arr) ? '' : sprintf(' AND deal_loan_id NOT IN (%s) ', implode(',', $deal_load_id_arr));
        }

        $loan_repay_list = $this->findAll($condition);
        $money_total     = 0;
        $arr_money       = array();
        foreach ($loan_repay_list as $val) {
            $arr_money[$val['type']] += $val['money'];
            $arr_money['time']       = $val['time'];
            if ($val['type'] != DealLoanRepayEnum::MONEY_MANAGE) {
                $money_total += $val['money'];
            }
            switch ($val['type']) {
                case DealLoanRepayEnum::MONEY_PRINCIPAL:
                    $moneyInfo['principal'] += $val['money'];
                    break;
                case DealLoanRepayEnum::MONEY_INTREST:
                    $moneyInfo['intrest'] += $val['money'];
                    break;
                case DealLoanRepayEnum::MONEY_PREPAY:
                    $moneyInfo['prepay'] += $val['money'];
                    break;
                case DealLoanRepayEnum::MONEY_COMPENSATION:
                    $moneyInfo['compensation'] += $val['money'];
                    break;
                case DealLoanRepayEnum::MONEY_IMPOSE:
                    $moneyInfo['impose'] += $val['money'];
                    break;
                case DealLoanRepayEnum::MONEY_PREPAY_INTREST:
                    $moneyInfo['prepayIntrest'] += $val['money'];
                    break;
                default:
                    break;
            }
        }
        $arr_money['total'] = $money_total;
        $arr_money['moneyInfo'] = $moneyInfo;
        return $arr_money;
    }

    /**
     * 获取单笔投资回款总额
     * @param $deal_id int
     * @param $loan_user_id int
     * @return float 回款总额
     */
    public function getTotalRepayMoney($deal_id, $loan_user_id) {
        $sql = sprintf('SELECT SUM(`money`) AS `m`,`type` FROM %s WHERE `deal_id`= %d AND `loan_user_id`= %d AND `status`= %d GROUP BY `type`', $this->tableName(), $deal_id, $loan_user_id, DealLoanRepayEnum::STATUS_ISPAYED);

        $result = $this->getDataBySepcFields('findAllBySql',array($sql),array('deal_id' => $deal_id));
       // $result = $this->findAllBySql($sql);
        $total_money = 0;
        foreach ($result as $val) {
            $total_money += $val['m'];
        }
        return $total_money;
    }

    /**
     * 获取用户在此标中指定类型和状态的总资金
     * @param int $deal_id
     * @param int $user_id
     * @param int $type 金额类别
     * @param boolean | int $status
     * @param boolean $exclude_reservation 是否排除前台预约投标
     * @return float 总金额
     */
    public function getTotalMoneyOfUserByDealId($deal_id, $user_id, $type, $status = false, $exclude_reservation = false) {
        $sql = sprintf(' SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id`= %d  AND `loan_user_id`= %d  AND `type`= %d ', $this->tableName(), $deal_id, $user_id, $type);
        $sql .= (false === $status) ? '' : sprintf(' AND `status`= %d ', $status);
        if ($exclude_reservation) {
            $deal_load_id_arr = DealLoadModel::instance()->getReserveDealLoadIdsByDealId($deal_id);
            $sql .= empty($deal_load_id_arr) ? '' : sprintf(' AND deal_loan_id NOT IN (%s) ', implode(',', $deal_load_id_arr));
        }
        if ($status == DealLoanRepayEnum::STATUS_ISPAYED){
            $res = $this->getDataBySepcFields('findBySql',array($sql),array('deal_id' => $deal_id));
        }else{
            $res = $this->findBySql($sql);
        }

        return $res['sum'];
    }

    /**
     * 向出借人发送站内信和邮件
     * @param $deal object
     * @param $user object
     * @param $repay_id int
     * @param $next_repay_id int
     */
    public function sendMsg($deal, $user, $repay_id, $next_repay_id) {
        $arr_change_money = $this->getChangeMoneyByRepayId($repay_id, $user['id'], true, $deal['id']);
        if ($next_repay_id) {
            $is_last         = 0;
            $arr_money_extra = $this->getChangeMoneyByRepayId($next_repay_id, $user['id'], true, $deal['id']);
        } else {
            $is_last         = 1;
            $arr_money_extra = array(
                "all_repay_money"  => number_format($this->getTotalRepayMoney($deal->id, $user['id']), 2),
                "all_impose_money" => number_format($this->getTotalMoneyOfUserByDealId($deal->id, $user['id'], DealLoanRepayEnum::MONEY_IMPOSE, DealLoanRepayEnum::STATUS_ISPAYED), 2),
                "all_income_money" => number_format($this->getTotalMoneyOfUserByDealId($deal->id, $user['id'], DealLoanRepayEnum::MONEY_INTREST, DealLoanRepayEnum::STATUS_ISPAYED), 2),
            );
        }

        // 向出借人发送回款站内信
        $this->sendMessage($deal, $user, $arr_change_money, $is_last, $arr_money_extra);

        // 向出借人发送回款邮件
        $this->sendEmail($user, $deal, $arr_change_money, $is_last, $arr_money_extra);
    }

    /**
     * 向出借人发回款邮件
     * @param $user object
     * @param $deal array
     * @param $arr_money array
     * @param $is_last int
     * @param $arr_money_extra array
     */
    private function sendEmail($user, $deal, $arr_money, $is_last, $arr_money_extra) {
        if (isset($user['user_type']) && (int)$user['user_type'] == UserEnum::USER_TYPE_ENTERPRISE) {
            $userName =$user['user_name'];
        }else{
            $userName =get_deal_username($user['id']);
        }
        $notice = array(
            "user_name"   => $userName,
            "deal_name"   => $deal['name'],
            "deal_url"    => $deal['share_url'],
            "site_name"   => app_conf("SHOP_TITLE"),
            "help_url"    => get_deal_domain($deal['id']) . '/helpcenter',
            "repay_money" => $arr_money['total'],
        );


        if ($is_last) {
            $notice['all_repay_money']  = $arr_money_extra['all_repay_money'];
            $notice['impose_money']     = $arr_money_extra['all_impose_money'] > 0 ? "其中违约金为:{$arr_money_extra['all_impose_money']}元," : "";
            $notice['all_income_money'] = $arr_money_extra['all_income_money'];

            SendEmailService::sendEmail($user['email'], $user['id'], $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL_LAST', "“{$deal['name']}”回款通知");
        } else {
            $notice['next_repay_time']  = to_date($arr_money_extra['time'], "Y年m月d日");
            $notice['next_repay_money'] = number_format($arr_money_extra['total'], 2);

            SendEmailService::sendEmail($user['email'], $user['id'], $notice, 'TPL_DEAL_LOAD_REPAY_EMAIL', "“{$deal['name']}”回款通知");
        }
    }

    /**
     * 向出借人发送回款站内信
     * @param $deal object 订单信息
     * @param $loan_user object
     * @param $arr_money array 回款金额信息
     * @param $is_last boolean 是否是最后一次回款
     * @param $arr_money_extra boolean 下次回款金额信息
     */
    private function sendMessage($deal, $loan_user, $arr_money, $is_last, $arr_money_extra) {
        $dealService = new DealService();

        $repay_money = $arr_money[DealLoanRepayEnum::MONEY_PRINCIPAL] + $arr_money[DealLoanRepayEnum::MONEY_INTREST] + $arr_money[DealLoanRepayEnum::MONEY_MANAGE] + $arr_money[DealLoanRepayEnum::MONEY_IMPOSE];
        $repay_money_format = format_price($repay_money);
        $principal_format = format_price($arr_money[DealLoanRepayEnum::MONEY_PRINCIPAL]);
        $interest_format = format_price($arr_money[DealLoanRepayEnum::MONEY_INTREST]);
        $compensation_format = format_price($arr_money[DealLoanRepayEnum::MONEY_COMPENSATION]);
        $impose_format = format_price($arr_money[DealLoanRepayEnum::MONEY_IMPOSE]);

        if (!$is_last) {
            $next_repay_date = to_date($arr_money_extra['time'], 'Y年m月d日');
            $next_repay_money_format = format_price($arr_money_extra[DealLoanRepayEnum::MONEY_PRINCIPAL] + $arr_money_extra[DealLoanRepayEnum::MONEY_INTREST] + $arr_money_extra[DealLoanRepayEnum::MONEY_MANAGE]);

            $content = sprintf('您投资的 “%s”成功回款%s。本笔投资的下个回款日为%s，需还本息%s。', $deal['name'], $repay_money_format, $next_repay_date, $next_repay_money_format);
        } else {
            $next_repay_date = 0;
            $next_repay_money_format = 0;

            $content = sprintf('您投资的“%s”成功回款%s，本次投资共回款%s，收益:%s。本次投资已回款完毕。', $deal['name'], $repay_money_format, $repay_money_format, $interest_format);
        }

        $load_counts = DealLoadModel::instance()->getDealLoadCountsByUserId($deal['id'], $loan_user['id'], true);
        $structured_content = array(
            'money' => sprintf('+%s', number_format($repay_money, 2)),
            'repay_periods' => $is_last ? '已完成' : sprintf('%s/%s期', $deal['repay_periods_order'], $deal['repay_periods_sum']), // 期数
            'main_content' => rtrim(sprintf("%s%s%s%s%s%s%s",
                sprintf("项目：%s（%s笔）\n", $deal['name'], $load_counts),
                empty($arr_money[DealLoanRepayEnum::MONEY_PRINCIPAL]) ? '' : sprintf("本金：%s\n", $principal_format),
                empty($arr_money[DealLoanRepayEnum::MONEY_INTREST]) ? '' : sprintf("收益：%s\n", $interest_format),
                empty($arr_money[DealLoanRepayEnum::MONEY_COMPENSATION]) ? '' : sprintf("提前还款补偿金：%s\n", $compensation_format),
                empty($next_repay_date) ? '' : sprintf("下次回款日：%s\n", to_date($arr_money_extra['time'], 'Y-m-d')),
                empty($next_repay_money_format) ? '' : sprintf("下次回款额：%s\n", $next_repay_money_format),
                empty($arr_money[DealLoanRepayEnum::MONEY_IMPOSE]) ? '' : sprintf("逾期利息：%s\n", $impose_format)
            )),
            'is_last' => $is_last,
            'prepay_tips' => '',
            'turn_type' => $is_last ? MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST : MsgBoxEnum::TURN_TYPE_REPAY_CALENDAR, // app 跳转类型标识
        );

        $msgbox = new MsgboxService();
        $msgbox->create($loan_user['id'], 9, '回款', $content, $structured_content);
    }

    /**
     * 获取用户的回款计划总额
     * @param int $user_id
     * @return array
     */
    public function getUserSummary($user_id) {
        $sql = "SELECT SUM(`money`) AS `m`, `type`, `status` FROM " . $this->tableName() . " WHERE `loan_user_id`=':user_id' GROUP BY `type`, `status`";
        $params = array(
            ':user_id' => $user_id,
        );
        $mergeArr = $this->getMergeDataFromDiffDb('findAllBySql',array($sql,true,$params,true));

        $moved_arr = array();

        foreach($mergeArr['res2'] as $key => $v){
            $moved_arr[$v['type'].$v['status']] = $v['m'];
        }

        foreach($mergeArr['res1'] as $key2 => $v2){
            if (isset($moved_arr[$v2['type'].$v2['status']])){
                $mergeArr['res1'][$key2]['m'] = bcadd($v2['m'],$moved_arr[$v2['type'].$v2['status']]);
            }
        }

        $res = $mergeArr['res1'];
        //$res = $this->findAllBySql($sql, true, $params, true);
        return $res;
    }

    /**
     * 获取用户汇款列表 web api 回款列表
     * @param $user_id
     * @param $start_time
     * @param $end_time
     * @param $limit array(0,5)
     * @param string $type web  或者 api newapi creditloanapi 速贷api
     * @param null $money_type
     * @param null $repay_status
     * @param int | string $deal_type
     */
    public function getLoanList($user_id,$start_time,$end_time,$limit,$type='web',$money_type=null,$repay_status=null, $deal_type = false){
        $where = " (`type` in (2,3,4,5,7,8,9) or (`type` = 1 and money != 0 )) AND `time`!='0' AND ";
        if(!$user_id){return false;}
        $condition = sprintf(' loan_user_id = %d ',$user_id);
        if($start_time){
            $condition .= sprintf(' and time >= %d ',$start_time);
        }
        if($end_time){
            $condition .= sprintf(' and time <= %d ',$end_time);
        }
        if($money_type !== null){
            $condition .= sprintf(' and type = %d ',$money_type);
        }
        if($repay_status !== null){
            $condition .= sprintf(' and status = %d ',$repay_status);
        }
        if(false !== $deal_type) {
            $condition .= sprintf(' and deal_type in (%s) ',$deal_type);
        }

        // 过滤智多鑫标的回款计划
        $dt_tag = \core\dao\tag\TagModel::instance()->getInfoByTagName(\core\service\duotou\DtDealService::TAG_DT);
        if (!empty($dt_tag) && $this->is_history_db == false) {
            $condition .= sprintf(" AND `deal_id` NOT IN (SELECT `deal_id` FROM %s WHERE `tag_id` = '%d')", \core\dao\deal\DealTagModel::instance()->tableName(), intval($dt_tag['id']));
        }

        if($type == 'web'){
            //$order = " order by deal_loan_id desc,type asc,id desc ";
            $order = " ORDER BY `status` ASC, `real_time` DESC, `time` ASC, `id` ASC, `type` ASC ";
        }elseif($type == 'api'){//老接口
            if($start_time){
                $order = ' ORDER BY time ASC ';
            }else{
                $order = ' ORDER BY time DESC ';
            }
        }elseif($type == 'newapi'){
            if($repay_status){//已还
                $order = ' ORDER BY time DESC, id DESC ';
            }else{
                $order = ' ORDER BY time ASC, id ASC ';
            }
        } elseif($type == 'creditloanapi') {
            $order = ' ORDER BY time ASC ';
        }
        $limit_str = " LIMIT %d,%d ";
        $limit_str = sprintf($limit_str,$limit[0],$limit[1]);

        if ($this->is_history_db){
            // 注意走moved 主库的时候需要重新连接db 用mysqldb 连接
            $this->db->useHistory = true;
            // 过滤多投的回款计划
            if(!empty($dt_tag)){
                $history_sql = "SELECT distinct deal_id FROM ".$this->tableName()." WHERE   loan_user_id ='{$user_id}'";
                $history_dealIds = $this->findAllBySql($history_sql,true,array(),true);
                if (!empty($history_dealIds)){
                    $implode_ids = array();
                    foreach ($history_dealIds as $hdi){
                        $implode_ids[$hdi['deal_id']] = $hdi['deal_id'];
                    }
                    $history_dealIds_explode = implode(',',$implode_ids);
                    $this->db->useHistory = false;
                    $tag_sql = "SELECT distinct `deal_id` FROM ".\core\dao\deal\DealTagModel::instance()->tableName()." WHERE `tag_id` = '{$dt_tag['id']}' and deal_id in ({$history_dealIds_explode})";
                    $tag_dealIds = $this->findAllBySql($tag_sql,true,array(),true);
                    if (!empty($tag_dealIds)){
                        $implode_tag_deal_ids = array();
                        foreach ($tag_dealIds as $tdi){
                            $implode_tag_deal_ids[$tdi['deal_id']] = $tdi['deal_id'];
                        }
                        $condition .= " AND `deal_id` NOT IN ( ".implode(',',$implode_tag_deal_ids).') ';
                    }
                }
            }
            $this->db->useHistory = true;

        }

        $where = $where.$condition.$order;

        try {
            $rs = $this->findAllViaSlave($where . $limit_str, TRUE);
            $counts = $this->countViaSlave($where);
        }catch (\Exception $e){
            // 需要捕获异常防止资源切不回来
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
        }
        $this->db->useHistory = false;
        $rs['counts'] = $counts;
        return $rs;
    }


    /**
     * 提前还款，根据投资人投资记录计算借款人实际还款金额
     * @param int $deal_id
     * @param floot $remain_principal 剩余本金
     * @param int $remain_days 剩余天数
     * @param array $result 投资人实际获得的金额总和，即借款人实际还款金额
     */
    public function getPrepayMoney($deal_id, $remain_principal, $remain_days) {
        $deal_model = new DealModel();
        $deal = $deal_model->find($deal_id);
        $rate = $deal['income_fee_rate'];
        $result = array(
            'prepay_money' => 0,
            'principal' => 0,
            'prepay_interest' => 0,
            'loan_fee' => 0,
            'consult_fee' => 0,
            'guarantee_fee' => 0,
            'pay_fee' => 0,
        );

        $deal_service = new DealService();
        if ($deal_service->isDealDTV3($deal_id) === true) {
            $principal = $remain_principal;
            $prepay_interest = $deal_model->floorfix($deal_model->prepay_money_intrest($principal, $remain_days, $rate));
            $prepay_compensation = $deal_model->floorfix($deal['borrow_amount'] * ($deal['prepay_rate']/100));
            $prepay_money = $principal + $prepay_interest + $prepay_compensation;
            $result['prepay_money'] = bcadd($result['prepay_money'],$deal_model->floorfix($prepay_money),2);
            $result['principal'] = bcadd($result['principal'],$deal_model->floorfix($principal),2);
            $result['prepay_interest'] = bcadd($result['prepay_interest'],$deal_model->floorfix($prepay_interest),2);
        } else {
            // 投资记录
            $deal_loan_model = new DealLoadModel();
            $deal_loan_list = $deal_loan_model->getDealLoanList($deal_id);

            $deal_loan_repay_model = new DealLoanRepayModel();
            foreach ($deal_loan_list as $deal_loan) {
                // 回款本金
                $principal = $deal_loan_repay_model->getTotalMoneyByTypeStatusLoanId($deal_loan['id'],DealLoanRepayEnum::MONEY_PRINCIPAL,DealLoanRepayEnum::STATUS_NOTPAYED);
                // 回款利息
                $prepay_interest = $deal_model->floorfix($deal_model->prepay_money_intrest($principal, $remain_days, $rate));

                // 提前还款违约金
                $prepay_compensation = $deal_model->floorfix($deal_loan['money'] * ($deal['prepay_rate']/100));
                // 回款实际金额
                $prepay_money = $principal + $prepay_interest + $prepay_compensation;
                // 进行舍余后，计算实际回款总额

                $result['prepay_money'] = bcadd($result['prepay_money'],$deal_model->floorfix($prepay_money),2);
                $result['principal'] = bcadd($result['principal'],$deal_model->floorfix($principal),2);
                $result['prepay_interest'] = bcadd($result['prepay_interest'],$deal_model->floorfix($prepay_interest),2);
            }
        }

        $deal_repay_model = new DealRepayModel();
        $deal_repay_list = $deal_repay_model->getDealUnpaiedRepayListByDealId($deal_id);
        foreach ($deal_repay_list as $k => $v) {
            $result['loan_fee'] += $v['loan_fee'];
            $result['consult_fee'] += $v['consult_fee'];
            $result['guarantee_fee'] += $v['guarantee_fee'];
            $result['pay_fee'] += $v['pay_fee'];
        }

        return $result;
    }

    /**
     * 取得某用户已还或待还的金额按照标的汇总
     * @param $uid
     * @param $time
     * @param $type
     */
    public function getRepayDealSumaryByTime($uid,$time) {
        $sql = "SELECT SUM(`money`) AS `m`,`type`,`deal_id`,`time`,`real_time`,`status`,`deal_type` FROM firstp2p_deal_loan_repay WHERE loan_user_id={$uid} ";
        $sql.=" AND status !=2 AND (`real_time` = ".$time . " or (`time`=".$time." AND real_time =0))";
        $sql.=" GROUP by deal_id,type";

        //return $this->findAllBySql($sql, true, array(),true);
        $res = $this->getMergeDataFromDiffDb('findAllBySql',array($sql,true,array(),true));
        foreach($res['res1'] as $k=>$v){
            foreach($res['res2'] as $kk=>$vv){
                if($v['deal_id'] == $vv['deal_id'] && $v['type'] == $vv['type']){
                    $res['res1'][$k]['m'] = bcadd($v['m'],$vv['m'],2);
                }
            }
        }
        return $res['res1'];
    }

    /**
     * 记录农担贷资金记录
     * @param $loanUser
     * @param $borrower
     * @param $compo
     * @param array $bizToken log业务类型用
     */
    public function addNDRepayMoneyLog($repayId,$dealLoanId,$logType,$note,$loanUser,$feeTypes, $bizToken = array()) {


        $partialRepayModel = new PartialRepayModel();
        //支付借款人的钱
        $borrowerRepayMoney = $partialRepayModel->getMoneyByLoanId($repayId,$dealLoanId,PartialRepayEnum::REPAY_TYPE_BORROWER,$feeTypes);
        if(bccomp($borrowerRepayMoney,'0.00',2) == 1) { //借款人还款大于0
            $accountId = AccountService::getUserAccountId($loanUser['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!AccountService::changeMoney($accountId,$borrowerRepayMoney, $logType, $note,AccountEnum::MONEY_TYPE_INCR, false,true, 0, $bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }

        //支付代偿机构的钱
        $compensatoryRepayMoney = $partialRepayModel->getMoneyByLoanId($repayId,$dealLoanId,PartialRepayEnum::REPAY_TYPE_COMPENSATORY,$feeTypes);
        if(bccomp($compensatoryRepayMoney,'0.00',2) == 1) { //代偿还款大于0
            $accountId = AccountService::getUserAccountId($loanUser['id'],UserAccountEnum::ACCOUNT_INVESTMENT);
            if (!AccountService::changeMoney($accountId,$compensatoryRepayMoney, $logType, $note,AccountEnum::MONEY_TYPE_INCR, false,true, 0, $bizToken)) {
                throw new \Exception("还款支付{$logType}失败");
            }
        }
        return true;
    }

    /**
     * 取得因提前还款而取消的最大的预期还款时间
     * @param $deal_id
     * @return mixed
     */
    public function getMaxPrepayTimeByDealId($deal_id) {
        $sql = "SELECT MAX(time) as maxtime  FROM firstp2p_deal_loan_repay where deal_id={$deal_id} AND `status`=2 ";
        //$res =  $this->findBySql($sql,array(),true);
        $res = $this->getMergeDataFromDiffDb('findBySqlViaSlave',array($sql));
        return $res['res1']->maxtime > $res['res2']->maxtime ? $res['res1']->maxtime : $res['res2']->maxtime;
    }

    public function getSumMoneyOfUserByDealIdRepayId($dealId, $userId,$repayId,$type){
        $sql = sprintf(' SELECT SUM(`money`) AS `sum` FROM %s WHERE `deal_id`= %d  AND `loan_user_id`= %d  AND `deal_repay_id`=%d AND `type` = %d', $this->tableName(), $dealId, $userId, $repayId,$type);
        $res = $this->getMergeDataFromDiffDb('findBySqlViaSlave',array($sql));
        return bcadd($res['res1']['sum'],$res['res2']['sum'],2);

    }

    /**
     * 仅适用从库不可用于主库
     * 仅适用从库不可用于主库
     * 仅适用从库不可用于主库
     *
     *  获取正常库和迁移库数据
     * @param $func
     * @param array $args
     * @return array
     * @throws \Exception
     */
    public function getMergeDataFromDiffDb($func,$args=array()){

        $res1 = call_user_func_array(array($this,$func),$args);

        $this->db->useHistory = true;
        try {
            $res2 = call_user_func_array(array($this, $func), $args);
        }catch (\Exception $e){
            // 需要捕获异常防止资源切不回来
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
        }

        $this->db->useHistory = false;
        return array('res1' => $res1,'res2'=>$res2);

    }

    /**
     * 在可以明确知道数据源情况下调用此方法
     * @param $func
     * @param $args
     * @param Array $byField  key值必须是允许的 deal_id,deal_loan_id,deal_repay_id
     * @return mixed
     * @throws \Exception
     */
    public function getDataBySepcFields($func,$args,Array $byField){
        try {
            $res = call_user_func_array(array($this, $func), $args);
            if(!$res){
                $this->db->useHistory = true;
                $res = call_user_func_array(array($this, $func), $args);
            }
        }catch (\Exception $e){
            // 需要捕获异常防止资源切不回来
            Logger::error(__CLASS__.' '.__FUNCTION__.' '.$e->getMessage());
        }
        $this->db->useHistory = false;

        return $res;
    }
}
