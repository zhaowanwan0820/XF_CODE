<?php
namespace openapi\controllers\deal;

/**
 * 店商互联还款
 * @author wangjiantong
 */
use libs\web\Form;
use openapi\controllers\BaseAction;
use libs\utils\Logger;
use libs\utils\Aes;
use libs\utils\Block;

use core\service\DealLoanTypeService;
use core\service\DealService;
use core\service\DealDkService;
use core\service\UserService;
use core\service\P2pDealRepayService;
use core\service\SupervisionAccountService;
use core\dao\UserModel;
use core\dao\DealRepayModel;



use core\dao\DealLoanTypeModel;

use NCFGroup\Common\Library\Idworker;




class StoreBusinessRepay extends BaseAction
{
    private $repay_type = array(1 => '代扣组合', 2 => '代充值');
    private $allow_repay_status = 4; //还款中状态

    public function init()
    {
        parent::init();
        $this->form = new Form();

        $this->form->rules = array(
            "approve_number" => array(
                "filter" => "required"
            ),
            "repay_type" => array(
                "filter" => "required"
            ),
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;

        $checkCounts = Block::check('DSD_REPAY_DOWN_MINUTE','dsd_repay_down_minute');
        if ($checkCounts === false) {
            $this->setErr('ERR_MANUAL_REASON','请不要频繁发送请求');
            return false;
        }

        //验证还款方式
        if(!array_key_exists(intval($data['repay_type']),$this->repay_type)){
            $this->setErr("ERR_DEAL_REPAY_TYPE");
            return false;
        }

        //验证标的
        $deal = $this->rpc->local('DealService\getDealByApproveNumber', array($data['approve_number']));
        $userService = new UserService();
        $dealService = new DealService();

        if(empty($deal)){
            $this->setErr("ERR_DEAL_FIND_NULL");
            return false;
        }

        //验证标的是否为店商互联
        $typeTag = DealLoanTypeModel::instance()->getLoanTagByTypeId($deal['type_id']);
        if($typeTag !== DealLoanTypeModel::TYPE_DSD){
            $this->setErr("ERR_DEAL_TYPE_ID");
            return false;
        }

        // 还款中才可发起还款请求
        if($deal['deal_status'] != $this->allow_repay_status){
            $this->setErr("ERR_REPAY_DEAL_STATUS");
            return false;
        }

        // 检查标的是否正在还款
        if($deal['is_during_repay'] == 1){
            $this->setErr("ERR_REPAY_DEAL_REPAYING");
            return false;
        }

        //账户余额验证（代扣验证利息账户,代充值验证代充值账户)
        $repayInfo = DealRepayModel::instance()->getNextRepayByDealId($deal['id']);
        if(!$repayInfo){
            $this->setErr("ERR_DEAL_REPAY_INFO");
            return false;
        }

        try{
            $repayTrial = $dealService->dealRepayTrial($deal,$repayInfo->id,date('Y-m-d',time()));
        }catch (\Exception $ex){
            $this->setErr("ERR_DEAL_REPAY_CALC");
            return false;
        }
        // 如果标的属于“到期还款”，并且调用时间大于14：00，则返回错误“还款失败，该标的不在可调用的时间范围内”
        if(($repayTrial['type'] == 2) && $data['repay_type'] == 2 &&  (intval(date("H", time())) >= 14)){
            $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
            return false;
        }

        // 代扣组合还款的提前还款需要在12点之前
        if(($repayTrial['type'] == 2) && $data['repay_type'] == 1 &&  (intval(date("H", time())) >= 12)){
            $this->setErr("ERR_DEAL_REPAY_NOT_IN_TIME");
            return false;
        }

        // 检查是否为放款日
        if($repayTrial['repay_start_time'] == date('Y-m-d')){
            $this->setErr("ERR_DEAL_REPAY_GRANT_TIME");
            return false;
        }

        if($data['repay_type'] == 1){
            //验证利息账户余额
            $interestAccountId = app_conf("DSD_INTEREST_ACCOUNT");

            if(!$interestAccountId){
                $this->setErr("ERR_DSD_INTEREST_ACCOUNT");
                return false;
            }

            $superAccountService = new SupervisionAccountService();
            $res = $superAccountService->balanceSearch($interestAccountId);
            $bankMoney = bcdiv($res['data']['availableBalance'],100,2);

            $repayInterest = bcadd($repayTrial['total_repay'],-$repayTrial['repay_principal'],2);
            if($repayInterest > $bankMoney){
                $this->setErr("ERR_INTEREST_ACCOUNT_BALANCE");
                return false;
            }

            //获取标的代扣状态
            $dkService = new DealDkService();
            $dkStatus = $dkService->getDkStatusForStoreBusiness($data['approve_number']);

            if($dkStatus == DealDkService::DK_STATUS_DOING){
                //代扣中
                return true;
            }elseif($dkStatus == DealDkService::DK_STATUS_SUCC){
                //代扣已成功(说明在利息划转或者还款过程中失败了--此时要跳过代扣直接划转利息)
                try{
                    $repayService = new P2pDealRepayService();
                    $repayRes = $repayService->repayBaseOnAccountType($deal,$repayInfo->id,DealRepayModel::DEAL_REPAY_TYPE_DAIKOU);
                    if(!$repayRes){
                        throw new \Exception("代扣成功-还款失败");
                    }
                }catch (\Exception $ex){
                    $this->setErr("ERR_DK_REPAY_REPAYERR",$ex->getMessage());
                    return false;
                }
            }elseif(($dkStatus == DealDkService::DK_STATUS_FAIL)||($dkStatus == DealDkService::DK_STATUS_NONE)){
                //代扣失败或代扣状态不存在,重新发起代扣组合还款
                $orderId = Idworker::instance()->getId();
                $dealRepayService = new P2pDealRepayService();
                try{
                    $expireTime = date('YmdHis',time()+3600);
                    $dealRepayService->dealDkRepayRequest($orderId,$deal['user_id'],$deal['id'],$repayInfo->id,$repayTrial['repay_principal'],$expireTime);
                    $updateRes = $deal->changeRepayStatus(\core\dao\DealModel::DURING_REPAY);
                    if(!$updateRes){
                        throw new \Exception("标的状态更改失败");
                    }
                }catch (\Exception $ex) {
                    Logger::error(__CLASS__ . ",". __FUNCTION__ . ",params:".$data.", errMsg:". $ex->getMessage());
                    $this->setErr("ERR_DK_REQUEST");
                    return false;
                }
            }else{
                //未知状态,查询代扣状态失败
                $this->setErr("ERR_SEARCH_DK_STATUS");
                return false;
            }
        }else if($data['repay_type'] == 2){
            //验证代充值账户余额
            $generationRechargeAccountId = app_conf("DSD_GR_ACCOUNT");
            if(!$generationRechargeAccountId){
                $this->setErr("ERR_DSD_GR_ACCOUNT");
                return false;
            }
            $superAccountService = new SupervisionAccountService();
            $res = $superAccountService->balanceSearch($generationRechargeAccountId);
            $bankMoney = bcdiv($res['data']['availableBalance'],100,2);

            if($repayTrial['total_repay'] > $bankMoney){
                $this->setErr("ERR_GR_ACCOUNT_BALANCE");
                return false;
            }

            try{
                $repayService = new P2pDealRepayService();
                $repayService->repayBaseOnAccountType($deal,$repayInfo->id,DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI);
            }catch (\Exception $ex){
                $this->setErr("ERR_DEAL_REPAY_GR",$ex->getMessage());
                return false;
            }
        }

        //执行还款
        $this->errorCode = 0;
        $this->errorMsg = '';
        return true;
    }
}