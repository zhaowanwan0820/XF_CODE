<?php
FP::import("app.deal");
use core\service\DealPrepayService;
use core\service\DealRepayService;
use core\service\CouponDealService;
use core\dao\DealModel;
use core\dao\DealExtModel;
use core\dao\JobsModel;
use core\dao\UserModel;
use core\dao\DealAgencyModel;
use core\dao\DealRepayModel;
use core\dao\FinanceQueueModel;
use app\models\dao\DealLoanRepay;
use app\models\dao\Deal;
use core\dao\DealLoanTypeModel;
use libs\utils\Finance;
use core\service\DealService;
use NCFGroup\Common\Library\Idworker;

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
            $ds = new DealPrepayService();
            $dealInfo = $ds->prepayCheck($deal_id);
            $deal = $dealInfo['deal_base_info'];
            $deal_ext = $dealInfo['deal_ext_info'];
        }catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }

        $deal = DealModel::instance()->find($deal_id);
        $isP2pPath = (new \core\service\DealService())->isP2pPath($deal);

        // 还款方 select 默认选中项
        $selected_repay_user = getRepayUserSelectStatus($deal['id']);

        $borrowUser = UserModel::instance()->find($deal['user_id']);
        $repayUser[] = array('userName' => $borrowUser['real_name'],'type'=> 0, 'is_selected' => $selected_repay_user['borrower']);

        //代垫机构
        if($deal['advance_agency_id'] > 0){
            $advance_agency = DealAgencyModel::instance()->find($deal['advance_agency_id']);
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> 1, 'is_selected' => $selected_repay_user['advance_agency']);
        }

        //代偿机构 & 间接代偿机构
        if($deal['agency_id'] > 0){//担保机构代偿
            $advance_agency = DealAgencyModel::instance()->find($deal['agency_id']);
            //代偿机构
            $repayUser[] = array('userName' => $advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name'],'type'=> 2, 'is_selected' => $selected_repay_user['agency']);
            //间接代偿机构
            $repayUser[] = array('userName' => '间接代偿' . ($advance_agency['short_name'] == '' ? $advance_agency['name']:$advance_agency['short_name']),'type'=> DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG, 'is_selected' => $selected_repay_user['indirect_agency']);

        }

        //代充值机构
        if($deal['generation_recharge_id'] > 0){
            $generation_recharge = \core\dao\DealAgencyModel::instance()->find($deal['generation_recharge_id']);
            $repayUser[] = array('userName' => $generation_recharge['short_name'] == '' ? $generation_recharge['name']:$generation_recharge['short_name'],'type'=> 3, 'is_selected' => $selected_repay_user['generation_recharge']);
        }

        //$deal_dao = new Deal();
        //$prepay_compensation = $deal_dao->floorfix($deal['borrow_amount'] * $deal['prepay_rate'] / 100); // 借款金额x提前还款违约金系数

        // 提前还款明细
        $prepay_info = array();
        $prepay = new \core\dao\DealPrepayModel();
        $prepay = $prepay->findBy("deal_id=".$deal_id." and status = 0"); // 查找是否有已经保存的记录
        if($prepay) {
            $prepay_info = $prepay->getRow();
            $this->assign('has_calc',to_date($prepay_info['prepay_time'],'Y-m-d'));
            $this->assign('deal_repay_id', $prepay_info['id']);
        }

        // 到期还款明细
        $dps = new DealRepayService();
        $interest_time =  $dps->getMaxRepayTimeByDealId($deal); // 计息时间(利息开始日期)
        $data = $dps->getExpectRepayStat($deal_id);

        $end_day = to_date($data->last_repay_time,'Y-m-d'); // 到期日期
        $expect_interest_days = ($data->last_repay_time - $deal->repay_start_time)/86400; // 到期利息天数

        $deal['isDtb'] = 0;
        $dealService = new \core\service\DealService();
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
        //$this->assign ( 'user_money', $borrowUser['money'] );
        $repayUserType = 0;
        $role = $this->getRole();
        if ($role == 'b') {
            $redis = \SiteApp::init()->dataCache->getRedisInstance();
            $repayUserType = $redis->get('admin_cache_service_audit_force_repay_user_type_'.$deal_id);
        }

        if ($repayUserType == '1') {
            $repayUserId = $dealService->getRepayUserAccount($deal_id,1);
        } elseif ($repayUserType == '2') {
            $repayUserId = $dealService->getRepayUserAccount($deal_id,2);
        }elseif ($repayUserType == '3') {
            $repayUserId = $dealService->getRepayUserAccount($deal_id,3);
        }elseif ($repayUserType == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG) {
            $repayUserId = $dealService->getRepayUserAccount($deal_id,DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG);
        }else {
            $repayUserId = $borrowUser['id'];
        }
        $repayUserModel = UserModel::instance()->find(intval($repayUserId));

        if($isP2pPath){
            $userMoneyInfo = (new \core\service\UserService())->getMoneyInfo($repayUserModel);
            $userMoney = $userMoneyInfo['bank'];
        }else{
            $userMoney = $repayUserModel['money'];
        }
        $this->assign('user_money',$userMoney);




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
        $template = $this->is_cn ? 'prepay_index_cn' : 'prepay_index';
        $this->display($template);
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
            $ds = new DealPrepayService();
            $dealInfo = $ds->prepayCheck($deal_id);

            $deal = $dealInfo['deal_base_info'];
            $deal['isDtb'] = 0;
            $dealService = new DealService();
            if($dealService->isDealDT($deal['id'])){
                $deal['isDtb'] = 1;
            }
            $deal_ext = $dealInfo['deal_ext_info'];

            $res = $this->calc($deal,$deal_ext,$end_day);
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
            $ds = new DealPrepayService();
            $dealInfo = $ds->prepayCheck($deal_id);
            $deal = $dealInfo['deal_base_info'];
            $deal['isDtb'] = 0;
            $dealService = new DealService();
            if($dealService->isDealDT($deal['id'])){
                $deal['isDtb'] = 1;
            }
            $deal_ext = $dealInfo['deal_ext_info'];

            $calc_res = $this->calc($deal,$deal_ext,$end_day);

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
            );

            if ($deal['isDtb'] == 1) {
                $data['management_fee'] = $calc_res['management_fee'];
            }

            try{
                $GLOBALS['db']->startTrans();
                $sql = "select * from ".DB_PREFIX."deal_prepay where deal_id= $deal_id and status =0";
                $res = $GLOBALS['db']->getRow($sql);

                if($res) {
                    $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"UPDATE","id=".$res['id']);
                    if ($res == false) {
                        throw new \Exception("insert deal_prepay error deal_id:".$deal_id);
                    }
                }else{
                    $res = $GLOBALS['db']->autoExecute(DB_PREFIX."deal_prepay",$data,"INSERT");
                    if ($res == false) {
                        throw new \Exception("update deal_prepay error deal_id:".$deal_id);
                    }
                }
                $GLOBALS['db']->commit();
            }catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                throw $e;
            }
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

        $negativeIds = app_conf('DEAL_REPAY_NEGATIVE');
        if($negativeIds){
            $negativeIds = explode(',',$negativeIds);
        }
        $canNegative = in_array($deal_id,$negativeIds) ?  1 : 0;

        try{
            if($deal_id == 0) {
                throw new \Exception("deal_id 参数错误");
            }
            $dealService = new DealService();
            if($dealService->isDealPartRepay($deal_id)){
                throw new \Exception("该标的有未完成部分用户还款，不能执行提前结清操作");
            }

            $ds = new DealPrepayService();
            $dealInfo = $ds->prepayCheck($deal_id);
            $deal = $dealInfo['deal_base_info'];
            $deal_ext = $dealInfo['deal_ext_info'];

            $prepay = new \core\dao\DealPrepayModel();
            $prepay = $prepay->findBy("deal_id=".$deal_id." and status = 0");
            if(!$prepay) {
                throw new \Exception("当前数据尚未保存，请先操作保存");
            }
            $ds = new \core\service\DealService();
            if(in_array($prepay->repay_type, array(DealRepayModel::DEAL_REPAY_TYPE_DAICHONGZHI, DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG))){
                $prepayUserId = $ds->getRepayUserAccount($deal_id,0);
            }else{
                $prepayUserId = $ds->getRepayUserAccount($deal_id,$prepay->repay_type);
            }

            if(!$prepayUserId) {
                throw new \Exception("还款用户ID获取失败");
            }
            $audit = D('serviceAudit')->where(array('service_type' => ServiceAuditModel::SERVICE_TYPE_PREPAY, 'service_id' => intval($deal_repay_id)))->find();


            try{
                $GLOBALS['db']->startTrans();

                // 标的优惠码设置信息
                $deal_coupon = M("CouponDeal")->where(array('deal_id' => $deal_id))->find();
                if(!$deal_coupon) {
                    throw new \Exception("优惠码设置信息获取失败deal_id:{$deal_id}");
                }
                // 优惠码结算时间为放款时结算：直接保存计算后得出的各项数据
                // 优惠码结算时间为还清时结算： 保存结算后的各项数据 并修改优惠码返利天数
                if($deal_coupon['pay_type'] == 1) {
                    $rebate_days = floor((get_gmtime() - $deal['repay_start_time'])/86400); // 优惠码返利天数=操作日期-放款日期

                    if($rebate_days < 0) {
                        throw new \Exception("优惠码返利天数不能为负值:rebate_days:".$rebate_days);
                    }
                    // 更新优惠码返利天数
                    $coupon_deal_service = new CouponDealService();
                    $coupon_res = $coupon_deal_service->updateRebateDaysByDealId($deal_id, $rebate_days);;
                    if(!$coupon_res){
                        throw new \Exception("更新标优惠码返利天数失败");
                    }
                }

                // 将标的置为还款中
                $res = $deal->changeRepayStatus(core\dao\DealModel::DURING_REPAY);
                if ($res == false) {
                    throw new \Exception("chage repay status error");
                }

                // 自动审核提前还款
                $prepay->status = 1;
                $prepay->save();

                // 用户资金冻结
                $deal_dao = new Deal();
                // 还款总额 = 应还本金+应还利息+手续费+咨询费+担保费+支付服务费。
                // 若多投宝，还需加上管理服务费
                $prepay_money = $prepay['prepay_money'];

                $user = UserModel::instance()->find($prepayUserId);
                $user->changeMoneyDealType = $ds->getDealType($deal);

                $bizToken = [
                    'dealId' => $deal['id'],
                ];

                //代充值还款逻辑
                if($prepay->repay_type == 3){
                    $generationRechargeUserId = $ds->getRepayUserAccount($deal_id,$prepay->repay_type);
                    $generationRechargeUser = UserModel::instance()->find($generationRechargeUserId);
                    $generationRechargeUser->changeMoneyDealType = $ds->getDealType($deal);

                    if ($generationRechargeUser->changeMoney(-$prepay_money, "代充值扣款", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('代充值提前还款失败');
                    }

                    if ($user->changeMoney($prepay_money, "代充值", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('代充值提前还款失败');
                    }

                    $syncRemoteData[] = array(
                        'outOrderId' => 'GENERATION_RECHARGE_FEE|' . $deal['id'],
                        'payerId' => $generationRechargeUser->id,
                        'receiverId' => $user->id,
                        'repaymentAmount' => bcmul($prepay_money, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,
                        'batchId' => $deal['id'],
                    );

                }

                //间接代偿还款逻辑
                if($prepay->repay_type == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
                    $indirectAgencyUserId = $ds->getRepayUserAccount($deal_id,$prepay->repay_type);
                    $indirectAgencyUser = UserModel::instance()->find($indirectAgencyUserId);
                    $indirectAgencyUser->changeMoneyDealType = $ds->getDealType($deal);

                    if ($indirectAgencyUser->changeMoney(-$prepay_money, "间接代偿扣款", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('间接代偿提前还款失败');
                    }

                    if ($user->changeMoney($prepay_money, "间接代偿", "编号".$deal['id'].' '.$deal['name'], 0, 0, 0, 0, $bizToken) === false) {
                        throw new \Exception('间接代偿提前还款失败');
                    }

                    $syncRemoteData[] = array(
                        'outOrderId' => 'INDIRECT_AGENCEY_FEE|' . $deal['id'],  // TODO 疑问
                        'payerId' => $indirectAgencyUser->id,
                        'receiverId' => $user->id,
                        'repaymentAmount' => bcmul($prepay_money, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 1,  // TODO  疑问
                        'batchId' => $deal['id'],
                    );

                }


                if (!empty($syncRemoteData) && !$ds->isP2pPath($deal)) {
                    FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_HIGH);
                }

                $res = $user->changeMoney($prepay_money, "提前还款", '编号'.$deal_id, $admInfo['adm_id'], 0, UserModel::TYPE_LOCK_MONEY,$canNegative,$bizToken);
                if(!$res) {
                    throw new \Exception("用户提前还款资金冻结失败");
                }

                if (!empty($audit) && $audit['status'] == 1) {
                    $auditRes= M("ServiceAudit")->where("id=" . $audit['id'])->save(array('status' => ServiceAuditModel::AUDIT_SUCC, 'audit_uid' => $admInfo['adm_id']));
                    if (!$auditRes) {
                        throw new \Exception("更新审核状态失败");
                    }
                }

                // 启动jobs进行还款操作

                $param = array('id' => $prepay->id, 'status' => $prepay->status, 'success' => C('SUCCESS'), 'saveLogFile' => $saveLogFile, 'admInfo' => $admInfo,'prepayUserId'=>$prepayUserId);


                if(!$ds->isP2pPath($deal)) {
                    // 异步处理还款
                    $function  = '\core\service\DealPrepayService::prepay';
                }else{
                    // p2p 还款逻辑
                    if($prepay->repay_type == 3){
                        $param['generationRechargeUserId'] = $generationRechargeUserId;
                    }
                    if($prepay->repay_type == DealRepayModel::DEAL_REPAY_TYPE_JIANJIE_DAICHANG){
                        $param['indirectAgencyUserId'] = $indirectAgencyUserId;
                    }
                    $orderId = Idworker::instance()->getId();
                    $function = '\core\service\P2pDealRepayService::dealPrepayRequest';
                    $param = array('orderId'=>$orderId,'prepayId'=>$prepay->id,'params'=>$param);
                }

                $job_model = new JobsModel();
                $job_model->priority = 80;
                $job_model->addJob($function, array('param' => $param), false, $retry);

                save_log('提前还款'.' deal_id:' . $deal_id, C('SUCCESS'), '', '', $saveLogFile);
                $GLOBALS['db']->commit();
            }catch (\Exception $e){
                $GLOBALS['db']->rollback();
                throw $e;
            }
        }catch (\Exception $ex) {
            save_log('提前还款'.' deal_id:' . $deal_id, C('FAILED'), '', '', $saveLogFile);
            $result = array('errCode'=>$ex->getCode(),'errMsg'=>$ex->getMessage(),'data'=>false);
        }
        ajax_return($result);
    }

    /**
     * 计算提前还款明细
     * @param $deal 标的对象信息
     * @param $deal_ext 标的对象扩展信息
     * @param $end_day 还款日期
     */
    private function calc($deal,$deal_ext,$end_day) {
        if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $end_day)) {
            throw new \Exception("结束日期{$end_day}格式不正确");
        }

        // 计算计息日期
        $dps = new DealRepayService();
        $interest_time =  $dps->getMaxRepayTimeByDealId($deal);
        // 因为$interest_time 有可能不是从零点开始记录的，所以计算天数会有误差
        $interest_time = to_timespan(to_date($interest_time,'Y-m-d')); // 转换为零点开始
        $end_interest_time = to_timespan($end_day); // 计息结束日期
         //$remain_days = get_remain_day($deal, $end_interest_time); // 利息天数 这种方式可能不准
        $remain_days = ceil(($end_interest_time - $interest_time)/86400); // 利息天数

        if($end_interest_time <= $interest_time) {
            throw new \Exception("计息结束日期必须大于最近一次还款日期");
        }

        $deal_loan_repay_model = new DealLoanRepay();
        $remain_principal = get_remain_principal($deal);
        $prepay_result = $deal_loan_repay_model->getPrepayMoney($deal['id'], $remain_principal, $remain_days);
        $prepay_money = $prepay_result['prepay_money']; // 还款总额
        $remain_principal = $prepay_result['principal']; // 应还本金
        $prepay_interest = $prepay_result['prepay_interest']; // 应还利息

        $deal_dao = new Deal();
        $remain_principal = $deal_dao->floorfix($remain_principal);
        $prepay_interest = $deal_dao->floorfix($prepay_interest);
        $prepay_compensation = $deal_dao->floorfix($prepay_money - $prepay_interest - $remain_principal);
        //$prepay_compensation = $deal_dao->floorfix($deal['borrow_amount'] * $deal['prepay_rate'] / 100); // 借款金额x提前还款违约金系数

        // 各项未收费用
        $deal_repay_model = new \core\dao\DealRepayModel();
        $fees = $deal_repay_model->getNoPayFees($deal,$deal_ext,$end_day);
        //$prepay_money = bcsub($prepay_money,$prepay_compensation,2);

        // 开始计算回扣支付费用
        $deal_ext = DealExtModel::instance()->getDealExtByDealId($deal['id']);
        if ($deal_ext['pay_fee_rate_type'] == 1) {
            $fee_days = ceil(($end_interest_time - $deal['repay_start_time'])/86400); // 利息天数
            $pay_fee_rate = Finance::convertToPeriodRate($deal['loantype'], $deal['pay_fee_rate'], $deal['repay_time'], false);
            $pay_fee = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate / 100.0);

            $pay_fee_rate_real = Finance::convertToPeriodRate(5, $deal['pay_fee_rate'], $fee_days, false);
            $pay_fee_real = DealModel::instance()->floorfix($deal['borrow_amount'] * $pay_fee_rate_real / 100.0);

            $pay_fee_remain = bcsub($pay_fee, $pay_fee_real, 2);
        }

        $data = array(
            'deal_id'             => $deal['id'],
            'user_id'             => $deal['user_id'],
            'interest_time'       => $interest_time, // 计息日期
            'prepay_time'         => $end_interest_time, // 提前还款日期
            'remain_days'         => $remain_days, // 利息天数
            'remain_principal'    => $remain_principal,
            'prepay_interest'     => $prepay_interest,
            'prepay_compensation' => $prepay_compensation,
            'loan_fee'            => $fees['loan_fee'],
            'consult_fee'         => $fees['consult_fee'],
            'guarantee_fee'       => $fees['guarantee_fee'],
            'pay_fee'             => $fees['pay_fee'],
            'canal_fee'           => $fees['canal_fee'],
            'pay_fee_remain'      => !empty($pay_fee_remain) ? $pay_fee_remain : 0,
            'deal_type'           => $deal['deal_type'],
        );

        $managementFee = 0;
        if ($deal['isDtb'] == 1) {
            $data['management_fee'] = $fees['management_fee'];
            $managementFee = $fees['management_fee'];
        }

        $prepay_money = $deal_dao->floorfix($prepay_money + $fees['loan_fee'] + $fees['consult_fee'] + $fees['guarantee_fee'] + $fees['pay_fee'] + $fees['canal_fee']+ $managementFee);
        $data['prepay_money'] = $prepay_money;

        return $data;
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
