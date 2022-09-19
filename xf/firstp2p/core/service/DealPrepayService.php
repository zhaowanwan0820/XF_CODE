<?php

namespace core\service;

\FP::import("libs.libs.msgcenter");
\FP::import("app.deal");

use core\dao\CouponDealModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\DealPrepayModel;
use app\models\dao\User;
use app\models\dao\Deal;
use app\models\dao\DealLoanRepay;
use core\dao\DealProjectModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\FinanceQueueModel;
use libs\utils\Logger;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\DealPrepayMsgEvent;
use core\service\UserCarryService;
use core\service\DealRepayService;
use core\dao\DealRepayOplogModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealRepayModel;
use core\dao\DealLoadModel;

use core\service\DealService;
use core\service\MsgBoxService;

use libs\utils\Finance;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;
use NCFGroup\Common\Library\Idworker;

/**
 * 提前还款
 *
 * Class DealPrepayService
 * @package core\service
 */
class DealPrepayService extends BaseService {

    private $lockKey = null;

    public function prepay($param) {
        $id = intval($param['id']);
        if (empty($id)) {
            throw new \Exception('id is null');
        }
        $orderId = $param['orderId'];
        $isBorrowerSelf = $param['isBorrowerSelf'] ? true : false;
        $prepay = new \core\dao\DealPrepayModel();
        $prepay = $prepay->find($id);

        $deal = get_deal($prepay->deal_id);
        $deal_user_info = get_user("mobile", $prepay->user_id);
        $mobile = $deal_user_info['mobile'];
        $data = array(
            'name' => $deal['name'],
            'id' => $deal['id'],
            'tel' => app_conf('SHOP_TEL'),
        );
        $adminInfo = $param["admInfo"];
        $deal_model = \core\dao\DealModel::instance()->find($prepay->deal_id);

        $prepayUserId = isset($param['prepayUserId']) && !empty($param['prepayUserId']) && ($prepay->repay_type != 3) ? $param['prepayUserId'] : $prepay->user_id;

        $dealService = new DealService();
        $user = new UserModel();
        $user = $user->find($prepayUserId);
        $user->changeMoneyDealType = $dealService->getDealType($deal_model);
        $isP2pPath = $dealService->isP2pPath($deal_model);

        $this->lockKey = DealPrepayModel::PREPAY_LOCK_KEY_PREFIX . $prepay->deal_id;
        register_shutdown_function(array($this, "errCatch"), $prepay->deal_id);
        $lockRes = \SiteApp::init()->dataCache->getRedisInstance()->setNX($this->lockKey,$prepay->deal_id);
        if(!$lockRes){
            throw new \Exception("其它进程正在进行该标的还款操作,请勿重复进行!");
        }
        $bizToken = [
            'dealId' =>  $data['id'],
            'dealRepayId' =>  $id,
        ];
        if ($param['status'] == 2) {
            $GLOBALS['db']->startTrans();
            try {
                $user->changeMoney(-$prepay->prepay_money, '提前还款申请拒绝', '编号' . $data['id'] . ' ' . $data['name'], 0, 0, \core\dao\UserModel::TYPE_LOCK_MONEY ,0 , $bizToken);

                $content = "尊敬的客户，“" . $data['name'] . "”的提前还款申请未通过审核，如有疑问请联系客服" . $data['tel'] . "。";
                send_user_msg("", $content, 0, $prepay->user_id, get_gmtime(), 0, 1, 17);
                // SMSSend 提前还款拒绝短信
                $_mobile = $mobile;
                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                }
                \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_SMS_PREPAY_NOT_PASS', $data, $prepay->user_id);

                $save_res = $deal_model->changeRepayStatus(\core\dao\DealModel::NOT_DURING_REPAY);
                if(!$save_res){
                    throw new \Exception('修改标的还款状态失败！');
                }

                $GLOBALS['db']->commit();
                \libs\utils\Logger::info("dealAction->doPrepare(deny deal_id['" . $prepay->deal_id . "'], repay_id['" . $id . "]')");
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                \libs\utils\Logger::error("dealAction->doPrepare(deny deal_id['" . $prepay->deal_id . "'], prepay_id['" . $id . "]'), msg["
                        . $e->getMessage() . "], line:[" . $e->getLine() . "]");

                \SiteApp::init()->dataCache->getRedisInstance()->del($this->lockKey);
                throw new \Exception("提前还款申请拒绝");
            }
        } else if ($param['status'] == 1) {
            $GLOBALS['db']->startTrans();
            try {
                $deal = \core\dao\DealModel::instance()->find($prepay->deal_id);
                $deal['isDtb'] = 0;
                $dealService = new DealService();
                if($dealService->isDealDT($deal['id'])){
                    $deal['isDtb'] = 1;
                }


                $user->changeMoneyAsyn = $isP2pPath ? false : true;
                $user->changeMoney($prepay->prepay_money, '提前还款申请通过', "编号" . $data['id'] . ' ' . $data['name'], $adminInfo['adm_id'], 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, 0, $bizToken);


                //还款时候触更新改投资体现限制
                $userCarryService = new UserCarryService();
                $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($prepayUserId,$prepay->prepay_money);
                if($rs === false){
                    throw new \Exception("更新投资体现限制失败");
                }

                // JIRA#1108 还款时收取服务费
                // 手续费
                if (bccomp($prepay->loan_fee, '0.00', 2) > 0) {
                    $loan_user_id = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']);
                    $user = $user->find($loan_user_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;

                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->loan_fee, '平台手续费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);

                    if (bccomp($prepay->loan_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'LOAN_FEE|' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $loan_user_id,
                            'repaymentAmount' => bcmul($prepay->loan_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }
                // 咨询费
                if (bccomp($prepay->consult_fee, '0.00', 2) > 0) {
                    $advisory_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
                    $consult_user_id = $advisory_info['user_id']; // 咨询机构账户
                    $user = $user->find($consult_user_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;
                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->consult_fee, '咨询费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($prepay->consult_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CONSULT_FEE' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $consult_user_id,
                            'repaymentAmount' => bcmul($prepay->consult_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }
                // 担保费
                if (bccomp($prepay->guarantee_fee, '0.00', 2) > 0) {
                    $agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 咨询机构
                    $guarantee_user_id = $agency_info['user_id']; // 担保机构账户
                    $user = $user->find($guarantee_user_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;
                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->guarantee_fee, '担保费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($prepay->guarantee_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'GUARANTEE_FEE|' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $guarantee_user_id,
                            'repaymentAmount' => bcmul($prepay->guarantee_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 支付服务费
                if (bccomp($prepay->pay_fee, '0.00', 2) > 0) {
                    $pay_agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
                    $pay_user_id = $pay_agency_info['user_id']; // 支付机构账户
                    $user = $user->find($pay_user_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;
                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->pay_fee, '支付服务费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($prepay->pay_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'PAY_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $pay_user_id,
                            'repaymentAmount' => bcmul($prepay->pay_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 渠道服务费
                if (bccomp($prepay->canal_fee, '0.00', 2) > 0) {
                    $canal_agency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 渠道机构
                    $canal_user_id = $canal_agency_info['user_id']; // 支付机构账户
                    $user = $user->find($canal_user_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;
                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->canal_fee, '渠道服务费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($prepay->canal_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CANAL_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $canal_user_id,
                            'repaymentAmount' => bcmul($prepay->canal_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                // 管理服务费
                if ( ($deal['isDtb'] == 1) &&(bccomp($prepay->management_fee, '0.00', 2) > 0)) {
                    $managementagency_info = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构
                    $managementuser_id = $managementagency_info['user_id']; // 支付机构账户
                    $user = $user->find($managementuser_id);
                    $user->changeMoneyAsyn = $isP2pPath ? false : true;
                    $user->changeMoneyDealType = $dealService->getDealType($deal);
                    $user->changeMoney($prepay->management_fee, '管理服务费', "编号" . $deal['id'] . ' ' . $deal['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($prepay->management_fee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'MANAGEMENT_SERVICE_FEE|' . $deal['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $managementuser_id,
                            'repaymentAmount' => bcmul($prepay->management_fee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $deal['id'],
                        );
                    }
                }

                $deal_service = new DealService();
                if ($deal_service->isDealDTV3($prepay->deal_id) === true) {
                    $jobs_model = new JobsModel();
                    $function = '\core\dao\DealPrepayModel::prepayDtV3';
                    $param = array(
                        'prepay_id' => $prepay->id,
                        'prepay_user_id' => $prepayUserId,
                    );
                    $jobs_model->priority = 85;
                    $r = $jobs_model->addJob($function, array('param' => $param));
                    if ($r === false) {
                        throw new \Exception("add prepay by loan id jobs error");
                    }
                } else {
                    // TODO finance 提前还款 | 已同步
                    $arr_deal_load = \core\dao\DealLoadModel::instance()->getDealLoanList($prepay->deal_id);
                    foreach ($arr_deal_load as $k => $deal_load) {
                        $jobs_model = new JobsModel();
                        $function = '\core\dao\DealPrepayModel::prepayByLoanId';
                        $param = array(
                            'deal_loan_id' => $deal_load['id'],
                            'prepay_id' => $prepay->id,
                            'prepay_user_id' => $prepayUserId,
                        );
                        $jobs_model->priority = 85;
                        $r = $jobs_model->addJob($function, array('param' => $param));
                        if ($r === false) {
                            throw new \Exception("add prepay by loan id jobs error");
                        }
                    }
                }

                $jobs_model = new JobsModel();
                $function = '\core\dao\DealPrepayModel::finishPrepay';
                $param = array('prepay_id' => $prepay->id,'user_id' => $prepay->user_id,'isBorrowerSelf'=>$isBorrowerSelf ,'prepayUserId'=>$prepayUserId, 'orderId' => $orderId);
                $jobs_model->priority = 85;
                $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
                if ($r === false) {
                    throw new \Exception("add finish prepay jobs error");
                }

                // 提前还款数据同步
                if ($syncRemoteData && !$dealService->isP2pPath($deal)) {
                    if (!FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
                        throw new \Exception("FinanceQueueModel push error");
                    }
                }
                $success = isset($param['success']) ? $param['success'] : 1;
                $saveLogFile = isset($param['saveLogFile']) ? $param['saveLogFile'] : 2;

                //增加提前还款的操作记录
                $repayOpLog = new DealRepayOplogModel();
                $repayOpLog->operation_type = $isBorrowerSelf ? DealRepayOplogModel::REPAY_TYPE_PRE_SELF : DealRepayOplogModel::REPAY_TYPE_PRE;//提前还款
                $repayOpLog->operation_time = get_gmtime();
                $repayOpLog->operation_status = 1;
                $repayOpLog->operator = $adminInfo['adm_name'];
                $repayOpLog->operator_id = $adminInfo['adm_id'];

                $repayOpLog->deal_id = $deal['id'];
                $repayOpLog->deal_name = $deal['name'];
                $repayOpLog->borrow_amount = $deal['borrow_amount'];
                $repayOpLog->rate = $deal['rate'];
                $repayOpLog->loantype = $deal['loantype'];
                $repayOpLog->repay_period = $deal['repay_time'];
                $repayOpLog->user_id = $deal['user_id'];
                $repayOpLog->submit_uid = intval($param['submitUid']);

                $repayOpLog->deal_repay_id = $prepay->id;
                $repayOpLog->repay_money = $prepay->prepay_money;
                $repayOpLog->real_repay_time = get_gmtime();

                //存管&&还款方式
                $repayOpLog->repay_type = $prepay->repay_type;
                $repayOpLog->report_status = $deal['report_status'];

                $save_res = $repayOpLog->save();
                if(!$save_res) {
                    throw new \Exception("插入还款操作记录失败");
                }

                // JIRA#3090 定向委托投资标的超额收益功能
                if ($deal['type_id'] == DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT)) {
                    $incomeExcessService = new IncomeExcessService();
                    $res = $incomeExcessService->pendingRepay($deal['id']);
                    if(!$res) {
                        throw new \Exception("超额收益待还款状态更新失败");
                    }
                }

                $GLOBALS['db']->commit();

                //save_log('提前还款审核 id:' . $id, $success, '', '', $saveLogFile);
                \libs\utils\Logger::info("dealAction->doPrepare(pass deal_id['" . $prepay->deal_id . "'], repay_id['" . $id . "]')");
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                \libs\utils\Logger::error("dealAction->doPrepare(pass deal_id['" . $prepay->deal_id . "'], repay_id['" . $id . "]'), msg["
                        . $e->getMessage() . "], line:[" . $e->getLine() . "]");

                \SiteApp::init()->dataCache->getRedisInstance()->del($this->lockKey);
                throw new \Exception("通过审核");
            }
        }else{
            \SiteApp::init()->dataCache->getRedisInstance()->del($this->lockKey);
            throw new \Exception("参数错误");
        }
        return true;
    }

    /**
     * 以合并多笔投资的方式发送提前回款站内信
     * @param DealPrepayModel $prepay
     * @param DealModel $deal
     * @param int $loan_user_id
     * @return
     */
    public function sendDealPrepayMessage($prepay, $deal, $loan_user_id)
    {
        $deal_service = new DealService();
        if (true ===  $deal_service->isDealDT($deal->id)) {
            return;
        }
        if (true ===  $deal_service->isDealDTV3($deal->id)) {
            return;
        }

        // 发消息开始
        $principal = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayModel::MONEY_PREPAY, DealLoanRepayModel::STATUS_ISPAYED, true));
        $prepay_interest = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayModel::MONEY_PREPAY_INTREST, DealLoanRepayModel::STATUS_ISPAYED, true));
        $prepay_compensation = $deal->floorfix(DealLoanRepayModel::instance()->getTotalMoneyOfUserByDealId($prepay->deal_id, $loan_user_id, DealLoanRepayModel::MONEY_COMPENSATION, DealLoanRepayModel::STATUS_ISPAYED, true));
        $prepay_money = $principal + $prepay_interest + $prepay_compensation;

        $prepay_money_format = format_price($prepay_money);
        $principal_format = format_price($principal);
        $prepay_interest_format = format_price($prepay_interest);
        $prepay_compensation_format = format_price($prepay_compensation);

        $content = sprintf('您投资的“%s”发生提前还款，总额%s，其中提前还款本金%s，提前还款利息%s，提前还款补偿金%s。本次投资已回款完毕。', $deal->name, $prepay_money_format, $principal_format, $prepay_interest_format, $prepay_compensation_format);

        //哈哈农庄还款后给用户发送本金转出短信
        if($deal_service->isDealHF($deal->id)){
            $content .= "本金已根据您的授权转入云图控股账户，详情查询您的账户中合同协议，如有问题咨询400-110-0025。";
        }

        $load_counts = DealLoadModel::instance()->getDealLoadCountsByUserId($deal->id, $loan_user_id, true);
        $structured_content = array(
            'money' => sprintf('+%s', number_format($prepay_money, 2)),
            'repay_periods' => '已完成', // 期数
            'main_content' => rtrim(sprintf("%s%s%s%s",
                                            sprintf("项目：%s（%s笔）\n", $deal['name'], $load_counts),
                                            empty($principal) ? '' : sprintf("本金：%s\n", $principal_format),
                                            empty($prepay_interest) ? '' : sprintf("收益：%s\n", $prepay_interest_format),
                                            empty($prepay_compensation) ? '' : sprintf("提前还款补偿金：%s\n", $prepay_compensation_format)
                                            )),
            'is_last' => 1,
            'prepay_tips' => '提前回款',
            'turn_type' => MsgBoxEnum::TURN_TYPE_CONTINUE_INVEST, // app 跳转类型标识
        );

        $msgbox = new MsgBoxService();
        $msgbox->create($loan_user_id, 9, '回款', $content, $structured_content);
    }

    /**
     * 提前还款检查 判断是否能够发起提前还款
     * 成功返回标的信息+扩展信息
     */
    public function prepayCheck($deal_id) {
        $deal_id = intval($deal_id);
        if($deal_id == 0){
            throw new \Exception("deal_id 参数不合法");
        }
        $deal_info = \core\dao\DealModel::instance()->find($deal_id);
        $deal_info['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal_id)){
            $deal_info['isDtb'] = 1;
        }

        if(!$deal_info || $deal_info['deal_status'] != 4){
            Logger::error("提前还款申请失败 deal_id:{$deal_id} 标状态错误");
            throw new \Exception("当前状态不允许提前还款");
        }

        if($deal_info['is_has_loans'] != 1) {
            Logger::error("提前还款申请失败 deal_id:{$deal_id} 标状正在放款不能进行提前还款");
            throw new \Exception("当前状态不允许提前还款");
        }
        if($deal_info['is_during_repay'] == \core\dao\DealModel::DURING_REPAY){
            Logger::error('提前还款申请失败 fail deal_id:'.$deal_id.' 标状态错误，正在还款中 during repay');
            throw new \Exception("操作失败， 正在还款中 deal_id:{$deal_id}");
        }
//        if(in_array($deal_info['loantype'],array(1,2,7))) {
//            throw new \Exception("提前还款暂不支持按月等额还款、按季等额还款方式、公益标等");
//        }
        if($deal_info['loantype'] == 7) {
            throw new \Exception("提前还款暂不支持公益标");
        }

        // $deal_ext = M("DealExt")->where(array('deal_id' => $deal_id))->find();
        $deal_ext = DealExtModel::instance()->findByViaSlave('deal_id='.$deal_id);
        if(!$deal_ext) {
            Logger::error('提前还款申请失败 deal_id:'.$deal_id.' 未找到标的扩展信息');
            throw new \Exception("未找到标的扩展信息");
        }

        return array('deal_base_info'=>$deal_info,'deal_ext_info'=>$deal_ext);
    }

    /**
     * 判断是否能提前还款
     * 判断是否存在提前还款申请 不存在保存
     * 计算提前还款各项费用
     * @param $dealId 标的ID
     * @param $repayType  还款类型(0-普通 1-待垫)
     */
    public function prepayForFenqi($dealId,$repayType=0) {
        $endInterestDay = date('Y-m-d'); // 计息结束日期为今天
        return $this->prepayPipeline($dealId,$endInterestDay,$repayType);
    }

    /**
     * @param object $deal 标的对象
     * @param object $deal_ext 标的扩展信息
     * @param $end_day 结束日期 个数：2016-02-12
     * @param $isBorrowerSelf 是否自主提前还款
     */
    public function prepayCalc($deal,$deal_ext,$end_day,$isBorrowerSelf = false) {
        if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_day)) {
            throw new \Exception("结束日期{$end_day}格式不正确");
        }

        // 计算计息日期
        $dps = new DealRepayService();
        $interest_time =  $dps->getMaxRepayTimeByDealId($deal);
        // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
        $interest_time = to_timespan(to_date($interest_time,'Y-m-d')); // 转换为零点开始
        $end_interest_time = to_timespan($end_day); // 计息结束日期

        $calc_interest_time = $end_interest_time; // 实际计算的计息结束日期

        if($isBorrowerSelf) {
            $end_day = ($end_interest_time - $deal['repay_start_time'])/86400 > $deal['prepay_days_limit']
                ? $end_day
                : to_date(($deal['repay_start_time'] + $deal['prepay_days_limit'] * 86400 ),'Y-m-d');

            // 如该天数小于该标的提前还款/回购限制天数，则以计息日加上提前还款/回购限制天数后的日期作为提前还款计息结束日期
            $calc_interest_time = to_timespan($end_day); // 实际计息结束日期
        }

        $remain_days = ceil(($calc_interest_time - $interest_time)/86400); // 利息天数

        if($end_interest_time < $interest_time) {
            throw new \Exception("计息结束日期必须大于计息日期");
        }

        $deal_loan_repay_model = new DealLoanRepay();

        $remain_principal = get_remain_principal($deal);

        $prepay_result = $deal_loan_repay_model->getPrepayMoney($deal['id'], $remain_principal, $remain_days);

        $prepay_money = $prepay_result['prepay_money']; // 还款总额
        $remain_principal = $prepay_result['principal']; // 应还本金
        $prepay_interest = $prepay_result['prepay_interest']; // 应还利息

        $deal_dao = new Deal();
        $remain_principal = $deal_dao->floorfix($remain_principal);
        $prepay_interest = $deal_dao->floorfix($prepay_interest);
        $prepay_compensation = $deal_dao->floorfix($prepay_money - $prepay_interest - $remain_principal);
        //$prepay_compensation = $deal_dao->floorfix($deal['borrow_amount'] * $deal['prepay_rate'] / 100); // 借款金额x提前还款违约金系数

        // 各项未收费用
        $deal_repay_model = new \core\dao\DealRepayModel();
        $fees = $deal_repay_model->getNoPayFees($deal,$deal_ext,$end_day);

        $prepay_money = $deal_dao->floorfix($prepay_money + $fees['loan_fee'] + $fees['consult_fee'] + $fees['guarantee_fee'] + $fees['pay_fee'] + $fees['canal_fee']);
        //$prepay_money = bcsub($prepay_money,$prepay_compensation,2);

        // 开始计算回扣支付费用
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);
        if ($deal_ext['pay_fee_rate_type'] == 1) {
            $fee_days = ceil(($calc_interest_time - $deal['repay_start_time'])/86400); // 费用天数
            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);

            $pay_fee_rate_real = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $fee_days, false);
            $pay_fee_real = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate_real / 100.0);

            $pay_fee_remain = bcsub($pay_fee, $pay_fee_real, 2);
        }

        $data = array(
            'deal_id'             => $deal['id'],
            'user_id'             => $deal['user_id'],
            'interest_time'       => $interest_time, // 计息日期
            'prepay_time'         => $end_interest_time, // 提前还款日期
            'remain_days'         => $remain_days, // 利息天数
            'prepay_money'        => $prepay_money,
            'remain_principal'    => $remain_principal,
            'prepay_interest'     => $prepay_interest,
            'prepay_compensation' => $prepay_compensation,
            'loan_fee'            => $fees['loan_fee'],
            'consult_fee'         => $fees['consult_fee'],
            'guarantee_fee'       => $fees['guarantee_fee'],
            'management_fee'      => 0,// 暂时不考虑智多鑫管理费
            'pay_fee'             => $fees['pay_fee'],
            'canal_fee'           => $fees['canal_fee'],
            'pay_fee_remain'      => !empty($pay_fee_remain) ? $pay_fee_remain : 0,
            'deal_type'           => $deal['deal_type'],
        );
        return $data;
    }

    /**
     * 保存提前还款数据
     * @param $dealId 标的ID
     * @param $data 提前还款数据
     * @return bool
     * @throws \Exception
     */
    public function prepaySave($dealId,$data) {
        $sql = "select * from ".DB_PREFIX."deal_prepay where deal_id= $dealId and status =0";
        $res = $GLOBALS['db']->getRow($sql);

        if($res) {
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"UPDATE","id=".$res['id']);
            if ($res == false) {
                throw new \Exception("insert deal_prepay error deal_id:".$dealId);
            }
        }else{
            $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT");
            if ($res == false) {
                throw new \Exception("update deal_prepay error deal_id:".$dealId);
            }
        }
        return true;
    }

    /**
     * 提前还款试算接口
     * @param $dealId 标的ID
     * @param $endInterestDay 结息日
     * @return array
     * @throws \Exception
     */
    public function prepayTryCalc($dealId,$endInterestDay) {
        $dealInfo = $this->prepayCheck($dealId);
        $deal = $dealInfo['deal_base_info'];
        $dealExt = $dealInfo['deal_ext_info'];
        $calcInfo = $this->prepayCalc($deal,$dealExt,$endInterestDay);
        return $calcInfo;
    }


    /**
     * 提前还款一条龙服务
     * @param $dealId 提前还款的标的ID
     * @param $endDate 提前还款 计息结束日期
     * @param $repayType 还款账户类型
     * @param $adminInfo 提前还款操作日志信息
     * @param $isBorrowerSelf 是否客户自助提前还款
     */
    public function prepayPipeline($dealId,$endDate,$repayType,$adminInfo=array(),$isBorrowerSelf=false, $orderId = '') {
        Logger::info(implode(',',array(__CLASS__,__FUNCTION__,"prepay begin"," deal_id:{$dealId},endDate:{$endDate},repayAccountType:{$repayType}")));
        $adminInfo['adm_id'] = !isset($adminInfo['adm_id']) ? 0 : $adminInfo['adm_id'];
        $adminInfo['adm_name'] = !isset($adminInfo['adm_name']) ? 0 : $adminInfo['adm_name'];

        try{
            $GLOBALS['db']->startTrans();
            $ds = new DealService();
            $isP2pPath = $ds->isP2pPath(intval($dealId));
            $dealMoneyType = $ds->getDealType(intval($dealId));

            $repayUserId = $ds->getRepayUserAccount($dealId,$repayType);
            if(!$repayUserId) {
                throw new \Exception("获取还款用户ID失败deal_id:{$dealId}");
            }
            $dealInfo = $this->prepayCheck($dealId);
            $deal = $dealInfo['deal_base_info'];
            $dealExt = $dealInfo['deal_ext_info'];
            $endInterestDay = $endDate;
            $retry = 0; // 无重试保证，不能重试

            $calcInfo = $this->prepayCalc($deal,$dealExt,$endInterestDay,$isBorrowerSelf); // 提前还款各项金额计算
            $calcInfo['status'] = 1; // 自动审核通过
            $calcInfo['repay_type'] = $repayType; // 借款人

            $this->prepaySave($dealId,$calcInfo); // 保存提前还款信息

            $prepay = new \core\dao\DealPrepayModel();
            $prepay = $prepay->findBy("deal_id=".$dealId);

            // 标的优惠码设置信息
            $dealCoupon = CouponDealModel::instance()->findByViaSlave('deal_id='.$dealId);
            if(!$dealCoupon) {
                throw new \Exception("优惠码设置信息获取失败deal_id:{$dealId}");
            }
            // 优惠码结算时间为放款时结算：直接保存计算后得出的各项数据
            // 优惠码结算时间为还清时结算： 保存结算后的各项数据 并修改优惠码返利天数
            if($dealCoupon['pay_type'] == 1) {
                $rebate_days = floor((get_gmtime() - $deal['repay_start_time'])/86400); // 优惠码返利天数=操作日期-放款日期
                if($rebate_days < 0) {
                    throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebate_days);
                }
                // 更新优惠码返利天数
                $coupon_deal_service = new CouponDealService();
                $coupon_res = $coupon_deal_service->updateRebateDaysByDealId($dealId, $rebate_days);;
                if(!$coupon_res){
                    throw new \Exception("更新标优惠码返利天数失败");
                }
            }

            // 将标的置为还款中
            $res = $deal->changeRepayStatus(\core\dao\DealModel::DURING_REPAY);
            if ($res == false) {
                throw new \Exception("chage repay status error");
            }

            // 用户资金冻结
            $deal_dao = new Deal();
            // 还款总额 = 应还本金+应还利息+手续费+咨询费+担保费+支付服务费。
            $prepay_money = $prepay['prepay_money'];

            //代充值还款逻辑
            $ds = new \core\service\DealService();
            $user = UserModel::instance()->find($repayUserId);
            $user->changeMoneyDealType = $ds->getDealType($deal);;

            $bizToken = [
                'dealId' => $dealId,
            ];

            if($prepay->repay_type == 3){
                $dealUser = UserModel::instance()->find($deal['user_id']);
                $dealUser->changeMoneyDealType = DealModel::DEAL_TYPE_SUPERVISION;
                $generationRechargeUserId = $ds->getRepayUserAccount($dealId,$prepay->repay_type);
                $generationRechargeUser = UserModel::instance()->find($generationRechargeUserId);
                $generationRechargeUser->changeMoneyDealType = $ds->getDealType($deal);

                if ($generationRechargeUser->changeMoney(-$prepay_money, "代充值扣款", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                    throw new \Exception('代充值提前还款失败');
                }

                if ($dealUser->changeMoney($prepay_money, "代充值", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                    throw new \Exception('代充值提前还款失败');
                }

                $syncRemoteData[] = array(
                    'outOrderId' => 'GENERATION_RECHARGE_FEE|' . $deal['id'],
                    'payerId' => $generationRechargeUser->id,
                    'receiverId' => $user->id,
                    'repaymentAmount' => bcmul($prepay_money, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 1,
                    'batchId' => $deal['id'],
                );
                if (!empty($syncRemoteData) && !$ds->isP2pPath($deal)) {
                    FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH);
                }

                $res = $dealUser->changeMoney($prepay_money, "提前还款", '编号'.$dealId, $adminInfo['adm_id'], 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);

            }else{
                $res = $user->changeMoney($prepay_money, "提前还款", '编号'.$dealId, $adminInfo['adm_id'], 0, UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
            }

            if(!$res) {
                throw new \Exception("用户提前还款资金冻结失败");
            }

            $param = array('id' => $prepay->id, 'status' => $prepay->status, 'admInfo' => $adminInfo,'prepayUserId'=>$repayUserId,'isBorrowerSelf'=>$isBorrowerSelf);
            if(!$isP2pPath) {
                // 异步处理还款
                $function  = '\core\service\DealPrepayService::prepay';
            }else{
                // p2p 还款逻辑
                if($prepay->repay_type == 3){
                    $generationRechargeUserId = $ds->getRepayUserAccount($dealId,$prepay->repay_type);
                    $param['generationRechargeUserId'] = $generationRechargeUserId;
                }
                if ($orderId == '') {
                    $orderId = Idworker::instance()->getId();
                }
                $param['orderId'] = $orderId;
                $function = '\core\service\P2pDealRepayService::dealPrepayRequest';
                $param = array('orderId'=>$orderId,'prepayId'=>$prepay->id,'params'=>$param);
            }

            // 启动jobs进行还款操作
            $job_model = new JobsModel();
            $job_model->priority = 80;
            $job_model->addJob($function, array('param' => $param), false, $retry);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            Logger::error(implode(',',array(__CLASS__,__FUNCTION__,$ex->getMessage())));
            throw $ex;
        }
        $params = array(
            'dealId' =>$dealId,
            'endDate' => $endDate,
            'repayType' => $repayType,
            'adminInfo' => $adminInfo,
            'isBorrowerSelf' => $isBorrowerSelf,
        );
        Logger::info(implode(',',array(__CLASS__,__FUNCTION__,"prepay success params:".json_encode($params))));
        return true;
    }

    /**
     * 通过标的，汇总项目的提前还款明细
     * @param int $project_id
     * @return array $prepay_collection
     */
    public function getProjectPrepayInfo($project_id)
    {
        $sql_deal_ids = sprintf('SELECT `id` FROM %s WHERE `project_id` = %d', DealModel::instance()->tableName(), $project_id);

        $sql_prepay = sprintf('SELECT
            SUM(`prepay_money`) AS `prepay_money`,
            SUM(`prepay_interest`) AS `prepay_interest`,
            SUM(`prepay_compensation`) AS `prepay_compensation`,
            SUM(`remain_principal`) AS `remain_principal`,
            SUM(`loan_fee`) AS `loan_fee`,
            SUM(`consult_fee`) AS `consult_fee`,
            SUM(`guarantee_fee`) AS `guarantee_fee`,
            SUM(`pay_fee`) AS `pay_fee`,
            SUM(`canal_fee`) AS `canal_fee`,
            SUM(`management_fee`) AS `management_fee`,
            SUM(`pay_fee_remain`) AS `pay_fee_remain`,
            MIN(`id`) AS `id`,
            MIN(`prepay_time`) AS `prepay_time`,
            MIN(`remain_days`) AS `remain_days`,
            MIN(`repay_type`) AS `repay_type`
            FROM %s WHERE `status` = 0 AND `deal_id` IN (%s)', DealPrepayModel::instance()->tableName(), $sql_deal_ids);

        return DealPrepayModel::instance()->findBySqlViaSlave($sql_prepay);
    }

    /**
     *项目提前还款
     */

    public function projectPrepay($param){

        $projectId = intval($param['projectId']);
        if (empty($projectId)) {
            throw new \Exception('project id is null');
        }

        $isBorrowerSelf = $param['isBorrowerSelf'] ? true : false;

        if(empty($param['prepayIds'])){
            throw new \Exception('prepayids is null');
        }

        if(empty($param['prepayUserId'])){
            throw new \Exception('prepayUserId is null');
        }

        $prepayUserId = $param['prepayUserId'];

        $GLOBALS['db']->startTrans();

        $project = DealProjectModel::instance()->find($projectId);
        $prepay = new \core\dao\DealPrepayModel();
        $prepayIds = $param['prepayIds'];
        $adminInfo = $param["admInfo"];
        $deal_user_info = get_user("mobile", $prepayUserId);
        $mobile = $deal_user_info['mobile'];

        $data = array(
            'name' => $project['name'],
            'id' => $project['id'],
            'tel' => app_conf('SHOP_TEL'),
        );

        $prepayMoney = 0;

        $user = new UserModel();
        $user = $user->find($prepayUserId);
        $user->changeMoneyDealType = DealModel::DEAL_TYPE_EXCLUSIVE;

        $bizToken = [
            'projectId' => $projectId,
        ];
        if ($param['status'] == 2) {
            try {
                foreach($prepayIds as $prepayId){
                    $prepay = $prepay->find($prepayId);
                    $deal = get_deal($prepay->deal_id);
                    $deal_model = \core\dao\DealModel::instance()->find($prepay->deal_id);

                    $save_res = $deal_model->changeRepayStatus(\core\dao\DealModel::NOT_DURING_REPAY);

                    $prepayMoney = bcadd($prepayMoney,$prepay->prepay_money,2);
                    if(!$save_res){
                        throw new \Exception('修改标的还款状态失败！');
                    }
                }
                $user->changeMoney(-$prepayMoney, '提前还款申请拒绝', $project['name'], 0, 0, \core\dao\UserModel::TYPE_LOCK_MONEY, 0, $bizToken);
                $content = "尊敬的客户，“" . $project['name'] . "”的提前还款申请未通过审核，如有疑问请联系客服" . app_conf('SHOP_TEL') . "。";
                send_user_msg("", $content, 0, $prepayUserId, get_gmtime(), 0, 1, 17);
                // SMSSend 提前还款拒绝短信
                $_mobile = $mobile;
                if ($user['user_type'] == UserModel::USER_TYPE_ENTERPRISE)
                {
                    $_mobile = 'enterprise';
                }

                \libs\sms\SmsServer::instance()->send($_mobile, 'TPL_SMS_PREPAY_NOT_PASS', $data, $prepayUserId);

                $GLOBALS['db']->commit();

            }catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                \libs\utils\Logger::error("project prepay fail (project_id['" . $projectId . "'], msg[".$e->getMessage() . "], line:[" . $e->getLine() . "]");
                throw new \Exception("提前还款申请拒绝");
            }
        }else if($param['status'] == 1) {
            try {
                $user->changeMoneyAsyn = true;
                $user->changeMoneyDealType = $project['deal_type'];


                $loanFee = 0;
                $consultFee = 0;
                $guaranteeFee = 0;
                $payFee = 0;
                $canalFee = 0;
                $managementFee = 0;
                $principal = 0;
                $interest = 0;
                $compensation = 0;
                $dtbTypeId = '';

                foreach($prepayIds as $prepayId){
                    $prepay = $prepay->find($prepayId);
                    $deal = \core\dao\DealModel::instance()->find($prepay->deal_id);
                    if(empty($dtbTypeId)){
                        $dtbTypeId = DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_DTB);
                        $deal['isDtb'] = 0;
                        if($deal['type_id'] == $dtbTypeId){
                            $deal['isDtb'] = 1;
                        }
                    }
                    if(empty($fistDeal)){
                        $fistDeal = $deal;
                    }

                    $prepayMoney = bcadd($prepayMoney,$prepay->prepay_money,2);
                    $loanFee = bcadd($loanFee,$prepay->loan_fee,2);
                    $consultFee = bcadd($consultFee,$prepay->consult_fee,2);
                    $guaranteeFee = bcadd($guaranteeFee,$prepay->guarantee_fee,2);
                    $payFee = bcadd($payFee,$prepay->pay_fee,2);
                    $canalFee = bcadd($canalFee,$prepay->canal_fee,2);
                    $managementFee = bcadd($managementFee,$prepay->management_fee,2);
                    $principal = bcadd($principal,$prepay->remain_principal,2);
                    $interest = bcadd($interest,$prepay->prepay_interest,2);
                    $compensation = bcadd($compensation,$prepay->prepay_compensation,2);

                }
                $user->changeMoney($prepayMoney, '提前还款申请通过', $project['name'], $adminInfo['adm_id'], 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, 0, $bizToken);

                //给委托机构还本付息
                $userModel = new UserModel();
                $entrustInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['entrust_agency_id']);
                $entrustUser = $userModel->find($entrustInfo['user_id']);
                $entrustUser->changeMoneyAsyn = true;
                $entrustUser->changeMoneyDealType = $project['deal_type'];
                if ($entrustUser->changeMoney($principal+$interest+$compensation, "权益回购", $project['name'],$adminInfo['adm_id'], 0, 0, 0, $bizToken) === false) {
                    throw new \Exception('还款失败');
                }else{
                    $syncRemoteData[] = array(
                        'outOrderId' =>  $projectId,
                        'payerId' => $user->id,
                        'receiverId' => $entrustInfo['user_id'],
                        'repaymentAmount' => bcmul($principal+$interest+$compensation, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $projectId,
                    );
                }

                //冻结委托人帐号还款金额
                if ($entrustUser->changeMoney($principal+$interest+$compensation, "权益回购本息冻结", $project['name'],$adminInfo['adm_id'],0,1, 0, $bizToken) === false) {
                    throw new \Exception('还款失败');
                }

                //扣除委托人给借款人的本息
                if ($entrustUser->changeMoney($principal+$interest+$compensation, "偿还本息", $project['name'],$adminInfo['adm_id'],0,2, 0, $bizToken) === false) {
                    throw new \Exception('还款失败');
                }

                //还款时候触更新改投资提现限制
                $userCarryService = new UserCarryService();
                $rs = $userCarryService->updateWithdrawLimitAfterRepalyMoney($prepayUserId,$prepayMoney);
                if($rs === false){
                    throw new \Exception("更新投资提现限制失败");
                }

                // JIRA#1108 还款时收取服务费
                // 手续费
                if (bccomp($loanFee, '0.00', 2) > 0) {
                    $loanUserId = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($fistDeal['id']);
                    $user = $user->find($loanUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($loanFee, '平台手续费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($loanFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'LOAN_FEE|' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $loanUserId,
                            'repaymentAmount' => bcmul($loanFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }
                // 咨询费
                if (bccomp($consultFee, '0.00', 2) > 0) {
                    $advisoryInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['advisory_id']); // 咨询机构
                    $consultUserId = $advisoryInfo['user_id']; // 咨询机构账户
                    $user = $user->find($consultUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($consultFee, '咨询费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($consultFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CONSULT_FEE' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $consultUserId,
                            'repaymentAmount' => bcmul($consultFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }

                // 担保费
                if (bccomp($guaranteeFee, '0.00', 2) > 0) {
                    $agencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['agency_id']); // 咨询机构
                    $guaranteeUserId = $agencyInfo['user_id']; // 担保机构账户
                    $user = $user->find($guaranteeUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($guaranteeFee, '担保费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($guaranteeFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'GUARANTEE_FEE|' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $guaranteeUserId,
                            'repaymentAmount' => bcmul($guaranteeFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }

                // 支付服务费
                if (bccomp($payFee, '0.00', 2) > 0) {
                    $payAgencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['pay_agency_id']); // 支付机构
                    $payUserId = $payAgencyInfo['user_id']; // 支付机构账户
                    $user = $user->find($payUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($payFee, '支付服务费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($payFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'PAY_SERVICE_FEE|' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $payUserId,
                            'repaymentAmount' => bcmul($payFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }

                // 渠道服务费
                if (bccomp($canalFee, '0.00', 2) > 0) {
                    $canalAgencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['canal_agency_id']); // 渠道机构
                    $canalUserId = $canalAgencyInfo['user_id']; // 渠道机构账户
                    $user = $user->find($canalUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($canalFee, '渠道服务费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($canalFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'CANAL_SERVICE_FEE|' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $canalUserId,
                            'repaymentAmount' => bcmul($canalFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }

                // 管理服务费
                if ( ($fistDeal['isDtb'] == 1) &&(bccomp($managementFee, '0.00', 2) > 0)) {
                    $managementAgencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($fistDeal['management_agency_id']); // 管理机构
                    $managementUserId = $managementAgencyInfo['user_id']; // 支付机构账户
                    $user = $user->find($managementUserId);
                    $user->changeMoneyAsyn = true;
                    $user->changeMoneyDealType = $fistDeal['deal_type'];
                    $user->changeMoney($managementFee, '管理服务费', $project['name'], 0, 0, 0, 0, $bizToken);
                    if (bccomp($managementFee, '0.00', 2) > 0) {
                        $syncRemoteData[] = array(
                            'outOrderId' => 'MANAGEMENT_SERVICE_FEE|' . $project['id'],
                            'payerId' => $prepayUserId,
                            'receiverId' => $managementUserId,
                            'repaymentAmount' => bcmul($managementFee, 100), // 以分为单位
                            'curType' => 'CNY',
                            'bizType' => 4,
                            'batchId' => $project['id'],
                        );
                    }
                }

                // 提前还款数据同步
                if ($syncRemoteData) {
                    if (!FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH)) {
                        throw new \Exception("FinanceQueueModel push error");
                    }
                }

                $deal_service = new DealService();

                foreach($prepayIds as $prepayId) {
                    $prepay = $prepay->find($prepayId);
                    $deal = DealModel::instance()->find($prepay->deal_id);

                    // TODO finance 提前还款 | 已同步
                    $arr_deal_load = \core\dao\DealLoadModel::instance()->getDealLoanList($prepay->deal_id);
                    foreach ($arr_deal_load as $k => $deal_load) {
                        $jobs_model = new JobsModel();
                        $function = '\core\dao\DealPrepayModel::prepayByLoanId';
                        $param = array(
                            'deal_loan_id' => $deal_load['id'],
                            'prepay_id' => $prepay->id,
                            'prepay_user_id' => $entrustInfo['user_id'],
                        );
                        $jobs_model->priority = 85;
                        $r = $jobs_model->addJob($function, array('param' => $param));
                        if ($r === false) {
                            throw new \Exception("add prepay by loan id jobs error");
                        }
                    }

                    $jobs_model = new JobsModel();
                    $function = '\core\dao\DealPrepayModel::finishPrepay';
                    $param = array('prepay_id' => $prepay->id,'user_id' => $entrustInfo['user_id'],'isBorrowerSelf'=>$isBorrowerSelf,'prepayUserId'=>$prepayUserId);
                    $jobs_model->priority = 85;
                    $r = $jobs_model->addJob($function, array('param' => $param), false, 90);
                    if ($r === false) {
                        throw new \Exception("add finish prepay jobs error");
                    }

                    $success = isset($param['success']) ? $param['success'] : 1;
                    $saveLogFile = isset($param['saveLogFile']) ? $param['saveLogFile'] : 2;

                    //增加提前还款的操作记录
                    $repayOpLog = new DealRepayOplogModel();
                    $repayOpLog->operation_type = $isBorrowerSelf ? DealRepayOplogModel::REPAY_TYPE_PRE_SELF : DealRepayOplogModel::REPAY_TYPE_PRE;//提前还款
                    $repayOpLog->operation_time = get_gmtime();
                    $repayOpLog->operation_status = 1;
                    $repayOpLog->operator = $adminInfo['adm_name'];
                    $repayOpLog->operator_id = $adminInfo['adm_id'];

                    $repayOpLog->deal_id = $deal['id'];
                    $repayOpLog->deal_name = $deal['name'];
                    $repayOpLog->borrow_amount = $deal['borrow_amount'];
                    $repayOpLog->rate = $deal['rate'];
                    $repayOpLog->loantype = $deal['loantype'];
                    $repayOpLog->repay_period = $deal['repay_time'];
                    $repayOpLog->user_id = $deal['user_id'];
                    $repayOpLog->submit_uid = intval($param['submitUid']);

                    $repayOpLog->deal_repay_id = $prepay->id;
                    $repayOpLog->repay_money = $prepay->prepay_money;
                    $repayOpLog->real_repay_time = get_gmtime();

                    //存管&&还款方式
                    $repayOpLog->repay_type = $prepay->repay_type;
                    $repayOpLog->report_status = $deal['report_status'];

                    $save_res = $repayOpLog->save();
                    if(!$save_res) {
                        throw new \Exception("插入还款操作记录失败");
                    }

                    // JIRA#3090 定向委托投资标的超额收益功能
                    if ($deal['type_id'] == DealLoanTypeModel::instance()->getIdByTag(DealLoanTypeModel::TYPE_BXT)) {
                        $incomeExcessService = new IncomeExcessService();
                        $res = $incomeExcessService->pendingRepay($deal['id']);
                        if(!$res) {
                            throw new \Exception("超额收益待还款状态更新失败");
                        }
                    }
                }

                $save_res = DealProjectModel::instance()->changeProjectStatus($projectId,7);
                if(!$save_res){
                    throw new \Exception("修改项目还款完成状态失败");
                }

                //更新项目还款列表状态jobs添加
                $function = '\core\dao\DealProjectModel::changeProjectRepayList';
                $param = array(
                    'project_id' => $projectId,
                );

                $jobs_model->priority = JobsModel::PRIORITY_PROJECT_REPAY;
                $r = $jobs_model->addJob($function, $param, false, 90);
                if ($r === false) {
                    throw new \Exception('add \core\dao\DealProjectModel::changeProjectRepayList error');
                }

                $GLOBALS['db']->commit();

                \libs\utils\Logger::info("dealProjectAction->projectProject(pass project['" . $project['id'] . "'])");
            }catch (\Exception $e){

                $GLOBALS['db']->rollback();
                \libs\utils\Logger::error("dealProjectAction->projectProject(pass project['" . $project['id'] . "']), msg["
                    . $e->getMessage() . "], line:[" . $e->getLine() . "]");
                throw new \Exception("通过审核");
            }
        }else{
            throw new \Exception("参数错误");
        }

        return true;
    }

    public function errCatch(){
        $res = \SiteApp::init()->dataCache->getRedisInstance()->del($this->lockKey);
        $logText = $res ? '成功' : '失败';
        Logger::info(__CLASS__ . "," . __FUNCTION__ . " key:".$this->lockKey . " res:".$logText);
    }
}
