<?php
/**
 * 标的代扣
 */

namespace core\service\deal;

use core\enum\DealDkEnum;
use core\enum\DealEnum;
use core\enum\DealRepayEnum;
use core\enum\P2pIdempotentEnum;
use libs\utils\Logger;
use core\dao\deal\DealModel;
use core\dao\repay\DealRepayModel;
use core\dao\supervision\SupervisionIdempotentModel;
use core\dao\jobs\JobsModel;
use core\service\deal\DealService;
use core\service\deal\P2pDepositoryService;
use core\service\BaseService;

class DealDkService extends BaseService {
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
        $latestTime = date('Y-m-d') . " ". DealDkEnum::UPDATE_REPAY_TYPE_LATEST_TIME;
        $latestTime = strtotime($latestTime);

        if(!in_array($repayType,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN))){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_PARAMS],DealDkEnum::ERR_CODE_PARAMS);
        }

        if($time > $latestTime){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_TIME_BEYOND],DealDkEnum::ERR_CODE_TIME_BEYOND);
        }
        $dealRepay = DealRepayModel::instance()->findViaSlave($repayId);
        if(!$dealRepay || $dealRepay->deal_id != $dealId){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NOT_EXISTS],DealDkEnum::ERR_CODE_NOT_EXISTS);
        }

        // “代充值还款”、“代垫还款”不可调整为“代扣还款”或空
        if(in_array($dealRepay->repay_type,array(DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN)) && $repayType == DealRepayEnum::DEAL_REPAY_TYPE_DAIKOU){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_STATUS_FORBID],DealDkEnum::ERR_CODE_STATUS_FORBID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal || (!empty($approveNumber) && $deal->approve_number != $approveNumber)){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NOT_EXISTS],DealDkEnum::ERR_CODE_NOT_EXISTS);
        }

        if($deal->report_status != DealEnum::DEAL_REPORT_STATUS_YES){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NO_REPORT],DealDkEnum::ERR_CODE_NO_REPORT);
        }

        if($deal->is_during_repay == 1){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_DKING],DealDkEnum::ERR_CODE_DKING);
        }

        if($deal->deal_status != DealEnum::$DEAL_STATUS['repaying']){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_STATUS_FORBID],DealDkEnum::ERR_CODE_STATUS_FORBID);
        }

        $res = $dealRepay->updateOne(array('repay_type'=>$repayType));
        if(!$res){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_SYS],DealDkEnum::ERR_CODE_SYS);
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
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NOT_EXISTS],DealDkEnum::ERR_CODE_NOT_EXISTS);
        }
        if($dealRepay->deal_id != $dealId){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_REPAY_ID],DealDkEnum::ERR_DEAL_REPAY_ID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_FIND_NULL],DealDkEnum::ERR_DEAL_FIND_NULL);
        }
        if(!empty($approveNumber) && $deal->approve_number != $approveNumber){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_APPROVE_NUMBER],DealDkEnum::ERR_DEAL_APPROVE_NUMBER);
        }

        if(!empty($orderId)){
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        }else{
            $orderInfo = P2pIdempotentService::getDkOrderInfoByDealIdAndRepayId($dealId,$repayId);
        }
        if(!$orderInfo){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NORESULT],DealDkEnum::ERR_CODE_NORESULT);
        }

        if($orderInfo['result'] == P2pIdempotentEnum::RESULT_FAIL){
            return DealDkEnum::DK_STATUS_FAIL;
        }elseif($orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC){
            return DealDkEnum::DK_STATUS_SUCC;
        }else{
            return DealDkEnum::DK_STATUS_DOING;
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
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NOT_EXISTS],DealDkEnum::ERR_CODE_NOT_EXISTS);
        }
        if($dealRepay->deal_id != $dealId){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_REPAY_ID],DealDkEnum::ERR_DEAL_REPAY_ID);
        }

        $deal = DealModel::instance()->findViaSlave($dealId);
        if(!$deal){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_FIND_NULL],DealDkEnum::ERR_DEAL_FIND_NULL);
        }
        if(!empty($approveNumber) && $deal->approve_number != $approveNumber){
            throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_APPROVE_NUMBER],DealDkEnum::ERR_DEAL_APPROVE_NUMBER);
        }


        if(!empty($orderId)){
            $orderInfo = P2pIdempotentService::getInfoByOrderId($orderId);
        }else{
            $orderInfo = P2pIdempotentService::getDkOrderInfoByDealIdAndRepayId($dealId,$repayId);
        }
        // 找不到订单原因有两个：1、还款时网贷账户足够还款；2、代扣还款进行中
        if(!$orderInfo){
            if(($dealRepay['repay_type']==DealRepayEnum::DEAL_REPAY_TYPE_SELF)&&($dealRepay['status'] > DealRepayEnum::STATUS_WAITING)){
                throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_DEAL_REPAY_SELF],DealDkEnum::ERR_DEAL_REPAY_SELF);
            }else{
                throw new \Exception(DealDkEnum::$errCodeMsg[DealDkEnum::ERR_CODE_NORESULT],DealDkEnum::ERR_CODE_NORESULT);
            }
        }
        $orderInfo['user_id'] = $deal['user_id'];
        if($orderInfo['result'] == P2pIdempotentEnum::RESULT_FAIL){
            return array_merge($orderInfo, array('dk_status' => DealDkEnum::DK_STATUS_FAIL));
        }elseif($orderInfo['result'] == P2pIdempotentEnum::RESULT_SUCC){
            return array_merge($orderInfo, array('dk_status' => DealDkEnum::DK_STATUS_SUCC));
        }else{
            return array_merge($orderInfo, array('dk_status' => DealDkEnum::DK_STATUS_DOING));
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
            Logger::error(implode("|",array(__CLASS__,__FUNCTION__,__LINE__,$ex->getMessage())));
            return 0;
        }
    }

    public function thirdPartyDkRepay($orderId,$userId,$dealId,$repayId,$repayMoney,$expireTime){
        $deal = DealModel::instance()->find($dealId);
        $dealRepayService = new P2pDealRepayService();
        try{
            $dealRepayService->dealDkRepayRequest($orderId,$userId,$dealId,$repayId,$repayMoney,$expireTime);
            $updateRes = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
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
