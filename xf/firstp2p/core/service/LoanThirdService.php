<?php

/**
 * 享花等第三方还款申请记录表的service
 */

namespace core\service;

use NCFGroup\Common\Library\Idworker;
use libs\utils\Alarm;
use libs\utils\Monitor;
use libs\utils\PaymentApi;
use libs\utils\Logger;
use libs\common\WXException;
use core\dao\UserModel;
use core\dao\DealModel;
use core\dao\LoanThirdModel;
use core\dao\LoanThirdMapModel;
use core\service\P2pIdempotentService;
use core\service\DealService;
use core\service\XHService;
use core\service\UniteBankPaymentService;
use core\service\SupervisionFinanceService;
use core\service\SupervisionBaseService;

class LoanThirdService extends BaseService {
    /**
     * 创建标的订单号关系记录-不会重复添加
     * @param int $userId
     * @param int $dealId
     * @param int $repayId
     * @param int $repayType
     * @param boolean $isAllowExist
     */
    public function addLoanThirdMap($userId, $dealId, $repayId, $repayType, $isAllowExist = false) {
        if ($isAllowExist) {
            return LoanThirdMapModel::instance()->createLoanThirdMap($userId, $dealId, $repayId, $repayType);
        }else{
            return LoanThirdMapModel::instance()->addNxLoanThirdMap($userId, $dealId, $repayId, $repayType);
        }
    }

