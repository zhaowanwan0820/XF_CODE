<?php
/**
 * 存管相关投资方法
 * @date 2017-3-8 17:48:05
 */
namespace core\service\duotou;


use core\dao\UserModel;
use core\dao\DealModel;
use libs\utils\Logger;
use libs\utils\Alarm;
use libs\utils\Rpc;
use libs\common\ErrCode;
use core\service\BonusService;
use core\service\P2pDealBidService;
use core\service\SupervisionBaseService;
use core\service\DtPaymenyService;
use core\dao\SupervisionIdempotentModel;
use core\service\duotou\DtP2pDealBaseService;
use core\service\P2pIdempotentService;
use NCFGroup\Common\Library\Idworker;
use core\service\SupervisionOrderService; // 存管对账
use core\service\P2pDepositoryService;
use NCFGroup\Protos\Duotou\RequestCommon;
use core\service\DtBidService;
use core\service\DtEntranceService;

class DtP2pDealBidService extends DtP2pDealBaseService {

    /**
     * 多投宝投资请求 免密投资
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
        \libs\utils\Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . $logParams);

        $params = array(
            'orderId'       => $orderId,
            'userId'        => $userId,
            'freezeType'    => self::FREEZETYPE_TYPE_DTBID,
            'freezeSumAmount'   => bcmul($totalAmount,100),
            'freezeAccountAmount'  => bcmul($accAmount,100),
        );
        $rpOrderList = (isset($bonusInfo['accountInfo']) && !empty($bonusInfo['accountInfo'])) ?  $bonusInfo['accountInfo'] :array();
        if(!empty($rpOrderList)){
            foreach($rpOrderList as $k=>$v){
                $rpOrderList[$k]['rpAmount'] = bcmul($v['rpAmount'],100);
            }
            $params['rpOrderList'] = json_encode($rpOrderList);
        }

        $bidService = new DtPaymenyService();
        $res = $bidService->bookfreezeCreate($params);

        if($res['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
            \libs\utils\Logger::error(__CLASS__ . "," . __FUNCTION__ . ",智多鑫存管投资失败 errMsg:" .$res['respMsg'] );
            if($res['respCode'] == ErrCode::getCode('ERR_REQUEST_TIMEOUT')){
                throw new \Exception("加入失败，请稍后查看资金记录");
            }
            return false;
        }

        $bidParams['bonusInfo'] = $bonusInfo;
        $data = array(
            'order_id' => $orderId,
            'deal_id' => $dealId,
            'loan_user_id' => $userId,
            'money' => $totalAmount,
            'type' => self::IDEMPOTENT_TYPE_DTBID,
            'status' => P2pIdempotentService::STATUS_SEND,
            'result' => P2pIdempotentService::RESULT_WAIT,
            'params'=> json_encode($bidParams),
        );
        return P2pIdempotentService::saveOrderInfo($orderId,$data);
    }


    /**
     * 智多鑫验密投资
     * @param $orderId
     * @param $dealId
     * @param $userId
     * @param $money
     * @param array $bidParams
     * @param array $optionParams
     * @return array
     */
    public function dealBidSecretRequest($orderId,$dealId, $userId, $money, $bidParams=array(), $optionParams=array()){
        $platform   = isset($optionParams['platform'])      ?   $optionParams['platform']   : 'pc';
        $mobileType = isset($optionParams['mobileType'])    ?   $optionParams['mobileType'] : '11';
        $formId     = isset($optionParams['formId'])        ?   $optionParams['formId']     : 'bidWithPwdForm';
        $targetNew  = isset($optionParams['targetNew'])     ?   $optionParams['targetNew']  : false;
        $returnUrl  = isset($optionParams['returnUrl'])     ?   $optionParams['returnUrl']  : false;

        $logParams = "orderId:{$orderId},dealId:{$dealId},userId:{$userId},money:{$money},bidParams:".json_encode($bidParams);
        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",验密投资," . $logParams);

        $activityId = $bidParams['activityId'];
        $siteId = \libs\utils\Site::getId();
        $dtEntranceService = new DtEntranceService();
        $activityInfo = $dtEntranceService->getEntranceInfo($activityId, $siteId);
        $user = UserModel::instance()->find($userId);
        $dtBidService = new DtBidService();
        $canDtUseBonus = $dtBidService->canDtUseBonus($activityInfo, $user);
        if (!$canDtUseBonus) {
            $bonusInfo =  array('money' => 0, 'bonuses' => array(), 'accountInfo' => array());
        } else {
            $bonusInfo = (new BonusService())->getUsableBonus($userId,true,$money,$orderId);
        }
        $bonusMoney = $bonusInfo['money'];

