<?php

namespace core\service\makeloans;

use core\enum\AccountEnum;
use core\enum\DealLoanRepayEnum;
use core\enum\UserAccountEnum;
use core\service\account\AccountService;
use libs\utils\Logger;
use libs\utils\Monitor;
use libs\utils\Finance;
use libs\lock\LockFactory;
use core\enum\DealEnum;
use core\enum\MsgbusEnum;
use core\enum\LoanOplogEnum;
use core\enum\JobsEnum;
use core\enum\contract\ContractEnum;
use core\enum\UserLoanRepayStatisticsEnum;
use core\service\BaseService;
use core\dao\deal\DealModel;
use core\dao\deal\DealAgencyModel;
use core\dao\deal\DealExtModel;
use core\dao\repay\DealRepayModel;
use core\dao\project\DealProjectModel;
use core\dao\user\UserLoanRepayStatisticsModel;
use core\dao\contract\DealContractModel;
use core\dao\dealloan\LoanOplogModel;
use core\dao\jobs\JobsModel;
use core\dao\makeloans\MakeLoansModel;
use core\service\deal\DealService;
use core\service\deal\DealAgencyService;
use core\service\contract\ContractService;
use core\service\contract\ContractNewService;
use core\service\msgbus\MsgbusService;
use core\service\coupon\CouponService;
use core\service\user\UserLoanRepayStatisticsService;
use core\service\deal\DealLoanRepayCalendarService;
use NCFGroup\Common\Library\Idworker;
use core\dao\repay\DealLoanRepayModel;

/**
 * 放款服务类
 * Class MakeLoansService
 * @package core\service\makeloans
 */
class MakeLoansService extends BaseService
{

    /**
     * 对指定标的进行自动放款操作
     * @param array $dealInfo 对应 deal 表字段
     * @return boolean
     */
    public function autoMakeLoans($dealInfo)
    {
        try {
            $GLOBALS['db']->startTrans();
            $this->isOKForMakingLoans($dealInfo); // 不符合条件抛出异常

            if ($this->saveServiceFeeExt($dealInfo) === false) {
                throw new \Exception("Save deal ext fail. Error:deal id:" . $dealInfo['id']);
            }

            //放款添加到jobs
            $grantOrderId = Idworker::instance()->getId();
            $function = '\core\service\deal\P2pDealGrantService::dealGrantRequest';
            $param = array(
                'orderId' => $grantOrderId,
                'dealId' => $dealInfo['id'],
                'param' => array('deal_id' => $dealInfo['id'], 'admin' => '', 'submit_uid' => 0),
            );
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",放款通知加入jobs orderId:" . $grantOrderId . " dealId:" . $dealInfo['id']);

            $job_model = new JobsModel();
            $job_model->priority = JobsEnum::PRIORITY_DEAL_GRANT;
            //延迟10秒处理，临时解决后续部分逻辑没在事务里的问题
            if (false == $job_model->addJob($function, $param)) {
                throw new \Exception('addJob fail. Error:orderId:' . $grantOrderId . ",deal id:" . $dealInfo['id']);
            }

            $deal_model = new DealModel();
            //更新标放款状态
            if (false == $deal_model->changeLoansStatus(intval($dealInfo['id']), DealEnum::DEAL_IS_HAS_LOANS_ING)) {
                throw new \Exception('changeLoansStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
            }

            //更新标的还款中状态
            if (false == $deal_model->changeDealStatus(intval($dealInfo['id']))) {
                throw new \Exception('changeDealStatus fail. Error:deal type:' . $dealInfo['type_id'] . ",deal id:" . $dealInfo['id']);
            }

            $GLOBALS['db']->commit();
            Logger::info(sprintf('success:params:%s, func:%s [%s:%s]', json_encode($dealInfo), __FUNCTION__, __FILE__, __LINE__));
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(sprintf('fail:err-msg:%s, params:%s, func:%s [%s:%s]', $e->getMessage(), json_encode($dealInfo), __FUNCTION__, __FILE__, __LINE__));
            return false;
        }
    }


