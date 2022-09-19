<?php
/**
 * p2p存管 标的放款
 */

namespace core\service;

use core\service\SupervisionDealService;
use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;

use core\dao\DealLoadModel;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\JobsModel;
use app\models\dao\DealLoad;
use core\dao\DealAgencyModel;
use core\dao\DealLoanTypeModel;
use core\dao\DealProjectModel;
use libs\utils\Finance;
use libs\utils\Aes;
use libs\utils\Logger;

use NCFGroup\Common\Library\Idworker;


class P2pDealGrantService extends P2pDepositoryService {

    /**
     * 放款通知银行 -- Jobs方式启动
     * @param $orderId 订单ID
     * @param $dealId 标的ID
     * @return bool
     */
    public function dealGrantRequest($orderId,$dealId,$params=array()) {
        $logParams = "orderId:{$orderId},deal_id:{$dealId},params:".json_encode($params);
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知银行 params:" .$logParams);

        $deal = DealModel::instance()->find($dealId);
        if(!$deal){
            throw new \Exception("标的信息不存在:".$dealId);
        }
        if($deal['deal_status'] != 4){
            throw new \Exception("标的状态需要在放款中状态才能发起放款通知");
        }

        $grantOrderInfo = P2pIdempotentService::getValidGrantOrderInfoByDealId($dealId);
        if(!empty($grantOrderInfo) && $grantOrderInfo['result'] == P2pIdempotentService::STATUS_CALLBACK){
            throw new \Exception("放款已回调不能再发起放款请求 dealId:".$dealId);
        }

        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId,false);
        $dealData = $deal->getRow();

        $totalBid = DealLoadModel::instance()->getLoadCount($dealId);

        $totalBidNum = $totalBid['buy_count']; // 投资总笔数
        $totalBidMoney = $deal->borrow_amount; // 投资总金额

        $dealService = new \core\service\DealService();
        $isDT = $dealService->isDealDT($dealId);
        $dealData['isDtb'] = ($isDT === true) ? 1 : 0;

        $dealModel = new DealModel();
        $agencyModel = new DealAgencyModel();
        $agencyFeeUser = $agencyModel->find($dealData['agency_id']);
        $advisoryFeeUser = $agencyModel->find($dealData['advisory_id']);
        $loanFeeUserId = $agencyModel->getLoanAgencyUserId($dealId);
        $canalAgencyUser = $agencyModel->find($dealData['canal_agency_id']);
        if (!$dealData['pay_agency_id']) {
            $dealData['pay_agency_id'] = $agencyModel->getUcfPayAgencyId();
        }
        $payAgencyUser = $agencyModel->find($dealData['pay_agency_id']);

        $managementUserId = 0;
        if($dealData['isDtb'] == 1) {
            $managementAgencyUser = $agencyModel->find($dealData['management_agency_id']);
            $managementUserId = $managementAgencyUser['user_id'];
        }

