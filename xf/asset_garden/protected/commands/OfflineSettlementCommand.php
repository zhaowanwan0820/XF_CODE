<?php


class OfflineSettlementCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/OfflineSettlement';	//项目ID文件锁的前缀
	public $fnLock_deal_id = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $repayment_fnLock = '/tmp/offline_repayment_fnLock_01.pid';
	public $part_repayment_fnLock = '/tmp/offline_part_repayment_fnLock.pid';
	public $table_prefix = 'Offline';
	public $db_name = 'offlinedb';
	public $global_conditions = '';
	public $repay_capital_total = 0.00;
	public $repay_interest_total = 0.00;
	public $loan_repay_type = [];
	private $task_remark='';


	/**
	 * wx线下产品部分还款脚本
	 * @return bool
	 */
	public function actionPartRepayment(){
		self::echoLog("offline partRepayment Start ");

		//文件锁
		$fpLock = self::enterLock(array('fnLock'=>$this->part_repayment_fnLock));
		if(!$fpLock){
			self::echoLog('offline partRepayment Commands Having Run!!!');
			return false;
		}


		try {
			//产品化取正常还本息的数据
			$t_midnight = strtotime("midnight");
			$plan_sql = " select * from offline_partial_repay where status=2 and pay_plan_time=$t_midnight order by addtime asc";
			$repay_model_name = "OfflinePartialRepay";
			$repay_plan_info = $repay_model_name::model()->findBySql($plan_sql);
			if(empty($repay_plan_info)){
				self::echoLog("offline partRepayment: No data!!!");
				self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
				return false;
			}

			//校验计划还款表的数据
			$check_plan_info = OfflinePrePaymentService::getInstance()->checkPartRepayment($repay_plan_info->id);
			if($check_plan_info['code'] != 0){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'offline.parRepayment.checkPartRepayment');
				self::echoLog("offline partRepayment: checkRepaymentPlan error code={$check_plan_info['code']} info:{$check_plan_info['info']};");
				self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
				return false;
			}
			//根据项目ID, 还款确认
			$result = $this->accordingToPartRepay($repay_plan_info->id);
			if($result == false){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'offline.parRepayment.accordingToPartRepay');
				$this->echoLog("offline partRepayment accordingToPartRepay return false,repay_id:$repay_plan_info->id; ", "email");
			}

			//释放文件锁
			self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
			$this->echoLog("offline partRepayment end");
		} catch (Exception $ee) {
			self::echoLog("offline partRepayment Exception,error_msg:".print_r($ee->getMessage(),true), "email");
			self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));;
		}
		$this->warningEmail();
	}

	private function warningSms($repay_model_name, $id, $name){
		//短信报警
		$error_info = "YJ_ERROR：{$name} id_$id：$this->task_remark ";
		$send_ret = SmsIdentityUtils::fundAlarm($error_info, $id);
		//更新还款计划
		$edit_ret = $repay_model_name::model()->updateByPk($id, ['status'=>4, 'task_remark'=>$this->task_remark]);
		$this->echoLog("warningSms: id:$id; edit_ret:$edit_ret;send_ret:{$send_ret['code']}");
	}

	/*
	* 部分还本付息
	*/
	private function accordingToPartRepay($part_id){
		$this->echoLog("offline accordingToPartRepay:  part_id:$part_id");

		$GLOBALS['NEED_XSS_PREVENT'] = false;   //不做XSS过滤

		//开启事务
		Yii::app()->{$this->db_name}->beginTransaction();
		try{
			//累计还款额
			$this->repay_capital_total = 0.00;

			//获取还款计划信息
			$plan_model_name = "{$this->table_prefix}PartialRepay";
			$detail_model_name = "{$this->table_prefix}PartialRepayDetail";
			$plan_info = $plan_model_name::model()->findBySql("select * from offline_partial_repay where id=$part_id for update");
			if(!$plan_info || $plan_info->status != 2){
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '待审核列表数据异常';
				$this->echoLog("offline accordingToPartRepay: id[$part_id]; offline_partial_repay not exist", "email");
				return false;
			}

			//获取部分还款明细
			$repay_detail_ids = $detail_model_name::model()->findAllBySql("select id from offline_partial_repay_detail where partial_repay_id=$part_id and status=1 and repay_status=0 ");
			if(empty($repay_detail_ids)) {
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '还款明细数据异常';
				$this->echoLog("offline accordingToPartRepay: id[$part_id]; offline_partial_repay_detail is empty", "email");
				return false;
			}

			$this->echoLog("offline accordingToPartRepay: id[$part_id]; count:".count($repay_detail_ids).";");

			//循环plan_repay_ids，还本付息
			foreach ($repay_detail_ids as $key => $value) {
				$detail_id = (int)$value['id'];
				//还本付息
				$repay_result = $this->partRepayDetail($detail_id);
				//失败一笔，其他待还数据停止还款
				if($repay_result == false){
					$this->task_remark = empty($this->task_remark) ? '还款失败，请联系技术' : $this->task_remark;
					Yii::app()->{$this->db_name}->rollback();
					$this->echoLog("offline accordingToPartRepay: id[$part_id]; return false; detail_id: $detail_id; ", "email");
					return false;
				}
				usleep(10000); //10毫秒
			}

			//更新还款计划数据

			$edit_data = array();
			$edit_data['task_success_time'] = time();
			$edit_data['status'] = 4;
			$edit_data['task_remark'] = '还款成功';
			$edit_ret = $plan_model_name::model()->updateByPk($plan_info->id, $edit_data);
			if(false == $edit_ret){
				$this->task_remark = '待审核列表更新失败';
				Yii::app()->{$this->db_name}->rollback();
				$this->echoLog("offline accordingToPartRepay: id[$part_id]; edit offline_partial_repay error; ".print_r($edit_data, true), "email");
				return false;
			}

			//全部成功提交事务
			Yii::app()->{$this->db_name}->commit();
			$this->echoLog("offline accordingToPartRepay end; part_id:$part_id");
			return true;
		}catch(Exception $e){
			$this->task_remark = '还款异常，请联系技术';
			Yii::app()->{$this->db_name}->rollback();
			$this->echoLog("offline accordingToPartRepay end; Fail:".print_r($e->getMessage(),true),"email");
			return false;
		}
	}

	/**
	 * 部分还本付息Collection
	 * @param $detail_id
	 * @return bool
	 */
	private function partRepayDetail($detail_id){
		$this->echoLog("offline partRepayDetail: detail_id:$detail_id ");
		if(!is_numeric($detail_id)){
			$this->echoLog("offline partRepayDetail params error", "email");
			return false;
		}

		//要处理的还款计划
		$detail_repay_model = "{$this->table_prefix}PartialRepayDetail";
		$repay_sql = "select * from offline_partial_repay_detail where id=$detail_id for update";
		$detail_repay_info = $detail_repay_model::model()->findBySql($repay_sql);
		if(empty($detail_repay_info)) {
			$this->echoLog("offline partRepayDetail: loan_repay_info is empty");
			return false;
		}
		//还款计划状态校验
		if($detail_repay_info->status != 1 || $detail_repay_info->repay_status!=0) {
			$this->echoLog("offline partRepayDetail: status:{$detail_repay_info->status} or repay_status:{$detail_repay_info->repay_status} error;");
			return false;
		}

		//项目信息校验
		$deal_model = "{$this->table_prefix}Deal";
		$deal_info = $deal_model::model()->findBySql("select * from offline_deal where id=$detail_repay_info->deal_id for update");
		if(!$deal_info || $deal_info->deal_status != 4){
			$this->echoLog("offline partRepayDetail: offline_deal is empty or status[$deal_info->deal_status] != 4");
			return false;
		}

		//投资记录校验
		$loan_model = "{$this->table_prefix}DealLoad";
		$loan_sql = "select * from offline_deal_load where id=$detail_repay_info->deal_loan_id for update";
		$load_info = $loan_model::model()->findBySql($loan_sql);
		if(empty($load_info) || $load_info->status != 1) {
			$this->echoLog("offline partRepayDetail: offline_deal_load.id:$detail_repay_info->deal_loan_id is empty");
			return false;
		}

		//更新还款明细数据
		$detail_repay_info->repay_yestime = time();
		$detail_repay_info->repay_status = 1;
		if(false == $detail_repay_info->save(true, array('repay_yestime', 'repay_status'))) {
			$this->echoLog("offline partRepayDetail: update offline_partial_repay_detail false;".print_r($detail_repay_info->getErrors(), true));
			return false;
		}

		//待还本金校验
		$rate = round($detail_repay_info->repay_money/$load_info->wait_capital, 6);
		$wait_capital = bcsub($load_info->wait_capital, $detail_repay_info->repay_money, 2);
		if(FunctionUtil::float_bigger(0.00, $wait_capital)){
			$this->echoLog("offline partRepayDetail: wait_capital[$wait_capital] < 0");
			return false;
		}
		//更新剩余本金
		$load_info->wait_capital = $wait_capital;
		if(FunctionUtil::float_bigger_equal(0.00, $wait_capital, 2) && FunctionUtil::float_bigger_equal(0.00, $load_info->wait_interest, 2) ){
			$load_info->status = 2;
		}
		if(false == $load_info->save(true, array('wait_capital', 'status'))) {
			$this->echoLog("offline partRepayDetail: update deal_load false;".print_r($load_info->getErrors(), true));
			return false;
		}
		//更新还款计划
		$loan_repay_model = "{$this->table_prefix}DealLoanRepay";
		$load_repay_sql = "select * from offline_deal_loan_repay where deal_loan_id=$detail_repay_info->deal_loan_id and type=1 and status=0 and money>0 for update ";
		$loan_repay_list = Yii::app()->{$this->db_name}->createCommand($load_repay_sql)->queryAll();
		if(!$loan_repay_list){
			$this->echoLog("offline partRepayDetail: offline_deal_load error ");
			return false;
		}

		//获取还款统计表数据
		$stat_model_name = "{$this->table_prefix}StatRepay";
		$stat_sql = "select * from offline_stat_repay where deal_id={$detail_repay_info->deal_id} and repay_status=0 and repay_type=1 for update";
		$stat_info = $stat_model_name::model()->findAllBySql($stat_sql);
		if(!$stat_info){
			$this->echoLog("offline partRepayDetail: offline_stat_repay error ");
			return false;
		}

		//待还待更新条数一致性校验
		if(count($stat_info) != count($loan_repay_list) ){
			$this->echoLog("offline partRepayDetail: count_stat_info != count_loan_repay_list ");
			return false;
		}

		//拼接还款计划更新sql
		$repay_sql_values = '';
		$repay_total = 0.00;
		$repay_data = array();
		foreach($loan_repay_list as $key=>$l_repay){
			$edit_repay_data = [];
			$edit_repay_data['id'] = $l_repay['id'];
			$edit_repay_data['last_part_repay_time'] = time();
			//最后一期误差消除
			if($key+1 == count($loan_repay_list)){
				$repaid_money = bcsub($detail_repay_info->repay_money,$repay_total,2);
			}else{
				$loan_wait_capital = $l_repay['money'];
				$repaid_money = round($loan_wait_capital*$rate, 2);
			}
			$edit_repay_data['money'] = bcsub($l_repay['money'], $repaid_money, 2);;
			$edit_repay_data['repaid_amount'] = bcadd($l_repay['repaid_amount'], $repaid_money, 2);
			$repay_total = bcadd($repay_total, $repaid_money, 2);
			//还款金额异常
			/*
			if(FunctionUtil::float_bigger($edit_repay_data['repaid_amount'], $l_repay['money'], 2)){
				$this->task_remark = '还款金额异常';
				$this->echoLog("offline partRepayDetail; repaid_amount: {$edit_repay_data['repaid_amount']} > money:{$l_repay['money']} ");
				return false;
			}*/
			//是否已还清
			$midnight_time = strtotime("midnight");
			$edit_repay_data['status'] = $l_repay['status'];
			$edit_repay_data['real_time'] = 0;
			if(FunctionUtil::float_equal($edit_repay_data['repaid_amount'], $l_repay['money'], 2)){
				$edit_repay_data['status'] = 1;
				$edit_repay_data['real_time'] = $midnight_time-60*60*8;
			}

			//拼接sql
			$repay_sql_values .= "(".implode(",", $edit_repay_data)."),";
			$repay_data[$l_repay['time']] = $repaid_money;
		}

		//更新还款计划
		$repay_sql_values = rtrim($repay_sql_values, ",");
		if(!empty($repay_sql_values)) {
			$sql = "INSERT INTO offline_deal_loan_repay (id, last_part_repay_time, money,repaid_amount, status,real_time ) VALUES $repay_sql_values ON DUPLICATE KEY".
				" UPDATE  last_part_repay_time=VALUES(last_part_repay_time),money=VALUES(money),repaid_amount=VALUES(repaid_amount), status=VALUES(status), real_time=VALUES(real_time)";
			self::echoLog('accordingToPartRepay: update offline_deal_loan_repay sql '.$sql);
			$command = Yii::app()->{$this->db_name}->createCommand($sql)->execute();
			if(false === $command) {
				$this->task_remark = '还款列表更新失败';
				$this->echoLog("offline partRepayDetail; update offline_deal_loan_repay error ");
				return false;
			}
		}
		//拼接还款统计表批量更新sql
		$stat_sql_values = '';
		$repay_times = array_keys($repay_data);
		foreach($stat_info as $s_repay){
			if(!in_array($s_repay['loan_repay_time'],$repay_times)){
				$this->task_remark = '还款计划更新失败';
				$this->echoLog("offline partRepayDetail; stat_repay loan_repay_time[{$s_repay['loan_repay_time']}] error ");
				return false;
			}

			$edit_stat_data = [];
			$edit_stat_data['id'] = $s_repay['id'];
			$edit_stat_data['last_part_repay_time'] = time();
			$edit_stat_data['repaid_amount'] = bcadd($s_repay['repaid_amount'], $repay_data[$s_repay['loan_repay_time']], 2);
			//还款金额异常
			if(FunctionUtil::float_bigger($edit_stat_data['repaid_amount'], $s_repay['repay_amount'], 2)){
				$this->task_remark = '统计表还款金额异常';
				$this->echoLog("offline partRepayDetail; repaid_amount: {$edit_stat_data['repaid_amount']} > repay_amount:{$s_repay['repay_amount']} ");
				return false;
			}
			//是否已还清

			$edit_stat_data['repay_status'] = $s_repay['repay_status'];
			$edit_stat_data['repay_yestime'] = 0;
			if(FunctionUtil::float_equal($edit_stat_data['repaid_amount'], $s_repay['repay_amount'], 2)){
				$edit_stat_data['repay_status'] = 1;
				$edit_stat_data['repay_yestime'] = time();
			}
			//拼接sql
			$stat_sql_values .= "(".implode(",", $edit_stat_data)."),";
		}

		//更新还款计划
		$stat_sql_values = rtrim($stat_sql_values, ",");
		if(!empty($stat_sql_values)) {
			$sql = "INSERT INTO offline_stat_repay (id, last_part_repay_time, repaid_amount, repay_status,repay_yestime ) VALUES $stat_sql_values ON DUPLICATE KEY".
				" UPDATE  last_part_repay_time=VALUES(last_part_repay_time),repaid_amount=VALUES(repaid_amount), repay_status=VALUES(repay_status), repay_yestime=VALUES(repay_yestime)";
			self::echoLog('accordingToPartRepay: update offline_stat_repay sql '.$sql);
			$command = Yii::app()->{$this->db_name}->createCommand($sql)->execute();
			if(false === $command) {
				$this->task_remark = '待还列表更新失败';
				$this->echoLog("offline partRepayDetail; update offline_stat_repay error ");
				return false;
			}
		}

		//更新项目信息，判断项目是否还完
		$count = $loan_repay_model::model()->count("status=0 and deal_id=$detail_repay_info->deal_id ");
		if($count == 0){
			//更新为已结清
			$edit_ret = $deal_model::model()->updateByPk($detail_repay_info->deal_id, ['deal_status'=>5]);
			if(false == $edit_ret){
				$this->echoLog("offline partRepayDetail: offline_deal edit deal_status=5 error ");
				return false;
			}
		}

		//线上还款账户变更&记录流水

		//短信或者站内信通知

		$this->echoLog("offline partRepayDetail end:  detail_id:$detail_id");
		return true;
	}

	/**
	 * 获取出借人端还款计划
	 */
	public function getLoanRepayList($deal_id){
		if($deal_id<=0 || !is_numeric($deal_id) || empty($this->global_conditions)){
			$this->echoLog("getLoanRepayList: params error, deal_id: $deal_id");
			return false;
		}
		//待还数据
		$repay_sql = "select id, loan_user_id, deal_id, money, type, status from offline_deal_loan_repay where $this->global_conditions ";
		$loan_repay_list = Yii::app()->{$this->db_name}->createCommand($repay_sql)->queryAll();
		if(empty($loan_repay_list)){
			$this->echoLog("getLoanRepayList: repay_list is empty");
			return false;
		}
		return $loan_repay_list;
	}

	/**
	 * 日志记录
	 * @param $yiilog
	 * @param string $level
	 */
	public function echoLog($yiilog, $level = "info") {
		echo date('Y-m-d H:i:s ')." ".microtime()." settlement {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("settlement: {$yiilog}", $level);
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
	 * 统计借款人待还数据
	 * @param $deal_id
	 * @return bool
	 */
	public function actionStatRepay($deal_id=0){
		self::echoLog(" statRepay Start !!! ");

		try{
			$dbname = 'offlinedb';
			//待还项目ID引入
			$deal_ids = Yii::app()->c->wait_deal_id;
			if(!empty($deal_id)){
				if(!is_numeric($deal_id)){
					self::echoLog("statRepay deal_id[{$deal_id}] error end ");
					return false;
				}
				$deal_ids = [$deal_id];
			}

			//待处理数据
			if(empty($deal_ids)){
				self::echoLog("statRepay end: deal_ids is empty ");
				return false;
			}

			self::echoLog("statRepay: count deal_ids: ".count($deal_ids));
			//逐一生成
			foreach($deal_ids as $deal_id){
				self::echoLog("deal_id[{$deal_id}] start");
				//校验是否已经生成
				$stat_sql = "select count(1) as total_repay from offline_stat_repay where deal_id=$deal_id ";
				$repay_info = Yii::app()->$dbname->createCommand($stat_sql)->queryRow();
				if($repay_info['total_repay'] > 0){
					self::echoLog(" statRepay End step01,offline_stat_repay deal_id[{$deal_id}] already exist !!! ", "error");
					continue;
				}

				//项目信息获取+咨询方信息
				$deal_sql = "select d.platform_id,d.id,d.user_id,d.agency_id as deal_agency_id ,da.name as deal_agency_name ,jys.name as jys_name ,d.deal_type,d.project_id,p.product_class,d.name,d.jys_record_number,p.name as project_name,
				  			 d.borrow_amount,d.rate,d.repay_time,d.loantype,d.repay_start_time,d.advisory_id,a.name as agency_name 
							 from offline_deal d 
							 left join offline_deal_project p on d.project_id=p.id 
							 left join offline_deal_agency a on a.id=d.advisory_id 
							 left join offline_deal_agency jys on jys.id=d.jys_id 
							 left join offline_deal_agency da on da.id=d.agency_id 
							 where d.id=$deal_id and d.deal_status=4  ";
				$deal_info = Yii::app()->$dbname->createCommand($deal_sql)->queryRow();
				if(empty($deal_info)){
					self::echoLog(" statRepay End step02, deal_id[$deal_id] offline_deal.offline_deal_project.offline_deal_agency error !!! ", "error");
					continue;
				}


				//借款人信息获取
				$user_sql = "select u.id,u.real_name,u.user_type,e.company_name  
							from firstp2p_user u 
							left join firstp2p_enterprise e on e.user_id=u.id 
							where u.id={$deal_info['user_id']}";
				$user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
				if(empty($user_info)){
					self::echoLog(" statRepay End step03, deal_id[$deal_id] firstp2p_user[{$deal_info['user_id']}] does not exist !!! ", "error");
					continue;
				}

				//待还还款计划
				$repay_sql = "select sum(money) as wait_money,time,type from offline_deal_loan_repay where status=0 and deal_id=$deal_id and type in (1,2) and money>0 group by `time`,type ";
				$loan_repay_list = Yii::app()->$dbname->createCommand($repay_sql)->queryAll();
				if(empty($loan_repay_list)){
					self::echoLog(" statRepay End step04, deal_id[$deal_id] offline_deal_loan_repay error !!! ", "error");
					continue;
				}

				//借款企业名称
				$company_name = $user_info['user_type'] == 1 ? $user_info['company_name'] : $user_info['real_name'];


				//拼接写入数据
				$stat_repay_str = $stat_repay_key = '';
				foreach ($loan_repay_list as $k => $v) {
					$_stat_repay = array(
						'deal_id' => $deal_id,
						'deal_type' => $deal_info['deal_type'],
						'platform_id' => $deal_info['platform_id'],
						'project_id' => $deal_info['project_id'],
						'project_product_class' => $deal_info['product_class'],
						'deal_name' => $deal_info['name'],
						'jys_name' => $deal_info['jys_name'],
						'deal_agency_id' => $deal_info['deal_agency_id'],
						'deal_agency_name' => $deal_info['deal_agency_name'],
						'jys_record_number' => $deal_info['jys_record_number'],
						'project_name' => $deal_info['project_name'],
						'borrow_amount' => $deal_info['borrow_amount'],
						'deal_rate' => $deal_info['rate'],
						'deal_repay_time' => $deal_info['repay_time'],
						'deal_loantype' => $deal_info['loantype'],
						'deal_repay_start_time' => $deal_info['repay_start_time'],
						'deal_advisory_id' => $deal_info['advisory_id'],
						'deal_advisory_name' => $deal_info['agency_name'],
						'deal_user_id' => $user_info['id'],
						'deal_user_real_name' => $company_name,
						'loan_repay_time' => $v['time'],
						'repay_amount' => $v['wait_money'],
						'repay_type' => $v['type'],
						'repay_status' => 0,
						'addtime' => time(),
					);
					$stat_repay_str .= "( '".  implode("','", $_stat_repay) ."' ),";
					//获取key
					$stat_repay_key = array_keys($_stat_repay);
				}

				//写入数据校验
				if(empty($stat_repay_str) || empty($stat_repay_key)) {
					self::echoLog(" deal_id[{$deal_id}] End step05, deal_id[$deal_id] stat_repay_str or stat_repay_key error !!! ", 'error');
					continue;
				}

				//批量写入
				$stat_repay_str = rtrim($stat_repay_str, ',');
				$stat_repay_key = implode(",", $stat_repay_key);
				$sql = "INSERT INTO offline_stat_repay (".$stat_repay_key.") VALUES $stat_repay_str";
				$result = Yii::app()->$dbname->createCommand($sql)->execute();
				if (false == $result){
					self::echoLog("deal_id[{$deal_id}] End step06, insert into offline_stat_repay fail; sql:$sql", 'error');
					return false;
				}

				self::echoLog("deal_id[{$deal_id}] success");
			}

			self::echoLog(" statRepay End!!! ");
		}catch(Exception $e){
			self::echoLog(" statRepay Exception:".print_r($e->getMessage(),true), 'error');
		}
	}

}
