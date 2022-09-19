<?php

/**
 * 权益兑换脚本自动债转
 * 每2分钟处理一次，每次50条
 * Class AgDebtTransactionCommand
 */
class AgDebtTransactionCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/agTransferDebt';	//项目ID文件锁的前缀
	public $fnLock_tenderid = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $where = ' ';
	public $buyer_uid = '';//回购人,从平台信息表读取
	public $debt_fee = 0;
	public $use_money_alarm_amount = 100;//回购人账户余额报警金额
   // public $use_money_alarm_key = 'use_money_alarm_key_';
	public $min_repay_time = '';
	public $old_type = 1;
	public $frozen_amount = 0.00;//交易金额
	public $debt_src=1;//1权益兑换债转


	/**
	 * 自动债权交易
	 * @param string $tender_id 指定投资记录ID
	 * @return bool
	 */
	public function actionRun($tender_id=''){
		$this->echoLog("AgDebtTransaction run start");

		//指定投资记录单笔处理
		if(!empty($tender_id)){
			$this->where .= " and tender_id=$tender_id ";
		}

		try {
			//查询待自动债转的项目
			$criteria = new CDbCriteria;
			$criteria->condition = "  status=1 and successtime=0 {$this->where}";
			$criteria->limit = 50;
			$order_list = AgDebtExchangeLog::model()->findAll($criteria);
			if(empty($order_list)) {
				$this->echoLog("actionRun: ag_debt_exchange_log is empty");
				return false;
			}
			$this->echoLog("actionRun: order_list count:".count($order_list));

			$f = $s = 0;
			foreach($order_list as $key => $value){
				$tender_id = $value->tender_id;

				//根据投资记录加文件锁
				$fpLock = $this->enterTenderIdFnLock($tender_id);

				//逐一债转处理
				$result = $this->actionHandelDebt($value->id);
				if($result == false){
					$f++;
					$this->echoLog("handelDebt return false; tender_id: $tender_id", "email");
				}else{
					$s++;
					$this->echoLog("handelDebt return true; tender_id: $tender_id; ");
				}

				sleep(1);
				//释放文件锁
				$this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
			}

			$this->echoLog("actionRun end, success_count:$s; fail_count:$f; ");

		} catch (Exception $e) {
			self::echoLog("actionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
		}
		$this->warningEmail();
	}

	/**
	 * 单笔兑换记录处理
	 */
	public function actionHandelDebt($log_id){
		$this->echoLog("handelDebt start log_id:$log_id");
		if(empty($log_id) || !is_numeric($log_id)){
			$this->echoLog("handelDebt end log_id:$log_id params error");
			return false;
		}
		Yii::app()->agdb->beginTransaction();
		try{
			//兑换记录
			$exchange_log = AgDebtExchangeLog::model()->findBySql("select * from ag_debt_exchange_log where id=:log_id for update", array(':log_id' => $log_id));
			if(!$exchange_log || $exchange_log->status != 1 || $exchange_log->successtime != 0){
				$this->echoLog("handelDebt end id:$log_id status!= 0 or successtime!=0 ");
				Yii::app()->db->rollback();
				return false;
			}

			//平台信息校验
			$check_p = AgDebtService::getInstance()->checkPlatform($exchange_log->platform_id);
			if($check_p['code'] != 0){
				$this->echoLog("handelDebt end id:$log_id checkPlatform return:".print_r($check_p, true));
				Yii::app()->db->rollback();
				return false;
			}
			$plat_info = $check_p['data'];

			//创建债权
			$c_data = array();
			$c_data['user_id'] = $exchange_log->user_id;
			$c_data['money'] = $exchange_log->debt_account;
			$c_data['debt_src'] = $this->debt_src;
			$c_data['discount'] = 0;
			$c_data['effect_days'] = 10;
			$c_data['tender_id'] = $exchange_log->tender_id;
			$create_ret = AgDebtService::getInstance()->createDebt($c_data);
			if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
				$this->echoLog("handelDebt createDebt tender_id {$exchange_log->tender_id} false:".print_r($create_ret,true), 'error', 'email');
				Yii::app()->agdb->rollback();
				return false;
			}

			//创建成功日志
			$this->echoLog("handelDebt createDebt tender_id {$exchange_log->tender_id} success");

			//债权认购
			$debt_data = array();
			$debt_data['user_id'] = $plat_info->buyback_user_id;
			$debt_data['money'] = $exchange_log->debt_account;
			$debt_data['debt_id'] = $create_ret['data']['debt_id'];
			$debt_data['debt_src'] = $this->debt_src;
			$debt_transaction_ret = AgDebtService::getInstance()->debtPreTransaction($debt_data);
			if($debt_transaction_ret['code'] != 0){
				$this->echoLog("handelDebt debtPreTransaction tender_id {$exchange_log->tender_id} false:".print_r($debt_transaction_ret,true), 'error', 'email');
				Yii::app()->agdb->rollback();
				return false;
			}

			//兑换记录数据更新
			$changeLogRet = BaseCrudService::getInstance()->update("AgDebtExchangeLog",array(
				"id" => $exchange_log->id,
				"successtime" => time(),
				"status" => 2
			), "id");
			if(!$changeLogRet){
				$this->echoLog("AgDebtExchangeLog update error, id=$exchange_log->id");
				Yii::app()->agdb->rollback();
				return false;
			}

			//债转成功数据确认
			Yii::app()->agdb->commit();
			$this->echoLog("handelDebt tender_id:$exchange_log->tender_id success");
			return true;
		}catch (Exception $ee) {
			$this->echoLog("handelDebt log_id:$log_id; exception:".print_r($ee->getMessage(), true));
			Yii::app()->agdb->rollback();
			return false;
		}
	}

	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()." agTransferDebt {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("transferDebt: {$yiilog}", $level, 'agTransferDebt');
	}

	//报警邮件
	public function warningEmail(){
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

	/**
	 * 过期债权
	 * 每分钟一次
	 */
	public function actionProcExpired(){
		$this->echoLog("debt procExpired start!!!");

		$now = time();
		$criteria = new CDbCriteria;
		$criteria->condition="end_time<$now and status=1 ";
		$attributes = array();
		// 省心计划和阳光智选债权
		$obj_debts = AgDebt::model()->findAllByAttributes($attributes, $criteria);
		//无可过期债权时
		if(count($obj_debts) != 0){
            //逐条过期
            foreach($obj_debts as $debt){
                //过期其他项目类型债权
                $cancelDebtRet = AgDebtService::getInstance()->CancelDebt($debt->id, 4);
                if($cancelDebtRet['code']!='0'){
                    $this->echoLog("Expired Debt wrong debt_id {$debt->id}, return:".print_r($cancelDebtRet,true), 'email');
                }
            }
		}
        //非导入数据平台
        $debtExchange = Yii::app()->yiidb->createCommand("select * from itz_ag_debt_exchange where status = 1 and create_debt_time + effect_days * 86400 < unix_timestamp(now())")->queryAll();
        //无可过期债权时
        if(count($debtExchange) != 0){
            //逐条过期
            foreach($debtExchange as $debt){
                //过期其他项目类型债权
                $cancelDebtRet = AgDebtitouziService::getInstance()->CancelDebt($debt['id'], 4);
                if($cancelDebtRet['code']!='0'){
                    $this->echoLog("Expired Debt wrong debt_id {$debt->id}, return:".print_r($cancelDebtRet,true), 'email');
                }
            }
        }
		$this->echoLog("debt procExpired end!!!");
		$this->warningEmail();
	}

}