    /**
     * 放款jobs
     * @param $deal_id
     * @return bool
     */
    public function makeDealLoansJob($deal_id, $admin = array(), $submit_uid = 0)
    {
        $admin['adm_id'] = isset($admin['adm_id']) ? $admin['adm_id'] :0;
        $deal = DealModel::instance()->find($deal_id);
        $dealService = new DealService();
        $deal_data = $deal->getRow();
        $deal_data['isDtb'] = 0;
        if ($dealService->isDealDT($deal_id)) {
            $deal_data['isDtb'] = 1;
        }
        $agency_model = new DealAgencyModel();
        $agencyFeeUser = $agency_model->find($deal_data['agency_id']);
        $advisoryFeeUser = $agency_model->find($deal_data['advisory_id']);
        $canalAgencyUser = $agency_model->find($deal_data['canal_agency_id']);
        $loanFeeUserId = $agency_model->getLoanAgencyUserId($deal_id);
        if (!$deal_data['pay_agency_id']) {
            $deal_data['pay_agency_id'] = $agency_model->getUcfPayAgencyId();
        }
        $payAgencyUser = $agency_model->find($deal_data['pay_agency_id']);

        $loanFeeAccountId = AccountService::getUserAccountId($loanFeeUserId,UserAccountEnum::ACCOUNT_PLATFORM);
        $advisoryFeeAccountId = AccountService::getUserAccountId($advisoryFeeUser['user_id'],UserAccountEnum::ACCOUNT_ADVISORY);
        $agencyFeeAccountId = AccountService::getUserAccountId($agencyFeeUser['user_id'],UserAccountEnum::ACCOUNT_GUARANTEE);
        $payAgencyAccountId = AccountService::getUserAccountId($payAgencyUser['user_id'],UserAccountEnum::ACCOUNT_PAY);
        $canalAgencyAccountId = AccountService::getUserAccountId($canalAgencyUser['user_id'],UserAccountEnum::ACCOUNT_CHANNEL);

        $managementAgencyAccountId = 0;
        if ($deal_data['isDtb'] == 1) {
            $management_agency_user = $agency_model->find($deal_data['management_agency_id']);
            $managementAgencyAccountId = AccountService::getUserAccountId($management_agency_user['user_id'],UserAccountEnum::ACCOUNT_MANAGEMENT);
        }
        $result = $this->makeDealLoans($deal_data, $advisoryFeeAccountId, $agencyFeeAccountId, $loanFeeAccountId, $payAgencyAccountId, $managementAgencyAccountId,$canalAgencyAccountId, $admin);

        $projectInfo = DealProjectModel::instance()->find($deal_data['project_id']);
        if ($result != false) {
            $loan_oplog_model = new LoanOplogModel();
            if (0 == $admin['adm_id']) {
                $loan_oplog_model->op_type = LoanOplogEnum::OP_TYPE_AUTO_MAKE_LOAN;
            } else {
                $loan_oplog_model->op_type = LoanOplogEnum::OP_TYPE_MAKE_LOAN;
            }
            $loan_oplog_model->loan_batch_no = '';
            $loan_oplog_model->deal_id = $deal_data['id'];
            $loan_oplog_model->deal_name = $deal_data['name'];
            $loan_oplog_model->borrow_amount = $deal_data['borrow_amount'];
            $loan_oplog_model->repay_time = $deal_data['repay_time'];
            $loan_oplog_model->loan_type = $deal_data['loantype'];
            $loan_oplog_model->borrow_user_id = $deal_data['user_id'];
            $loan_oplog_model->op_user_id = $admin['adm_id'];
            $loan_oplog_model->loan_money_type = $projectInfo['loan_money_type'];
            $loan_oplog_model->op_time = get_gmtime();
            $loan_oplog_model->submit_uid = intval($submit_uid);
            $loan_oplog_model->loan_money = $deal_data['borrow_amount'] - $result['services_fee'];
            if (!$loan_oplog_model->save()) {
                throw new \Exception("保存放款操作记录失败");
            };
        }

        return ($result === false) ? false : true;
    }

    /**
     * 封装放款方法
     */
    public function makeDealLoans($deal_data, $advisoryFeeAccountId, $agencyFeeAccountId, $loanFeeAccountId, $payAgencyAccountId, $managementAgencyAccountId,$canalAgencyAccountId, $admin = array())
    {
        $deal_ext = DealExtModel::instance()->getInfoByDeal($deal_data['id'], false);

        // 悲观锁，以group_id为锁的键名，防止重复生成
        $lockKey = "DealService-makeDealLoansJob" . $deal_data['id'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 900)) {
            return false;
        }

