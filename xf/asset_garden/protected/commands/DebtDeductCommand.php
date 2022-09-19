<?php

class DebtDeductCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/debtDeduct';	//文件锁的前缀
	public $fnLock_tenderid = '';//投资记录ID文件
	public $is_email = false;
	public $where = ' ';

	public $db_name='db';//db-尊享 phdb-普惠
	public $table_prefix = '';
	public $frozen_amount = 0;

	/**
	 * 自动债权划扣
	 * @param string $tender_id 指定投资记录ID
	 * @param string $user_id 指定用户ID
	 * @return bool
	 */
	public function actionRun($user_id='', $tender_id=''){
		$this->echoLog("DebtDeduct run start");

		//指定投资记录单笔处理
		if(!empty($tender_id)){
			$this->where .= " and tender_id=$tender_id ";
		}

		//指定用户
		if(!empty($tender_id)){
			$this->where .= " and user_id=$user_id ";
		}

		try {
			//查询待自动债转的项目
			$criteria = new CDbCriteria;
			$criteria->condition = "  status=1 and successtime=0 {$this->where}";
			$criteria->limit = 50;
			$criteria->order = ' start_time desc ';
			$order_list = DebtDeductLog::model()->findAll($criteria);
			if(empty($order_list)) {
				$this->echoLog("actionRun: firstp2p_debt_deduct_log is empty");
				return false;
			}

			$this->echoLog("actionRun: order_list count:".count($order_list));

			$f = $s = 0;
			foreach($order_list as $key => $value){
				//重置db选择
				$this->db_name = "db";
				$this->table_prefix = "";
				$tender_id = $value->tender_id;
				$edit_deduct['id'] = $value->id;

				//根据投资记录加文件锁
				$fpLock = $this->enterTenderIdFnLock($tender_id);
				//逐一债转处理
				$result = $this->handelDebt($value);
				if($result == false){
					$f++;
					$edit_deduct['status'] = 3;
					$this->echoLog("handelDebt return false; tender_id: $tender_id", "email");
				}else{
					$s++;
					$edit_deduct['status'] = 2;
					$edit_deduct['successtime'] = time();
					$this->echoLog("handelDebt return true; tender_id: $tender_id; ");
				}

				//更新数据结果
				$edit_deduct_ret = BaseCrudService::getInstance()->update("DebtDeductLog", $edit_deduct, "id");
				if(!$edit_deduct_ret){
					$this->echoLog("DebtDeductLog update error, id=$value->id");
				}

				sleep(1);
				//释放文件锁
				$this->releaseLock(array('fnLock'=>$this->fnLock_tenderid, 'fpLock'=>$fpLock));
			}

			$this->echoLog("actionRun end, success_count:$s; fail_count:$f; ");
		} catch (Exception $e) {
			self::echoLog("actionRun Exception,error_msg:".print_r($e->getMessage(),true), "email");
		}
		//$this->warningEmail();
	}


	/**
	 * 债权扣除
	 * @param $deduct_log
	 * @return bool
	 */
	private function handelDebt($deduct_log){
		$this->echoLog("handelDebt start");

		//扣除记录
		if(!$deduct_log){
			$this->echoLog("handelDebt end; firstp2p_debt_deduct_log is empty ");
			return false;
		}

		//从表里取数据结合原始脚本
		$type = $deduct_log->deal_type;
		$tender_id = $deduct_log->tender_id;
		$user_id = $deduct_log->user_id;
		$debt_capital = $deduct_log->debt_account;
		$buyer_uid = $deduct_log->buyback_user_id;
		$now_time = time();

		//参数校验
		if(!in_array($type, [1,2,3,4]) || empty($tender_id) || empty($user_id) || empty($debt_capital) || !is_numeric($tender_id) || !is_numeric($user_id) || !is_numeric($debt_capital) || $debt_capital<=0){
			$this->echoLog("handelDebt: params error");
			return false;
		}

		//启动时间必须小于等于当前时间
		if(empty($deduct_log->start_time) || $deduct_log->start_time>$now_time){
			$this->echoLog("handelDebt: start_time error");
			return false;
		}

		//普惠
		if($type == 2){
			$this->db_name = "phdb";
			$this->table_prefix = "Ph";
		}elseif(in_array($type, [3,4])){
            $this->db_name = "offlinedb";
            $this->table_prefix = "Offline";
        }

		Yii::app()->{$this->db_name}->beginTransaction();
		try{
			//区别service
			$service_name = "{$this->table_prefix}DebtService";

			//创建债权
			$c_data = array();
			$c_data['user_id'] = $user_id;
			$c_data['money'] = $debt_capital;
			$c_data['discount'] = 0;
			$c_data['deal_loan_id'] = $tender_id;
			$c_data['debt_src'] = 3;
			$create_ret = $service_name::getInstance()->createDebt($c_data);
			if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
				$this->echoLog(" createDebt tender_id {$tender_id} false:".print_r($create_ret,true), 'error');
				Yii::app()->{$this->db_name}->rollback();
				return false;
			}

			//创建成功日志
			$this->echoLog(" createDebt tender_id {$tender_id} success");

			//债权认购
			$debt_data = array();
			$debt_data['user_id'] = $buyer_uid;
			$debt_data['money'] = $debt_capital;
			$debt_data['debt_id'] = $create_ret['data']['debt_id'];
			$debt_data['frozen_amount'] = $this->frozen_amount;
			$debt_transaction_ret = $service_name::getInstance()->debtPreTransaction($debt_data);
			if($debt_transaction_ret['code'] != 0){
				$this->echoLog(" debtPreTransaction tender_id {$tender_id} false:".print_r($debt_transaction_ret,true), 'error');
				Yii::app()->{$this->db_name}->rollback();
				return false;
			}

			//债转成功数据确认
			Yii::app()->{$this->db_name}->commit();
			$this->echoLog("debtPreTransaction tender_id:$tender_id success");
			return true;
		}catch (Exception $ee) {
			$this->echoLog("handelDebt tender_id:$tender_id; exception:".print_r($ee->getMessage(), true));
			Yii::app()->{$this->db_name}->rollback();
			return false;
		}
	}

	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()."debtDeduct {$yiilog} \n";
		Yii::log("DebtDeduct: {$yiilog}", $level);
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
