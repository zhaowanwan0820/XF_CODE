<?php


class SettlementCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/settlement';	//项目ID文件锁的前缀
	public $fnLock_deal_id = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $repayment_fnLock = '/tmp/repayment_fnLock_01.pid';
	public $part_repayment_fnLock = '/tmp/part_repayment_fnLock.pid';
	public $table_prefix = '';
	public $db_name = 'db';
	public $global_conditions = '';
	public $repay_capital_total = 0.00;
	public $repay_interest_total = 0.00;
	public $loan_repay_type = [];
	private $task_remark='';

	/**
	 * wx还款脚本[目前只支持尊享]
	 * @param $type 1尊享 2普惠
	 * @return bool
	 */
	public function actionRepayment($type=1){
		self::echoLog("repayment Start type:$type");

		//区分数据库
		if(!in_array($type, [1,2])){
			$this->echoLog("repayment end: type[$type] not in (1,2)");
			return false;
		}

		//普惠
		if($type == 2){
			$this->db_name = "phdb";
			$this->table_prefix = "PH";
		}

		try {
			//产品化取正常还本息的数据
			$t_midnight = strtotime("midnight");
			$plan_sql = " select * from ag_wx_repayment_plan where status=1 and plan_time=$t_midnight order by addtime asc";
			$repay_model_name = "{$this->table_prefix}WxRepaymentPlan";
			$repay_plan_info = $repay_model_name::model()->findBySql($plan_sql);
			if(empty($repay_plan_info)){
				self::echoLog("repayment: No data!!!");
				return false;
			}

			//根据项目加文件锁,不允许同一个项目同时执行
			$fpLock = $this->enterBorrowIdFnLock($repay_plan_info->deal_id);

			//校验计划还款表的数据
			$check_plan_info = PrePaymentService::getInstance()->checkRepaymentPlan($repay_plan_info->id, $type);
			if($check_plan_info['code'] != 0 || empty($check_plan_info['data']['conditions'])){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'repayment.checkRepaymentPlan');
				self::echoLog("repayment: checkRepaymentPlan error code={$check_plan_info['code']};");
				$this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));
				return false;
			}

			//数据条件
			$this->global_conditions .= $check_plan_info['data']['conditions'];

			// 过期项目债权
			/* C1用户债转开放后打开
			$expire_result = $this->debtExpired($repay_plan_info->deal_id);
			if (false == $expire_result){
				self::echoLog("actionRepayment: debtExpired return false", "email");
				$this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));;
				return false;
			}*/

			//根据项目ID, 还款确认
			$result = $this->accordingToLoanRepay($repay_plan_info->deal_id, $repay_plan_info->id);
			if($result == false){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'repayment.accordingToLoanRepay');
				self::echoLog("repayment: accordingToLoanRepay return false;", "email");
			}

			//释放文件锁
			$this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));
			$this->echoLog("repayment end");
		} catch (Exception $ee) {
			self::echoLog("repayment Exception,error_msg:".print_r($ee->getMessage(),true), "email");
 			$this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));
		}
		$this->warningEmail();
	}

	/**
	 * wx部分还款脚本[目前只支持尊享]
	 * @param $type 1尊享 2普惠
	 * @return bool
	 */
	public function actionPartRepayment($type=1){
		self::echoLog("partRepayment Start type:$type");

		//文件锁
		$fpLock = self::enterLock(array('fnLock'=>$this->part_repayment_fnLock));
		if(!$fpLock){
			self::echoLog(' partRepayment Commands Having Run!!!');
			return false;
		}
		//区分数据库
		if(!in_array($type, [1,2])){
			$this->echoLog("partRepayment end: type[$type] not in (1,2)");
			self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
			return false;
		}

		//普惠
		if($type == 2){
			//第一期只支持尊享,支持普惠时打开限制
			$this->db_name = "phdb";
			$this->table_prefix = "PH";
		}

		try {
			//产品化取正常还本息的数据
			$t_midnight = strtotime("midnight");
			$plan_sql = " select * from ag_wx_partial_repayment where status=2 and pay_plan_time=$t_midnight order by addtime asc";
			$repay_model_name = "{$this->table_prefix}AgWxPartialRepayment";
			$repay_plan_info = $repay_model_name::model()->findBySql($plan_sql);
			if(empty($repay_plan_info)){
				self::echoLog("partRepayment: No data!!!");
				self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
				return false;
			}

			//校验计划还款表的数据
			$check_plan_info = PrePaymentService::getInstance()->checkPartRepayment($repay_plan_info->id, $type);
			if($check_plan_info['code'] != 0){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'parRepayment.checkPartRepayment');
				self::echoLog("actionRepayment: checkRepaymentPlan error code={$check_plan_info['code']} info:{$check_plan_info['info']};");
				self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
				return false;
			}
			//根据项目ID, 还款确认
			$result = $this->accordingToPartRepay($repay_plan_info->id);
			if($result == false){
				$this->warningSms($repay_model_name, $repay_plan_info->id, 'parRepayment.accordingToPartRepay');
				$this->echoLog("partRepayment accordingToPartRepay return false,repay_id:$repay_plan_info->id; ", "email");
			}

			//释放文件锁
			self::releaseLock(array('fnLock'=>$this->part_repayment_fnLock, 'fpLock'=>$fpLock));
			$this->echoLog("partRepayment end");
		} catch (Exception $ee) {
			self::echoLog("partRepayment Exception,error_msg:".print_r($ee->getMessage(),true), "email");
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
		$this->echoLog("accordingToPartRepay:  part_id:$part_id");

		$GLOBALS['NEED_XSS_PREVENT'] = false;   //不做XSS过滤

		//开启事务
		Yii::app()->{$this->db_name}->beginTransaction();
		try{
			//累计还款额
			$this->repay_capital_total = 0.00;

			//获取还款计划信息
			$plan_model_name = "{$this->table_prefix}AgWxPartialRepayment";
			$detail_model_name = "{$this->table_prefix}AgWxPartialRepayDetail";
			$plan_info = $plan_model_name::model()->findBySql("select * from ag_wx_partial_repayment where id=$part_id for update");
			if(!$plan_info || $plan_info->status != 2){
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '待审核列表数据异常';
				$this->echoLog("accordingToPartRepay: id[$part_id]; ag_wx_partial_repayment not exist", "email");
				return false;
			}

			//获取部分还款明细
			$repay_detail_ids = $detail_model_name::model()->findAllBySql("select id from ag_wx_partial_repay_detail where partial_repay_id=$part_id and status=1 and repay_status=0 ");
			if(empty($repay_detail_ids)) {
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '还款明细数据异常';
				$this->echoLog("accordingToPartRepay: id[$part_id]; ag_wx_partial_repay_detail is empty", "email");
				return false;
			}

			$this->echoLog("accordingToPartRepay: id[$part_id]; count:".count($repay_detail_ids).";");

			//循环plan_repay_ids，还本付息
			foreach ($repay_detail_ids as $key => $value) {
				$detail_id = (int)$value['id'];
				//还本付息
				$repay_result = $this->partRepayDetail($detail_id);
				//失败一笔，其他待还数据停止还款
				if($repay_result == false){
					$this->task_remark = empty($this->task_remark) ? '还款失败，请联系技术' : $this->task_remark;
					Yii::app()->{$this->db_name}->rollback();
					$this->echoLog("accordingToPartRepay: id[$part_id]; return false; detail_id: $detail_id; ", "email");
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
				$this->echoLog("accordingToPartRepay: id[$part_id]; edit ag_wx_partial_repayment error; ".print_r($edit_data, true), "email");
				return false;
			}

			//全部成功提交事务
			Yii::app()->{$this->db_name}->commit();
			$this->echoLog("accordingToPartRepay end; part_id:$part_id");
			return true;
		}catch(Exception $e){
			$this->task_remark = '还款异常，请联系技术';
			Yii::app()->{$this->db_name}->rollback();
			$this->echoLog("accordingToPartRepay end; Fail:".print_r($e->getMessage(),true),"email");
			return false;
		}
	}

	/**
	 * 部分还本付息Collection
	 * @param $detail_id
	 * @return bool
	 */
	private function partRepayDetail($detail_id){
		$this->echoLog("partRepayDetail: detail_id:$detail_id ");
		if(!is_numeric($detail_id)){
			$this->echoLog("partRepayDetail params error", "email");
			return false;
		}

		//要处理的还款计划
		$detail_repay_model = "{$this->table_prefix}AgWxPartialRepayDetail";
		$repay_sql = "select * from ag_wx_partial_repay_detail where id=$detail_id for update";
		$detail_repay_info = $detail_repay_model::model()->findBySql($repay_sql);
		if(empty($detail_repay_info)) {
			$this->echoLog("partRepayDetail: loan_repay_info is empty");
			return false;
		}
		//还款计划状态校验
		if($detail_repay_info->status != 1 || $detail_repay_info->repay_status!=0) {
			$this->echoLog("partRepayDetail: status:{$detail_repay_info->status} or repay_status:{$detail_repay_info->repay_status} error;");
			return false;
		}

		//项目信息校验
		$deal_model = "{$this->table_prefix}Deal";
		$deal_info = $deal_model::model()->findBySql("select * from firstp2p_deal where id=$detail_repay_info->deal_id for update");
		if(!$deal_info || $deal_info->deal_status != 4){
			$this->echoLog("partRepayDetail: firstp2p_deal is empty or status[$deal_info->deal_status] != 4");
			return false;
		}

		//投资记录校验
		$loan_model = "{$this->table_prefix}DealLoad";
		$loan_sql = "select * from firstp2p_deal_load where id=$detail_repay_info->deal_loan_id for update";
		$load_info = $loan_model::model()->findBySql($loan_sql);
		if(empty($load_info) || $load_info->status != 1) {
			$this->echoLog("partRepayDetail: firstp2p_deal_load.id:$detail_repay_info->deal_loan_id is empty");
			return false;
		}

		//更新还款明细数据
		$detail_repay_info->repay_yestime = time();
		$detail_repay_info->repay_status = 1;
		if(false == $detail_repay_info->save(true, array('repay_yestime', 'repay_status'))) {
			$this->echoLog("partRepayDetail: update ag_wx_partial_repay_detail false;".print_r($detail_repay_info->getErrors(), true));
			return false;
		}

		//待还本金校验
		$rate = round($detail_repay_info->repay_money/$load_info->wait_capital, 6);
		$wait_capital = bcsub($load_info->wait_capital, $detail_repay_info->repay_money, 2);
		if(FunctionUtil::float_bigger(0.00, $wait_capital)){
			$this->echoLog("partRepayDetail: wait_capital[$wait_capital] < 0");
			return false;
		}
		//更新剩余本金
		$load_info->wait_capital = $wait_capital;
		if(FunctionUtil::float_bigger_equal(0.00, $wait_capital, 2) && FunctionUtil::float_bigger_equal(0.00, $load_info->wait_interest, 2) ){
			$load_info->status = 2;
		}
		if(false == $load_info->save(true, array('wait_capital', 'status'))) {
			$this->echoLog("partRepayDetail: update deal_load false;".print_r($load_info->getErrors(), true));
			return false;
		}
		//更新还款计划
		$loan_repay_model = "{$this->table_prefix}DealLoanRepay";
		$load_repay_sql = "select * from firstp2p_deal_loan_repay where deal_loan_id=$detail_repay_info->deal_loan_id and type=1 and status=0 and money>0 for update ";
		$loan_repay_list = Yii::app()->{$this->db_name}->createCommand($load_repay_sql)->queryAll();
		if(!$loan_repay_list){
			$this->echoLog("partRepayDetail: firstp2p_deal_load error ");
			return false;
		}

		//获取还款统计表数据
		$stat_model_name = "{$this->table_prefix}WxStatRepay";
		$stat_sql = "select * from ag_wx_stat_repay where deal_id={$detail_repay_info->deal_id} and repay_status=0 and repay_type=1 for update";
		$stat_info = $stat_model_name::model()->findAllBySql($stat_sql);
		if(!$stat_info){
			$this->echoLog("partRepayDetail: ag_wx_stat_repay error ");
			return false;
		}

		//待还待更新条数一致性校验
		if(count($stat_info) != count($loan_repay_list) ){
			$this->echoLog("partRepayDetail: count_stat_info != count_loan_repay_list ");
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
				$this->echoLog("partRepayDetail; repaid_amount: {$edit_repay_data['repaid_amount']} > money:{$l_repay['money']} ");
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
			$sql = "INSERT INTO firstp2p_deal_loan_repay (id, last_part_repay_time, money,repaid_amount, status,real_time ) VALUES $repay_sql_values ON DUPLICATE KEY".
				" UPDATE  last_part_repay_time=VALUES(last_part_repay_time),money=VALUES(money),repaid_amount=VALUES(repaid_amount), status=VALUES(status), real_time=VALUES(real_time)";
			self::echoLog('accordingToPartRepay: update firstp2p_deal_loan_repay sql '.$sql);
			$command = Yii::app()->{$this->db_name}->createCommand($sql)->execute();
			if(false === $command) {
				$this->task_remark = '还款列表更新失败';
				$this->echoLog("partRepayDetail; update firstp2p_deal_loan_repay error ");
				return false;
			}
		}
		//拼接还款统计表批量更新sql
		$stat_sql_values = '';
		$repay_times = array_keys($repay_data);
		foreach($stat_info as $s_repay){
			if(!in_array($s_repay['loan_repay_time'],$repay_times)){
				$this->task_remark = '还款计划更新失败';
				$this->echoLog("partRepayDetail; stat_repay loan_repay_time[{$s_repay['loan_repay_time']}] error ");
				return false;
			}

			$edit_stat_data = [];
			$edit_stat_data['id'] = $s_repay['id'];
			$edit_stat_data['last_part_repay_time'] = time();
			$edit_stat_data['repaid_amount'] = bcadd($s_repay['repaid_amount'], $repay_data[$s_repay['loan_repay_time']], 2);
			//还款金额异常
			if(FunctionUtil::float_bigger($edit_stat_data['repaid_amount'], $s_repay['repay_amount'], 2)){
				$this->task_remark = '统计表还款金额异常';
				$this->echoLog("partRepayDetail; repaid_amount: {$edit_stat_data['repaid_amount']} > repay_amount:{$s_repay['repay_amount']} ");
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
			$sql = "INSERT INTO ag_wx_stat_repay (id, last_part_repay_time, repaid_amount, repay_status,repay_yestime ) VALUES $stat_sql_values ON DUPLICATE KEY".
				" UPDATE  last_part_repay_time=VALUES(last_part_repay_time),repaid_amount=VALUES(repaid_amount), repay_status=VALUES(repay_status), repay_yestime=VALUES(repay_yestime)";
			self::echoLog('accordingToPartRepay: update ag_wx_stat_repay sql '.$sql);
			$command = Yii::app()->{$this->db_name}->createCommand($sql)->execute();
			if(false === $command) {
				$this->task_remark = '待还列表更新失败';
				$this->echoLog("partRepayDetail; update ag_wx_stat_repay error ");
				return false;
			}
		}

		//更新项目信息，判断项目是否还完
		$count = $loan_repay_model::model()->count("status=0 and deal_id=$detail_repay_info->deal_id ");
		if($count == 0){
			//更新为已结清
			$edit_ret = $deal_model::model()->updateByPk($detail_repay_info->deal_id, ['deal_status'=>5]);
			if(false == $edit_ret){
				$this->echoLog("partRepayDetail: firstp2p_deal edit deal_status=5 error ");
				return false;
			}
		}

		//线上还款账户变更&记录流水

		//短信或者站内信通知

		$this->echoLog("partRepayDetail end:  detail_id:$detail_id");
		return true;
	}

	/**
	 * 过期项目的债权
	 * @param $deal_id 项目ID
	 * @return bool
	 */
	private function debtExpired($deal_id){
		self::echoLog("debtExpired: Expire Debt Start, deal_id: $deal_id");
		//基本参数验证
		if($deal_id<=0 || !is_numeric($deal_id)){
			self::echoLog("debtExpired: params error; deal_id=$deal_id", "error");
			return false;
		}
		//过期项目的债权
		$debt_model_name = "{$this->table_prefix}Debt";
		$obj_debts = $debt_model_name::model()->findAll("deal_id=$deal_id and status=1");
        if(!$obj_debts){
			self::echoLog("debtExpired: Expire Debt End!!!");
			return true;
		}
		//逐一过期
		$expire_fail_count = 0;     //过期债权失败的条数
		foreach($obj_debts as $debt){
			self::echoLog("debtExpired: Expire Debt; deal_id=$deal_id, debt_id={$debt->id}");
			$debt_result = DebtService::getInstance()->CancelDebt($debt->id, 4);
			if($debt_result['code'] != '0'){
				self::echoLog("debtExpired: Expire Debt Wrong; deal_id=$deal_id, debt_id={$debt->id}, result: ".print_r($debt_result, true), "error");
				$expire_fail_count += 1;
			}
		}

		//存在过期失败的债权
		if($expire_fail_count > 0){
			self::echoLog("debtExpired: expire_fail_count: $expire_fail_count", "email");
			return false;
		}

		self::echoLog("debtExpired: Expire Debt End!!!");
		return true;
	}

	/*
	* 根据项目ID还本付息
	*/
	private function accordingToLoanRepay($deal_id, $plan_id){
		$this->echoLog("accordingToLoanRepay: deal_id:$deal_id, plan_id:$plan_id");

		$GLOBALS['NEED_XSS_PREVENT'] = false;   //不做XSS过滤

		//开启事务
		Yii::app()->{$this->db_name}->beginTransaction();
		try{

			//累计还款额
			$this->repay_capital_total = 0.00;
			$this->repay_interest_total = 0.00;

			//获取还款计划信息
			$plan_model_name = "{$this->table_prefix}WxRepaymentPlan";
			$plan_info = $plan_model_name::model()->findBySql("select * from ag_wx_repayment_plan where id=$plan_id for update");
			if(!$plan_info){
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '待审核列表数据异常';
				$this->echoLog("accordingToLoanRepay: deal_id[$deal_id]; ag_wx_repayment_plan.id[$plan_id] not exist", "email");
				return false;
			}

			//暂不支持线上还款
			if($plan_info->repayment_form == 1){
				$this->task_remark = '暂不支持线上还款';
				$this->echoLog("accordingToLoanRepay: repayment_form[$plan_info->repayment_form] error", "email");
				return false;
			}

			//获取还款统计表数据
			$stat_model_name = "{$this->table_prefix}WxStatRepay";
			$stat_sql = "select * from ag_wx_stat_repay where deal_id={$plan_info->deal_id} and loan_repay_time={$plan_info->normal_time} for update";
			$stat_info = $stat_model_name::model()->findAllBySql($stat_sql);
			if(!$stat_info){
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '还款列表数据异常';
				$this->echoLog("accordingToLoanRepay: ag_wx_stat_repay.deal_id={$plan_info->deal_id} and loan_repay_time={$plan_info->normal_time} not exist", "email");
				return false;
			}

			//获取plan_repay_ids
			$plan_repay_ids = $this->getPlanRepayIds();
			if($plan_repay_ids == false || empty($plan_repay_ids)) {
				Yii::app()->{$this->db_name}->rollback();
				$this->task_remark = '出借人还款计划数据异常';
				$this->echoLog("accordingToLoanRepay: getPlanRepayIds is empty", "email");
				return false;
			}

			$this->echoLog("repayLoanRepay count.getPlanRepayIds:".count($plan_repay_ids)."; repayment_form:$plan_info->repayment_form");

			//循环plan_repay_ids，还本付息
			foreach ($plan_repay_ids as $key => $value) {
				$plan_repay_id = (int)$value['id'];
				//还本付息
				$repay_result = $this->repayLoanRepay($plan_repay_id, $plan_info->repayment_form);
				//失败一笔，其他待还数据停止还款
				if($repay_result == false){
					$this->task_remark = empty($this->task_remark) ? '还款失败，请联系技术' : $this->task_remark;
					Yii::app()->{$this->db_name}->rollback();
					$this->echoLog("repayLoanRepay return false; plan_repay_id: $plan_repay_id; ", "email");
					return false;
				}
			}

			//拼接批量更新sql
			$stat_sql_values = '';
			foreach($stat_info as $stat_repay){
				$stat_data = [];
				$stat_data['id'] = $stat_repay->id;
				$stat_data['repay_yestime'] = time();
				//本金+本息
				if(in_array($plan_info->loan_repay_type, [1,3]) && $stat_repay->repay_type == 1){
					$stat_data['repaid_amount'] = bcadd($stat_repay->repaid_amount, $this->repay_capital_total, 2);
				}
				//利息+本息
				if(in_array($plan_info->loan_repay_type, [2,3]) && $stat_repay->repay_type == 2){
					$stat_data['repaid_amount'] = bcadd($stat_repay->repaid_amount, $this->repay_interest_total, 2);
				}

				//只更新匹配数据
				if(!isset($stat_data['repaid_amount'])){
					continue;
				}

				//还款金额异常
				if(FunctionUtil::float_bigger($stat_data['repaid_amount'], $stat_repay->repay_amount, 2)){
					$this->task_remark = '还款金额异常';
					Yii::app()->{$this->db_name}->rollback();
					$this->echoLog("repayLoanRepay return false; repay_amount: $stat_repay->repay_amount; repaid_amount:{$stat_data['repaid_amount']} ", "email");
					return false;
				}

				//已还清
				$stat_data['repay_status'] = $stat_repay->repay_status;
				if(FunctionUtil::float_equal($stat_data['repaid_amount'], $stat_repay->repay_amount, 2)){
					$stat_data['repay_status'] = 1;
				}
				//拼接sql
				$stat_sql_values .= "(".implode(",", $stat_data)."),";
			}

			//更新stat_repay
			$stat_sql_values = rtrim($stat_sql_values, ",");
			if(!empty($stat_sql_values)) {
				$sql = "INSERT INTO ag_wx_stat_repay (id, repay_yestime, repaid_amount, repay_status) VALUES $stat_sql_values ON DUPLICATE KEY".
					  " UPDATE  repay_yestime=VALUES(repay_yestime),repaid_amount=VALUES(repaid_amount), repay_status=VALUES(repay_status)";
				self::echoLog('repayLoanRepay: update ag_wx_stat_repay sql '.$sql);
				$command = Yii::app()->{$this->db_name}->createCommand($sql)->execute();
				if(false === $command) {
					$this->task_remark = '还款列表更新失败';
					Yii::app()->{$this->db_name}->rollback();
					self::echoLog("repayLoanRepay update ag_wx_stat_repay false: ".print_r($command->errors,true), "email");
					return false;
				}
			}

			//更新还款计划数据
			$plan_info->task_success_time = time();
			$plan_info->status = 3;
			$plan_info->task_remark = '还款成功';
			if(false == $plan_info->save()){
				$this->task_remark = '待审核列表更新失败';
				Yii::app()->{$this->db_name}->rollback();
				$this->echoLog("accordingToLoanRepay; ag_wx_repayment_plan.id[$plan_id] edit status=3 error; ", "email");
				return false;
			}

			//全部成功提交事务
			Yii::app()->{$this->db_name}->commit();
			$this->echoLog("accordingToLoanRepay: deal_id:$deal_id");
			return true;
		}catch(Exception $e){
			$this->task_remark = '还款异常，请联系技术';
			Yii::app()->{$this->db_name}->rollback();
			$this->echoLog("accordingToLoanRepay Fail:".print_r($e->getMessage(),true),"email");
			return false;
		}
	}

	/**
	 *  还本付息Collection，
	 */
	private function repayLoanRepay($plan_repay_id, $repayment_form){
		$this->echoLog("repayLoanRepay: plan_repay_id:$plan_repay_id ");
		if(!is_numeric($plan_repay_id) || $plan_repay_id<=0 || !in_array($repayment_form, [0,1])){
			$this->echoLog("repayLoanRepay params error", "email");
			return false;
		}

		//要处理的还款计划
		$loan_repay_model = "{$this->table_prefix}DealLoanRepay";
		$repay_sql = "select * from firstp2p_deal_loan_repay where id=$plan_repay_id for update";
		$loan_repay_info = $loan_repay_model::model()->findBySql($repay_sql);
		if(empty($loan_repay_info)) {
			$this->echoLog("repayLoanRepay: loan_repay_info is empty");
			return false;
		}

		//还款计划状态校验
		if($loan_repay_info->status != 0) {
			$this->echoLog("repayLoanRepay: loan_repay status != 0 :{$loan_repay_info->status};");
			return false;
		}

		//暂时只支持正常还款与逾期还款, 不支持提前还款
		$midnight_time = strtotime("midnight");
		/*
		if($loan_repay_info->time > $midnight_time) {
			$this->task_remark = "暂未支持提前还款";
			$this->echoLog("repayLoanRepay: loan_repay time illegal: $loan_repay_info->time");
			return false;
		}*/

		//更新还款计划数据【因要与wx数据存储保持一致性，固当前凌晨时间减8小时】
		$loan_repay_info->real_time = $midnight_time-60*60*8;
		$loan_repay_info->repaid_amount = $loan_repay_info->money;
		$loan_repay_info->status = 1;
		if(false == $loan_repay_info->save(true, array('real_time', 'repaid_amount','status'))) {
			$this->echoLog("repayLoanRepay: update firstp2p_deal_loan_repay false;".print_r($loan_repay_info->getErrors(), true));
			return false;
		}

		//特殊数据处理,无需还款，直接变更状态为已还
		if($loan_repay_info->type == 1 && FunctionUtil::float_equal($loan_repay_info->money, 0.00, 2)){
			$this->echoLog("repayLoanRepay: loan_repay_info is empty");
			return true;
		}

		//日志记录
		$remark = '还息';
		$log_type = 'repayment_interest';

		//还款类型为本金并且，待还金额大于0更新投资记录
		if($loan_repay_info->type == 1 && FunctionUtil::float_bigger($loan_repay_info->money, 0.00, 2)){
			//投资记录校验
			$loan_model = "{$this->table_prefix}DealLoad";
			$loan_sql = "select * from firstp2p_deal_load where id=$loan_repay_info->deal_loan_id for update";
			$load_info = $loan_model::model()->findBySql($loan_sql);
			if(empty($load_info)) {
				$this->echoLog("repayLoanRepay: firstp2p_deal_load.id:$loan_repay_info->deal_loan_id is empty");
				return false;
			}

			//待还本金校验
			$wait_capital = bcsub($load_info->wait_capital, $loan_repay_info->money, 2);
			if(FunctionUtil::float_bigger(0.00, $wait_capital)){
				$this->echoLog("repayLoanRepay: wait_capital[$wait_capital] < 0");
				return false;
			}

			//更新剩余本金
			$load_info->wait_capital = $wait_capital;
			if(FunctionUtil::float_bigger_equal(0.00, $wait_capital, 2) && FunctionUtil::float_bigger_equal(0.00, $load_info->wait_interest, 2) ){
				$load_info->status = 2;
			}
			if(false == $load_info->save(true, array('wait_capital', 'status'))) {
				$this->echoLog("repayLoanRepay: update deal_load false;".print_r($load_info->getErrors(), true));
				return false;
			}

			//记录还本债权减少数据
			$this->repay_capital_total = bcadd($this->repay_capital_total, $loan_repay_info->money, 5);
		}

		//还息
		if($loan_repay_info->type == 2 && FunctionUtil::float_bigger($loan_repay_info->money, 0.00, 2)){
			//投资记录校验
			$loan_model = "{$this->table_prefix}DealLoad";
			$loan_sql = "select * from firstp2p_deal_load where id=$loan_repay_info->deal_loan_id for update";
			$load_info = $loan_model::model()->findBySql($loan_sql);
			if(empty($load_info)) {
				$this->echoLog("repayLoanRepay: firstp2p_deal_load.id:$loan_repay_info->deal_loan_id is empty");
				return false;
			}

			//待还本金校验
			$wait_interest = bcsub($load_info->wait_interest, $loan_repay_info->money, 2);
			if(FunctionUtil::float_bigger(0.00, $wait_interest)){
				$this->echoLog("repayLoanRepay: wait_interest[$wait_interest] < 0");
				return false;
			}

			//更新剩余本金
			$load_info->wait_interest = $wait_interest;
			$load_info->yes_interest = bcadd($load_info->yes_interest, $loan_repay_info->money, 2);
			if(FunctionUtil::float_bigger_equal(0.00, $load_info->wait_capital, 2) && FunctionUtil::float_bigger_equal(0.00, $wait_interest, 2) ){
				$load_info->status = 2;
			}
			if(false == $load_info->save(true, array('wait_interest', 'yes_interest', 'status'))) {
				$this->echoLog("repayLoanRepay: update deal_load false;".print_r($load_info->getErrors(), true));
				return false;
			}

			//记录还本债权减少数据
			$this->repay_interest_total = bcadd($this->repay_interest_total, $loan_repay_info->money, 5);
		}

		//项目信息校验
		$deal_model = "{$this->table_prefix}Deal";
		$deal_info = $deal_model::model()->findBySql("select * from firstp2p_deal where id=$loan_repay_info->deal_id for update");
		if(!$deal_info || $deal_info->deal_status != 4){
			$this->echoLog("repayLoanRepay: firstp2p_deal is empty or status[$deal_info->deal_status] != 4");
			return false;
		}

		//更新项目信息，判断项目是否还完
		$count = $loan_repay_model::model()->count("status=0 and deal_id=$loan_repay_info->deal_id ");
		if($count == 0){
			//更新为已结清
			$edit_ret = $deal_model::model()->updateByPk($loan_repay_info->deal_id, ['deal_status'=>5]);
			if(false == $edit_ret){
				$this->echoLog("repayLoanRepay: firstp2p_deal edit deal_status=5 error ");
				return false;
			}
		}

		/*
		//记录用户还款操作记录
		$op_log = [
			'user_id' => $loan_repay_info->loan_user_id,
			'log_type' => $log_type,
			'direction' => 1,
			'deal_load_id' => $loan_repay_info->deal_loan_id,
			'deal_id' => $loan_repay_info->deal_id,
			'money' => $loan_repay_info->money,
			'remark' => $remark,
		];
		$add_op  =  UserService::getInstance()->addWxOplog($op_log);
		if($add_op == false){
			Yii::log('repayLoanRepay: addWxOplog error, data:'.  print_r($op_log,true));
			return false;
		}*/

		//线上还款账户变更&记录流水

		//短信或者站内信通知

		$this->echoLog("repayLoanRepay end:  plan_repay_id:$plan_repay_id");
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
		$repay_sql = "select id, loan_user_id, deal_id, money, type, status from firstp2p_deal_loan_repay where $this->global_conditions ";
		$loan_repay_list = Yii::app()->{$this->db_name}->createCommand($repay_sql)->queryAll();
		if(empty($loan_repay_list)){
			$this->echoLog("getLoanRepayList: repay_list is empty");
			return false;
		}
		return $loan_repay_list;
	}

	/**
	 * 根据条件获取多有待还ID
	 */
	private function getPlanRepayIds(){
		if(empty($this->global_conditions)){
			$this->echoLog("getLoanRepayList: params error;");
			return false;
		}

		//待还计划ID
		$model_name = "{$this->table_prefix}DealLoanRepay";
		$criteria = new CDbCriteria;
		$criteria->select = "id";
		$criteria->condition = "$this->global_conditions";
		$criteria->order = 'money desc, time asc, id asc';
		$plan_repay_ids = $model_name::model()->findAll($criteria);
		if($plan_repay_ids == false) {
			$this->echoLog("getPlanRepayIds: getPlanRepayIds empty or false; ");
			return false;
		}
		return $plan_repay_ids;
	}

	/**
	 * 统计借款人待还数据
	 * @param $deal_id
	 * @param int $type 1尊享 2普惠
	 * @return bool
	 */
	public function actionStatRepay($deal_id=0, $type=1){
		self::echoLog(" statRepay Start !!! ");

		try{
			//区分数据库
			$dbname = $type == 1 ? 'db' : 'phdb';
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
				$stat_sql = "select count(1) as total_repay from ag_wx_stat_repay where deal_id=$deal_id ";
				$repay_info = Yii::app()->$dbname->createCommand($stat_sql)->queryRow();
				if($repay_info['total_repay'] > 0){
					self::echoLog(" statRepay End step01,ag_wx_stat_repay deal_id[{$deal_id}] already exist !!! ", "error");
					continue;
				}

				//项目信息获取+咨询方信息
				$deal_sql = "select d.id,d.user_id,d.agency_id as deal_agency_id ,da.name as deal_agency_name ,jys.name as jys_name ,d.deal_type,d.project_id,p.product_class,d.name,d.jys_record_number,p.name as project_name,
				  			 d.borrow_amount,d.rate,d.repay_time,d.loantype,d.repay_start_time,d.advisory_id,a.name as agency_name 
							 from firstp2p_deal d 
							 left join firstp2p_deal_project p on d.project_id=p.id 
							 left join firstp2p_deal_agency a on a.id=d.advisory_id 
							 left join firstp2p_deal_agency jys on jys.id=d.jys_id 
							 left join firstp2p_deal_agency da on da.id=d.agency_id 
							 where d.id=$deal_id and d.deal_status=4  ";
				$deal_info = Yii::app()->$dbname->createCommand($deal_sql)->queryRow();
				if(empty($deal_info)){
					self::echoLog(" statRepay End step02, deal_id[$deal_id] firstp2p_deal.firstp2p_deal_project.firstp2p_deal_agency error !!! ", "error");
					continue;
				}



				//借款人信息获取
				$user_sql = "select u.id,u.real_name,u.user_type,e.company_name,uc.name 
							from firstp2p_user u 
							left join firstp2p_enterprise e on e.user_id=u.id 
							left join firstp2p_user_company uc on uc.user_id=u.id 
							where u.id={$deal_info['user_id']}";
				$user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
				if(empty($user_info)){
					self::echoLog(" statRepay End step03, deal_id[$deal_id] firstp2p_user[{$deal_info['user_id']}] does not exist !!! ", "error");
					continue;
				}

				//待还还款计划
				$repay_sql = "select sum(money) as wait_money,time,type from firstp2p_deal_loan_repay where status=0 and deal_id=$deal_id and type in (1,2) and money>0 group by `time`,type ";
				$loan_repay_list = Yii::app()->$dbname->createCommand($repay_sql)->queryAll();
				if(empty($loan_repay_list)){
					self::echoLog(" statRepay End step04, deal_id[$deal_id] firstp2p_deal_loan_repay error !!! ", "error");
					continue;
				}

				//借款企业名称
				$company_name = $user_info['user_type'] == 1 ? $user_info['company_name'] : $user_info['name'];
				if(empty($company_name)){
					$company_name = $user_info['real_name'];
				}


				//拼接写入数据
				$stat_repay_str = $stat_repay_key = '';
				foreach ($loan_repay_list as $k => $v) {
					$_stat_repay = array(
						'deal_id' => $deal_id,
						'deal_type' => $deal_info['deal_type'],
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
				$sql = "INSERT INTO ag_wx_stat_repay (".$stat_repay_key.") VALUES $stat_repay_str";
				$result = Yii::app()->$dbname->createCommand($sql)->execute();
				if (false == $result){
					self::echoLog("deal_id[{$deal_id}] End step06, insert into ag_wx_stat_repay fail; sql:$sql", 'error');
					return false;
				}

				self::echoLog("deal_id[{$deal_id}] success");
			}

			self::echoLog(" statRepay End!!! ");
		}catch(Exception $e){
			self::echoLog(" statRepay Exception:".print_r($e->getMessage(),true), 'error');
		}
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
	 * 根据项目ID建立文件锁
	 */
	private function enterBorrowIdFnLock($deal_id){
		$deal_id = (int)$deal_id;
		if($deal_id<=0) {
			self::echoLog($deal_id." illegal!!!");
			exit(1);
		}
		$this->fnLock_deal_id = $this->fnLock_pre.$deal_id.'.pid';
		$fpLock = $this->enterLock(array('fnLock'=>$this->fnLock_deal_id));
		if(!$fpLock){
			self::echoLog($this->fnLock_deal_id." Having Run!!!");
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
	 * 修复还款计划异常数据
	 * @param int $type
	 * @param string $tender_id
	 * @return bool
	 */
	public function actionRepairRepay($type=1, $tender_id=''){
		self::echoLog(" repairRepay Start !!! ");
		//超级出借人2账户
		try{
			$dbname = $type == 1 ? 'db' : 'phdb';
			if(!empty($tender_id) && is_numeric($tender_id)){
				$where_tender = "  and t.id=$tender_id ";
			}

			//待处理数据
			$repay_c_sql = "select t.id,t.wait_capital,sum(r.money) as tpmoney 
								from firstp2p_deal_load t 
								left join firstp2p_deal_loan_repay r on r.deal_loan_id=t.id 
								where t.wait_capital>0 and r.status=0 and r.type=1 and r.money>0 $where_tender
								group by t.id  
								having tpmoney<>t.wait_capital ";
			$loan_list = Yii::app()->$dbname->createCommand($repay_c_sql)->queryAll();
			if(!$loan_list){
				self::echoLog(" repairRepay End!!! ");
				return false;
			}
			foreach ($loan_list as $loan_info){
				//修复
				$repair_ret = $this->repairLoanRepay($loan_info['id'], $type);
				self::echoLog(" repairLoanRepay id:{$loan_info['id']} return:$repair_ret  ");
			}

			self::echoLog(" repairRepay End!!!  ");
		}catch(Exception $e){
			self::echoLog(" repairRepay Exception:".print_r($e->getMessage(),true), 'error');
		}
	}

	/**
	 * 单笔处理
	 * @param $deal_load_id
	 * @param int $type
	 * @return bool
	 */
	private function repairLoanRepay($deal_load_id, $type=1){
		if(empty($deal_load_id) || !is_numeric($deal_load_id) || !in_array($type, [1,2])){
			self::echoLog("repairLoanRepay tender_id[{$deal_load_id}] params error");
			return false;
		}
		$load_model_name = $type == 1 ? 'DealLoad' : 'PHDealLoad';
		$dbname = $type == 1 ? 'db' : 'phdb';

		self::echoLog("repairLoanRepay tender_id[{$deal_load_id}] start");

		//投资记录
		$load_info = $load_model_name::model()->findByPk($deal_load_id);
		if(!$load_info || $load_info->wait_capital == 0 ){
			self::echoLog(" repairLoanRepay End step03, tender_id[{$deal_load_id}] deal_loan error !!! ");
			return false;
		}

		//还款计划表待还本金
		$repay_sql = "select id,sum(money) as repay_wait_capital from firstp2p_deal_loan_repay where status=0 and deal_loan_id=$load_info->id and type=1 and money>0";
		$repay_info = Yii::app()->$dbname->createCommand($repay_sql)->queryRow();
		if(empty($repay_info) || $repay_info['repay_wait_capital']<=0 ){
			self::echoLog(" repairLoanRepay End step03, tender_id[$load_info->id] deal_loan_repay error !!! ");
			return false;
		}

		//待还本金一致
		if(FunctionUtil::float_equal($repay_info['repay_wait_capital'], $load_info->wait_capital, 2)){
			self::echoLog(" repairLoanRepay End step04, tender_id[$load_info->id] wait_capital equal !!! ");
			return false;
		}

		$diff_amount = bcsub($load_info->wait_capital, $repay_info['repay_wait_capital'], 2);
		if(FunctionUtil::float_bigger_equal(abs($diff_amount), 0.1, 2)){
			self::echoLog(" repairLoanRepay End step05, tender_id[$load_info->id] diff_amount[$diff_amount]>0.1 !!! ");
			return false;
		}
		//更新还款计划
		$e_repay_sql = "update firstp2p_deal_loan_repay set money=money+$diff_amount where id={$repay_info['id']} and type=1 and status=0 and deal_loan_id=$load_info->id ";
		$edit_repay = Yii::app()->$dbname->createCommand($e_repay_sql)->execute();
		if(false == $edit_repay ){
			self::echoLog(" repairLoanRepay End step06, tender_id[$load_info->id] edit firstp2p_deal_loan_repay  error !!! ");
			return false;
		}

		self::echoLog("tender_id[{$load_info->id}] success");
		return true;
	}

}
