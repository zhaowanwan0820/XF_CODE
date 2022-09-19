<?php
use core\service\repay\DealPrepayService;
use core\service\repay\DealRepayService;
use core\dao\deal\DealModel;
use core\dao\deal\DealExtModel;
use core\dao\jobs\JobsModel;
use core\dao\deal\DealAgencyModel;
use core\dao\repay\DealLoanRepayModel;
use core\dao\repay\DealRepayModel;
use core\dao\repay\DealPrepayModel;
use core\dao\supervision\SupervisionWithdrawAuditModel;
use libs\utils\Finance;
use core\service\deal\DealService;
use NCFGroup\Common\Library\Idworker;
use core\service\account\AccountService;
use core\service\user\UserService;
use core\enum\ServiceAuditEnum;
use core\enum\DealEnum;
use core\enum\DealExtEnum;
use core\enum\UserAccountEnum;
use core\enum\DealRepayEnum;
use libs\utils\Logger;

/**
 * 提前还款操作
 * Class DealPrepayAction
 */
class DealPrepayAction extends CommonAction{

    public static $returnTypes = array('1' => '差错', '2' => '其他');//AB角审核回退类型
    public function prepay_index() {
        $this->assign('role', $this->getRole());

        $this->assign('return_type_list', self::$returnTypes);
        $deal_id = intval($_GET['deal_id']);
        $type = intval($_GET['type']);
        $role = $this->getRole();
        if($deal_id == 0 || $type == 0){
            $this->error("参数错误！");
        }
        try{
            $deal = DealModel::instance()->find($deal_id);
            $ds = new DealPrepayService();
            $ds->setDeal($deal);
            $ds->checkCanPrepay($deal_id);
        }catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
        $borrowUser = UserService::getUserById($deal['user_id']);

        $repayUser = $ds->getAllRepayAccountInfo($deal);

        // 提前还款明细
        $prepay_info = array();
        $prepay = new DealPrepayModel();
        $prepay = $prepay->findBy("deal_id=".$deal_id." and status = 0"); // 查找是否有已经保存的记录
        if($prepay) {
            $prepay_info = $prepay->getRow();
            $this->assign('has_calc',to_date($prepay_info['prepay_time'],'Y-m-d'));
            $this->assign('deal_repay_id', $prepay_info['id']);
        }

        // 到期还款明细
        $dps = new DealRepayService();
        $interest_time =  $dps->getMaxRepayTimeByDeal($deal); // 计息时间(利息开始日期)
        $data = $dps->getExpectRepayStat($deal_id);

        $end_day = to_date($data->last_repay_time,'Y-m-d'); // 到期日期
        $expect_interest_days = ($data->last_repay_time - $deal->repay_start_time)/86400; // 到期利息天数

        $deal['isDtb'] = 0;
        $dealService = new DealService();
        if($dealService->isDealDT($deal['id'])){
            $deal['isDtb'] = 1;
        }
        $this->assign("repay_user",$repayUser);
        $this->assign('interest_time',$interest_time);
        $this->assign('type',$type);
        $this->assign('expect_interest_days',$expect_interest_days);
        $this->assign ( 'deal', $deal );
        $this->assign('data',$data->getRow());
        $this->assign ( 'end_day', $end_day );
        $this->assign ( 'deal', $deal );
        $this->assign ( 'prepay', $prepay_info );
        $this->assign ( 'not_ab', $_GET['not_ab'] );
        $repayUserType = 0;
        $role = $this->getRole();
        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $repayUserType = $redis->get('admin_cache_service_audit_force_repay_user_type_'.$deal_id);
        }

        // 4 还款方各角色的网贷账户余额
        // 借款方
        $borrowerMoney = AccountService::getAccountMoney($borrowUser['id'],UserAccountEnum::ACCOUNT_FINANCE);
        $borrowUser['money'] = $borrowerMoney['money'];
        $dealService = new DealService();
        // 代垫户
        $advanceAgencyUserId = $dealService->getRepayUserAccount($deal_id,DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN);
        $advanceAgencyUserInfo = AccountService::getAccountMoney(intval($advanceAgencyUserId),UserAccountEnum::ACCOUNT_REPLACEPAY);
        $advanceAgencyUserInfo = array_merge(array('id'=>intval($advanceAgencyUserId)),$advanceAgencyUserInfo);

        // 担保户(直接代偿)
        $agencyUserId = $dealService->getRepayUserAccount($deal_id,DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG);
        $agencyUserInfo = AccountService::getAccountMoney(intval($agencyUserId),UserAccountEnum::ACCOUNT_GUARANTEE);
        $agencyUserInfo= array_merge(array('id'=>intval($agencyUserId)),$agencyUserInfo);
        // 代充值户
        $generationRechargeUserId = $dealService->getRepayUserAccount($deal_id,DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI);
        $generationRechargeUserInfo = AccountService::getAccountMoney(intval($generationRechargeUserId),UserAccountEnum::ACCOUNT_RECHARGE);
        $generationRechargeUserInfo = array_merge(array('id'=>intval($generationRechargeUserId)),$generationRechargeUserInfo);

