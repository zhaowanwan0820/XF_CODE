<?php
/**
 * 存管相关投资方法
 * @date 2017-3-8 17:48:05
 */
namespace core\service;

use core\dao\DealModel;
use core\dao\SupervisionIdempotentModel;
use core\service\P2pDepositoryService;
use core\service\P2pIdempotentService;
use core\service\DealBidService;

use core\service\BonusService;
use libs\utils\Aes;
use NCFGroup\Common\Library\Idworker;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\service\SupervisionAccountService;
use core\service\SupervisionFinanceService;
use core\service\UserService;
use core\service\DealService;
use core\service\DealLoadService;
use libs\utils\Logger;
use libs\payment\supervision\Supervision;
use core\service\UserThirdBalanceService;
use core\service\GoldBidCurrentService;
use core\service\UserTrackService;

class P2pDealBidService extends P2pDepositoryService {


    const MONEY_SHORT = 0; //总余额不足
    const MONEY_ENOUGH_GO_BID = 1; //余额足调本地BID
    const MONEY_ENOUGH_GO_BANK = 2; //余额足够去银行投资
    const GO_TO_BANK_TRANSFER = 3; //去银行划转,网贷->超级
    const WX_TO_P2P_TRANSFER = 4; //划转提示,网信To存管
    const P2P_TO_WX_TRANSFER = 5; //划转提示,存管To网信
    const GO_TO_BANK_TRANSFER_SUPER  = 6; //去银行划转,超级->网贷
    const UNACTIVATED_USER = 7; //未激活用户去开户激活


    const STATUS_NONE                = 1000; // 无需验密投资或划转
    const STATUS_TRANSFER            = 1001; // 免密划转
    const STATUS_SECRET_TRANSFER     = 1002; // 验密划转 网贷->超级
    const STAtUS_SECRET_BID          = 1003; // 验密投资
    const STATUS_SECRET_TRANSFER_WX  = 1004; // 验密划转 超级->网贷
    const STATUS_UNACTIVATED_USER    = 1005; // 网贷为未激活用户


    /**
     * 获取存管验密投资时候的表单数据
     * @param $orderId
     * @param $dealId pc的话传的是加密的|h5传的是标的ID
     * @param $userId
     * @param $money
     * @param array $bidParams
     * $bidParams = array(
        'couponId' => '',
        'sourceType' => '',
        'siteId' => '',
        'jforderId' => '',
        'discountId' => '',
        'discountType' => '',
        'discountGoodsPrice' => '',
        'discountGoodsType' => '',
     * @param bool|false $targetNew
     * @return array
     */
    public function dealBidSecretRequest($orderId,$dealId, $userId, $money, $bidParams=array(), $optionParams=array()){
        $platform   = isset($optionParams['platform'])      ?   $optionParams['platform']   : 'pc';
        $mobileType = isset($optionParams['mobileType'])    ?   $optionParams['mobileType'] : '11';
        $formId     = isset($optionParams['formId'])        ?   $optionParams['formId']     : 'bidWithPwdForm';
        $targetNew  = isset($optionParams['targetNew'])     ?   $optionParams['targetNew']  : false;
        $returnUrl  = isset($optionParams['returnUrl'])     ?   $optionParams['returnUrl']  : false;

        $dealId = ($platform == 'pc') ? Aes::decryptForDeal($dealId) : $dealId;
        $logParams = "orderId:{$orderId},dealId:{$dealId},userId:{$userId},money:{$money},bidParams:".json_encode($bidParams);
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",验密投资," . $logParams);

        $bonusInfo = (new BonusService())->getUsableBonus($userId,true,$money,$orderId);
        $bonusMoney = $bonusInfo['money'];
        $dealService = new DealService();
        $isDT = $dealService->isDealDT($dealId);

        $rpOrderList = (bccomp($bonusMoney,0,2)==1) ? $bonusInfo['accountInfo'] : array();
        $bonusData = array();
        if(!empty($rpOrderList) || $isDT === false){
            foreach($rpOrderList as $k=>$v){
                $rpOrderList[$k]['rpAmount'] = bcmul($v['rpAmount'],100);
            }
            $bonusData = $rpOrderList;
        }
        $accAmount = bcsub($money,$bonusMoney,2);
        $accAmount = $accAmount > 0 ? $accAmount : 0; // 使用账户余额