        try {
            $GLOBALS['db']->startTrans();
            $makeloansModel = new MakeLoansModel();
            $result_make = $makeloansModel->makeDealLoans($deal_data, $advisoryFeeAccountId, $agencyFeeAccountId, $loanFeeAccountId, $payAgencyAccountId, $managementAgencyAccountId,$canalAgencyAccountId, $admin);
            if ($result_make === false) {
                throw new \Exception("放款逻辑处理失败,返回对象：" . json_encode($result_make));
            }

            $services_fee = 0;
            //自动提现
            if ($deal_ext['is_auto_withdrawal'] == 1) {
                //平台费+咨询费+担保费+支付服务费
                $services_fee = round($result_make['data']['services_fee'], 2);
                $function = '\core\service\deal\P2pDealGrantService::afterGrantWithdraw';
                $grantMoney = bcsub($deal_data['borrow_amount'], $services_fee, 2);
                $orderId = Idworker::instance()->getId();
                $param = array($orderId, $deal_data['id'], $grantMoney);
                $job_model = new JobsModel();
                $job_model->priority = JobsEnum::PRIORITY_DEAL_GRANT_WITHDRAW;
                if (!$job_model->addJob($function, $param, false, 99)) {
                    throw new \Exception('存管标的放款提现jobs添加失败');
                }
                $result = array('result' => 0, 'money' => $grantMoney, 'services_fee' => $services_fee);
            } else {
                $result = true;
            }

            //创建还款计划
            $result = $makeloansModel->createDealRepayList($deal_data['id']);
            if ($result === false) {
                throw new \Exception("生成回款与还款计划失败");
            }

            $function = '\core\service\makeloans\MakeLoansService::finishDealLoans';
            $param = array($deal_data['id']);
            $job_model = new JobsModel();
            $job_model->priority = JobsEnum::PRIORITY_FINISH_DEALLOANS;
            if (!$job_model->addJob($function, $param, false, 99)) {
                throw new \Exception('回款计划收尾任务添加失败');
            }

            $GLOBALS['db']->commit();
            $lock->releaseLock($lockKey);//解锁
        } catch (\Exception $e) {
            $log = array(
                'type' => 'makeDealLoans',
                'user_name' => $admin['adm_name'],
                'money' => bcsub($deal_data['borrow_amount'], $services_fee, 2),
                'deal_id' => $deal_data['id'],
                'path' => __FILE__,
                'time' => time(),
            );
            $log['desc'] = '提现申请失败，借款编号：' . $deal_data['id'] . ' 错误消息：' . $e->getMessage();
            Logger::error(sprintf('fail:info:%s, func:%s [%s:%s]', json_encode($log), __FUNCTION__, __FILE__, __LINE__));
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey);//解锁
            return false;
        }
        return $result;
    }


    /**
     * 放款收尾任务
     * @param $deal_id
     * @return bool
     */
    public function finishDealLoans($deal_id) {
        $deal = DealModel::instance()->find($deal_id);
        $GLOBALS['db']->startTrans();
        try {
            $repay_times = $deal->getRepayTimes();
            $deal_ext = DealExtModel::instance()->getInfoByDeal($deal_id, false);
            $loantype_tmp = array(
                $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_INTEREST_REPAY'],
                $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY'],
            );
            if ($deal_ext['first_repay_interest_day'] && in_array($deal->loantype, $loantype_tmp)) {
                // 如果是第一期还款日并且是按月、按季还款，还款期数加一期
                $repay_times++;
            }

            $total = $deal->buy_count * $repay_times;

            $count = DealLoanRepayModel::instance()->getCountByDealId($deal_id, DealLoanRepayEnum::MONEY_INTREST);
            if ($count < $total) {
                // 数据库中的count比理论值小代表放款未完成
                throw new \Exception(JobsEnum::ERRORMSG_NEEDDELAY, JobsEnum::ERRORCODE_NEEDDELAY);
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

            $message = array('dealId'=>$deal_id);

            $mq_job_model = new JobsModel();
            $mq_job_model->priority = JobsEnum::PRIORITY_MESSAGE_QUEUE_LOAN;
            $mq_res = $mq_job_model->addJob('\core\service\mq\MqService::loan', array('param' => $message), false, 90);
            if ($mq_res === false) {
                throw new \Exception("Add MqService loan Jobs Fail");
            }

            // 放款成功 消息队列
            //原有放款通知智多鑫、通知业财、向第三方推送还款计划，通过消息订阅实现
            MsgbusService::produce(MsgbusEnum::TOPIC_DEAL_MAKE_LOANS,$message);

            $GLOBALS['db']->commit();
            Monitor::add('PH_DEAL_MAKE_LOANS');
            return true;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            $GLOBALS['db']->rollback();
            throw $e;
        }
    }

    /**
     * [判断标的是否满足放款需求]
     * @author <fanjingwen@ucfgroup.com>
     * @param array $dealInfo [对应deal表中的key-value]
     * @return boolen [false:throw]
     */
    public function isOKForMakingLoans($dealInfo)
    {
        if (empty($dealInfo)) {
            throw new \Exception("无法找到id为{$dealInfo['id']}的标");
        }

        // 标没有满标
        if (!in_array($dealInfo['deal_status'], array(2, 4))) {
            throw new \Exception("标还未满标");
        }

        $dealService = new DealService();
        $isDtb = $dealService->isDealDT($dealInfo['id']);

        //除附件合同除外，验证合同状态
        $contract_new_service = new ContractNewService();
        $contract_category_info = $contract_new_service->getCategoryByCid($dealInfo['contract_tpl_type']);
        if (false === $contract_category_info) {
            throw new \Exception("合同服务异常");
        }
        if (!empty($contract_category_info) && !in_array($contract_category_info['typeTag'], ContractEnum::$tpl_type_tag_attachment)) {
            //验证合同是否已生成,添加合同服务化标的逻辑
            if (is_numeric($dealInfo['contract_tpl_type'])) {
                $dealContractModel = new DealContractModel();
                if (!$dealContractModel->getDealContractUnSignInfo($dealInfo['id'])) {
                    throw new \Exception("借款人或担保,咨询公司,委托人的合同未通过");
                }
            }
        }

        // 检查机构账户
        $dealAgcServs = new DealAgencyService();
        if ($isDtb) {//多投宝验证管理机构
            $management_agency_info = $dealAgcServs->getDealAgency($dealInfo['management_agency_id']); // 管理机构
            if (empty($management_agency_info) || empty($management_agency_info['user_id'])) {
                throw new \Exception('管理机构信息有误');
            }
        }

        //已经放过款
        if ($dealInfo['is_has_loans'] != 0) {
            throw new \Exception("已经放过款");
        }

        return true;
    }

    /**
     * 保存服务费
     * @param $dealInfo 标的信息
     * @return mixed
     */
    public function saveServiceFeeExt($dealInfo)
    {
        $loan_fee_arr = $consult_fee_arr = $guarantee_fee_arr = $pay_fee_arr = $canal_fee_arr = $management_fee_arr = array();
        $deal = DealModel::instance()->find($dealInfo['id']);
        $dealService = new DealService();
        $isDtb = $dealService->isDealDT($dealInfo['id']);
        $deal_ext_data = DealExtModel::instance()->getInfoByDeal($deal['id'], false);
        $repay_times = $deal->getRepayTimes();

        //手续费
        if ($deal_ext_data['loan_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['loan_fee_rate_type'] = 1;
            $deal_ext_data['loan_fee_ext'] = "";
        } else if ($deal_ext_data['loan_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['loan_fee_rate_type'] = 2;
            for ($i = 0; $i < $repay_times; $i++) {
                $loan_fee_arr[] = 0.00;
            }

            $loan_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            $loan_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $loan_fee_rate / 100.0);
            $deal_ext_data['loan_fee_ext'] = json_encode($loan_fee_arr);
        }

        //咨询费
        if ($deal_ext_data['consult_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['consult_fee_rate_type'] = 1;
            $deal_ext_data['consult_fee_ext'] = "";
        } else if ($deal_ext_data['consult_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['consult_fee_rate_type'] = 2;
            for ($i = 0; $i < $repay_times; $i++) {
                $consult_fee_arr[] = 0.00;
            }

            $consult_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $consult_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $consult_fee_rate / 100.0);
            $deal_ext_data['consult_fee_ext'] = json_encode($consult_fee_arr);
        }

        //担保费

        if ($deal_ext_data['guarantee_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['guarantee_fee_rate_type'] = 1;
            $deal_ext_data['guarantee_fee_ext'] = "";
        } else if ($deal_ext_data['guarantee_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['guarantee_fee_rate_type'] = 2;
            for ($i = 0; $i < $repay_times; $i++) {
                $guarantee_fee_arr[] = 0.00;
            }

            $guarantee_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $guarantee_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $guarantee_fee_rate / 100.0);
            $deal_ext_data['guarantee_fee_ext'] = json_encode($guarantee_fee_arr);
        }

        //支付费
        if ($deal_ext_data['pay_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['pay_fee_rate_type'] = 1;
            $deal_ext_data['pay_fee_ext'] = "";
        } else if ($deal_ext_data['pay_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['pay_fee_rate_type'] = 2;
            for ($i = 0; $i < $repay_times; $i++) {
                $pay_fee_arr[] = 0.00;
            }

            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);
            $deal_ext_data['pay_fee_ext'] = json_encode($pay_fee_arr);
        }

        //渠道费
        if ($deal_ext_data['canal_fee_rate_type'] == 1) {//前期收
            $deal_ext_data['canal_fee_rate_type'] = 1;
            $deal_ext_data['canal_fee_ext'] = "";
        } else if ($deal_ext_data['canal_fee_rate_type'] == 2) {//后期收
            $deal_ext_data['canal_fee_rate_type'] = 2;
            for ($i = 0; $i < $repay_times; $i++) {
                $canal_fee_arr[] = 0.00;
            }

            $canal_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $canal_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $canal_fee_rate / 100.0);
            $deal_ext_data['canal_fee_ext'] = json_encode($canal_fee_arr);
        }

        if ($isDtb) {//多投宝收取管理服务费
            //管理服务费
            if ($deal_ext_data['management_fee_rate_type'] == 1) {//前期收
                $deal_ext_data['management_fee_rate_type'] = 1;
                $deal_ext_data['management_fee_ext'] = "";
            } else if ($deal_ext_data['management_fee_rate_type'] == 2) {//后期收
                $deal_ext_data['management_fee_rate_type'] = 2;
                for ($i = 0; $i < $repay_times; $i++) {
                    $management_fee_arr[] = 0.00;
                }

                $management_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['management_fee_rate'], $deal['repay_time'], false);
                $management_fee_arr[] = $deal->floorfix($deal['borrow_amount'] * $management_fee_rate / 100.0);
                $deal_ext_data['management_fee_ext'] = json_encode($management_fee_arr);
            }
        }

        return $deal_ext_data->saveDealExtServicefee($deal['id'], $loan_fee_arr, $consult_fee_arr, $guarantee_fee_arr, $pay_fee_arr, $canal_fee_arr, $management_fee_arr);
    }

    /**
     * 生成回款计划
     */
    public function createDealLoanRepay($deal_repay, $deal_loan, $arr_deal_loan_repay, $arr_deal_repay, $principal, $is_last)
    {
        Logger::info('DealLoanRepayCreate. params:'.json_encode(func_get_args()));
        $deal_service = new DealService();
        $deal = DealModel::instance()->find($deal_loan['deal_id']);

        // 按月、按季等额本息需要检查是否为最后一期，如果为最后一期，则计算出剩余未生成计划的本金
        $special_repay_types = array(
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_MONTH_MATCH'],
            $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_MATCH'],
        );

        if ($is_last == true && in_array($deal['loantype'], $special_repay_types)) {
            $principal_fix = DealLoanRepayModel::instance()->getFixPrincipalByLoanId($deal_loan, $deal);
            if ($principal_fix === false) {
                throw new \Exception(JobsEnum::ERRORMSG_NEEDDELAY, JobsEnum::ERRORCODE_NEEDDELAY);
            }
        }

        $model = new DealLoanRepayModel();
        $model->deal_id = $deal_repay['deal_id'];
        $model->deal_repay_id = $deal_repay['id'];
        $model->deal_loan_id = $deal_loan['id'];
        $model->loan_user_id = $deal_loan['user_id'];
        $model->borrow_user_id = $deal_repay['user_id'];
        $model->time = $deal_repay['repay_time'];
        $model->status = DealLoanRepayEnum::STATUS_NOTPAYED;
        $model->create_time = get_gmtime();
        $model->update_time = get_gmtime();
        $model->deal_type = $deal_repay['deal_type'];

        $GLOBALS['db']->startTrans();
        try {
            // 本金
            $model->type = DealLoanRepayEnum::MONEY_PRINCIPAL;
            if (isset($principal_fix) && !empty($principal_fix)) {
                $model->money = $principal_fix;
            } else {
                $model->money = $arr_deal_loan_repay['principal'];
            }

            if (bccomp($model->money, 0, 2) >= 0) {
                if ($model->insert() === false) {
                    throw new \Exception('deal_loan_repay insert principal fail');
                }
            }

            // 利息
            $model->type = DealLoanRepayEnum::MONEY_INTREST;
            $model->money = $arr_deal_loan_repay['interest'];
            if ($model->insert() === false) {
                throw new \Exception('deal_loan_repay insert interest fail');
            }

            // 管理费
            $model->type = DealLoanRepayEnum::MONEY_MANAGE;
            $model->money = $arr_deal_repay['total'] - $arr_deal_loan_repay['total'];
            if (bccomp($model->money, 0, 2) == 1) {
                if ($model->insert() === false) {
                    throw new \Exception('deal_loan_repay insert manage fail');
                }
            }

            if ($is_last == true) {
                $moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL] = $deal_loan['money'];
                $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_PRINCIPAL] = $deal_loan['money'];
            }
            $moneyInfo[UserLoanRepayStatisticsEnum::NOREPAY_INTEREST] = $arr_deal_loan_repay['interest'];
            $moneyInfo[UserLoanRepayStatisticsEnum::CG_NOREPAY_EARNINGS] = $arr_deal_loan_repay['interest'];

            // 智多鑫标的不进入回款日历，不进入总资产
            if ($deal_service->isDealDT($deal['id']) === false) {
                $infoCal = array(
                    UserLoanRepayStatisticsEnum::NOREPAY_INTEREST => $arr_deal_loan_repay['interest'],
                    UserLoanRepayStatisticsEnum::NOREPAY_PRINCIPAL => (isset($principal_fix) && !empty($principal_fix)) ? $principal_fix : $arr_deal_loan_repay['principal'],
                );
                if (DealLoanRepayCalendarService::collect($model->loan_user_id,strtotime(to_date($deal_repay['repay_time'])), $infoCal) === false) {
                    throw new \Exception("save calendar error");
                }

                if (UserLoanRepayStatisticsService::updateUserAssets($model->loan_user_id,$moneyInfo) === false) {
                    throw new \Exception("user loan repay statistic error");
                }
            } else {
                if ($is_last == true) {
                    $moneyInfo = array(
                        UserLoanRepayStatisticsEnum::DT_LOAD_MONEY => $deal_loan['money'],
                    );
                    if (UserLoanRepayStatisticsModel::instance()->updateUserDtAsset($model->loan_user_id, $moneyInfo) === false) {
                        throw new \Exception("user loan repay statistic error");
                    }
                }
            }

            if ($is_last == true) {
                // 转账开始
                $bizToken = array('dealId' => $deal['id']);
                $userAccountId = AccountService::getUserAccountId($deal_loan['user_id'],UserAccountEnum::ACCOUNT_INVESTMENT);
                $res = AccountService::changeMoney($userAccountId,$deal_loan['money'],'投资放款',"编号{$deal['id']} {$deal['name']}，单号{$deal_loan['id']}",AccountEnum::MONEY_TYPE_LOCK_REDUCE,false,true,0,$bizToken);
                if (!$res) {
                    throw new \Exception("user loan repay change money fail");
                }
            }

            $msg = array('type' => 'grant','uid' => $model->loan_user_id ,'moneyInfo' => $moneyInfo,'time'=>time());
            MsgbusService::produce(MsgbusEnum::TOPIC_USER_ASSET_CHANGE,$msg);

            $GLOBALS['db']->commit();
            return true;
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            throw $e;
        }
        return true;
    }
}

