<?php

/**
 * WX普惠[供应链]->商城债权兑换权益币成功后，1分钱债权交易脚本
 * Class DebtTransactionCommand
 */
class PhDebtTransactionCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/pxTransferDebt';	//项目ID文件锁的前缀
	public $fnLock_tenderid = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $where = ' ';
	public $buyer_uid = '';
	public $debt_fee = 0;
	public $use_money_alarm_amount = 100;//回购人账户余额报警金额
    //public $use_money_alarm_key = 'use_money_alarm_key_';
	public $min_repay_time = '';
	public $old_type = 1;
	public $frozen_amount = 0.00;//交易金额
    private $error_code=0;


	/**
	 * 自动债权交易
	 * @param string $tender_id 指定投资记录ID
	 * @param string $buyer_uid 指定其他买方ID
	 * @return bool
	 */
	public function actionRun($tender_id=''){
		$this->echoLog("PhDebtTransaction run start");

		//指定投资记录单笔处理
		if(!empty($tender_id)){
			$this->where .= " and tender_id=$tender_id ";
		}

		try {
			//承接金额490w预警, 仅报警，超过也不终止兑换脚本
			/*
			$send_ret = DebtService::getInstance()->sendAlarm($this->buyer_uid);
			$this->echoLog("actionRun sendAlarm return $send_ret ");
			*/

			//查询待自动债转的项目
			$criteria = new CDbCriteria;
			$criteria->condition = "  status=1 and successtime=0 {$this->where}";
			$criteria->limit = 50;
			$order_list = PHDebtExchangeLog::model()->findAll($criteria);
			if(empty($order_list)) {
				$this->echoLog("actionRun: firstp2p_debt_exchange_log is empty");
				return false;
			}
			$this->echoLog("actionRun: order_list count:".count($order_list));

			$f = $s = 0;
			foreach($order_list as $key => $value){
				$tender_id = $value->tender_id;

				//根据投资记录加文件锁
				$fpLock = $this->enterTenderIdFnLock($tender_id);

				//逐一债转处理
				$result = $this->actionPhHandelDebt($value->id);
				if($result == false){
					$f++;
					$this->echoLog("phHandelDebt return false; tender_id: $tender_id", "email");
				}else{
					$s++;
					$this->echoLog("phHandelDebt return true; tender_id: $tender_id; ");
				}

				sleep(1);
				//释放文件锁
				$this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
			}

			//增加短信报警
			if($f>0 && $this->error_code != 2007){
				$error_info = "YJ_DEBT_ALARM：PhDebtTransaction_fail_count：$f";
				$send_ret = SmsIdentityUtils::fundAlarm($error_info, 'PhDebtTransaction');
				$this->echoLog("phHandelDebt sendAlarm return $send_ret ");
			}
			
			$this->echoLog("actionRun end, success_count:$s; fail_count:$f; ");

		} catch (Exception $e) {
			self::echoLog("actionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
		}
		//邮件报警错误详情
		$this->warningEmail();
	}

	/**
	 * 单笔兑换记录处理
	 */
	public function actionPhHandelDebt($log_id){
		$this->echoLog("phHandelDebt start log_id:$log_id");
		if(empty($log_id) || !is_numeric($log_id)){
			$this->echoLog("phHandelDebt end log_id:$log_id params error");
			return false;
		}
		Yii::app()->phdb->beginTransaction();
		Yii::app()->db->beginTransaction();
		try{
			//兑换记录
			$exchange_log = PHDebtExchangeLog::model()->findBySql("select * from firstp2p_debt_exchange_log where id=:log_id for update", array(':log_id' => $log_id));
			if(!$exchange_log || $exchange_log->status != 1 || $exchange_log->successtime != 0){
				$this->echoLog("phHandelDebt end id:$log_id status!= 0 or successtime!=0 ");
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//获取回购人
            $this->buyer_uid = !empty($exchange_log->buyer_uid) ? $exchange_log->buyer_uid : DebtService::getInstance()->getBuyerUid($exchange_log->debt_account);
			if(empty($this->buyer_uid) || !is_numeric($this->buyer_uid)){
				$this->echoLog("handelDebt end id:$log_id buyer_uid empty! ");
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//受让人信息
			$assignee_info = AgWxAssigneeInfo::model()->findBySql("select * from ag_wx_assignee_info where user_id={$this->buyer_uid}  and status=2 for update");
			$use_money = bcsub($assignee_info->transferability_limit, $assignee_info->transferred_amount, 2);
			if(!$assignee_info || FunctionUtil::float_bigger($exchange_log->debt_account, $use_money, 2)){
				$this->echoLog("handelDebt end id:$log_id ag_wx_assignee_info error ");
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//创建债权
			$c_data = array();
			$c_data['user_id'] = $exchange_log->user_id;
			$c_data['money'] = $exchange_log->debt_account;
			$c_data['discount'] = 0;
			$c_data['deal_loan_id'] = $exchange_log->tender_id;
			$c_data['debt_src'] = $exchange_log->debt_src;
            $c_data['platform_no'] = $exchange_log->platform_no;
			$create_ret = PhDebtService::getInstance()->createDebt($c_data);
            $this->error_code = $create_ret['code'];
			if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
				$this->echoLog("phHandelDebt createDebt tender_id {$exchange_log->tender_id} false:".print_r($create_ret,true), 'error', 'email');
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//创建成功日志
			$this->echoLog("phHandelDebt createDebt tender_id {$exchange_log->tender_id} success");

			//债权认购
			$debt_data = array();
			$debt_data['user_id'] = $this->buyer_uid;
			$debt_data['money'] = $exchange_log->debt_account;
			$debt_data['debt_id'] = $create_ret['data']['debt_id'];
			$debt_data['frozen_amount'] = $this->frozen_amount;
            $debt_data['platform_no'] = $exchange_log->platform_no;
			$debt_transaction_ret = PhDebtService::getInstance()->debtPreTransaction($debt_data);
			if($debt_transaction_ret['code'] != 0){
				$this->echoLog("phHandelDebt debtPreTransaction tender_id {$exchange_log->tender_id} false:".print_r($debt_transaction_ret,true), 'error', 'email');
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//兑换记录数据更新
			$changeLogRet = BaseCrudService::getInstance()->update("PHDebtExchangeLog",array(
				"id" => $exchange_log->id,
				"successtime" => time(),
				"status" => 2,
				"buyer_uid"=>$this->buyer_uid,
                "new_deal_load_id"=>$debt_transaction_ret['data']['new_tender_id']
			), "id");
			if(!$changeLogRet){
				$this->echoLog("PHDebtExchangeLog update error, id=$exchange_log->id");
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}

			//更新收购额度
			$assignee_info->transferred_amount = bcadd($assignee_info->transferred_amount, $exchange_log->debt_account, 2);
			if($assignee_info->save(false, array('transferred_amount')) == false){
				$this->echoLog("DebtExchangeLog update ag_wx_assignee_info transferred_amount[$assignee_info->transferred_amount] error, id=$exchange_log->id");
				Yii::app()->phdb->rollback();
				Yii::app()->db->rollback();
				return false;
			}


			//债转成功数据确认
			Yii::app()->phdb->commit();
			Yii::app()->db->commit();
			$this->echoLog("phHandelDebt tender_id:$exchange_log->tender_id success");
			return true;
		}catch (Exception $ee) {
			$this->echoLog("phHandelDebt log_id:$log_id; exception:".print_r($ee->getMessage(), true));
			Yii::app()->phdb->rollback();
			Yii::app()->db->rollback();
			return false;
		}
	}

	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()." wx_phTransferDebt {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("transferDebt: {$yiilog}", $level, 'wx_phTransferDebt');
	}

	//报警邮件
	public function warningEmail(){
        return true;
		if(!empty($this->alarm_content) && $this->is_email) {
			FunctionUtil::alertToAccountWx($this->alarm_content);
		}
		return true;
	}

	/**
	 * 跑脚本加锁
	 */
	private static function enterLock($config){
		if(empty($config['fnLock'])){
			return false;
		}
		$fnLock = $config['fnLock'];
		$fpLock = fopen( $fnLock, 'w+');
		if($fpLock){
			if ( flock( $fpLock, LOCK_EX | LOCK_NB ) ) {
				return $fpLock;
			}
			fclose( $fpLock );
			$fpLock = null;
		}
		return false;
	}

	/**
	 * 根据项目ID建立文件锁
	 */
	private function enterTenderIdFnLock($tender_id){
		$tender_id = (int)$tender_id;
		if($tender_id<=0) {
			self::echoLog($tender_id." illegal!!!");
			exit(1);
		}
		$this->fnLock_tenderid = $this->fnLock_pre.$tender_id.'.pid';
		$fpLock = $this->enterLock(array('fnLock'=>$this->fnLock_tenderid));
		if(!$fpLock){
			self::echoLog($this->fnLock_tenderid." Having Run!!!");
			exit(1);
		}
		return $fpLock;
	}

	/**
	 * 检查跑脚本加锁
	 */
	private static function releaseLock($config){
		if (!$config['fpLock']){
			return;
		}
		$fpLock = $config['fpLock'];
		$fnLock = $config['fnLock'];
		flock($fpLock, LOCK_UN);
		fclose($fpLock);
		unlink($fnLock);
	}

}