        $rpOrderList = (bccomp($bonusMoney,0,2)==1) ? $bonusInfo['accountInfo'] : array();
        $bonusData = array();
        if(!empty($rpOrderList)){
            foreach($rpOrderList as $k=>$v){
                $rpOrderList[$k]['rpAmount'] = bcmul($v['rpAmount'],100);
            }
            $bonusData = $rpOrderList;
        }
        $accAmount = bcsub($money,$bonusMoney,2);
        $accAmount = $accAmount > 0 ? $accAmount : 0; // 使用账户余额

        $data = array(
            'orderId' => $orderId,
            'userId' => $userId,
            'freezeType'    => self::FREEZETYPE_TYPE_DTBID,
            'freezeSumAmount'   => bcmul($money,100),
            'freezeAccountAmount'  => bcmul($accAmount,100),
            'returnUrl' => $returnUrl,
            'mobileType' => $mobileType,
        );
        if(!empty($bonusData)){
            $data['rpOrderList'] = json_encode($bonusData);
        }

        $bidService = new DtPaymenyService();
        $res = $bidService->bookfreezeCreatePage($data,$platform);

        if(!$res['data']['form']){
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",请求验密投资接口失败 params:".json_encode($data));
            return $res;
        }
        $returnData['form'] = $res['data']['form'];
        $returnData['formId'] = $res['data']['formId'];

        // 验密投资需要保存投资时的信息以便后续使用
        $params = array(
            'activityId'     => isset($bidParams['activityId']) ? $bidParams['activityId'] : '',
            'couponId'   => isset($bidParams['couponId']) ? $bidParams['couponId'] :'',
            'siteId'       => isset($bidParams['siteId']) ? $bidParams['siteId'] : '',
            'discount_id'   => isset($bidParams['discount_id']) ? $bidParams['discount_id'] : '',
            'discount_type' => isset($bidParams['discount_type']) ? $bidParams['discount_type'] : '',
            'bonusInfo'   => $bonusInfo,
        );

        // 保存订单信息
        $orderData = array(
            'deal_id' => $dealId,
            'loan_user_id' => $userId,
            'money' => $money,
            'type' => self::IDEMPOTENT_TYPE_DTBID,
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
     * App|Wap 在存管验密之后的 验密投资
     * @param $orderId
     * @param $uid
     * @param $status
     */
    public function dealBidForSecret($orderId,$uid,$status){
        Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . "验密投资回调 orderId:{$orderId},uid:{$uid},status:{$status}");
        $return = array('errCode' => 0, 'errMsg' => "", 'data' => '');
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        if(!$orderInfo){
            $return['errCode'] = '-1';
            $return['errMsg'] = '加入失败，请稍后查看资金记录';
        }elseif($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK){
            if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL){
                $return['errCode'] = '-1';
                $return['errMsg'] = '加入失败，请稍后查看资金记录';
            }else{
                $return = $this->getDtBidSecretReturnInfo($orderInfo);
            }
        }elseif($orderInfo['status'] == P2pIdempotentService::STATUS_SEND){
            $isLock = false;
            for($i=0;$i<3;$i++){
                if($this->getBidLock($orderId) === '0'){
                    sleep(1);
                    continue;
                }else{
                    $isLock = true;
                    break;
                }
            }
            if($isLock === false){
                return array(
                    'errCode' => '-100',
                    'errMsg' => '投资进行中，请稍后查看资金记录',
                );
            }

            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . "验密投资回调 orderId:{$orderId},uid:{$uid},status:{$status} 获得投资锁");

            $res = $this->dealBidCallBack($orderId,$status);

            if($res === true){
                return $this->getDtBidSecretReturnInfo($orderInfo);
            }
            if(isset($res['errCode'])){
                return $res;
            }else{
                $return['errCode'] = '-1';
                $return['errMsg'] = '加入失败，请稍后查看资金记录';
            }
        }
        return $return;
    }

    public function getDtBidSecretReturnInfo($orderInfo){
        $rpc = new Rpc('duotouRpc');
        $dealRequest = new RequestCommon();
        $dealRequest->setVars(array('project_id' => $orderInfo['deal_id']));
        $response = $rpc->go('NCFGroup\Duotou\Services\Project', 'getProjectInfoById', $dealRequest);

        $dealName = isset($response['data']['name']) ? $response['data']['name'] : '';

        $params = json_decode($orderInfo['params'],true);
        $return['errMsg'] = '投资成功';
        $return['data'] = array(
            'token' => $orderInfo['order_id'],
            'loadId' => $orderInfo['load_id'],
            'money' => $orderInfo['money'],
            'isFirst' => $params['isFirst'],
            'projectName' => $dealName);
        return $return;
    }

    /**
     * 智多鑫预约投资回调
     * @param $orderId
     * @param $status
     */
    public function dealBidCallBack($orderId,$status){
        $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);

        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",当前订单信息" . json_encode($orderInfo));

        if(!$orderInfo){
            Alarm::push(self::ALARM_BANK_CALLBAK,'智多鑫预约冻结回调订单不存在'," orderId:".$orderId);
            return false;
        }

        if($orderInfo['status'] == P2pIdempotentService::STATUS_CALLBACK){
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '银行投资回调 end： orderId:'.$orderId." status:{$status}");
            return true;
        }

        // 存管明确返回失败
        if($status == P2pDepositoryService::CALLBACK_STATUS_FAIL){
            // 保存订单状态
            $data = array(
                'status' => P2pIdempotentService::STATUS_CALLBACK,
                'result' => P2pIdempotentService::RESULT_FAIL,
            );
            return P2pIdempotentService::updateOrderInfo($orderId,$data);
        }


        $userId = $orderInfo['loan_user_id'];
        $dealId = $orderInfo['deal_id'];
        $money = $orderInfo['money'];
        $orderParams = json_decode($orderInfo['params'],true);

        if(json_last_error()){
            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",json解析错误 解析前 orderParams" .$orderInfo['params'].",解析后 orderParams:".json_encode($orderParams));
        }

        $coupon_id = $orderParams['couponId'];

        if($orderInfo['result'] == P2pIdempotentService::RESULT_WAIT && $status == \core\service\P2pDepositoryService::CALLBACK_STATUS_SUCC){
            $bidService = new \core\service\DtBidService();


            /** 智多鑫投资理论上orderParmas 肯定有值，如果为空说明json解析出问题了，临时解决 */
            if(!empty($orderParams)){
                $orderParams['orderInfo'] = $orderInfo;
                $bidRes = $bidService->bid($userId, $dealId, $money, $coupon_id,$orderParams);
            }else{
                $bidRes['errCode'] = -1;
            }
            Logger::info(__CLASS__ . "," . __FUNCTION__ . "," . '当前订单信息 orderParams:'.json_encode($orderParams));



            if($bidRes['errCode'] == 0){
                return $bidRes;
            }else{
                $request = new RequestCommon();
                $request->setVars(array("token"=>$orderId));
                $rpc = new Rpc('duotouRpc');
                $response = $rpc->go('\NCFGroup\Duotou\Services\DealLoan', "getDealLoanByToken", $request);
                if(!empty($response)) { //获得了返回值
                    if(!$response['data']) { //投资记录不存在
                        //发起取消
                        return $this->dealBidCancelRequest($orderId);
                    } else {
                        // 投资记录存在，投资失败报警
                        Logger::error(__CLASS__ . "," . __FUNCTION__ . "," . '智多鑫投资异常 orderId:'.$orderId);
                        return false;
                    }
                }
            }
        }

    }

    /**
     * 取消预约冻结
     * @param $orderId
     * @param $userId
     * @param $amount
     * @return bool|mixed
     * @throws \Exception
     */