        $bidService = new SupervisionDealService();
        $data = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'totalAmount' => bcmul($money,100),
            'accAmount' => bcmul($accAmount,100),
            'currency' => 'CNY',
            'bidId' => $dealId,
            'returnUrl' => $returnUrl,
            'mobileType' => $mobileType,
        );
        if(!empty($bonusData)){
            $data['rpOrderList'] = json_encode($bonusData);
        }

        $res = $bidService->investCreateSecret($data,$platform,$formId,$targetNew);

        if(!$res['data']['form']){
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",请求验密投资接口失败 params:".json_encode($data));
            return $res;
        }
        $returnData['form'] = $res['data']['form'];
        $returnData['formId'] = $res['data']['formId'];

        // 验密投资需要保存投资时的信息以便后续使用
        $params = array(
            'couponId'     => isset($bidParams['couponId']) ? $bidParams['couponId'] : '',
            'sourceType'   => isset($bidParams['sourceType']) ? $bidParams['sourceType'] :'',
            'siteId'       => isset($bidParams['siteId']) ? $bidParams['siteId'] : '',
            'jforderId'    => isset($bidParams['jforderId']) ? $bidParams['jforderId'] : '',
            'discountId'   => isset($bidParams['discountId']) ? $bidParams['discountId'] : '',
            'discountType' => isset($bidParams['discountType']) ? $bidParams['discountType'] : '',
            'discountGoodsPrice' => isset($bidParams['discountGoodsPrice']) ? $bidParams['discountGoodsPrice'] : '',
            'discountGoodsType' => isset($bidParams['discountGoodsType']) ? $bidParams['discountGoodsType'] : '',
            'bonusInfo'   => $bonusInfo,
        );

        // 保存订单信息
        $orderData = array(
            'deal_id' => $dealId,
            'loan_user_id' => $userId,
            'money' => $money,
            'type' => P2pDepositoryService::IDEMPOTENT_TYPE_BID,
            'status' => P2pIdempotentService::STATUS_SEND,
            'result' => P2pIdempotentService::RESULT_WAIT,
            'params' => json_encode($params),
        );

        $saveRes = P2pIdempotentService::saveOrderInfo($orderId,$orderData,"",true);

        if($saveRes){
            return  array(
                'status' => 'S',
                'respCode' => '00',
                'data' => $returnData,
            );
        }else{
            return array(
                'status' => 'S',
                'respCode' => '01',
                'respMsg' => '订单保存失败',
            );
        }
    }

    /**
     * 投资请求 免密投资
     * 详见: http://sandbox.firstpay.com/hk-api-demo/interface/web/586f5fea2034f15341db7758
     * @param $orderId
     * @param $dealId
     * @param $userId
     * @param $totalAmount
     * @param $accAmount
     * @param $bonusInfo 红包信息
     * @return bool
     */
    public function dealBidRequest($orderId,$dealId,$userId,$totalAmount,$accAmount,$bonusInfo) {
        $logParams = "orderId:{$orderId},dealId:{$dealId},userId:{$userId},totalAmount:{$totalAmount},accAmount:{$accAmount},bonusInfo:".json_encode($bonusInfo);
        Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . $logParams);

        $params = array(
            'orderId'       => $orderId,
            'userId'        => $userId,
            'totalAmount'   => bcmul($totalAmount,100),
            'accAmount'     => bcmul($accAmount,100),
            'bidId'         => $dealId,
        );
        $rpOrderList = (isset($bonusInfo['accountInfo']) && !empty($bonusInfo['accountInfo'])) ?  $bonusInfo['accountInfo'] :array();
        if(!empty($rpOrderList)){
            foreach($rpOrderList as $k=>$v){
                $rpOrderList[$k]['rpAmount'] = bcmul($v['rpAmount'],100);
            }
            $params['rpOrderList'] = json_encode($rpOrderList);
        }
        $bidService = new SupervisionDealService();
        $res = $bidService->investCreate($params);
        if($res['respCode'] != '00') {
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",存管投资失败 errMsg:" .$res['respMsg'] );
            return false;
        }

        $bidParams['bonusInfo'] = $bonusInfo;
        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'loan_user_id' => $userId,
            'money' => $totalAmount,
            'type' => P2pDepositoryService::IDEMPOTENT_TYPE_BID,
            'status' => P2pIdempotentService::STATUS_SEND,
            'result' => P2pIdempotentService::RESULT_WAIT,
            'params'=> json_encode($bidParams),
        );
        // GTM 存在重试所以此处改成用saveOrderInfo
        return P2pIdempotentService::saveOrderInfo($orderId,$data);
    }

    /**
     * 投资取消 同步执行
     * 不存在取消回调 所以不需要启jobs执行
     * @param $orderId
     */
    public function dealBidCancelRequest($orderId){
        $logParams = "orderId:{$orderId}";
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",投资失败通知银行取消 " . $logParams);
        $params = array(
            'origOrderId' => $orderId,
            'rpDirect' => '01',// 投资失败红包回到红包账户
        );
        $sds = new SupervisionDealService();
        $res = $sds->investCancel($params);

        if($res['status'] !== SupervisionBaseService::RESPONSE_SUCCESS && $res['respCode'] !== \libs\common\ErrCode::getCode('ERR_INVEST_NO_EXIST')) {
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",存管投资回滚失败 orderId:{$orderId} errMsg:" .$res['respMsg'] );
            return false;
        }

        $data = array(
            'status' => P2pIdempotentService::STATUS_CALLBACK,
            'result' => P2pIdempotentService::RESULT_FAIL,
        );
        return P2pIdempotentService::updateOrderInfo($orderId,$data);
    }

    /**
     * 投资回调（验密投资）
     * @param $orderId 订单ID
     * @param $status 回调状态
     * @return bool
     * @throws \Exception
     */
    public function bankBidCallBack($orderId,$status) {
        $logParams = "orderId:{$orderId},status:{$status}";
        Logger::info(__CLASS__ . ",". __FUNCTION__ ."," .$logParams);

        try{
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
            if(!$orderInfo) {
//                $cancRes = $this->dealBidCancelRequest($orderId);
//                // 保证一定要通知到
//                if(!$cancRes){
//                    $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
//                    $param = array($orderId);
//                    $job_model = new \core\dao\JobsModel();
//                    $job_model->priority = 99;
//                    $add_job = $job_model->addJob($function, $param,false,10);
//                    if (!$add_job) {
//                        throw new \Exception("投资取消通知银行jobs添加失败 orderId:".$orderId);
//                    }
//                }
                throw new \Exception("order_id不存在");
            }
            if(empty($orderInfo['loan_user_id'])){
                throw new \Exception("投资用户不存在");
            }

            $cbRes = $this->bankCallBack($orderId,$status);
            if(!$cbRes) {
                throw new \Exception("存管投资回调处理失败");
            }
        }catch (\Exception $ex) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ",params:".$logParams.", errMsg:". $ex->getMessage());
            return false;
        }
        return true;
    }


    /**
     * 判断是否需满足划转条件 满足返回划转金额不满足返回false
     * @param UserModel $user
     * @param DealModel $deal
     * @param $bidMoney
     * @return bool|string
     * @throws \Exception
     * @throws \libs\common\WXException
     */
    public function needTransferMoney(\core\dao\UserModel $user,DealModel $deal,$bidMoney){
        $dealService = new \core\service\DealService();
        $superService = new \core\service\SupervisionAccountService();
        $userService = new \core\service\UserService();

        try{
            $moneyInfo = $userService->getMoneyInfo($user,$bidMoney);
            // 获取用户余额
            $money = $moneyInfo['lc']; // 余额
            $bonusMoney = $moneyInfo['bonus']; // 红包
            //读取资产中心存管金额
            $userThirdBalanceService = new UserThirdBalanceService();
            $svBalanceResult = $userThirdBalanceService->getUserSupervisionMoney($user['id']);
            $bankMoney = min($moneyInfo['bank'], $svBalanceResult['supervisionBalance']); // 存管
            $bankUsableMoney = bcadd($bankMoney,$bonusMoney,2); // 存管可用余额
            $lcUsableMoney = bcadd($money,$bonusMoney,2);   // 非存管可用余额
            $totalUsableMoney = bcadd($bankMoney,bcadd($money,$bonusMoney,2),2); // 所有账户余额

            // 用户是否开通存管账户
            $isSvUser = $superService->isSupervisionUser($user);
            if(!$isSvUser){
                // 未开通存管账户不能划转
                Logger::error(__CLASS__ . "," . __FUNCTION__ . ",未开通存管账户不能划转");
                return false;
            }
            $isP2pPath = $dealService->isP2pPath($deal);

            if($isP2pPath === true){
                // 投资存管标的在余额不足时 且总余额大于等于投资金额 需要划转
                $transferMoney = bcsub($bidMoney,$bankUsableMoney,2);
                if(bccomp($transferMoney,'0.00',2) == 1 && bccomp($totalUsableMoney,$bidMoney,2) !== -1){
                    Logger::info(__CLASS__ . "," . __FUNCTION__ . ",网贷可以划转 金额:".$transferMoney);
                    return $transferMoney;
                }
            }else{
                // 是否开通免密提现授权
                $isOpenAuth = $superService->checkUserPrivileges($user['id'],array('WITHDRAW_TO_SUPER'));
                $transferMoney = bcsub($bidMoney,$lcUsableMoney,2);

                if($isOpenAuth && bccomp($transferMoney,'0.00',2) == 1 && bccomp($totalUsableMoney,$bidMoney,2) !== -1){
                    Logger::info(__CLASS__ . "," . __FUNCTION__ . ",专享可以划转 金额:".$transferMoney);
                    return $transferMoney;
                }
            }
        }catch (\Exception $ex){
            Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . 'errMsg:'.$ex->getMessage());
        }
        return false;
    }

    /**
     * 资金划转
     * @param $orderId
     * @param $userId
     * @param $amount
     * @param $isP2pPath
     * @return bool
     */
    public function moneyTransfer($orderId,$userId,$amount,$isP2pPath){
        return $isP2pPath ? $this->rechargeToBank($orderId,$userId,$amount) : $this->withdrawToSuper($orderId,$userId,$amount);
    }

    /**
     * 提现到超级账户
     * @param $orderId
     * @param $userId
     * @param $amount
     * @return bool
     */
    public function withdrawToSuper($orderId,$userId,$amount){
        $data['orderId'] = $orderId;
        $data['userId'] = $userId;
        $data['amount'] = bcmul($amount,100);
        $data['superUserId'] = $userId;
        $data['currency'] = 'CNY';

        try{
            Logger::info(__CLASS__ . "," . __FUNCTION__ . ",请求支付提现到超级账户 orderId:{$orderId}, userId:{$userId},amount:{$amount}");
            $service = new \core\service\SupervisionFinanceService();
            $res = $service->accountSuperWithdraw($data);
        }catch (\Exception $ex){
            $res['status'] = false;
            \libs\utils\Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '支付提现到超级账户异常 orderId:'.$orderId . " errMsg:".$ex->getMessage());
        }
        return $res['status'] == SupervisionBaseService::RESPONSE_SUCCESS ? true :false;
    }

    /**
     * 超级账户充值到存管账户
     * @param $orderId
     * @param $userId
     * @param $amount
     * @return bool
     */
    public function rechargeToBank($orderId,$userId,$amount){
        $data['orderId'] = $orderId;
        $data['userId'] = $userId;
        $data['amount'] = bcmul($amount,100);
        $data['currency'] = 'CNY';

        try{
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . "请求支付划转 orderId:{$orderId},userId:{$userId},amount:{$amount}");
            $service = new \core\service\SupervisionFinanceService();
            $res = $service->superRecharge($data);
        }catch (\Exception $ex){
            $res['status'] = SupervisionBaseService::RESPONSE_FAILURE;
            Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '支付划转异常 orderId:'.$orderId . " errMsg:".$ex->getMessage());
        }
        return $res['status'] == SupervisionBaseService::RESPONSE_SUCCESS ? true :false;
    }


    /**
     * 查看用户总账号金额是否够投资(未划转之前) 用来判断是否需要
     * @param $uid
     * @param $lcMoney    超级账户余额
     * @param $bounsMoney 红包余额
     * @param $bankMoney  存管余额
     * @param $bidMoney   投资金额
     * @param $isP2pPath  是否投资的存管标的
     * @return bool
     */
    public function checkMoneyEnough($uid,$lcMoney,$bounsMoney,$bankMoney,$bidMoney,$isP2pPath){
        if($isP2pPath){
            //投资存管标的不需要开通授权直接从超级账户划转
            $totalMoney = bcadd($bounsMoney,$bankMoney,2);
            $totalMoney = bcadd($totalMoney,$lcMoney,2);
        }else{
            $totalMoney = bcadd($bounsMoney,$lcMoney,2);
            $superAccount = new SupervisionAccountService();
            $transferAuth = $superAccount->checkUserPrivileges($uid,array('WITHDRAW_TO_SUPER'));
            $totalMoney = ($transferAuth === true) ? bcadd($totalMoney,$bankMoney,2) : $totalMoney;
        }
        return (bccomp($bidMoney,$totalMoney,2) == 1) ? false : true;
    }

    /**

     * 此回调用于验密投资回调(pc 回调 app回调 app回调的时候会传投资人ID)
     * 根据银行回调结果 结合 订单状态进行成功或失败处理
     * 因为是分布式事务 所以银行的回调有可能在理财处理之前就已经到达
     * 要考虑理财未处理而银行回调已经到达情况
     * @param $orderId
     * @param $uid 投资人ID
     * @param $status
     */
    public function bankCallBack($orderId,$status) {
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        $orderParams = json_decode($orderInfo['params'],true);

        if(json_last_error()){
            $orderInfo = false;
        }

        if(!$orderInfo){
            $cancRes = $this->dealBidCancelRequest($orderId);
            // 保证一定要通知到
            if(!$cancRes){
                $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
                $param = array($orderId);
                $job_model = new \core\dao\JobsModel();
                $job_model->priority = 99;
                $add_job = $job_model->addJob($function, $param,false,100);
                if (!$add_job) {
                    return false;
                }
            }
            Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '订单号不存在 orderId:'.$orderId);
            \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK, '银行验密投资回调：订单号不存在', 'orderId:'.$orderId);
            return false;
        }
        Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调 begin： orderId:'.$orderId." status:{$status}");



        if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK){
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调 end： orderId:'.$orderId." status:{$status}");
            return true;
        }

        // 理财处理成功 银行处理失败 这种情况要推进银行必须成功 理论上这种情况应该不存在
        if($orderInfo['result'] == P2pIdempotentService::RESULT_SUCC && $status == P2pDepositoryService::CALLBACK_STATUS_FAIL) {
            \libs\utils\Alarm::push(self::ALARM_BANK_CALLBAK, '银行投资回调：理财投资成功但是银行投资失败', 'orderId:'.$orderId);
            Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调：理财投资成功但是银行投资失败, orderId:'.$orderId);
            return false;
        }


        $data = array(
            'status' => P2pIdempotentService::STATUS_CALLBACK,
            'result' => P2pIdempotentService::RESULT_SUCC,
        );

        // 双方都已经处理成功
        if($orderInfo['result'] == P2pIdempotentService::RESULT_SUCC && $status == P2pDepositoryService::CALLBACK_STATUS_SUCC) {
            return P2pIdempotentService::updateOrderInfo($orderId,$data);
        }

        // 双方都处理失败
        if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL && $status == P2pDepositoryService::CALLBACK_STATUS_FAIL) {
            $data['result'] = P2pIdempotentService::RESULT_FAIL;
            return P2pIdempotentService::updateOrderInfo($orderId,$data);
        }

        // 理财处理失败 银行处理成功 这种情况推进银行投资取消
        if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL && $status == P2pDepositoryService::CALLBACK_STATUS_SUCC) {
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '投资取消通知银行： orderId:'.$orderId);

            $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
            $param = array($orderId);
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = 99;
            $add_job = $job_model->addJob($function, $param,false,9999);
            if (!$add_job) {
                Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '投资取消通知银行jobs添加失败： orderId:'.$orderId);
                return false;
            }
            $data['result'] = P2pIdempotentService::RESULT_SUCC;
            return P2pIdempotentService::updateOrderInfo($orderId,$data);
        }

        // 理财还未处理 银行处理成功 试着投资成功 如果投资失败调取消投资
        if($orderInfo['result'] == P2pIdempotentService::RESULT_WAIT && $status == P2pDepositoryService::CALLBACK_STATUS_SUCC) {
            $dealLoadService = new DealLoadService();
            $bidRes = $dealLoadService->bidForBankSecret($orderId,$orderInfo['loan_user_id'],$status);

            try{
                $GLOBALS['db']->startTrans();
                if($bidRes['error'] === true) {
                    Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '理财投资失败 尝试通知银行取消投资 orderId:'.$orderId);

                    $cancelS = new \core\service\P2pDealBidService();
                    $cancRes = $cancelS->dealBidCancelRequest($orderId);

                    // 保证一定要通知到
                    if(!$cancRes){
                        $function = '\core\service\P2pDealBidService::dealBidCancelRequest';
                        $param = array($orderId);
                        $job_model = new \core\dao\JobsModel();
                        $job_model->priority = 99;
                        $add_job = $job_model->addJob($function, $param,false,100);
                        if (!$add_job) {
                            throw new \Exception("投资取消通知银行jobs添加失败 orderId:".$orderId);
                        }
                    }
                    $data['result'] = P2pIdempotentService::RESULT_FAIL;
                }else{
                    $data['result'] = P2pIdempotentService::RESULT_SUCC;
                }
                $res = P2pIdempotentService::updateOrderInfo($orderId,$data);
                if($res === false){
                     throw new \Exception("订单信息修改失败 orderId:".$orderId);
                }
                $GLOBALS['db']->commit();
            }catch (\Exception $ex){
                $GLOBALS['db']->rollback();
                Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . "errMsg:".$ex->getMessage());
                return false;
            }
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调 end： orderId:'.$orderId."  银行处理成功回调理财 理财处理成功(投资成功或投资取消成功)" );
            return true;
        }

        // 理财还未处理 银行处理失败
        if($orderInfo['result'] == P2pIdempotentService::RESULT_WAIT && $status == P2pDepositoryService::CALLBACK_STATUS_FAIL) {
            $data['result'] = P2pIdempotentService::RESULT_FAIL;
            $res =  P2pIdempotentService::updateOrderInfo($orderId,$data);
            if($res){
                Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调 end： orderId:'.$orderId."  银行处理失败理财直接更新状态成功" );
            }
            return $res;
        }
        return true;
    }


    /**
     * @param $user
     * @param $deal
     * @param $bidMoney
     * @param int $source_type
     * @param int $coupon_id
     * @param int $site_id
     * @param  string $source 来源
     * @return array
     * @throws \Exception
     * @throws \libs\common\WXException
     */
    public function beforeBid($orderId,$user, $deal, $bidMoney, $bidParams=array(),$source='p2p'){
        $couponId = isset($bidParams['couponId']) ? $bidParams['couponId'] : '';
        $sourceType = isset($bidParams['sourceType']) ? $bidParams['sourceType'] : '';
        $siteId = isset($bidParams['siteId']) ? $bidParams['siteId'] : '';

        $returnData = array(
            'status' => self::STATUS_NONE,
            'data' => array()
        );

        $userService = new UserService();
        $dealService = new DealService();
        $dealLoadService = new DealLoadService();
        $isP2pPath = $dealService->isP2pPath($deal);
        $uts = new UserTrackService();

        if($isP2pPath){
            //P2P标的非投资户不允许投资
            if(!$userService->allowAccountLoan($GLOBALS['user_info']['user_purpose'])){
                throw new \Exception($GLOBALS['lang']['非投资账户不允许投资']);
            }
        }

        // 降级开关关闭 不能投资报备标的
        if($isP2pPath && Supervision::isServiceDown()){
            throw new \Exception(Supervision::maintainMessage());
        }

        $saService = new SupervisionAccountService();
        $isOpened = $saService->isSupervisionUser($user); // 用户是否开通存管账户
        if($isP2pPath && !$isOpened){
            throw new \Exception("请先开通存管账户在进行投资");
        }

        // 专享标的未开通存管户的话直接返回不需要进行其它判断
        if($isOpened === false){
            return $returnData;
        }

        $untransferable = intval(app_conf('SV_UNTRANSFERABLE'));

        $moneyInfo = $userService->getMoneyInfo($user, $bidMoney);
        $bankMoney = bcadd($moneyInfo['bank'], $moneyInfo['bonus'], 2);
        $wxMoney = bcadd($moneyInfo['lc'], $moneyInfo['bonus'], 2);
        $totalMoney = bcadd($bankMoney, $moneyInfo['lc'], 2);// 总的真实可用余额
        $balanceResult = (new \core\service\UserThirdBalanceService())->getUserSupervisionMoney($user['id']);
        $svLocalMoney = $balanceResult['supervisionBalance']; // 资产中心的余额
        $totalLocalMoney = bcadd($svLocalMoney,$wxMoney,2); // 以资产中心为准的总的可用余额

        // 普惠站从并且不是从主站过来的用户不允许余额划转 只使用网贷余额
        if(is_firstp2p() && $uts->isWxlcLogin($user['id']) == false){
            if(bccomp($bankMoney,$bidMoney,2) == -1){
                throw new \Exception("余额不足，请充值");
            }
        }

        // 投资专享标 并且已经开通了存管账户 并且存管降级开关开启并且超级账户余额不足 则提示升级信息
        if(!$isP2pPath && bccomp($wxMoney, $bidMoney, 2) == -1 && Supervision::isServiceDown()){
            throw new \Exception('余额不足，海口联合农商银行系统维护中，网贷账户现金余额暂不可用');
        }

        if (bccomp($totalMoney, $bidMoney, 2) == -1 && Supervision::isServiceDown()){
            throw new \Exception('余额不足，海口联合农商银行系统维护中，网贷账户现金余额暂不可用');
        }

        if($untransferable && $isOpened){
            $compMoney = $isP2pPath ? $bankMoney : $wxMoney;
            if(bccomp($compMoney,$bidMoney,2) == -1){
                $throwMsg = $isP2pPath ? '网贷P2P账户余额不足，可从网信账户提现后再充值到网贷P2P账户' : '网信账户余额不足，可从网贷P2P账户提现后再充值到网信账户';
                throw  new \Exception($throwMsg);
            }
        }

        // jira:4871 投资体验优化
        if($bidMoney > min($totalMoney,$totalLocalMoney)){
            if(bccomp($totalLocalMoney,$totalMoney) == 0){
                throw new \Exception("余额不足，请充值");
            }else{
                throw new \Exception("正在与银行系统同步您的账户信息，请过段时间再操作");
            }
        }


        $svService = new SupervisionService();
        $isFreePayment = $svService->isFreePayment($user['id']);//是否开通打包免密协议

        $transService = new \core\service\SupervisionFinanceService();

        // 调用remin服务
        //$isRemind = $transService->isPromptTransfer($user['id']);// 是否提醒
        //$isAlertTransfer = $isRemind; // 是否需要弹划转框
        $isAlertTransfer = true; // 默认一定弹划转框

        if($isP2pPath){
            $isAlertTransfer = (bccomp($bankMoney,$bidMoney,2) == -1) ? $isAlertTransfer : false;
        }else{
            $isAlertTransfer = (bccomp($wxMoney,$bidMoney,2) == -1) ? $isAlertTransfer : false;
        }

        // 是否需要弹出验密投资框
        $isNeedSecretBid = ($isP2pPath && !$isFreePayment && $isOpened) ? true :false;

        //判断是否需要提前划转一笔资金
//        if($isP2pPath && $isAlertTransfer === false && $isFreePayment === false){
//            $tranMoney = bcsub($bidMoney, $bankMoney, 2);
//            if(bccomp($tranMoney,'0.00',2) == 1){
//                $transOrderId = Idworker::instance()->getId();
//                $transferRes = $this->rechargeToBank($transOrderId, $user['id'], $tranMoney);
//                Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "开始余额划转 orderId:{$orderId},money:{$tranMoney}")));
//                if ($transferRes === false) {
//                    Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, "余额划转失败 orderId:{$orderId},money:{$tranMoney}")));
//                    throw new \Exception('余额划转失败');
//                }
//            }
//        }

        // 无需划转 则判断是否需要验密投资
        if(($isP2pPath && $isAlertTransfer === false) || (!$isP2pPath && $isFreePayment && $isAlertTransfer === false)){
            if($isNeedSecretBid){//验密投资
                $dealLoadService->checkCanBid($deal, $user, $bidMoney, $sourceType, $couponId, $siteId);
                $returnData['status'] = self::STAtUS_SECRET_BID;
                $returnData['data'] = $this->getSecretPassInfo($orderId,$deal['id'],$bidMoney,$bidParams,$source);
            }
            return $returnData;
        }

        $dealLoadService->checkCanBid($deal, $user, $bidMoney, $sourceType, $couponId, $siteId);

        $hasUnactivatedTag = (new \core\service\UserTagService())->getTagByConstNameUserId('SV_UNACTIVATED_USER', $user['id']);
        if ($hasUnactivatedTag) {
            $returnData['status'] = self::STATUS_UNACTIVATED_USER;
            $resutnData['data'] = [];
        } else {
            // 存管标的 未点击过不在提示按钮的情况
            if($isP2pPath && $isAlertTransfer){
                $tranMoney = bcsub($bidMoney, $bankMoney, 2);
                $returnData['status'] = self::STATUS_SECRET_TRANSFER_WX;
                //$returnData['data'] = array('transferMoney' => $tranMoney, 'direction' => 'wx_to_bank');
                $returnData['data'] = $this->getSecretTransferInfo($orderId,$bidMoney,$tranMoney,2);
            }elseif(!$isP2pPath && $isFreePayment === false && bccomp($wxMoney, $bidMoney, 2) == -1){
                // 未开通划转协议的专享标的 且超级账户余额不足 需要验密划转
                $tranMoney = bcsub($bidMoney, $wxMoney, 2);
                $returnData['status'] = self::STATUS_SECRET_TRANSFER;
                $returnData['data'] = $this->getSecretTransferInfo($orderId,$bidMoney,$tranMoney);
            }elseif(!$isP2pPath && $isFreePayment && $isAlertTransfer){
                // 已开通划转协议的专享标的 且超级账户余额不足 且未同意过不在提示则需要弹划转框
                $tranMoney = bcsub($bidMoney, $wxMoney, 2);
                $returnData['status'] = self::STATUS_TRANSFER;
                $returnData['data'] = array('transferMoney' => $tranMoney, 'direction' => 'bank_to_wx');
            }
        }

        return $returnData;
    }

    /**
     * 投资前校验,余额划转
     * @params obj $user
     * @params obj $deal
     * @params double $bidMoney
     * @return int
     */
    public function preBid($user, $deal, $bidMoney, $source_type = 0, $coupon_id = 0, $site_id = 1,$ext =  array())
    {
        $userService = new UserService();
        if(is_numeric($user)){
            $user = $userService->getUser($user);
        }
        if (empty($user)) {
            return false;
        }
        $dealService = new DealService();
        $isP2p = $dealService->isP2pPath($deal);

        $svService = new SupervisionService();
        $svInfo = $svService->svInfo($user['id']);
        $isFreePayment = $isOpened = 0;
        if ($svInfo['status']) {
            $isFreePayment = $svInfo['isFreePayment'];
            $isOpened = $svInfo['isSvUser'];
        }
        $money = $userService->getMoneyInfo($user, $bidMoney);

        //提金不能使用红包
        if(isset($ext['type']) && $ext['type'] == 'gold_deliver'){
            $money['bonus'] = 0;
        }
        $cgMoney = min($money['bank'], $svInfo['svBalance']);
        $p2pMoney = bcadd($cgMoney, $money['bonus'], 2);
        $wxMoney = bcadd($money['lc'], $money['bonus'], 2);
        $total = bcadd($p2pMoney, $money['lc'], 2);

        $data = 'unknow';
        $status = -1;

        $sfService = new SupervisionFinanceService();

        Logger::info("preBid:".json_encode(['money' => $money, 'svInfo' => $svInfo, 'isp2p' => $isP2p, 'dealId' => $deal['id'], 'uid'=>$user['id']]));

        do {

            //P2P存管标不允许非投资户投资
            if($isP2p){
                if(!$userService->allowAccountLoan($user['user_purpose'])){
                    $status = -1;
                    $data = $GLOBALS['lang']['ONLY_INVESTMENT_USER_CAN_BID'];
                    break;
                }
            }
            // 降级
            if(Supervision::isServiceDown()){
                if ($isP2p) {
                    $status = -1;
                    $data = Supervision::maintainMessage();
                    break;
                } elseif ($isOpened && bccomp($total, $bidMoney, 2) == -1) {
                    $status = -1;
                    $data = '余额不足，海口联合农商银行系统维护中，网贷账户现金余额暂不可用';
                    break;
                }
            }

            if(!empty($deal['id'])){
                try {
                    if(isset($ext['type'])){
                        if ($ext['type'] == 'gold') {
                            $dealLoadService = new GoldBidService($deal['id'],$user['id'],$ext['buyAmount'],$ext['buyPrice'],$coupon_id);
                        }elseif($ext['type'] == 'gold_deliver') {
                            $dealLoadService = new GoldDeliverService(
                            $user['id'],
                            floatval($ext['goodsAmount']),
                            json_decode(urldecode($ext['goodsDetails']),true)
                            );
                        }elseif($ext['type'] == 'gold_current'){
                            $dealLoadService = new GoldBidCurrentService($user['id'],$ext['buyAmount'],$ext['buyPrice'],$coupon_id);
                        }
                        $dealLoadService->checkCanBid();
                    }else{
                        $dealLoadService = new DealLoadService();
                        $dealLoadService->checkCanBid($deal, $user, $bidMoney, $source_type, $coupon_id, $site_id);
                    }
                } catch (\Exception $e) {
                    $data = $e->getMessage();
                    Logger::error(__CLASS__ . "," . __FUNCTION__ . ", errMsg:" . $e->getMessage());
                    $status = -1;
                    break;
                }
            }

            /**
             * money is not enough
             */
            if (bccomp($total, $bidMoney, 2) == -1) {
                if (bccomp($money['bank'], $svInfo['svBalance'], 2) == 0) {
                    $data = '余额不足，请充值';
                } else {
                    $data = '正在与银行系统同步您的账户信息，请过段时间再操作';
                }
                $status = self::MONEY_SHORT;
                break;
            }

            if ($isP2p) {
                //投资p2p，网贷余额足够
                if (bccomp($p2pMoney, $bidMoney, 2) !== -1) {
                    $status = $isFreePayment ? self::MONEY_ENOUGH_GO_BID : self::MONEY_ENOUGH_GO_BANK;
                    break;
                }
                $tranToP2p = bcsub($bidMoney, $p2pMoney, 2);
                $wxRemain = bcsub($money['lc'], $tranToP2p, 2);
                $returnData = ['transfer' => $tranToP2p, 'remain' => $wxRemain];
            } else {
                //投资专享，超级账户余额足够
                if (bccomp($wxMoney, $bidMoney, 2) !== -1) {
                    $status = self::MONEY_ENOUGH_GO_BID;
                    break;
                }
                $tranToWx = bcsub($bidMoney, $wxMoney, 2);
                $p2pRemain = bcsub($svInfo['svBalance'], $tranToWx, 2);
                $returnData = ['transfer' => $tranToWx, 'remain' => $p2pRemain];
            }

            if (!$isOpened) {
                $data = '存管未开户';
                break;
            }

            /*下面的都是需要划转*/
            if(Supervision::isServiceDown()){ //降级不能划转
                $data = Supervision::maintainMessage();
                $status = -1;
                break;
            }

            // 普惠app和wap 不可以划转
            $uts = new UserTrackService();
            if ($site_id == 100 && $uts->isWxlcLogin($user['id']) == false) {
                $data = '余额不足，请充值';
                $status = -1;
                break;
            }

            //划转关闭
            $untransferable = intval(app_conf('SV_UNTRANSFERABLE'));
            if ($untransferable && $isOpened) {
                $data = $isP2p ? '网贷P2P账户余额不足，可从网信账户提现后再充值到网贷P2P账户' : '网信账户余额不足，可从网贷P2P账户提现后再充值到网信账户';
                $status = -1;
                break;
            }

            //验密划转
            if (!$isFreePayment) {
                //未激活
                if (!$svInfo['isActivated']) {
                    $status = self::UNACTIVATED_USER;
                } else {
                    $status = $isP2p ? self::GO_TO_BANK_TRANSFER_SUPER : self::GO_TO_BANK_TRANSFER;
                    $data = $returnData;
                }
                break;
            }

            //免密划转
            $isNeedTip = $sfService->isPromptTransfer($user['id']);
            if ($isNeedTip) { //提示状态
                $status = $isP2p ? self::WX_TO_P2P_TRANSFER : self::P2P_TO_WX_TRANSFER;
                $data = $returnData;
                break;
            } else { //不提示状态
                if ($isP2p) {
                    try {
                        $orderId = Idworker::instance()->getId();
                        $transferMoney = bcsub($bidMoney, $p2pMoney, 2);
                        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, $user['id'], $bidMoney)));
                        $transferRes = $this->rechargeToBank($orderId, $user['id'], $transferMoney);
                        if ($transferRes === false) {
                            throw new \Exception('余额划转失败');
                        }
                        if ($isFreePayment) {
                            $status = self::MONEY_ENOUGH_GO_BID;
                        } else {
                            $status = self::MONEY_ENOUGH_GO_BANK;
                        }
                    } catch (\Exception $e) {
                        Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, APP, $orderId, $user['id'], $bidMoney, " errMsg:" . $e->getMessage())));
                        $status = self::MONEY_SHORT;
                        $data = '余额划转失败，请充值';
                    }
                    break;
                } else {
                    $status = self::MONEY_ENOUGH_GO_BID;
                    break;
                }
            }
        } while (false);
        return array('status' => $status, 'data' => $data);
    }


    /**
     * 验密划转
     * @param $orderId
     * @param $bidMoney
     * @param $transferMoney
     * @param $direction 1 == 验密从存管账户划转到超级账户  2 == 验密从超级账户划转到存管账户
     * @return mixed
     */
    public function getSecretTransferInfo($orderId,$bidMoney,$transferMoney,$direction=1){
        $params = array(
            'srv' => $direction == 1 ? 'transfer' : 'transferWx',
            'orderId'=> $orderId,
            'amount' => $transferMoney,
        );
        $returnData['orderId'] = $orderId;
        $returnData['transferMoney'] = $transferMoney;
        $returnData['url'] = '/payment/Transit?'.http_build_query($params);
        return $returnData;
    }

    /**
     * 验密投资框内容
     * @param $orderId
     * @param $dealId
     * @param $money
     * @param $bidParams
     * @return mixed
     */
    public function getSecretPassInfo($orderId,$dealId,$money,$bidParams,$source){
        $params = array(
            'srv' => ($source == 'p2p') ? 'bid' : 'dtbid',
            'orderId' => $orderId,
            'dealId' => ($source == 'p2p') ? Aes::encryptForDeal($dealId) : $dealId,
            'money' => $money,
        );
        foreach($bidParams as $k=>$v){
            $params[$k] = $v;
        }

        $returnData['orderId'] = $orderId;
        $returnData['url'] = '/payment/Transit?'.http_build_query($params);
        return $returnData;
    }


    /**
     * 监管需求、临时同步数据方法==日后删除
     * @param $orderId
     * @param $dealId
     * @param $userId
     * @param $amount
     * @throws \Exception
     */
    public function syncBidDataToBank($orderId,$dealId,$loadId,$userId,$amount,$isInsertIde){
        $s = new SupervisionDealService();
        $params = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'bidId' => $dealId,
            'amount' => bcmul($amount,100),
            'orgAmount' => 0
        );
        $res = $s->dealOrderImport($params);
        if(!$res){
            throw new \Exception("同步失败");
        }
        if($isInsertIde){
            // 保存订单数据
            $data = array(
                'order_id' => $orderId,
                'deal_id' => $dealId,
                'load_id' => $loadId,
                'loan_user_id' => $userId,
                'money' => $amount,
                'type' => P2pDepositoryService::IDEMPOTENT_TYPE_BID,
                'status' => P2pIdempotentService::STATUS_SEND,
                'result' => P2pIdempotentService::RESULT_SUCC,
            );
            $saveRes = P2pIdempotentService::addOrderInfo($orderId,$data);
            if(!$saveRes){
                throw  new \Exception("订单数据保存失败");
            }
        }
        return ture;
    }


    /**
     * 债权信息同步给存管  --只执行一次 日后删除
     * @param $userId
     * @param $bidId
     * @param $sumAmount
     * @param $leftAmount
     * @return bool
     * @throws \Exception
     */
    public function syncDealCreditImport($userId,$bidId,$sumAmount,$leftAmount){
        $s = new SupervisionDealService();
        $params = array(
            'userId' => $userId,
            'bidId' => $bidId,
            'sumAmount' => bcmul($sumAmount,100),
            'leftAmount' => bcmul($leftAmount,100),
        );
        $res = $s->dealCreditImport($params);
        if(!$res){
            throw new \Exception("同步失败");
        }
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",债权同步 succ," . json_encode($params));
        return true;
    }
}