        // 担保户(间接代偿) 去掉间接代偿
        //$indirectAencyUserId = $dealService->getRepayUserAccount($deal_id, DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG);
        //$indirectAencyUserInfo = AccountService::getAccountMoney(intval($indirectAencyUserId),UserAccountEnum::ACCOUNT_GUARANTEE);
        //$indirectAencyUserInfo = array_merge(array('id'=>intval($indirectAencyUserId)),$indirectAencyUserInfo);

        if ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAIDIAN) {
            $payer = $advanceAgencyUserInfo;
        } elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHANG) {
            $payer = $agencyUserInfo;
        }elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_DAICHONGZHI) {
            $payer = $generationRechargeUserInfo;
        }elseif ($repayUserType == DealRepayEnum::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $payer = $agencyUserInfo;
        }else {
            $payer = $borrowUser;
        }

        $userMoney = $payer['money'];
        $this->assign('agency_money',$agencyUserInfo['money']);
        $this->assign('generation_recharge_money',$generationRechargeUserInfo['money']);
        $this->assign('user_money',$userMoney);
        $this->assign('advance_money',$advanceAgencyUserInfo['money']);
        //$this->assign('indirect_agency_money',$indirectAencyUserInfo['money']);
        $querystring = array();
        foreach ($_GET as $k => $v) {
            if (!empty($v)) {
                if ($k == 'deal_id') {
                    continue;
                }
                $querystring[$k] = $v;
            }
        }

        $this->assign('querystring', http_build_query($querystring));
        $this->display('prepay_index_cn');
    }

    /**
     * 计算提前还款各项费用
     * 1：计息结束日期是否大于计息日期
     * 2：利息天数小于提前还款锁定期天数
     */
    public function calc_prepay() {
        $deal_id = intval($_GET['deal_id']);
        $end_day = trim($_GET['day']);
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>'');

        try{
            $deal = DealModel::instance()->find($deal_id);
            $ds = new DealPrepayService();
            $ds->setDeal($deal);
            $ds->checkCanPrepay();
            $res = $ds->prepayCalc($end_day);
            $res['interest_day'] = to_date($res['interest_time'],'Y-m-d');
            $result['data'] = $res;
            $result['isDtb'] = $deal['isDtb'];
        }catch (\Exception $ex) {
            $result['errCode'] = $ex->getCode();
            $result['errMsg'] = $ex->getMessage();
        }
        ajax_return($result);
    }

    /**
     * 保存提前还款
     */
    public function save_prepay() {
        $deal_id = intval($_GET['deal_id']);
        $repay_type = trim($_GET['repay_user_type']);

        $end_day = trim($_GET['day']);
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>true);

        try{
            $deal = DealModel::instance()->find($deal_id);
            $ds = new DealPrepayService();
            $ds->setDeal($deal);
            $ds->checkCanPrepay();
            $calc_res = $ds->prepayCalc($end_day);

            $data = array(
                'deal_id'             => $deal_id,
                'user_id'             => $calc_res['user_id'],
                'prepay_time'         => $calc_res['prepay_time'],
                'remain_days'         => $calc_res['remain_days'],
                'prepay_money'        => $calc_res['prepay_money'],
                'remain_principal'    => $calc_res['remain_principal'],
                'prepay_interest'     => $calc_res['prepay_interest'],
                'prepay_compensation' => $calc_res['prepay_compensation'],
                'loan_fee'            => $calc_res['loan_fee'],
                'consult_fee'         => $calc_res['consult_fee'],
                'guarantee_fee'       => $calc_res['guarantee_fee'],
                'pay_fee'             => $calc_res['pay_fee'],
                'canal_fee'           => $calc_res['canal_fee'],
                'repay_type'          => $repay_type,
                'pay_fee_remain'      => $calc_res['pay_fee_remain'],
                'deal_type'           => $calc_res['deal_type'],
                'management_fee'      => $calc_res['management_fee']
            );
            $ds->prepaySave($data);
        }catch (\Exception $ex) {
            $result = array('errCode'=>$ex->getCode(),'errMsg'=>$ex->getMessage(),'data'=>false);
            save_log('提前还款保存失败 deal_id:'.$deal_id,C('FAILED'), '', $data, C('SAVE_LOG_FILE'));
        }
        ajax_return($result);
    }

    /**
     * 开始提前还款
     * 逻辑判断:
     *  1: deal.loantype 提前还款暂不支持按月等额还款(loantype=2)、按季等额还款(loantype=1)方式
     *  2: deal.status =4 标的为还款中才可以发起提前还款
     *  3：deal.is_during_repay=0 (标的不能是正在放款中)
     *  3：deal_repay 中有需要还款的记录 deal_repay.deal_id = xx and status = 0
     */
    public function do_prepay($deal_id) {
        $result = array('errCode'=>1000,'errMsg'=>'success','data'=>true);
        $deal_id = intval($_GET['deal_id']);
        $deal_repay_id = intval($_GET['deal_repay_id']);
        $saveLogFile = C('SAVE_LOG_FILE');
        $authKey =conf ("AUTH_KEY");
        $admInfo = \es_session::get(md5($authKey));
        $retry = 0; // 无重试保证，不能重试

        try{
            if($deal_id == 0) {
                throw new \Exception("deal_id 参数错误");
            }

            $deal = DealModel::instance()->find($deal_id);
            $prepay = new DealPrepayModel();
            $prepay = $prepay->findBy("deal_id=".$deal_id." and status = 0");
            if(!$prepay) {
                throw new \Exception("当前数据尚未保存，请先操作保存");
            }
            $ds = new DealService();
            $prepayUserId = $ds->getRepayUserAccount($deal_id,$prepay->repay_type);
            if(!$prepayUserId) {
                throw new \Exception("还款用户ID获取失败");
            }
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditEnum::SERVICE_TYPE_PREPAY, 'service_id' => intval($deal_repay_id)))->find();

            try{
                $GLOBALS['db']->startTrans();
                // 将标的置为还款中
                $res = $deal->changeRepayStatus(DealEnum::DEAL_DURING_REPAY);
                if ($res == false) {
                    throw new \Exception("chage repay status error");
                }
                // 自动审核提前还款
                $prepay->status = 1;
                $prepay->save();
                // 还款总额 = 应还本金+应还利息+手续费+咨询费+担保费+支付服务费。
                // 若多投宝，还需加上管理服务费
                $prepay_money = $prepay['prepay_money'];
                if (!empty($audit) && $audit['status'] == 1) {
                    $auditRes= M("ServiceAudit")->where("id=" . $audit['id'])->save(array('status' => ServiceAuditEnum::AUDIT_SUCC, 'audit_uid' => $admInfo['adm_id']));
                    if (!$auditRes) {
                        throw new \Exception("更新审核状态失败");
                    }
                }
                // 启动jobs进行还款操作

                $param = array('id' => $prepay->id, 'status' => $prepay->status, 'success' => C('SUCCESS'), 'saveLogFile' => $saveLogFile, 'admInfo' => $admInfo,'prepayUserId'=>$prepayUserId);
                    // p2p 还款逻辑
                $orderId = Idworker::instance()->getId();
                $function = '\core\service\repay\P2pDealRepayService::dealPrepayRequest';
                $param = array('orderId'=>$orderId,'prepayId'=>$prepay->id,'params'=>$param);

                $job_model = new JobsModel();
                $job_model->priority = 80;
                $job_model->addJob($function, array('param' => $param), false, $retry);

                save_log('提前还款'.' deal_id:' . $deal_id, C('SUCCESS'), '', '', $saveLogFile);
                $GLOBALS['db']->commit();
            }catch (\Exception $e){
                $GLOBALS['db']->rollback();
                throw $e;
            }
            try {
                // 先计息后放款的,更新网贷账户放款提现审核状态
                $dealExt = DealExtModel::instance()->getDealExtByDealId($deal_id);
                if ($dealExt['loan_type'] == DealExtEnum::LOAN_TYPE_LATER_LOAN) {
                    $loginfo = __CLASS__ . ' ' . __FUNCTION__ . ' '.$deal['user_id'] . ' ' . $deal_id.' ';
                    $supervisionWithdrawAuditModel = new SupervisionWithdrawAuditModel();
                    $ret = $supervisionWithdrawAuditModel->repayWithdrawAudit($deal['user_id'], $deal_id);
                    if ($ret['respCode'] != 0) {
                        Logger::error( $loginfo.$ret['respMsg']);
                    }
                    Logger::info($loginfo . $deal_id);
                }
            }catch (\Exception $e){
                Logger::error($loginfo .$e->getMessage());
            }
        }catch (\Exception $ex) {
            save_log('提前还款'.' deal_id:' . $deal_id, C('FAILED'), '', '', $saveLogFile);
            $result = array('errCode'=>$ex->getCode(),'errMsg'=>$ex->getMessage(),'data'=>false);
        }
        ajax_return($result);
    }

    function get_deal_repay_id()
    {

        $deal_id = intval($_REQUEST['deal_id']);
        $sql = "select `id` from ".DB_PREFIX."deal_prepay where deal_id= $deal_id and status =0";
        $res = $GLOBALS['db']->getRow($sql);
        if (empty($res)) {
            $result = array('errCode'=>-1, 'errMsg' => '获取ID失败','data' => false);
        } else {
            $result = array('errCode'=>0, 'errMsg' => '','data' => $res['id']);
        }
        ajax_return($result);
    }
}
