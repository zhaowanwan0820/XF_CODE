<?php
/**
 * 标的代扣
 */

namespace core\service;

use core\service\DealService;
use core\service\P2pDepositoryService;
use libs\utils\Logger;
use core\dao\DealModel;
use core\dao\DealRepayModel;
use core\dao\UserModel;
use core\dao\JobsModel;
use core\dao\SupervisionIdempotentModel;

class DealDkService extends BaseService {

    const DK_STATUS_NONE  = 0; // 未查询到代扣状态
    const DK_STATUS_SUCC  = 1; // 代扣成功
    const DK_STATUS_FAIL  = 2; // 代扣失败
    const DK_STATUS_DOING = 3; // 代扣进行中


    const ERR_DEAL_FIND_NULL = '21011';
    const ERR_DEAL_REPAY_ID = '21030';
    const ERR_DEAL_APPROVE_NUMBER = '21041';
    const ERR_DEAL_REPAY_DONE = '21042';
    const ERR_DEAL_REPAY_SELF = '21043';

    const ERR_CODE_SYS = '40000';
    const ERR_CODE_PARAMS = '40010';
    const ERR_CODE_TIME_BEYOND = '40011';
    const ERR_CODE_DKING = '40012';
    const ERR_CODE_STATUS_FORBID = '40013';
    const ERR_CODE_NOTDK = '40014';
    const ERR_CODE_NOT_EXISTS = '40015';
    const ERR_CODE_NORESULT = '40016';
    const ERR_CODE_NO_REPORT = '40017';

    const BUSINESS_STATUS_NONE = 0; //未还款
    const BUSINESS_STATUS_SUCC = 1; //还款完成
    const BUSINESS_STATUS_REPAYING = 2; //还款中

    public static $errCodeMsg = array(
        self::ERR_CODE_SYS => '系统错误,更新还款信息失败',
        self::ERR_CODE_PARAMS => '参数错误',
        self::ERR_CODE_TIME_BEYOND => '已过标的最晚调整时间',
        self::ERR_CODE_DKING => '代扣进行时段不能进行还款方式变更',
        self::ERR_CODE_STATUS_FORBID => '当前状态不允许更改还款方式',
        self::ERR_CODE_NOTDK => '当前标的非代扣还款',
        self::ERR_CODE_NOT_EXISTS => '标的还款信息不存在',
        self::ERR_CODE_NORESULT => '未查询到代扣信息',
        self::ERR_CODE_NO_REPORT => '未报备标的不允许更改还款方式',
        self::ERR_DEAL_FIND_NULL => '未查询到标的',
        self::ERR_DEAL_REPAY_ID => '标的还款ID异常',
        self::ERR_DEAL_APPROVE_NUMBER => '标的放款审批单号异常',
        self::ERR_DEAL_REPAY_DONE => '该期还款已完成',
        self::ERR_DEAL_REPAY_SELF => '使用网贷账户还款',
    );

    const UPDATE_REPAY_TYPE_LATEST_TIME = '15:30:00';

