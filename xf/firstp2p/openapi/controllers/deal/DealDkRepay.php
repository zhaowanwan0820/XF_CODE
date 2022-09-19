<?php

namespace openapi\controllers\deal;

use libs\web\Form;
use openapi\conf\Error;
use openapi\controllers\BaseAction;
use libs\utils\Logger;

use core\service\DealService;
use core\service\SupervisionAccountService;
use core\service\DealDkService;
use core\service\P2pDealRepayService;
use core\service\P2pIdempotentService;

use core\dao\DealModel;
use core\dao\JobsModel;
use core\dao\DealRepayModel;
use core\dao\ThirdpartyDkModel;
use core\service\ThirdpartyDkService;

use openapi\conf\adddealconf\common\CommonConf;

use NCFGroup\Common\Library\Idworker;



/**
 * 代扣还款
 * @author wangjiantong
 * @package openapi\controllers\deal
 */
class DealDkRepay extends BaseAction {

    private $allow_status = array(4,5); //允许调用状态

    public function init(){
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'deal_id' => ['filter' => 'required',"message" => "deal_id is error"],
            'repay_id' => ['filter' => 'required', "message" => "repay_id is error"],
            'dk_time' => ['filter' => 'required', "message" => "dk_time is error"],
            'outer_order_id' => ['filter' => 'required',"message" => "outer_order_id is error"],
            'repay_money' => ['filter' => 'required', "message" => "repay_money is error"],
            'notify_url' => ['filter' => 'string', 'option' => array('optional' => true)],
            'user_name' => ['filter' => 'required', "message" => "user_name is required"], //用户姓名
            'id_no' => ['filter' => 'required', "message" => "id_no is required"],
            'bank_no' => ['filter' => 'required', "message" => "bank_no is required"],
            'mobile' => ['filter' => 'required', "message" => "mobile is required"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if(!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke(){
        $data = $this->form->data;
        $dealId = intval($data['deal_id']);
        $repayId = intval($data['repay_id']);
        $dkTime = trim($data['dk_time']);
        $outerOrderId = trim($data['outer_order_id']);
        $user_name = trim($data['user_name']);
        $id_no = trim($data['id_no']);
        $bank_no = trim($data['bank_no']);
        $mobile = preg_match("/^1[3456789]\d{9}$/", trim($data['mobile'])) ? trim($data['mobile']) : '';
        $repayMoney = 0;

        $dkStatus = 0;
        $businessStatus = 0;
        $repayType = '';
/* 不用再次判断资产端的权限
        $platform = CommonConf::getAllowPlateormClientId($data['client_id']);
        if (empty($platform)) {
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，请检查参数');
            return false;
        }
 */
        if(isset($data['repay_money']) && $data['repay_money'] > 0){
            $repayMoney = $data['repay_money'];
        }
        if(empty($user_name)){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，user_name参数错误');
            return false;
        }
        if(empty($id_no)){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，id_no参数错误');
            return false;
        }
        if(empty($bank_no)){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，bank_no参数错误');
            return false;
        }
        if(empty($mobile)){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，mobile参数错误');
            return false;
        }


        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款",json_encode($data))));


        try{
            //验证标的信息
            $deal = DealModel::instance()->find($dealId);

            $dealRepay = DealRepayModel::instance()->find($repayId);
            if(empty($deal)){
                $this->setErr("ERR_DEAL_FIND_NULL");
                return false;
            }

            if(empty($dealRepay)){
                $this->setErr("ERR_DEAL_REPAY_INFO");
                return false;
            }

            // 只有还款中和已还清的标的可以进行代扣还款
            if(!in_array($deal['deal_status'],$this->allow_status)){
                $this->setErr("ERR_REPAY_DEAL_STATUS");
                return false;
            }

            //检查还款id和标的是否正确
            if($dealId != $dealRepay['deal_id']){
                $this->setErr("ERR_DEAL_REPAY_ID");
                return false;
            }

            //还款金额错误
            if($repayMoney != $dealRepay['repay_money']){
                $this->setErr("ERR_DK_MONEY");
                return false;
            }

            //验证本期还款是否已还清
            if($dealRepay['status'] > 0){
                $this->setErr("ERR_REPAYED");
                return false;
            }

            //验证是否为最近一期还款
            $dealRepayModel = new DealRepayModel();
            $nextRepay = $dealRepayModel->getNextRepayByDealId($dealId);
            $prevRepay = $dealRepayModel->getPrevRepayByDealId($dealId);
            if($repayId != $nextRepay['id']){
                $this->setErr("ERR_DEAL_REPAY_ID");
                return false;
            }

            //是否是智多鑫标的
            $dealService = new DealService();
            $isDt = $dealService->isDealDT($dealId);
            //验证还款时间
            if(date('Y-m-d',time()) == to_date($dealRepay['repay_time'],'Y-m-d')){
                //到期日 智多鑫标的当日配置值到17:30允许还款;
                if($isDt && ((date('His',time()) < intval(app_conf('DEAL_DK_REPAY_BEGIN_TIME'))) || (date('His',time()) >= 173000))){
                    $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
                    return false;
                }
                //到期日当日0:00到17:30允许还款;
                if(date('His',time()) >= 173000){
                    $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
                    return false;
                }
            }else if(date('Ymd',time()) <= to_date($dealRepay['repay_time'],'Ymd')){
                //到期日之前和上期还款日之间的日期
                if(!empty($prevRepay)){
                    if(date('Ymd',time()) <= to_date($prevRepay['repay_time'],'Ymd')){
                        $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
                        return false;
                    }
                }
                // 调用时间智多鑫标的当日配置值到23:00允许还款;
                if($isDt && ((date('His',time()) < intval(app_conf('DEAL_DK_REPAY_BEGIN_TIME'))) || (date('His',time()) >= 230000))){
                    $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
                    return false;
                }

                //调用时间 当天0:00到23:00之间的日期
                if(date('His',time()) >= 230000){
                    $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
                    return false;
                }
            }else{
                $this->setErr("ERR_DEAL_REPAY_ID");
                return false;
            }

            //验证用户存管余额
            $superAccountService = new SupervisionAccountService();
            $accountRes = $superAccountService->balanceSearch($deal['user_id']);
            $bankMoney = bcdiv($accountRes['data']['availableBalance'],100,2);

/*
            //没有使用存管账户余额还款的逻辑了
            //存管余额大于还款金额时,直接进行还款
            if($dealRepay['repay_money'] <= $bankMoney){
                //网贷账户直接还款
                if($deal['is_during_repay'] != 1){
                    $repayRes =  $this->rpc->local('P2pDealRepayService\doRepay', array($deal,$repayId,DealRepayModel::DEAL_REPAY_TYPE_SELF,P2pDealRepayService::REPAY_TYPE_NORMAL,date('Y-m-d')));
                    $dkStatus = DealDkService::DK_STATUS_NONE;
                    $businessStatus = DealDkService::BUSINESS_STATUS_REPAYING;
                    $repayType = DealRepayModel::DEAL_REPAY_TYPE_SELF;
                }else{
                    $this->setErr('ERR_REPAY_DEAL_REPAYING');
                    return false;
                }

            }else{
 */
                //查询订单
                $dkService = new DealDkService();
                //重发代扣标记
                $reDk = false;
                //获取代扣状态

                $outOrderInfo =  $this->rpc->local('ThirdpartyDkService\getThirdPartyByOutOrderId', array($outerOrderId,$data['client_id']));

                if(empty($outOrderInfo)){
                    //查询还款代扣状态
                    try {
                        $dkStatus = $dkService->getDkStatus($dealId, $repayId);
                        if($dkStatus != P2pIdempotentService::RESULT_FAIL){
                            $this->setErr("ERR_DK_REQUEST");
                            return false;
                        }
                    } catch (\Exception $e) {
                        if ($e->getCode() == $dkService::ERR_CODE_NORESULT){
                            //重新发起代扣标记
                            $reDk = true;
                        }
                    }

                    if(($dkStatus == P2pIdempotentService::RESULT_FAIL)||($reDk == true)){
                        $processOrder = ThirdpartyDkService::getThirdPartyOrderByStatus($dealId,$repayId,"0,1");
                        if(empty($processOrder)){
                            try{
                                $GLOBALS['db']->startTrans();
                                $thirdParams = array(
                                    'realName' => $user_name,
                                    'certNo' => $id_no,
                                    'bankCardNo' => $bank_no,
                                    'mobile' => $mobile,
                                ); //订单参数
                                $orderId = Idworker::instance()->getId();
                                $thirdpartyDkModel = new ThirdpartyDkModel();
                                $thirdpartyDkModel->outer_order_id = $outerOrderId;
                                $thirdpartyDkModel->order_id = $orderId;
                                $thirdpartyDkModel->deal_id = $dealId;
                                $thirdpartyDkModel->repay_id = $repayId;
                                $thirdpartyDkModel->client_id = $data['client_id'];
                                $thirdpartyDkModel->status = ThirdpartyDkModel::REQUEST_STATUS_WATTING;
                                $thirdpartyDkModel->notify_url = $data['notify_url'];
                                $thirdpartyDkModel->create_time = time();
                                $thirdpartyDkModel->update_time = time();;
                                $thirdpartyDkModel->notice_url = "";
                                $thirdpartyDkModel->params = addslashes(json_encode($thirdParams));

                                if ($thirdpartyDkModel->insert() === false) {
                                    throw new \Exception(sprintf('insert fail: %s'));
                                }

                                //插入代扣jobs
                                $jobsModel = new JobsModel();
                                $expireTime = date('YmdHis',time()+3600);
                                $jobParams = array_merge($thirdParams, array(
                                    'orderId' => $orderId,
                                    'userId'=> $deal['user_id'],
                                    'dealId' => $dealId,
                                    'repayId' => $repayId,
                                    'money'=>$repayMoney ,
                                    'expireTime'=>$expireTime,
                                )); //jobs参数
                                $param = array('params' => $jobParams);
                                $function = '\core\service\P2pDealRepayService::dealMulticardDkRepayRequest';
                                $jobsModel->priority = JobsModel::PRIORITY_DEAL_REPAY;
                                $jobsRes = $jobsModel->addJob($function, $param);
                                if ($jobsRes === false) {
                                    throw new \Exception("加入jobs失败");
                                }

                                $GLOBALS['db']->commit();

                                $dkStatus = DealDkService::DK_STATUS_DOING;
                                $businessStatus = DealDkService::BUSINESS_STATUS_NONE;
                                $repayType = $dealRepay['repay_type'];

                            }catch (\Exception $ex) {
                                $GLOBALS['db']->rollback();
                                $dkStatus = DealDkService::DK_STATUS_NONE;
                                $businessStatus = DealDkService::BUSINESS_STATUS_NONE;
                                $repayType = $dealRepay['repay_type'];
                            }

                        }else{
                            $dkStatus = DealDkService::DK_STATUS_DOING;
                            $businessStatus = $deal['is_during_repay'] == 1?DealDkService::BUSINESS_STATUS_REPAYING:DealDkService::BUSINESS_STATUS_NONE;
                            $repayType = $dealRepay['repay_type'];
                        }
                    }else{
                        $businessStatus = $deal['is_during_repay'] == 1?DealDkService::BUSINESS_STATUS_REPAYING:DealDkService::BUSINESS_STATUS_NONE;
                        $repayType = $dealRepay['repay_type'];
                    }
                }else {
                    //外部订单不属于此笔还款
                    if(($outOrderInfo['deal_id'] != $dealId)||($outOrderInfo['repay_id']!==$repayId)){
                        $this->setErr("ERR_OUTER_ID_USED");
                        return false;
                    }

                    try {
                        $dkStatus = $dkService->getDkStatus($dealId, $repayId,'', $outOrderInfo['order_id']);
                    } catch (\Exception $e) {
                        if ($e->getCode() == $dkService::ERR_CODE_NORESULT) {
                            $dkStatus = DealDkService::DK_STATUS_DOING;
                        } else {
                            $this->setErr("ERR_DK_SEARCH");
                            return false;
                        }
                    }
                    if($dkStatus == DealDkService::BUSINESS_STATUS_SUCC){
                        $this->setErr("ERR_DK_SUCCESSED");
                        return false;
                    }else{
                        if($dealRepay['status'] > 0){
                            $businessStatus = DealDkService::BUSINESS_STATUS_SUCC;
                            $repayType = $dealRepay['repay_type'];
                        }elseif($deal['is_during_repay'] == 1){
                            $businessStatus = DealDkService::BUSINESS_STATUS_REPAYING;
                            $repayType = $dealRepay['repay_type'];
                        }else{
                            $businessStatus = DealDkService::BUSINESS_STATUS_NONE;
                            $repayType = $dealRepay['repay_type'];
                        }
                    }
                }
//            }
        }catch (\Exception $ex){
            $this->errorCode = $ex->getCode();
            $this->errorMsg = $ex->getMessage();
            Logger::error(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__,"代扣还款失败 errMsg:".$ex->getMessage(),"deal_id:{$dealId},repay_id:{$repayId},outerOrderId:{$outerOrderId}")));
            return false;
        }

        $res['dk_order'] = $outerOrderId;
        $res['dk_status'] = $dkStatus;
        $res['business_status'] = $businessStatus;
        $res['repay_type'] = $repayType;
        $this->json_data = $res;
        return true;
    }
}