    /**
     * 请求划扣受理接口
     * @param array $params
     * @throws \Exception
     * @return boolean
     */
    public function repayCreateLoanApply($params) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '请求划扣受理接口,params:' . json_encode($params))));
        $startTrans = false;
        try {
            if (empty($params['out_order_id']) || empty($params['user_id']) || empty($params['deal_id'])
                || (int)$params['repay_id'] < 0 || (int)$params['repay_money'] < 0 || empty($params['bankcard'])) {
                throw new WXException('ERR_PARAM');
            }

            // 查询标的订单号关系记录
            $loanThirdMapInfo = LoanThirdMapModel::instance()->getLoanThirdMapByOrderId((int)$params['out_order_id']);
            if (empty($loanThirdMapInfo)) {
                throw new WXException('ERR_REPAYORDER_NO_EXIST');
            }
            if ($loanThirdMapInfo['user_id'] != $params['user_id'] || $loanThirdMapInfo['deal_id'] != $params['deal_id']
                || $loanThirdMapInfo['repay_id'] != $params['repay_id']) {
                throw new WXException('ERR_REPAYORDER_NOIDEN');
            }

            // 用户UID
            $userId = (int)$loanThirdMapInfo['user_id'];
            // 外部订单号
            $outOrderId = (int)$loanThirdMapInfo['out_order_id'];
            // 标的ID
            $dealId = (int)$loanThirdMapInfo['deal_id'];
            // 还款ID
            $repayId = (int)$loanThirdMapInfo['repay_id'];
            // 还款类型
            $repayType = (int)$loanThirdMapInfo['repay_type'];
            // 海口银行电子帐号
            $bankcard = addslashes($params['bankcard']);
            // 云图需要还款的钱，单位:分
            $ytMoneyCent = (int)$params['repay_money'];
            // 云图需要还款的钱，单位:元
            $ytMoney = bcdiv($ytMoneyCent, 100, 2);
            // 对比云图需要还款的钱跟该标的本次回款的钱
            $dealRepayMoneyData = $this->getRepayMoneyByDealId($dealId, $repayId, $userId, $repayType);
            if (empty($dealRepayMoneyData) || bccomp($dealRepayMoneyData['total'], $ytMoney, 2) < 0) {
                throw new WXException('ERR_REPAYMONEY_LOANMONEY');
            }

            // 查询有效的还款申请记录
            $loanThirdData = LoanThirdModel::instance()->getLoanThirdByOrderId($outOrderId);
            if (!empty($loanThirdData)) {
                return ['status'=>1, 'repay_money'=>$params['repay_money'], 'bankcard'=>$params['bankcard']];
            }

            // 创建还款申请记录
            $data = [
                'user_id' => $userId,
                'deal_id' => $dealId,
                'repay_id' => $repayId,
                'repay_type' => $repayType,
                'out_order_id' => $outOrderId,
                'bankcard' => $bankcard,
                'repay_money' => $ytMoney,
                'total_money' => $dealRepayMoneyData['total'], // 总额
                'principal' => $dealRepayMoneyData['principal'], // 本金
                'interest' => $dealRepayMoneyData['interest'], // 收益
                'type' => LoanThirdModel::TYPE_WHOLE, // 全额还款
                'status' => LoanThirdModel::STATUS_ACCEPT, // 已受理
            ];
            $createLoanRet = LoanThirdModel::instance()->createLoanThird($data);
            if (false === $createLoanRet) {
                throw new \Exception('loan third record create failed, data:' . json_encode($data));
            }

            $GLOBALS['db']->startTrans();
            $startTrans = true;
            $dealService = new DealService();
            // 云图传过来的还款金额为0时，直接解冻
            if ($ytMoneyCent == 0) {
                $user = UserModel::instance()->find($userId);
                if(empty($user)) {
                    throw new \Exception('用户ID不存在，uid:' . $userId);
                }
                $dealInfo = DealModel::instance()->find($dealId, '*', true);
                $user->changeMoneyDealType = $dealService->getDealType($dealInfo);
                $bizToken = [
                    'dealId' => $dealId,
                ];
                $user->changeMoney(-$dealRepayMoneyData['total'], '享花还款', '享花还款余额解冻', 0, 0, UserModel::TYPE_LOCK_MONEY, 0 ,$bizToken);

                // 更新还款申请记录
                LoanThirdModel::instance()->updateLoanThirdByOrderId($userId, $outOrderId, ['status'=>LoanThirdModel::STATUS_SUCCESS]);

                $GLOBALS['db']->commit();
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('享花还款余额解冻成功,无需请求划扣接口, userId:%d, outOrderId:%s, params:%s, dealRepayMoneyData:%s', $params['user_id'], $params['out_order_id'], json_encode($params), json_encode($dealRepayMoneyData)))));
                return ['status'=>1, 'repay_money'=>$params['repay_money'], 'bankcard'=>$params['bankcard']];
            }

            // 通知支付提现到银行
            $bankParams = array(
                'userId' => $userId,
                'dealId' => $dealId,
                'amount' => $ytMoneyCent,
                'totalAmount' => bcmul($dealRepayMoneyData['total'], 100, 2),
                'outOrderId' => $outOrderId,
                'pAccount' => $bankcard,
            );
            // 判断标的是否走p2p存管流程
            $isP2pPath = $dealService->isP2pPath($dealId);
            if($isP2pPath) {
                $this->repayLoanThirdSupervision($bankParams);
            }else{
                $bankParams['callbackUrl'] = '/payment/withdrawTrustThirdNotify';
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '通知支付提现,params:' . json_encode($bankParams))));
                $bankService = new UniteBankPaymentService();
                $bankRes = $bankService->withdrawTrustBank($bankParams, false);
                if(!$bankRes) {
                    throw new \Exception('先锋支付资金划拨处理异常');
                }
            }

            $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('请求划扣受理接口成功, userId:%d, outOrderId:%s, params:%s, dealRepayMoneyData:%s, bankParams:%s', $params['user_id'], $params['out_order_id'], json_encode($params), json_encode($dealRepayMoneyData), json_encode($bankParams)))));
            return ['status'=>1, 'repay_money'=>$params['repay_money'], 'bankcard'=>$params['bankcard']];
        } catch (\Exception $e) {
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('请求划扣受理接口失败, userId:%d, outOrderId:%s, params:%s, dealRepayMoneyData:%s, exceptionMsg:%s', $params['user_id'], $params['out_order_id'], json_encode($params), json_encode($dealRepayMoneyData), $e->getMessage()))));
            $startTrans && $GLOBALS['db']->rollback();
            return ['status'=>0, 'repay_money'=>$params['repay_money'], 'bankcard'=>$params['bankcard']];
        }
    }

    /**
     * 获取该标的本次回款的金额
     * @param int $dealId
     * @param int $repayId
     * @param int $userId
     * @return 总额:total,本金:principal,收益:interest
     */
    public function getRepayMoneyByDealId($dealId, $repayId, $userId,$repayType) {
        $xHService = new XHService();
        return $xHService->getXHRepayMoneyInfo($dealId, $repayId, $userId,$repayType);
    }

    /**
     * 存管行还款通知提现
     */
    public function repayLoanThirdSupervision($params) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '通知存管行提现,params:' . json_encode($params))));

        $params = array(
            'bidId' => $params['dealId'],
            'orderId' => $params['outOrderId'],
            'userId' => $params['userId'],
            'totalAmount' => $params['totalAmount'],
            'repayAmount' => $params['amount'],
            'bankCardNo' => $params['pAccount'],
            'callbackUrl' => '/supervision/bidElecwithdrawThirdNotify',
        );

        $sfs = new SupervisionFinanceService();
        $bankRes = $sfs->bidElecWithdraw($params);
        if($bankRes['status'] === SupervisionBaseService::RESPONSE_SUCCESS){
            $data = array(
                'order_id' => $params['outOrderId'],
                'deal_id' => $params['dealId'],
                'loan_user_id' => $params['userId'],
                'money' => $params['amount'],
                'type' => \core\service\P2pDepositoryService::IDEMPOTENT_TYPE_XH,
                'status' => P2pIdempotentService::STATUS_SEND,
                'result' => P2pIdempotentService::RESULT_SUCC
            );
            $res = P2pIdempotentService::addOrderInfo($params['outOrderId'], $data);
            if(!$res){
                throw new \Exception('订单信息保存失败');
            }
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '通知存管行提现成功,orderId:' . $params['outOrderId'] . ',data:' . json_encode($data))));
            return true;
        }
        throw new \Exception('存管行提现处理失败');
    }

    /**
     * 先锋支付发送还款指令处理结果回调-非报备标
     */
    public function withdrawTrustThirdNotifyCallback($params)
    {
        $result = ['respCode' => '00', 'respMsg' => '成功'];
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, 'params:' . json_encode($params))));
        try{
            if ($params['status'] === SupervisionBaseService::RESPONSE_CODE_SUCCESS) {
                $params['status'] = SupervisionBaseService::RESPONSE_SUCCESS;
            }else{
                $params['status'] = SupervisionBaseService::RESPONSE_FAILURE;
            }
            $res = $this->LoanThirdForPay($params);
            if(!$res) {
                throw new \Exception('先锋支付还款回调失败');
            }
        }catch (\Exception $ex) {
            $result['respCode'] = '01';
            $result['respMsg'] = $ex->getMessage();
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, 'error:' . $ex->getMessage())));
        }
        return $result;
    }

    /**
     * 存管行支付提现回调
     * @param array $params
     * @return bool
     * @throws \Exception
     */
    public function repayLoanThirdSupervisionCallBack($params) {
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, '存管行提现回调, params:' . json_encode($params))));
        $res = P2pIdempotentService::updateStatusByOrderId($params['orderId'], P2pIdempotentService::STATUS_CALLBACK);
        if(!$res){
            throw new \Exception('订单信息保存失败');
        }
        return $this->LoanThirdForPay($params);
    }

    /**
     * 支付回调处理
     * @param $userId
     * @param $dealId
     */
    public function LoanThirdForPay($params) {
        $startTrans = false;
        try {
            if ($params['status'] == SupervisionBaseService::RESPONSE_FAILURE) {
                throw new \Exception('支付划扣回调，只接受成功状态');
            }
            // 查询标的订单号关系记录
            $loanThirdMapInfo = LoanThirdMapModel::instance()->getLoanThirdMapByOrderId($params['orderId']);
            if (empty($loanThirdMapInfo)) {
                throw new WXException('ERR_REPAYORDER_NO_EXIST');
            }
            // 查询还款申请记录
            $loanThirdInfo = LoanThirdModel::instance()->getLoanThirdByOrderId($loanThirdMapInfo['out_order_id']);
            if (empty($loanThirdInfo)) {
                throw new WXException('ERR_REPAYDATA_NO_EXIST');
            }
            if (in_array($loanThirdInfo->status, [LoanThirdModel::STATUS_SUCCESS, LoanThirdModel::STATUS_FAIL])) {
                return true;
            }
            if($loanThirdInfo->status != LoanThirdModel::STATUS_ACCEPT) {
                throw new WXException('ERR_REPAYDATA_STATUS');
            }

            $user = UserModel::instance()->find($loanThirdInfo->user_id);
            if(empty($user)) {
                throw new \Exception('用户ID不存在，uid:' . $loanThirdInfo->user_id);
            }

            $GLOBALS['db']->startTrans();
            $startTrans = true;

            $updateData = [];
            if ($params['status'] === SupervisionBaseService::RESPONSE_SUCCESS) {
                $updateData['status'] = LoanThirdModel::STATUS_SUCCESS;
                $statusNotify = 1; // 划扣结果-划扣成功
            }else{
                $updateData['status'] = LoanThirdModel::STATUS_FAIL;
                $statusNotify = 0; // 划扣结果-划扣失败
            }
            // 银行还款回调时间
            $updateData['loan_time'] = time();
            $saveRes = LoanThirdModel::instance()->updateLoanThirdByOrderId($loanThirdInfo->user_id, $loanThirdInfo->out_order_id, $updateData);
            if(!$saveRes) {
                throw new \Exception('第三方还款申请记录状态修改失败');
            }

            // 查询标的是否已报备
            $dealInfo = DealModel::instance()->find($loanThirdInfo->deal_id, '*', true);
            // 扣除云图生活传过来的还款金额
            $dealService = new DealService();
            $user->changeMoneyDealType = $dealService->getDealType($dealInfo);
            $bizToken = [
                'dealId' => $loanThirdInfo->deal_id,
            ];
            $user->changeMoney($loanThirdInfo->repay_money, '享花还款', '享花还款成功', 0, 0, UserModel::TYPE_DEDUCT_LOCK_MONEY, 0 ,$bizToken);
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付划扣回调，享花扣减冻结，orderId:%s, userId:%d, repayMoney:%s', $params['orderId'], $loanThirdInfo->user_id, $loanThirdInfo->repay_money))));
            // 解冻剩余金额
            if (bccomp($loanThirdInfo->total_money, $loanThirdInfo->repay_money, 2) > 0) {
                $remainMoney = bcsub($loanThirdInfo->total_money, $loanThirdInfo->repay_money, 2);
                $user->changeMoneyDealType = $dealService->getDealType($dealInfo);
                $user->changeMoney(-$remainMoney, '享花还款', '享花还款余额解冻', 0, 0, UserModel::TYPE_LOCK_MONEY, 0 ,$bizToken);
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付划扣回调，享花还款余额解冻，orderId:%s, userId:%d, totalMoney:%s, repayMoney:%s, remainMoney:%s', $params['orderId'], $loanThirdInfo->user_id, $loanThirdInfo->total_money, $loanThirdInfo->repay_money, $remainMoney))));
            }

            // 通知享花等第三方
            $thirdParams = ['out_order_id'=>$loanThirdInfo->out_order_id, 'status'=>$statusNotify];
            $thirdResult = $this->requestThirdAsync($thirdParams, 'transfer.notify');
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付划扣回调，通知享花等第三方接口(transfer.notify)，orderId:%s, 请求参数:%s, 返回结果:%s', $params['orderId'], json_encode($thirdParams), json_encode($thirdResult)))));

            if (app_conf('SMS_ON') == 1) {
                $sms_content = array(
                    'repay_time' => date('m月d日 H时i分'),
                    'money' => $loanThirdInfo->total,
                    'principal' => $loanThirdInfo->principal,
                    'interest' => $loanThirdInfo->interest,
                    'service_fee' => $loanThirdInfo->service_fee,
                );
                \libs\sms\SmsServer::instance()->send($user['mobile'], 'TPL_SMS_CREDIT_REPAY_SUCCESS', $sms_content, $user['id']);
                Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付划扣回调，发送还款成功的短信通知, orderId:%s, userId:%d, mobile:%s, content:%s', $params['orderId'], $user['id'], $user['mobile'], $sms_content))));
            }
            $GLOBALS['db']->commit();
            Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付划扣回调成功，params:%s, 划扣订单信息:%s', json_encode($params), json_encode($loanThirdInfo->getRow())))));
            return true;
        }catch (\Exception $ex) {
            // 记录告警
            Alarm::push('loanThirdNotify', __METHOD__, sprintf('享花等第三方支付划扣回调|订单ID:%d，订单状态:%s，异常内容:%s', $params['orderId'], $params['status'], $ex->getMessage()));
            // 添加监控
            Monitor::add('LOANTHIRD_EXCEPTION');
            Logger::error(implode(' | ', array(__CLASS__, __FUNCTION__, sprintf('支付扣款回调失败，params:%s, exceptionMsg:%s', json_encode($params), $ex->getMessage()))));
            if($startTrans) {
                $GLOBALS['db']->rollback();
            }
            throw $ex;
        }
    }

    /**
     * 异步通知财益通享花等第三方
     * @param array $params
     * @return array
     */
    public function requestThirdAsync($params, $apiName = 'transfer.notify', $projectName = 'xianghua') {
        try {
            $xhRes = \core\service\partner\RequestService::init($projectName)
            ->setApi($apiName)
            ->setPost($params)
            ->setAsyn()
            ->request();
            return ['ret'=>true, 'result'=>$xhRes];
        } catch (\Exception $e) {
            return ['ret'=>false, 'errorCode'=>$e->getCode(), 'errorMsg'=>$e->getMessage()];
        }
    }
}