    /**
     * 更改代扣还款方式
     * 已报备标的可将“代扣还款”调整为“代充值还款”或“代垫还款”
     * “代充值还款”、“代垫还款”不可调整为“代扣还款”
     * @param $dealId
     * @param $repayId
     * @param $repayType
     * @param string $approveNumber
     * @return bool
     * @throws \Exception
     */
    public function updateDkRepayType($dealId,$repayId,$repayType,$approveNumber=''){
        $time = time();
        $latestTime = date('Y-m-d') . " ". self::UPDATE_REPAY_TYPE_LATEST_TIME;
        $latestTime = strtotime($latestTime);

        if(!in_array($repayType,array(DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN))){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_PARAMS],self::ERR_CODE_PARAMS);
        }

        if($time > $latestTime){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_TIME_BEYOND],self::ERR_CODE_TIME_BEYOND);
        }
        $dealRepay = DealRepayModel::instance()->findViaSlave($repayId);
        if(!$dealRepay || $dealRepay->deal_id != $dealId){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NOT_EXISTS],self::ERR_CODE_NOT_EXISTS);
        }

        // “代充值还款”、“代垫还款”不可调整为“代扣还款”或空
        if(in_array($dealRepay->repay_type,array(DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayModel::DEAL_REPAY_TYPE_DAIDIAN)) && $repayType == DealRepayModel::DEAL_REPAY_TYPE_DAIKOU){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_STATUS_FORBID],self::ERR_CODE_STATUS_FORBID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal || (!empty($approveNumber) && $deal->approve_number != $approveNumber)){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NOT_EXISTS],self::ERR_CODE_NOT_EXISTS);
        }

        if($deal->report_status != DealModel::DEAL_REPORT_STATUS_YES){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NO_REPORT],self::ERR_CODE_NO_REPORT);
        }

        if($deal->is_during_repay == 1){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_DKING],self::ERR_CODE_DKING);
        }

        if($deal->deal_status != DealModel::$DEAL_STATUS['repaying']){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_STATUS_FORBID],self::ERR_CODE_STATUS_FORBID);
        }

        $res = $dealRepay->updateOne(array('repay_type'=>$repayType));
        if(!$res){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_SYS],self::ERR_CODE_SYS);
        }
        return true;
    }


    /**
     * 代扣结果查询
     * @param $dealId
     * @param $repayId
     * @param string $approveNumber 放款审批单号
     * @param $orderId 订单id
     * @return int
     * @throws \Exception
     */
    public function getDkStatus($dealId,$repayId,$approveNumber='',$orderId=''){
        $dealRepay = DealRepayModel::instance()->findViaSlave($repayId);
        if(!$dealRepay) {
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NOT_EXISTS],self::ERR_CODE_NOT_EXISTS);
        }
        if($dealRepay->deal_id != $dealId){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_REPAY_ID],self::ERR_DEAL_REPAY_ID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_FIND_NULL],self::ERR_DEAL_FIND_NULL);
        }
        if(!empty($approveNumber) && $deal->approve_number != $approveNumber){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_APPROVE_NUMBER],self::ERR_DEAL_APPROVE_NUMBER);
        }

        if(!empty($orderId)){
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        }else{
            $orderInfo = P2pIdempotentService::getDkOrderInfoByDealIdAndRepayId($dealId,$repayId);
        }
        if(!$orderInfo){
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NORESULT],self::ERR_CODE_NORESULT);
        }

        if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL){
            return self::DK_STATUS_FAIL;
        }elseif($orderInfo['result'] == P2pIdempotentService::RESULT_SUCC){
            return self::DK_STATUS_SUCC;
        }else{
            return self::DK_STATUS_DOING;
        }
    }

    /**
     * 代扣结果查询
     * @param $dealId
     * @param $repayId
     * @param string $approveNumber 放款审批单号
     * @param $orderId 订单id
     * @return array
     *      integer dk_status
     *      integer id
     *      integer order_id
     *      integer loan_user_id
     *      integer borrow_user_id
     *      integer deal_id
     *      integer repay_id
     *      integer prepay_id
     *      integer load_id
     *      string money
     *      integer type
     *      integer status
     *      integer result
     *      string params
     *      string create_time
     *      string update_time
     *      integer user_id
     *
     * @throws \Exception
     */
    public function getDkResult($dealId,$repayId,$approveNumber='',$orderId=''){
        $dealRepay = DealRepayModel::instance()->findViaSlave($repayId);
        if(!$dealRepay) {
            throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NOT_EXISTS],self::ERR_CODE_NOT_EXISTS);
        }
        if($dealRepay->deal_id != $dealId){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_REPAY_ID],self::ERR_DEAL_REPAY_ID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_FIND_NULL],self::ERR_DEAL_FIND_NULL);
        }
        if(!empty($approveNumber) && $deal->approve_number != $approveNumber){
            throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_APPROVE_NUMBER],self::ERR_DEAL_APPROVE_NUMBER);
        }


        if(!empty($orderId)){
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        }else{
            $orderInfo = P2pIdempotentService::getDkOrderInfoByDealIdAndRepayId($dealId,$repayId);
        }
        // 找不到订单原因有两个：1、还款时网贷账户足够还款；2、代扣还款进行中
        if(!$orderInfo){
            if(($dealRepay['repay_type']==DealRepayModel::DEAL_REPAY_TYPE_SELF)&&($dealRepay['status'] > DealRepayModel::STATUS_WAITING)){
                throw new \Exception(self::$errCodeMsg[self::ERR_DEAL_REPAY_SELF],self::ERR_DEAL_REPAY_SELF);
            }else{
                throw new \Exception(self::$errCodeMsg[self::ERR_CODE_NORESULT],self::ERR_CODE_NORESULT);
            }
        }
        $orderInfo['user_id'] = $deal['user_id'];
        if($orderInfo['result'] == P2pIdempotentService::RESULT_FAIL){
            return array_merge($orderInfo, array('dk_status' => self::DK_STATUS_FAIL));
        }elseif($orderInfo['result'] == P2pIdempotentService::RESULT_SUCC){
            return array_merge($orderInfo, array('dk_status' => self::DK_STATUS_SUCC));
        }else{
            return array_merge($orderInfo, array('dk_status' => self::DK_STATUS_DOING));
        }
    }


    /**
     * 因为恶心的店商互联特殊需求，此方法只提供给店商互联使用
     * @param $approveNumber
     */
    public function getDkStatusForStoreBusiness($approveNumber){
        try{
            $deal = DealModel::instance()->findBy("approve_number='{$approveNumber}'");
            if(!$deal){
                throw new \Exception("标的信息不存在");
            }
            $repayInfo = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);

            if(!$repayInfo){
                $lastRepayInfo = DealRepayModel::instance()->findBySql("SELECT * FROM firstp2p_deal_repay WHERE deal_id = ".$deal['id']." ORDER BY id DESC LIMIT 1;");
                if(!$lastRepayInfo){
                    throw new \Exception("标的还款信息不存在");
                }else{
                    $repayInfo = $lastRepayInfo;
                }
            }
            return $this->getDkStatus($deal['id'],$repayInfo['id']);
        }catch (\Exception $ex){
            Logger::error(implode("|",__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage()));
            return 0;
        }
    }

    public function thirdPartyDkRepay($orderId,$userId,$dealId,$repayId,$repayMoney,$expireTime){
        $deal = DealModel::instance()->find($dealId);
        $dealRepayService = new P2pDealRepayService();
        try{
            $dealRepayService->dealDkRepayRequest($orderId,$userId,$dealId,$repayId,$repayMoney,$expireTime);
            $updateRes = $deal->changeRepayStatus(\core\dao\DealModel::DURING_REPAY);
            if(!$updateRes){
                throw new \Exception("标的变更状态失败");
            }
        }catch (\Exception $ex) {
            Logger::error(__CLASS__ . ",". __FUNCTION__ . ", errMsg:". $ex->getMessage());
            throw new \Exception($ex->getMessage());
        }

        return true;
    }
}
