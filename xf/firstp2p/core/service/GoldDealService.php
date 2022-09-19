<?php
/**
 * 黄金标相关操作
 * @data 2017.05.22
 * @author 晓安
 */


namespace core\service;

use app\models\service\GoldFinance;
use core\dao\DealModel;
use core\dao\UserModel;
use core\dao\FinanceQueueModel;
use libs\utils\Logger;
use NCFGroup\Protos\Gold\RequestCommon;
use libs\lock\LockFactory;
use core\service\GoldLoanRepayService;
use core\service\GoldService;
use NCFGroup\Task\Services\TaskService AS GTaskService;
use core\event\Gold\GoldDealLoansMsgEvent;
use core\event\Gold\GoldFailDealMsgEvent;
use core\service\MoneyOrderService;
use NCFGroup\Protos\Ptp\Enum\MoneyOrderEnum;
use NCFGroup\Protos\Gold\Enum\GoldMoneyOrderEnum;
use core\exception\MoneyOrderException;
use core\data\GoldDealData;
use core\dao\JobsModel;

class GoldDealService extends GoldService{


    /**
     * 放款jobs
     */
    public function makeDealLoansJob($deal_id, $admin = array(), $submit_uid = 0,$pay_user_id=0,$loan_user_id=0){
        $request = new RequestCommon();
        $request_data = array(
                    'deal_id' => $deal_id
        );
        $request->setVars($request_data);
        try {
            // 获取标信息
            $response = $this->requestGold('NCFGroup\Gold\Services\Deal', 'getUnderlineDealById', $request);
        }catch (\Exception $e) {
            Logger::error('获取gold rpc 标id为'.$deal_id .'信息失败');
            return false;
        }
        unset($request);
        $deal_data = $response['data'];
        if (empty($deal_data)){
            Logger::error('获取标id为'.$deal_id .'信息为空');
            return false;
        }
        // 悲观锁，以group_id为锁的键名，防止重复生成
        $lockKey = "GoldDealService-makeDealLoansJob".$deal_data['id'];
        $lock = LockFactory::create(LockFactory::TYPE_REDIS, \SiteApp::init()->cache);
        if (!$lock->getLock($lockKey, 900)) {
            Logger::error('标id为'.$deal_id .'获取锁失败');
            return false;
        }


        // 不是放款状态
        if ($deal_data['is_has_loans'] !=2){
            $lock->releaseLock($lockKey);//解锁
            Logger::error('标id为'.$deal_id .'不是放款状态');
            return false;
        }
        // 异步发送合并投资短信
        $obj = new GTaskService();
        $event = new GoldDealLoansMsgEvent($deal_id);
        $obj->doBackground($event, 1);
        try {
            $GLOBALS['db']->startTrans();
            $request = new RequestCommon();
            $request->setVars(array('id' => $deal_data['id'],'id_desc' => 1));
            $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getDealLoadByDealId', $request);
            if (empty($response['data'])){
                throw new \Exception("获取标所有投资记录失败");
            }
            $loan_list = $response['data'];
            $syncRemoteData = array();
            // 购买标的总金额
            $borrow_amount = 0;
            // 购买黄金手续费
            $borrow_amount_fee = 0;
            foreach($loan_list as $key => $v){

                $deal_load_money = $v['money'] - $v['fee'];
                if (bccomp($deal_load_money, '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'GOLD_DEAL|'.$deal_data['id'],
                        'payerId' => $v['userId'],
                        'receiverId' => $deal_data['user_id'],
                        'repaymentAmount' => bcmul($deal_load_money, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => FinanceQueueModel::PAYQUEUE_BIZTYPE_GOLD_GRANT,
                        'batchId' => $deal_data['id'],
                    );
                }
                // 购买定期手续费
                if (bccomp($v['fee'], '0.00', 2) > 0) {
                    $syncRemoteData[] = array(
                        'outOrderId' => 'GOLD_DEAL_LOAD_FEE|'.$deal_data['id'],
                        'payerId' => $v['userId'],
                        'receiverId' => $deal_data['user_id'],
                        'repaymentAmount' => bcmul($v['fee'], 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => FinanceQueueModel::PAYQUEUE_BIZTYPE_GOLD_GRANT,
                        'batchId' => $deal_data['id'],
                    );
                }
                // 货款
                $borrow_amount = bcadd($borrow_amount, $deal_load_money, 2);

                $borrow_amount_fee = bcadd($borrow_amount_fee,$v['fee'],2);

                $function = '\core\service\GoldDealService::loanRepayChangLogUser';
                $param = array(
                    'deal_load_id' => $v['id'],
                    'user_id' => $v['userId'],
                    'deal_id' => $deal_data['id'],
                    'deal_name' => $deal_data['name'],
                    'admin_id' => $admin['adm_id'],
                );
                $job_model = new \core\dao\JobsModel();
                $job_model->priority = \core\dao\JobsModel::PRIORITY_GOLD_MAKE_LOAN_USER_LOG;
                $add_job = $job_model->addJob($function, $param, get_gmtime(),1);
                if (!$add_job) {
                    throw new \Exception("添加用户解冻任务失败");
                }
            }

            // 给借款人打货款
            $money_order_service = new  MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            $user_model = new UserModel();
            $jk_user = $user_model->find($deal_data['user_id']);
            $money_order_service->changeMoneyAsyn = false;
            $money_order_service->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $note = "编号{$deal_id} 优长金{$deal_data['name']} 运营方ID{$deal_data['user_id']} 运营方姓名{$jk_user['real_name']}";
            try {
                $money_order_service->changeUserMoney($deal_data['id'], $deal_data['user_id'], GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_BORROW, $borrow_amount, '买金货款划转', $note);
            }catch (\Exception $e){
                throw new \Exception($note.' '.$e->getMessage().' 失败');
            }

            //购买人手续费
            if (bccomp($borrow_amount_fee, '0.00', 2) > 0){
                $money_order_service->changeUserMoney($deal_data['id'], $deal_data['user_id'], GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_BORROW_BUY_FEE, $borrow_amount_fee, '买金手续费', $note);
            }
            // 支付费
            if ($deal_data['pay_fee_rate']){
                $pay_fee = $this->payFeeRate($deal_data['id'],$deal_data['borrow_amount'],$deal_data['loantype'],$deal_data['pay_fee_rate'],$deal_data['repay_time']);
            }

            // 平台手续费
            if ($deal_data['loan_fee_rate']){
                $loan_fee = $this->payFeeRate($deal_data['id'],$deal_data['borrow_amount'],$deal_data['loantype'],$deal_data['loan_fee_rate'],$deal_data['repay_time']);
            }

            if ($loan_user_id && bccomp($loan_fee, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'GOLD_LOAN_FEE|' . $deal_id,
                    'payerId' => $deal_data['user_id'],
                    'receiverId' => $loan_user_id,
                    'repaymentAmount' => bcmul($loan_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $deal_id,
                );
            }

            // 借款人支付
            try {
                $money_order_service->changeUserMoney($deal_data['id'], $deal_data['user_id'], GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_BORROW_FEE, -$loan_fee, '导流服务费', $note);
            }catch (\Exception $e){
                throw new \Exception($note.' '.$e->getMessage().' 借款人支付平台手续费失败');
            }

            if ($pay_user_id && bccomp($pay_fee, '0.00',2) > 0) {
                $syncRemoteData[] = array(
                    'outOrderId' => 'GOLD_PAY_SERVICE_FEE|' . $deal_id,
                    'payerId' => $deal_data['user_id'],
                    'receiverId' => $pay_user_id,
                    'repaymentAmount' => bcmul($pay_fee, 100), // 以分为单位
                    'curType' => 'CNY',
                    'bizType' => 4,
                    'batchId' => $deal_id,
                );
            }

            try {
                $money_order_service->changeUserMoney($deal_data['id'], $deal_data['user_id'], GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_BORROW_PAY_FEE, -$pay_fee, '支付服务费', $note);
            }catch (\Exception $e){
                throw new \Exception($note.' '.$e->getMessage().' 借款人支付支付服务费失败');
            }

            if ($loan_user_id) {
                try {
                    $money_order_service->changeUserMoney($deal_data['id'], $loan_user_id, GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_FEE, $loan_fee, '导流服务费', $note);
                }catch (\Exception $e){
                    throw new \Exception($note.' '.$e->getMessage().' 平台收入手续费失败');
                }
            }

            if ($pay_user_id) {
                try {
                    $money_order_service->changeUserMoney($deal_data['id'], $pay_user_id, GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_PAY_FEE, $pay_fee, '支付服务费', $note);
                }catch (\Exception $e){
                    throw new \Exception($note.' '.$e->getMessage().' 支付收入服务费失败');
                }
            }

            //生成回款计划
            $loanRepayService = new GoldLoanRepayService();
            $ret = $loanRepayService->makeLoanRepay($deal_id);
            if ($ret == false){
                throw new \Exception("生成用户回款计划失败");
            }


            /** 技术服务费开始  **/
            $tech_user_id = app_conf('TECH_FEE_USER_ID');//技术服务费收费id

            if ($deal_data['tech_fee_rate']){
                $tech_fee = $this->payFeeRate($deal_data['id'],$deal_data['borrow_amount'],$deal_data['loantype'],$deal_data['tech_fee_rate'],$deal_data['repay_time']);
            }

            if(bccomp($tech_fee, '0.00', 2) > 0){
                try {
                    $money_order_service->changeUserMoney($deal_data['id'], $deal_data['user_id'], GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_BORROW_TECH_FEE, -$tech_fee, '技术服务费', $note);
                }catch (\Exception $e){
                    throw new \Exception($note.' '.$e->getMessage().' 借款人支付技术服务费失败');
                }
            }

            if ($tech_user_id && bccomp($tech_fee, '0.00', 2) > 0) {
                try {
                    $money_order_service->changeUserMoney($deal_data['id'], $tech_user_id, GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_TECH_FEE, $tech_fee, '技术服务费', $note);
                }catch (\Exception $e){
                    throw new \Exception($note.' '.$e->getMessage().' 平台收入技术服务费失败');
                }
            }

            if ($tech_user_id && bccomp($tech_fee, '0.00', 2) > 0) {
                $syncRemoteData[] = array(
                        'outOrderId' => 'GOLD_TECH_FEE|' . $deal_id,
                        'payerId' => $deal_data['user_id'],
                        'receiverId' => $tech_user_id,
                        'repaymentAmount' => bcmul($tech_fee, 100), // 以分为单位
                        'curType' => 'CNY',
                        'bizType' => 4,
                        'batchId' => $deal_id,
                );
            }
            /** 技术服务费结束 **/

            //同步支付
            if ( !empty($syncRemoteData)) {
                FinanceQueueModel::instance()->push(array('orders' => $syncRemoteData), 'transfer', FinanceQueueModel::PRIORITY_DEAL);
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            $lock->releaseLock($lockKey);//解锁
            Logger::error(__CLASS__.' '.__FUNCTION__.'标id为'.$deal_id .$e->getMessage());
            return false;
        }

        return true;

    }

    /**
     * 计算服务费
     * @param $borrow_amount
     * @param $loantype
     * @param $pay_fee_rate
     * @param $repay_time
     * @return string
     */
    public function payFeeRate($dealId,$borrow_amount,$loantypes,$pay_fee_rate,$repay_time){

        switch($loantypes){
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_DAY']:
                $repay_time = $repay_time;
                break;
            case $GLOBALS['dict']['LOAN_TYPE_ENUM']['BY_SEASON_INTEREST_REPAY']:
                $repay_time = $repay_time * 30;
                break;
        }

        $request = new RequestCommon();
        $request->setVars(array('id' => $dealId));
        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad', 'getSumMoneyBydealId', $request);
        if (empty($response['data'])){
            throw new \Exception('获取标的总成交价失败');
        }
        $rate = $pay_fee_rate/100;
        //年化支付服务费=上线克重*成交总价*年化平台手续费率*期限/360
        $value = $response['data']*$rate*$repay_time/GoldFinance::DAY_OF_YEAR;

        $loanRepayServic = new GoldLoanRepayService();

        return $loanRepayServic->floorfix($value);
    }

    /**
     * 用户放款解冻并冻结用户所买黄金
     * @param $deal_id
     * @param $deal_load_id
     */
    public function loanRepayChangLogUser($deal_load_id,$user_id,$deal_id,$deal_name,$admin_id){

        // 查询标信息
        $request = new RequestCommon();
        $request_data = array('id'=>$deal_load_id);
        $request->setVars($request_data);
        $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad','getDealLoadByUserId',$request);

        if (empty($response['data'])){
            return false;
        }
        $deal_load_info = $response['data'];
        $user_model = new UserModel();

        try {
            $GLOBALS['db']->startTrans();
            $money_order_service = new  MoneyOrderService(MoneyOrderEnum::BIZ_TYPE_GOLD);
            //$user = $user_model->find($deal_load_info['userId']);
            $money_order_service->changeMoneyAsyn =  false;
            $money_order_service->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
            $note = "编号$deal_id {$deal_name}，单号{$deal_load_id}";
            try {
                $deal_load_money = $deal_load_info['money']-$deal_load_info['fee'];
                $money_order_service->changeUserMoney($deal_load_info['orderId'],$deal_load_info['userId'],GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK,$deal_load_money, '买金货款划转',$note,UserModel::TYPE_DEDUCT_LOCK_MONEY);
                // 买金手续费
                if (bccomp($deal_load_info['fee'],'0.00',2) > 0){
                    $money_order_service->changeUserMoney($deal_load_info['orderId'],$deal_load_info['userId'],GoldMoneyOrderEnum::BIZ_SUBTYPE_GOLD_LOAN_DEDUCT_LOCK_FEE,$deal_load_money, '买金手续费',$note,UserModel::TYPE_DEDUCT_LOCK_MONEY);
                }
            }catch (\Exception $e){
                throw new \Exception(" 解冻用户资金失败 ".$user_id.' deal_load order id  '.$deal_load_info['orderId'].' 失败'.$e->getMessage());
            }

            // 买金并冻结
            unset($request);
            $request = new RequestCommon();
            $request_data = array(
                'userId' => $user_id,
                'adminId' => 0, // 前台为0
                'gold' => $deal_load_info['buyAmount'],
                'message' => array('买金','买金冻结'),
                'note' => "编号$deal_id {$deal_name}，单号{$deal_load_id}",
                'moneyType' => 1,
                'dealLoadId' => $deal_load_id,
                'dealType' => 0, //优长金
            );

            $request->setVars($request_data);
            $response = $this->requestGold('NCFGroup\Gold\Services\User','changeMoneyLoanRepay',$request);
            if (empty($response['data'])){
                throw new \Exception(" gold用户 ".$user_id.' deal id '.$deal_id.'记录 失败');
            }
            $GLOBALS['db']->commit();
        }catch (\Exception $e){
            $GLOBALS['db']->rollback();
            Logger::error(__CLASS__.' '.__FUNCTION__.$e->getMessage());
            return false;
        }


        return true;

    }

    /**
     * 流标
     * @param intval $dealId
     * @throws \Exception
     */
    public function failDeal($dealId){

        try {
            $GLOBALS['db']->startTrans();
            $response  = $this->getDealById($dealId,0);
            if($response['errCode']){
                throw new \Exception('标不存在');
            }
            $deal = $response['data'];

            if($deal['dealStatus'] != 3){
                throw new \Exception('标没有流标');
            }

            $dealLoadList = $this->getDealLoadByDealId($dealId);
            $event = new GoldFailDealMsgEvent();
            if($dealLoadList){
                $user_dao = new UserModel();
                foreach($dealLoadList as $v){
                    $user_id = $v['userId'];
                    $user = $user_dao->find($user_id);
                    $note = '编号' . $deal['id'] .' ' . $deal['name'] . '，单号' . $v['id'];
                    $user->changeMoneyAsyn = false;
                    $user->changeMoneyDealType = DealModel::DEAL_TYPE_GOLD;
                    $bizToken = array('dealId'=>$deal['id'],'dealLoadId'=>$v['id']);
                    $chg_rs = $user->changeMoney(-$v['money']+$v['fee'], "买金流标返还", $note, 0, 0, 1, 0, $bizToken);
                    $chg_rs = $user->changeMoney(-$v['fee'], "买金流标手续费返还", $note, 0, 0, 1, 0, $bizToken);
                    $event->setMsgList(
                                array('userId'=>$user_id,
                                       'dealName'=>$deal['name'],
                                       'money'=>$v['money']
                                )
                            );
                }

                $request = new RequestCommon();
                $request->setVars(array('dealId'=>$dealId));
                $response = $this->requestGold('NCFGroup\Gold\Services\DealLoad','setIsrepayByDealId',$request);
                if (empty($response['data'])){
                    throw new \Exception('更新已还状态失败');
                }
            }
            $GLOBALS['db']->commit();
        } catch (\Exception $e) {
            $GLOBALS['db']->rollback();
            Logger::error(implode('|',array(__CLASS__,__FUNCTION__,'dealId:'.$dealId,"msg:".$e->getMessage())));
            return false;
        }

        Logger::info(implode('|',array(__CLASS__,__FUNCTION__,'dealId:'.$dealId,"msg:操作成功")));
        //短信可以容忍失败，所以不判断返回值
        $obj = new GTaskService();
        $obj->doBackground($event, 1);
        return true;
    }

    /**
     * 根据放款时间获取起息日时间
     * @param $repay_start_time
     */
    public function getInterestDate($repay_start_time){
        if (empty($repay_start_time)){
            return false;
        }
        \FP::import("libs.common.dict");
        // 放款后T+1日起息，遇非工作日顺延至下一工作日。非工作日取值于人工维护的数据字典。
        $holidays = \dict::get('REDEEM_HOLIDAYS');
        //默认循环20天
        for($ij=1;$ij<=20;$ij++){
            $repay_start_time +=86400;// t+1
            $day = date("Y-m-d",$repay_start_time);
            $n = date("N",$repay_start_time);
            if (!in_array($n,array(6,7))){ // 不在周末
                //检查 词典中节日
                if(!empty($holidays)){
                    if (!in_array($day,$holidays)){
                        break;
                    }
                }else{
                    break;
                }
            }
        }

        return $repay_start_time;
    }

    /**
     *截标
     */
    public function updateDealAmountAndStatus($dealId){

        $goldDealData = new GoldDealData();

        // 需要加锁
        $lock = $goldDealData->enterPool($dealId);
        if ($lock === false) {
            $goldDealData->leavePool($dealId);
            throw new \Exception("系统繁忙，请稍后再试");
        }
        //标的信息
        $request = new RequestCommon();
        $request->setVars(array("deal_id"=>$dealId));
        $response=$this->requestGold('NCFGroup\Gold\Services\Deal', 'getDealById', $request);
        if (empty($response['data'])){
            $goldDealData->leavePool($dealId);
            throw new \Exception("标".$dealId."信息不存在");
        }
        $dealInfo = $response['data'];
        //判断前置条件
        //('只有状态为“进行中”的标才能修改')
        if($dealInfo['dealStatus'] !=1){
            $goldDealData->leavePool($dealId);
            return true;
        }
        //('投资额为0的标将标状态修改为待确认，并且置为无效')
        if(bccomp($dealInfo['loadMoney'],0)==0){
            $update_dealInfo['id'] = $dealId;
            $update_dealInfo['deal_status'] = 0;
            $update_dealInfo['is_effect'] = 0;
        }else{
            //('投资额不为0的标修改为满标')
            $update_dealInfo['id'] = $dealId;
            $update_dealInfo['borrow_amount'] = $dealInfo['loadMoney'];
            $update_dealInfo['point_percent'] = 1;
            $update_dealInfo['success_time'] = time();
            $update_dealInfo['deal_status'] = 2;
        }
        $request = new RequestCommon();
        $request->setVars($update_dealInfo);
        $response=$this->requestGold('NCFGroup\Gold\Services\Deal', 'updateDeal', $request);
        if (empty($response['data'])){
            $goldDealData->leavePool($dealId);
            throw new \Exception("更新标".$dealId."状态失败");
        }
        // 发送相关消息和邮件
        //生成借款人合同
        //合同jobs
        if(bccomp($dealInfo['loadMoney'],0)>0) {
            $param = array();
            $param['borrowId'] = $dealInfo['userId'];
            $param['dealId'] = $dealId;
            $param['userId'] = 0;
            $param['loadId'] = 0;
            $param['isFull'] = true;
            $function = '\core\service\SendContractService::sendGoldConstract';
            $job_model = new \core\dao\JobsModel();
            $job_model->priority = JobsModel::PRIORITY_GOLD_CONTRACT;
            $ret = $job_model->addJob($function, $param); //不重试
            if ($ret === false) {
                $goldDealData->leavePool($dealId);
                throw new \Exception("上标队列自动截标添加借款人生成合同任务失败,标ID:".$dealId);
            }
        }
        // 解锁
        $goldDealData->leavePool($dealId);
        return true;
    }
}
