<?php
/**
 * 专享 盈嘉1.75 标的还款数据发送给支付
 * @author jinhaidong
 * @date 2018-5-7 14:31:09
 */

namespace core\service;

use core\dao\CreditLoanModel;
use core\dao\DealLoadModel;
use core\dao\DealLoanRepayModel;
use core\dao\DealProjectModel;
use core\dao\DealRepayModel;
use core\dao\JobsModel;
use core\dao\DealModel;
use core\dao\ProjectRepayListModel;
use core\service\ZxIdempotentService;
use libs\utils\Logger;
use NCFGroup\Common\Library\Idworker;
use core\service\WithdrawProxyService;
use core\dao\WithdrawProxyModel;
use core\service\DealProjectRepayYjService;
use core\service\CreditLoanService;
use core\service\DealProjectService;


class ZxDealRepayService {

    /**
     * 汇总代发费用数据
     * @param $batchOrderId
     * @param $repayInfo
     * @param $dealId
     * @return array
     */
    public function collectFeeTransData($batchOrderId,$repayInfo,$dealId){
        $deal = DealModel::instance()->find($dealId);

        $loanUserId = \core\dao\DealAgencyModel::instance()->getLoanAgencyUserId($deal['id']); // 平台机构
        $advisoryInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['advisory_id']); // 咨询机构
        $agencyInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['agency_id']); // 担保机构
        $entrustInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['entrust_agency_id']);
        $payUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['pay_agency_id']); // 支付机构
        $canalUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['canal_agency_id']); // 支付机构
        $managementUserInfo = \core\dao\DealAgencyModel::instance()->getDealAgencyById($deal['management_agency_id']); // 管理机构

        $baseTransData = array(
            'batch_order_id' => $batchOrderId,
            'merchant_id' => $this->getMerchantId($deal),
            'project_id' => $deal['project_id'],
            'deal_id' => $dealId,
            'repay_id' => $repayInfo['id'],
            'repay_type' => self::REPAY_TYPE_NORMAL,
            'biz_type'=> 1,
        );

        $consultFee = $repayInfo['consult_fee'];
        $loanFee = $repayInfo['loan_fee'];
        $guaranteeFee = $repayInfo['guarantee_fee'];
        $payFee = $repayInfo['pay_fee'];
        $canalFee = $repayInfo['canal_fee'];
        $managementFee = $repayInfo['management_fee'];

        $transData = array();
        if ($consultFee > 0) {
            $tmpData = array('receiver_uid' => $advisoryInfo['user_id'],'money' => $consultFee, 'money_type'=>self::MONEY_TYPE_CONSULTFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        if($loanFee > 0){
            $tmpData = array('receiver_uid' => $loanUserId,'money' => $loanFee, 'money_type'=>self::MONEY_TYPE_LOANFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        if($guaranteeFee > 0){
            $tmpData = array('receiver_uid' => $agencyInfo['user_id'],'money' => $guaranteeFee, 'money_type'=>self::MONEY_TYPE_GUARANTEEFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        if($payFee > 0){
            $tmpData = array('receiver_uid' => $payUserInfo['user_id'],'money' => $payFee, 'money_type'=>self::MONEY_TYPE_PAYFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        if($canalFee > 0){
            $tmpData = array('receiver_uid' => $canalUserInfo['user_id'],'money' => $canalFee, 'money_type'=>self::MONEY_TYPE_CANALFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        if($managementFee > 0 && ($deal['isDtb'] == 1)){
            $tmpData = array('receiver_uid' => $managementUserInfo['user_id'],'money' => $managementFee, 'money_type'=>self::MONEY_TYPE_MANAGEMENTFEE);
            $transData[] = array_merge($baseTransData,$tmpData);
        }
        return $transData;
    }

    /**
     * 根据标的受托机构获取商户号
     * 376  深圳市前海新金盎资产管理有限公司
     * 364   深圳市中诚信资产管理有限公司
     * @param $deal
     */
    public function getMerchantId($deal){
        $mconf = app_conf("PROJECT_YJ_MERCHANT_ID");
        if(!$mconf){
            throw new \Exception("商户号未配置");
        }
        $merchantConf = explode(",",$mconf);

        $idconf = array();
        foreach($merchantConf as $val){
            $tmpVal = explode(":",$val);
            $idconf[$tmpVal[0]] = $tmpVal[1];
        }
        if(empty($idconf)){
            throw new \Exception("商户号配置格式错误");
        }

        if(!$deal['entrust_agency_id'] || !array_key_exists($deal['entrust_agency_id'],$idconf)){
            throw new \Exception("无法获取商户号");
        }

        return $idconf[$deal['entrust_agency_id']];
    }


    public function getTransMoneySummary($batchOrderId){
        $summary = ZxIdempotentService::getSumMoneyByBizType($batchOrderId);
        if(!$summary){
            throw new \Exception("代发记录还未生成");
        }
        //1、正常还款代发金额
        $repayMoeny = isset($summary[ZxIdempotentService::BIZ_TYPE_REPAY]) ? $summary[ZxIdempotentService::BIZ_TYPE_REPAY] : 0;
        //2、银信通汇总金额
        $yxtMoney = isset($summary[ZxIdempotentService::BIZ_TYPE_YXT_TJ]) ? $summary[ZxIdempotentService::BIZ_TYPE_YXT_TJ] : 0;
        //3、速贷代发金额
        $sudaiMoney = isset($summary[ZxIdempotentService::BIZ_TYPE_SUDAI]) ? $summary[ZxIdempotentService::BIZ_TYPE_SUDAI] : 0;

        $total = $repayMoeny + $yxtMoney + $sudaiMoney;
        return array('repay'=>$repayMoeny,'yxt'=>$yxtMoney,'sudai'=>$sudaiMoney,'total'=>$total);
    }


    /**
     * 项目代发包含(正常还款代发、银信通代发)
     * @param $batchOrderId
     * @return bool
     */
    public function projectTrans($batchOrderId){
        $checkRes = $this->transRepayMoneyCheck($batchOrderId);
        if(!$checkRes){
            throw new \Exception("代发金额有误,不能进行代发操作！");
        }

        try{
            $GLOBALS['db']->startTrans();
            $this->addRepayTransJobsByBatchOrderId($batchOrderId);
            $this->addYXTTransRecordByBatchOrderId($batchOrderId);
            $this->addTransFinishCheckJob($batchOrderId);
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__ . ",". __FUNCTION__ .", batchOrderId:{$batchOrderId},代发失败 errMsg:".$ex->getMessage());
            throw $ex;
        }
        return true;
    }

    /**
     * 代发之前的检查 还款金额是否一致
     * @param $projectId
     * @param $startTime
     * @param $endTime
     * @return bool
     */
    public function transRepayMoneyCheck($batchOrderId){
        $repayInfo = ProjectRepayListModel::instance()->findBy("id={$batchOrderId} AND `status` = 0");
        if(!$repayInfo){
            return false;
        }

        $repayMoney = $repayInfo['repay_money'];
        $repayFees = $repayInfo['loan_fee'] + $repayInfo['consult_fee'] + $repayInfo['guarantee_fee'] + $repayInfo['pay_fee'] + $repayInfo['canal_fee'];
        $needTransMoney = bcsub($repayMoney,$repayFees,2);
        $realTransInfo = $this->getTransMoneySummary($batchOrderId);
        $realTransMoney = $realTransInfo['total'];

        return bccomp($needTransMoney,$realTransMoney,2) == 0 ? true : false;
    }

    /**
     * 普通还款代发jobs
     * @param $batchOrderId
     * @return bool
     */
    public function  addRepayTransJobsByBatchOrderId($batchOrderId){
        $orders = ZxIdempotentService::getRepayTransByBatchOrderId($batchOrderId);
        foreach($orders as $order){
            $this->addTransJobs($order['order_id']);
        }
        return true;
    }

    /**
     * 银信通还款代发jobs
     * @param $batchOrderId
     * @return bool
     * @throws \Exception
     */
    public function addYXTTransJobsByBatchOrderId($batchOrderId){
        $orders = ZxIdempotentService::getYXTTransByBatchOrderId($batchOrderId);
        foreach($orders as $order){
            $this->addTransJobs($order['order_id']);
        }
        return true;
    }

    /**
     * 银信通还款代发
     * @param $batchOrderId
     * @throws \Exception
     */
    public function addYXTTransRecordByBatchOrderId($batchOrderId){
        $orders = ZxIdempotentService::getYXTTransRecordByBatchOrderId($batchOrderId);
        if(empty($orders)){
            Logger::info(__CLASS__ . ",". __FUNCTION__ .", batchOrderId:{$batchOrderId},不需要进行银信通代发");
            return true;
        }
        $cm = new CreditLoanModel();

        $function = '\core\service\CreditLoanService::dealCreditAfterRepayOne';

        foreach($orders as $order){
            $creditLoanInfo = $cm->getCreditLoanInfo($order['deal_id'],$order['receiver_uid']);
            if(!$creditLoanInfo){
                throw new \Exception("未查询到银信通信息 orderId:".$order['order_id']);
            }
            $param = array(
                'credit_loan_id' => $creditLoanInfo['id'],
                'repay_type' => 2,
            );
            $jobs_model = new JobsModel();
            $jobs_model->priority = 90;
            $r = $jobs_model->addJob($function, $param,false,10);
            if ($r === false) {
                throw new \Exception("add dealCreditAfterRepayOne jobs error");
            }
        }
        return true;
    }

    /**
     * 在银行完成还款回调后在生产银信通代发记录
     *
     * @param $userId
     * @param $dealId
     * @param $creditPrincipal 银信通借款金额
     * @param $creditInterest  银信通借款利息
     * @param $creditServiceFee 银信通服务费
     * @throws \Exception
     */
    public function genYXTDFRecord($userId,$dealId,$creditPrincipal,$creditInterest,$creditServiceFee){
        $logParams = "userId:{$userId},dealId:{$dealId},principal:{$creditPrincipal},interest:{$creditInterest},fee:{$creditServiceFee}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ .", 生成银信通代发记录 params:".$logParams);

        $parentOrderInfo = ZxIdempotentService::getYXTParentOrder($userId,$dealId);
        if(!$parentOrderInfo){
            throw new \Exception("未查询到银信通父级订单信息 userI:{$userId},dealId:{$dealId}");
        }
        $unfreezeMoney = ( $parentOrderInfo['money'] - $creditPrincipal - $creditInterest - $creditServiceFee);
        $batchOrderId = $parentOrderInfo['batch_order_id'];

        $baseData = array(
            'merchant_id' => $parentOrderInfo['merchant_id'],
            'project_id' => $parentOrderInfo['project_id'],
            'deal_id' => $parentOrderInfo['deal_id'],
            'repay_id' => $parentOrderInfo['repay_id'],
            'deal_load_id' => $parentOrderInfo['deal_load_id'],
        );
        if($creditPrincipal > 0){
            $transPrincipalData =  array('batch_order_id' =>$batchOrderId,'biz_type' =>ZxIdempotentService::BIZ_TYPE_YXT_DF, 'is_need_trans' => ZxIdempotentService::NEED_TRANS_YES,'receiver_uid' => $userId,'money' => $creditPrincipal, 'money_type'=> ZxIdempotentService::MONEY_TYPE_YXT_PRINCIPAL);
            $transData[] = array_merge($baseData,$transPrincipalData);
        }
        if($creditInterest > 0){
            $transInterestData =  array('batch_order_id' =>$batchOrderId,'biz_type' =>ZxIdempotentService::BIZ_TYPE_YXT_DF,'is_need_trans' =>  ZxIdempotentService::NEED_TRANS_YES,'receiver_uid' => $userId,'money' => $creditInterest, 'money_type'=> ZxIdempotentService::MONEY_TYPE_YXT_INTEREST);
            $transData[] = array_merge($baseData,$transInterestData);
        }
        if($creditServiceFee > 0){
            $transFeeData=  array('batch_order_id' =>$batchOrderId,'biz_type' =>ZxIdempotentService::BIZ_TYPE_YXT_DF,'is_need_trans' => ZxIdempotentService::NEED_TRANS_YES,'money' => $creditServiceFee, 'money_type'=> ZxIdempotentService::MONEY_TYPE_YXT_FEE);
            $transData[] = array_merge($baseData,$transFeeData);
        }

        if($unfreezeMoney > 0){
            $unFreeZeData = array('batch_order_id' =>$batchOrderId,'biz_type' =>ZxIdempotentService::BIZ_TYPE_YXT_DF,'is_need_trans' => ZxIdempotentService::NEED_TRANS_YES,'receiver_uid' => $userId,'money' => $unfreezeMoney, 'money_type'=> ZxIdempotentService::MONEY_TYPE_YXT_RETURN);
            $transData[] =  array_merge($baseData,$unFreeZeData);
        }
        $this->saveTransData($batchOrderId,$transData);

        $data = array(
            'result' => ZxIdempotentService::RESULT_SUCC,
        );
        $updateRes = ZxIdempotentService::updateOrderInfoByResult($parentOrderInfo['order_id'],$data,ZxIdempotentService::RESULT_WAIT);
        if(!$updateRes){
            throw new \Exception("银信通父订单信息更新失败 orderId:".$parentOrderInfo['order_id']);
        }
        // 代发记录添加成功后
        $this->addYXTTransJobsByBatchOrderId($batchOrderId);
        return true;
    }

    /**
     * 生成代发记录
     * @param $projectId
     * @param $batchOrderId
     * @return bool
     * @throws \Exception
     */
    public function genRepayDfRecord($projectId,$batchOrderId){
        $dealProjectService = new DealProjectService();
        if(!$dealProjectService->isProjectYJ175($projectId)) {
           throw new \Exception("非盈嘉项目，不允许线下还款");
        }
        $jobsModel = new JobsModel();
        $function = '\core\service\ZxDealRepayService::genRepayDfRecordJob';
        $params = array(
            'projectId' => $projectId,
            'batchOrderId' => $batchOrderId,
        );
        $jobsModel->priority = JobsModel::PRIORITY_ZX_DF_RECORD;
        $res = $jobsModel->addJob($function, $params); //不重试
        if ($res === false) {
            throw new \Exception('genRepayDfRecordJobs insert error projectId:'.$projectId);
        }
        return true;
    }


    /**
     * 代发记录jobs
     * @param $projectId
     * @param $batchOrderId
     * @return bool
     * @throws \Exception
     */
    public function genRepayDfRecordJob($projectId,$batchOrderId){
        if(!$batchOrderId){
            throw new \Exception("缺少参数");
        }
        $existProjectId = ZxIdempotentService::getProjectIdByBatchOrderId($batchOrderId);
        if($projectId == $existProjectId){
            throw new \Exception("代发记录已生成，请勿重复执行");
        }

        $dealRepayList = DealRepayModel::instance()->getProjectDealRepay($projectId);

        $transData = array();
        foreach($dealRepayList as $repay){
            $tmpData = $this->collectLoanTransData($batchOrderId,$repay['deal_id'],$repay['id']);
            if(empty($tmpData)){
                continue;
            }
            $transData = array_merge($transData,$tmpData);
        }
        if(empty($transData)){
            throw new \Exception("未查询到代发数据");
        }
        return $this->saveTransData($batchOrderId,$transData);
    }

    /**
     * 转账信息保存
     * @param $transData
     * @return bool
     * @throws \Exception
     */
    public function saveTransData($batchOrderId,$transData){
        try{
            $GLOBALS['db']->startTrans();
            foreach($transData as $data){
                $data['batch_order_id'] = $batchOrderId;
                $orderId = !$transData['order_id'] ? Idworker::instance()->getId() : $transData['order_id'];
                $saveRes = ZxIdempotentService::addOrderInfo($orderId,$data);
                if(!$saveRes){
                    throw new \Exception("订单信息保存失败 orderId:{$orderId},data:".json_encode($data));
                }
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .", err:".$ex->getMessage());
            $GLOBALS['db']->rollback();
            throw $ex;
        }
        return true;
    }

    /**
     * 投资记录代发数据
     * @param $batchOrderId
     * @param $dealId
     * @param $repayId
     * @return array
     * @throws \Exception
     */
    public function collectLoanTransData($batchOrderId,$dealId,$repayId){
        $deal = DealModel::instance()->find($dealId);
        if(!$deal){
            throw new \Exception("标的信息不存在");
        }
        $dealLoanList = DealLoadModel::instance()->getDealLoanList($dealId);

        $merchantId = $this->getMerchantId($deal);
        $baseTransData = array(
            'batch_order_id' => $batchOrderId,
            'merchant_id' => $merchantId,
            'project_id' => $deal['project_id'],
            'deal_id' => $dealId,
            'repay_id' => $repayId,
            'repay_type' => ZxIdempotentService::REPAY_TYPE_NORMAL,
            'biz_type'=> ZxIdempotentService::BIZ_TYPE_REPAY,
        );
        $transData = array();

        $creditLoanService = new CreditLoanService();
        $dealRepayModel = new DealRepayModel();
        $dealRepay = $dealRepayModel->find($repayId);
        $dealLoanRepayModel = new DealLoanRepayModel();


        foreach ($dealLoanList as $dealLoan) {

            $condition = "`deal_repay_id`= '%d' AND `deal_loan_id` = '%d' AND `loan_user_id`= '%d' AND status = 0";
            $condition = sprintf($condition, $repayId, $dealLoan['id'], $dealLoan['user_id']);

            $loanRepayList = $dealLoanRepayModel->findAll($condition);

            foreach ($loanRepayList as $loanRepay) {
                if($loanRepay['money'] <= 0){
                    continue;
                }

                switch ($loanRepay['type']) {
                    case DealLoanRepayModel::MONEY_PRINCIPAL :
                        $isNeedFreeze = false;
                        $isNeedFreeze = $creditLoanService->isNeedFreeze($deal,$dealLoan['user_id'],$repayId,1);

                        // 银信通借款
                        if($creditLoanService->isCreditingUser($dealLoan['user_id'],$dealId)){
                            $tmpData['biz_type'] = ZxIdempotentService::BIZ_TYPE_YXT_TJ;
                            $tmpData['is_need_trans'] = ZxIdempotentService::NEED_TRANS_NO;
                            $tmpData['receiver_uid'] = $dealLoan['user_id'];
                            $tmpData['deal_load_id'] = $dealLoan['id'];
                            $tmpData['money'] = $loanRepay['money'];
                            $tmpData['money_type'] = ZxIdempotentService::MONEY_TYPE_PRINCIPAL;
                        }elseif(bccomp($loanRepay->money,'0.00',2) > 0 && $isNeedFreeze){
                            $tmpData['biz_type'] = ZxIdempotentService::BIZ_TYPE_SUDAI;
                            $tmpData['is_need_trans'] = ZxIdempotentService::NEED_TRANS_NO;
                            $tmpData['receiver_uid'] = $dealLoan['user_id'];
                            $tmpData['deal_load_id'] = $dealLoan['id'];
                            $tmpData['money'] = $loanRepay['money'];
                            $tmpData['money_type'] = ZxIdempotentService::MONEY_TYPE_PRINCIPAL;
                            $creditLoanService->freezeNotifyCreditloan($dealLoan['user_id'],$dealId,$repayId,1,$batchOrderId,$merchantId);
                        }else{
                            $tmpData['biz_type'] = ZxIdempotentService::BIZ_TYPE_REPAY;
                            $tmpData['is_need_trans'] = ZxIdempotentService::NEED_TRANS_YES;
                            $tmpData['receiver_uid'] = $dealLoan['user_id'];
                            $tmpData['deal_load_id'] = $dealLoan['id'];
                            $tmpData['money'] = $loanRepay['money'];
                            $tmpData['money_type'] = ZxIdempotentService::MONEY_TYPE_PRINCIPAL;
                        }

                        $transData[] = array_merge($baseTransData,$tmpData);
                        break;
                    case  DealLoanRepayModel::MONEY_INTREST :
                        $tmpData = array('deal_load_id' => $dealLoan['id'],'receiver_uid' => $dealLoan['user_id'],'money' => $loanRepay['money'], 'money_type'=>ZxIdempotentService::MONEY_TYPE_INTEREST);
                        $transData[] = array_merge($baseTransData,$tmpData);
                        break;
                    case  DealLoanRepayModel::MONEY_MANAGE :
                        $platformUserId = app_conf('MANAGE_FEE_USER_ID');
                        $tmpData = array('deal_load_id' => $dealLoan['id'],'receiver_uid' => $platformUserId,'money' => $loanRepay['money'], 'money_type'=>ZxIdempotentService::MONEY_TYPE_MANAGEMENTFEE);
                        $transData[] = array_merge($baseTransData,$tmpData);
                        break;

                }
            }
        }
        return $transData;
    }

    /**
     * 单笔代发请求 jobs
     * @param $orderId
     * @return bool
     * @throws \Exception
     */
    public function addTransJobs($orderId){
        $jobsModel = new JobsModel();
        $function = '\core\service\ZxDealRepayService::transferRequest';
        $params = array(
            'orderId' => $orderId,
        );
        $jobsModel->priority = JobsModel::PRIORITY_ZX_TRANSFER;
        $res = $jobsModel->addJob($function, $params); //不重试
        if ($res === false) {
            throw new \Exception('transferJobs insert error orderId:'.$orderId);
        }
        return true;
    }

    /**
     * 请求支付代发
     * @param $orderId
     * @return bool
     * @throws \Exception
     */
    public function transferRequest($orderId){
        $orderInfo = ZxIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在");
        }

        $params = json_decode($orderInfo['params'],true);
        $memo = isset($params['memo']) ? $params['memo'] : '';

        $projectInfo = DealProjectModel::instance()->find($orderInfo['project_id']);
        if(!$projectInfo){
            throw new \Exception("项目信息不存在");
        }

        $bizType = $this->getTransBizType($orderInfo['biz_type'],$orderInfo['money_type']);
        if(!$bizType){
            throw new \Exception("biz_type不存在");
        }
        $transData = array(
            'userId' => $orderInfo['receiver_uid'], //收款人
            'projectId' => $orderInfo['project_id'],
            'projectName' => $projectInfo['name'],
            'merchantBatchNo' => $orderInfo['batch_order_id'],//项目批次号
            'bizType' => $bizType, // 业务类型
            'merchantNo' => $orderId, // 业务单号
            'merchantId' => $orderInfo['merchant_id'], // 商户号
            'amount' => bcmul($orderInfo['money'],100), // 打款金额 单位(分)
            'memo' => $memo, // 打款备注
        );

        if($orderInfo['money_type'] == ZxIdempotentService::MONEY_TYPE_YXT_FEE){
            $transData['userId'] = WithdrawProxyModel::USER_CREDITLOAN; // 银信通配置特定用户
        }

        Logger::info(__CLASS__ . ",". __FUNCTION__ .", orderId:{$orderId},发送转账请求 transData:".json_encode($transData));

        $transRes = WithdrawProxyService::addWithdrawRecord($transData);
        if(!$transRes){
            Logger::error(__CLASS__ . ",". __FUNCTION__ .", orderId:{$orderId}  代发请求失败");
            throw new \Exception("代发请求失败");
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ .", orderId:{$orderId},发送转账请求成功 params:".json_encode($transData));

        $data = array(
            'notify_time' => time(),
            'status' => ZxIdempotentService::STATUS_SEND,
        );
        $res = ZxIdempotentService::updateOrderInfo($orderId,$data);
        if(!$res){
            throw new \Exception("更新订单信息失败");
        }
        return true;
    }

    public function getTransBizType($bizType,$moneyType){
        $newBizType = false;
        switch($bizType){
            case ZxIdempotentService::BIZ_TYPE_REPAY :
                if($moneyType == ZxIdempotentService::MONEY_TYPE_PRINCIPAL){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_REPAY_PRINCIPAL;
                }elseif($moneyType == ZxIdempotentService::MONEY_TYPE_INTEREST){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_REPAY_INTEREST;
                }else{
                    throw new \Exception("Error mone_type:{$moneyType}");
                }
                break;
            case ZxIdempotentService::BIZ_TYPE_YXT_DF :
                if($moneyType == ZxIdempotentService::MONEY_TYPE_YXT_PRINCIPAL){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_PRINCIPAL;
                }elseif($moneyType == ZxIdempotentService::MONEY_TYPE_YXT_INTEREST){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_INTEREST;
                }elseif($moneyType == ZxIdempotentService::MONEY_TYPE_YXT_FEE){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_FEE;
                }elseif($moneyType == ZxIdempotentService::MONEY_TYPE_YXT_RETURN){
                    $newBizType = WithdrawProxyModel::BIZ_TYPE_CREDITLOAN_RETURN;
                }
                break;
        }
        return $newBizType;
    }

    /**
     * 代发成功回调 -- 更改代发状态
     * @param $orderId
     * @param $status
     * @return mixed
     * @throws \Exception
     */
    public function transferCallBack($orderId){
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",orderId:{$orderId}");

        $orderInfo = ZxIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            throw new \Exception("订单信息不存在");
        }
        if($orderInfo['status'] == ZxIdempotentService::STATUS_CALLBACK){
            return true;
        }

        $data = array(
            'status' => ZxIdempotentService::STATUS_CALLBACK,
            'result' => ZxIdempotentService::RESULT_SUCC,
            'callback_time' => time(),
        );
        $affectedRows = ZxIdempotentService::updateOrderInfo($orderId,$data);
        return $affectedRows > 0 ? true : false;
    }
    /**
     * 是否全部完成转账 jobs
     * @param $batchOrderId
     * @return bool
     * @throws \Exception
     */
    public function addTransFinishCheckJob($batchOrderId){
        $jobsModel = new JobsModel();
        $function = '\core\service\ZxDealRepayService::finishAllTrans';
        $params = array(
            'batchOrderId' => $batchOrderId,
        );
        $jobsModel->priority = JobsModel::PRIORITY_ZX_TRANSFER_CHECK;
        $res = $jobsModel->addJob($function, $params,get_gmtime()+180,1000);
        if ($res === false) {
            throw new \Exception('transferJobs insert error orderId:'.$batchOrderId);
        }
        return true;
    }

    /**
     * 检查支付是否完成全部转账
     * @param $batchOrderId
     */
    public function finishAllTrans($batchOrderId){

        $cnt = ZxIdempotentService::getTransUnSuccCnt($batchOrderId);

        if($cnt > 0){
            Logger::info(__CLASS__ . ",". __FUNCTION__ .", batchOrderId:{$batchOrderId},代发请求未完成数量:{$cnt}");
            sleep(1);
            throw new \Exception("代发回调未完成");
        }else{
            // 检查速贷是否全部完成代发
            $transMoneyInfo = $this->getTransMoneySummary($batchOrderId);
            $suDaiMoney = WithdrawProxyService::sumByMerchantBatchNo($batchOrderId);

            if($suDaiMoney || $transMoneyInfo['sudai'] > 0){
                if($suDaiMoney != bcmul($transMoneyInfo['sudai'],100)){
                    throw new \Exception("速贷代发未完成");
                }
            }
        }


        $projectId = ZxIdempotentService::getProjectIdByBatchOrderId($batchOrderId);
        if(!$projectId){
            throw new \Exception("无法通过批次订单获取项目ID");
        }

        $projectInfo = DealProjectModel::instance()->find($projectId);
        if(in_array($projectInfo['business_status'],array(
            DealProjectModel::$PROJECT_BUSINESS_STATUS['during_repay'],
            DealProjectModel::$PROJECT_BUSINESS_STATUS['repaid'],
        ))){
            return true;
        }

        $admInfo = array(
            'adm_name' => 'system',
            'adm_id' => 0,
        );
        $repayYjService = new DealProjectRepayYjService();
        $res = $repayYjService->changeRepayStatus($batchOrderId,$admInfo);
        if(!$res){
            throw new \Exception("项目状态修改失败");
        }
        return true;
    }

}