//    public function dealBidCancelRequest($orderId,$userId,$amount){
//        $params = array(
//            'orderId' => $orderId,
//            'userId' => $userId,
//            'unFreezeType' => self::FREEZETYPE_TYPE_DTBID,
//            'amount' => bcmul($amount,100),
//        );
//        Logger::info(__CLASS__ . "," . __FUNCTION__ . ",智多鑫预约投资失败通知银行取消 " . json_encode($params));
//
//        $sds = new DtPaymenyService();
//        $res = $sds->bookfreezeCancel($params);
//
//        if($res['status'] !== SupervisionBaseService::RESPONSE_SUCCESS) {
//            Logger::error(__CLASS__ . "," . __FUNCTION__ . ",存管投资回滚失败 orderId:{$orderId} errMsg:" .$res['respMsg'] );
//            return false;
//        }
//
//        $data = array(
//            'status' => P2pIdempotentService::STATUS_CALLBACK,
//            'result' => P2pIdempotentService::RESULT_FAIL,
//        );
//        return P2pIdempotentService::updateOrderInfo($orderId,$data);
//    }

    public function dealBidCancelRequest($orderId){
        $service = new \core\service\P2pDealBidService();
        return $service->dealBidCancelRequest($orderId);
    }

    public function getBidLock($orderId,$timeout=5){
        try{
            $key = "dtbid_".$orderId;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $res = $redis->setNx($key, 1);
            if($res){
                $redis->expire($key, $timeout);
            }
            return $res ? '1' : '0';
        }catch (\Exception $ex){
            return false;
        }
        return '0';
    }

    public function delBidLock($orderId){
        try{
            $key = "dtbid_".$orderId;
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            return $redis->del($key);
        }catch (\Exception $ex){
            return false;
        }
    }
}