        // 手续费
        if (!$dealExt['loan_fee_ext']) {
            $loanFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['loan_fee_rate'], $deal['repay_time'], false);
            $loanFee = $dealModel->floorfix($deal['borrow_amount'] * $loanFeeRate / 100.0);
        } else {
            $loanFeeArr = json_decode($dealExt['loan_fee_ext'], true);
            $loanFee = $loanFeeArr[0];
        }

        // 咨询费
        if (!$dealExt['consult_fee_ext']) {
            $consultFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['consult_fee_rate'], $deal['repay_time'], false);
            $consultFee = $dealModel->floorfix($deal['borrow_amount'] * $consultFeeRate / 100.0);
        } else {
            $consultFeeArr = json_decode($dealExt['consult_fee_ext'], true);
            $consultFee = $consultFeeArr[0];
        }

        // 担保费
        if (!$dealExt['guarantee_fee_ext']) {
            $guaranteeFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['guarantee_fee_rate'], $deal['repay_time'], false);
            $guaranteeFee = $dealModel->floorfix($deal['borrow_amount'] * $guaranteeFeeRate / 100.0);
        } else {
            $guaranteeFeeArr = json_decode($dealExt['guarantee_fee_ext'], true);
            $guaranteeFee = $guaranteeFeeArr[0];
        }

        // 支付服务费
        if (!$dealExt['pay_fee_ext']) {
            $payFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $payFee = $dealModel->floorfix($deal['borrow_amount'] * $payFeeRate / 100.0);
        } else {
            $payFeeArr = json_decode($dealExt['pay_fee_ext'], true);
            $payFee = $payFeeArr[0];
        }

        // 渠道服务费
        if (!$dealExt['canal_fee_ext']) {
            $canalFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['canal_fee_rate'], $deal['repay_time'], false);
            $canalFee = $dealModel->floorfix($deal['borrow_amount'] * $canalFeeRate / 100.0);
        } else {
            $canalFeeArr = json_decode($dealExt['canal_fee_ext'], true);
            $canalFee = $canalFeeArr[0];
        }

        $managementFee = 0;//管理服务费
        if( $dealData['isDtb'] == 1) {//多投宝收取管理服务费
            if (!$dealExt['management_fee_ext']) {
                $managementFeeRate = Finance::convertToPeriodRate($deal['loantype'], $deal['management_fee_rate'], $deal['repay_time'], false);
                $managementFee = $dealModel->floorfix($deal['borrow_amount'] * $managementFeeRate / 100.0);
            } else {
                $managementFeeArr = json_decode($dealExt['management_fee_ext'], true);
                $managementFee = $managementFeeArr[0];
            }
        }

        $grantOrderList = array();
        if (bccomp($loanFee, '0.00', 2) > 0) {
            if(empty($loanFeeUserId)){
                throw new \Exception("平台手续费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_SX . $dealId,
                'receiveUserId' => $loanFeeUserId,
                'amount' => bcmul($loanFee, 100),
                'type' => $this->getP2pMoneyType("平台手续费"), //平台手续费
            );
        }

        if(bccomp($consultFee, '0.00', 2) > 0 ) {
            if(empty($advisoryFeeUser['user_id'])){
                throw new \Exception("咨询费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_ZX . $dealId,
                'receiveUserId' => $advisoryFeeUser['user_id'],
                'amount' => bcmul($consultFee, 100),
                'type' => $this->getP2pMoneyType("咨询费"), //咨询费
            );
        }
        if(bccomp($guaranteeFee, '0.00', 2) > 0 ) {
            if(empty($agencyFeeUser['user_id'])){
                throw new \Exception("担保费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_DB . $dealId,
                'receiveUserId' => $agencyFeeUser['user_id'],
                'amount' => bcmul($guaranteeFee, 100),
                'type' => $this->getP2pMoneyType("担保费"), //担保费
            );
        }
        if(bccomp($payFee, '0.00', 2) > 0 ) {
            if(empty($payAgencyUser['user_id'])){
                throw new \Exception("支付服务费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_FW . $dealId,
                'receiveUserId' => $payAgencyUser['user_id'],
                'amount' => bcmul($payFee, 100),
                'type' => $this->getP2pMoneyType("支付服务费"), //支付服务费
            );
        }

        if (bccomp($canalFee, '0.00', 2) > 0) {
            if(empty($canalAgencyUser['user_id'])){
                throw new \Exception("渠道手续费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_QD . $dealId,
                'receiveUserId' => $canalAgencyUser['user_id'],
                'amount' => bcmul($canalFee, 100),
                'type' => $this->getP2pMoneyType("渠道手续费"), //渠道手续费
            );
        }

        if(bccomp($managementFee, '0.00', 2) > 0 ) {
            if(empty($managementAgencyUser['user_id'])){
               throw new \Exception("多投宝管理服务费账户未设置");
            }
            $grantOrderList[] = array(
                'subOrderId' => self::REQUEST_BIZ_GRANT . self::FEE_GL .$dealId,
                'receiveUserId' => $managementAgencyUser['user_id'],
                'amount' => bcmul($managementFee, 100),
                'type' => $this->getP2pMoneyType("管理服务费"), //管理服务费
            );
        }

        $servicesFee = $loanFee + $consultFee + $guaranteeFee + $payFee + $managementFee + $canalFee;
        $realGrantMoney = $totalBidMoney - $servicesFee;

        // 请求数据容器
        $repayOrderList = array();
        $requestData = array(
            'orderId' => $orderId,
            'bidId' => $dealId,
            'userId' => $deal->user_id,
            'totalNum' => $totalBidNum,  // 放款比数
            'totalAmount' => bcmul($totalBidMoney,100),  // 放款总金额 单位分
            'grantAmount' => bcmul($realGrantMoney,100), // 借款人实收金额
            'currency' => 'CNY',
            'shareProfitOrderList' => json_encode($grantOrderList),
        );

        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'repay_id' => 0,
            'prepay_id' => 0,
            'money' => $totalBidMoney,
            'params' => json_encode($params),
            'type' => self::IDEMPOTENT_TYPE_GRANT,
            'status' => P2pIdempotentService::STATUS_SEND,
        );

        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知银行开始 orderId:".$orderId);
        $sds = new SupervisionDealService();
        $sendRes = $sds->dealGrant($requestData);
        if($sendRes['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS || $sendRes['status'] == \core\service\SupervisionBaseService::RESPONSE_PROCESSING) {

            /**
             * 支付非要求每次放款请求都用不同orderId 然而每个标的放款只能有一次
             * 导致在处理放款时候需要把以前订单置为无效

            $updateRes = P2pIdempotentService::invalidGrantOrderByDealId($dealId);
            if(!$updateRes){
                throw new \Exception("订单信息更改失败");
            }
            */

            $res =  P2pIdempotentService::saveOrderInfo($orderId,$data);
            if($res === false){
                throw new \Exception("订单信息保存失败");
            }
            \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款通知银行成功 params:" .json_encode($requestData));
            return true;
        }
        \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ .",放款通知银行失败 orderId:".$orderId);
        \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK,'放款通知银行失败'," dealId:{$dealId}, 错误信息:".$sendRes['respMsg']);
        throw new \Exception("放款通知银行失败 params:".json_encode($requestData)." errMsg:".$sendRes['respMsg']);
    }

    /**
     * 放款回调
     * 与支付沟通:放款回调不会有失败状态，支付一定是在放款成功后才会发起回调
     *
     * @param $orderId
     * @param $dealId
     * @param $status
     * @return bool
     * @throws \Exception
     */
    public function dealGrantCallBack($orderId,$status) {
        $logParams = "orderId:{$orderId},status:{$status}";
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ ." 放款回调 params:" .$logParams);

        try{
            if($status == self::CALLBACK_STATUS_FAIL) {
                throw new \Exception("放款回调状态不接受失败状态");
            }

            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo) {
                throw new \Exception("order_id不存在");
            }

            if($orderInfo['status'] == P2pIdempotentService::STATUS_INVALID) {
                throw new \Exception("order_id 无效");
            }

            // 幂等处理
            if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK) {
                return true;
            }
        }catch (\Exception $ex) {
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ . " 放款回调失败 params:".$logParams.", errMsg:". $ex->getMessage());
            \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK,'放款回调失败'," params:{$logParams}, 错误信息:".$ex->getMessage());
            throw $ex;
        }

        try {
            $GLOBALS['db']->startTrans();
            $function = '\core\service\DealService::makeDealLoansJob';
            $param = json_decode($orderInfo['params'],true);
            $jobModel = new \core\dao\JobsModel();
            $jobModel->priority = 99;

            $res = $jobModel->addJob($function, $param);
            if(!$res) {
                throw new \Exception("放款任务添加失败");
            }
            $orderData = array(
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );

            $affectedRows = P2pIdempotentService::updateOrderInfoByResult($orderId,$orderData,P2pIdempotentService::RESULT_WAIT);
            if($affectedRows == 0){
                throw new \Exception("订单信息保存失败");
            }
           $GLOBALS['db']->commit();
        }catch (\Exception $ex) {
            $GLOBALS['db']->rollback();
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ . " 放款回调失败 params:".$logParams.", errMsg:". $ex->getMessage());
            \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK,'放款回调失败'," params:{$logParams}, 错误信息:".$ex->getMessage());
            throw $ex;
        }
        \libs\utils\Logger::info(__CLASS__ . ",". __FUNCTION__ . " 放款回调成功 params:".$logParams);
        return true;
    }


    /**
     * 放款后提现
     * @param $grantOrderId 原放款单号
     * @param $dealId
     * @param $grantMoney 实际放款金额 = 提现金额
     * @return bool
     * @throws \Exception
     */
    public function afterGrantWithdraw($orderId,$dealId,$grantMoney){
        $deal = DealModel::instance()->find($dealId);
        $dealExt = DealExtModel::instance()->getInfoByDeal($dealId, false);
        $dealProjectObj = DealProjectModel::instance()->findViaSlave($deal['project_id']);
        $dealService = new \core\service\DealService();
         // 原始放款单号
        $grantOrderId = P2pIdempotentService::getGrantOrderByDealId($dealId);
        if(!$grantOrderId){
            throw new \Exception("未找到放到原始订单号 dealId:{$dealId}");
        }
        // 掌众标走单独的快速提现逻辑（20171121 19:50，改成“所有P2P资产端提现通道切换到快速提现通道”）
        // 闪电消费和闪电消费(线上) 不走快速通道
        if ($dealService->isDealOfDealTypeList($dealId, [DealLoanTypeModel::TYPE_XJDYYJ,
            DealLoanTypeModel::TYPE_XFD, DealLoanTypeModel::TYPE_XSJK, DealLoanTypeModel::TYPE_XJDCDT, DealLoanTypeModel::TYPE_XJDGFD])) {
            $this->quickWithdraw($orderId,$deal['user_id'],$grantMoney,$dealId, $grantOrderId);
        }elseif($dealProjectObj->loan_money_type == DealProjectModel::LOAN_MONEY_TYPE_ENTRUST
                && $dealExt['is_auto_withdrawal'] == 1){
            // 受托支付
           $this->entrustedWithdraw($grantOrderId,$orderId,$deal['user_id'],$dealId,$grantMoney);
        } elseif ($dealExt['is_auto_withdrawal'] == 1) {
            // 免密提现走受托支付
           $this->entrustedWithdraw($grantOrderId,$orderId,$deal['user_id'],$dealId,$grantMoney);
        }else{
            Logger::info(__CLASS__ . ",". __FUNCTION__ .",不需要进行放款提现 dealId:{$dealId}");
            return true;
        }

        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'repay_id' => 0,
            'prepay_id' => 0,
            'money' => $grantMoney,
            'params' => json_encode(array()),
            'type' => self::IDEMPOTENT_TYPE_WITHDRAW,
            'status' => P2pIdempotentService::STATUS_SEND,
        );

        $res =  P2pIdempotentService::addOrderInfo($orderId,$data);
        if(!$res){
            throw new \Exception("订单信息保存失败");
        }
        Logger::info(__CLASS__ . ",". __FUNCTION__ .",放款提现通知银行成功 orderId:{$orderId}");
        return true;
    }

    /**
     * 快速提现 掌众标的走此放款提现
     * @param $orderId
     * @param $userId
     * @param $amount
     * @param $dealId
     * @param $grantOrderId 原始放款单号
     */
    public function quickWithdraw($orderId,$userId,$amount,$dealId, $grantOrderId){
        $params = array(
            'orderId' => $orderId,
            'bidId' => $dealId,
            'userId' => $userId,
            'amount' => bcmul($amount,100),
            'grantOrderId' => $grantOrderId,
        );
        $financeService = new \core\service\SupervisionFinanceService();
        $res = $financeService->bankpayupWithdraw($params);
        if($res['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
            return true;
        }
        \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK, '掌众提现申请失败', 'params:'.json_encode($params));
        throw new \Exception("放款快速提现失败 params:".json_encode($params).",errMsg:".$res['respMsg']);
    }

    /**
     * 放款后提现
     * @param $orderId
     * @param $userId
     * @param $amount
     * @return bool
     * @throws \Exception
     */
    public function withdraw($orderId,$userId,$amount,$dealId) {
        $params = array(
            'orderId' => $orderId,
            'bidId' => $dealId,
            'userId' => $userId,
            'bizType' => '02', // 提现业务类型 NW：普通提现 02：放款提现
            'efficType' => 'D0', // 提现时效类型 T1：T+1提现 D0：D+0提现
            'amount' => bcmul($amount,100),
        );
        $financeService = new \core\service\SupervisionFinanceService();
        $res = $financeService->withdraw($params);
        if($res['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
            return true;
        }
        throw new \Exception("放款提现失败 params:".json_encode($params).",errMsg:".$res['respMsg']);
    }

    /**
     * 受托放款
     * @param $grandOrderId 原放款单号
     * @param $orderId
     * @param $userId
     * @param $dealId
     * @param $amount
     * @return bool
     * @throws \Exception
     */
    public function entrustedWithdraw($grandOrderId,$orderId,$userId,$dealId,$amount) {
        $params = array(
            'grandOrderId' => $grandOrderId,
            'orderId' => $orderId,
            'userId' => $userId,
            'bidId' => $dealId,
            'bizType' => '02', // 提现业务类型 NW：普通提现 FW：放款提现
            'efficType' => 'D0', // 提现时效类型 T1：T+1提现 D0：D+0提现
            'amount' => bcmul($amount,100),
        );

        $logParams = "grantOrderId:{$grandOrderId},userId:{$userId},dealId:{$dealId},amount:{$amount}";
        $financeService = new \core\service\SupervisionFinanceService();
        $res = $financeService->entrustedWithdraw($params);
        if($res['status'] == \core\service\SupervisionBaseService::RESPONSE_SUCCESS){
            return true;
        }
        throw new \Exception("受托提现失败 params:".json_encode($params).",errMsg:".$res['respMsg']);
    }

    /**
     * 放款提现回调
     * @param $orderId
     * @param $status
     * @param $grantMoney 放款金额
     */
    public function withdrawNotify($orderId,$status,$grantMoney){
        if($status == \core\service\SupervisionBaseService::RESPONSE_FAILURE){
            // \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK, '银行放款回调失败', 'orderId:'.$orderId);
            \libs\utils\Logger::error(__CLASS__ . ",". __FUNCTION__ ." 放款提现回调失败 orderId:{$orderId},status:{$status}");
        }else{
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo || $orderInfo['type'] != self::IDEMPOTENT_TYPE_WITHDRAW){
                return true;
            }

            $deal = DealModel::instance()->find($orderInfo['deal_id']);
            if(!$deal){
                throw new \Exception("标的ID不存在");
            }
            $servicesFee = bcsub($deal['borrow_amount'],$grantMoney,2);

            // 存管标的放款成功不发送短信
            //$this->sendWithdrawMsg($deal,$grantMoney,$servicesFee);
        }
        $data = array(
            'status' => P2pIdempotentService::STATUS_CALLBACK,
            'result' => $status == \core\service\SupervisionBaseService::RESPONSE_SUCCESS ?
                P2pIdempotentService::RESULT_SUCC : P2pIdempotentService::RESULT_FAIL,
        );
        $res = P2pIdempotentService::updateOrderInfo($orderId,$data);
        if(!$res){
            throw new \Exception("订单信息更新失败");
        }
        return true;
    }

    /**
     * 放款提现发送短信
     * @param object $deal
     * @param $grantMoney 放款金额
     * @param $seviceFee 服务费
     */
    public function sendWithdrawMsg($deal,$grantMoney,$seviceFee){
        $dealId = $deal['id'];
        $dealUrl = '/d/'.Aes::encryptForDeal($dealId);
        $siteTitle = get_deal_domain_title($dealId);
        $content = sprintf("您好，您在%s的借款 “<a href=\"%s\">%s</a>”已招标成功。借款金额:%s元，扣除服务费%s元，实得%s元。",
            $siteTitle, $dealUrl, $deal['name'], format_price($deal['borrow_amount'], 0),
            format_price($seviceFee, 0), format_price($grantMoney,0)
        );

        $content .= "系统已进行提现处理，如您填写的账户信息正确无误，您的资金将会于3个工作日内到达您的银行账户。";
        send_user_msg("招标成功自动提现", $content, 0, $deal['user_id'], get_gmtime(), 0, true, 5);
    }
}
