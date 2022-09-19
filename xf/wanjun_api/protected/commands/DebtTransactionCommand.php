<?php

/**
 * WX尊享->商城债权兑换权益币成功后，1分钱债权交易脚本
 * Class DebtTransactionCommand
 */
class DebtTransactionCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/zxTransferDebt';	//项目ID文件锁的前缀
	public $fnLock_tenderid = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $where = ' ';
	public $buyer_uid = '';
	public $debt_fee = 0;
	public $use_money_alarm_amount = 100;//回购人账户余额报警金额
   // public $use_money_alarm_key = 'use_money_alarm_key_';
	public $min_repay_time = '';
	public $old_type = 1;
	public $frozen_amount = 0.00;//交易金额


	/**
	 * 自动债权交易
	 * @param string $tender_id 指定投资记录ID
	 * @return bool
	 */
	public function actionRun($tender_id=''){
		$this->echoLog("debtTransaction run start");
		
		//指定投资记录单笔处理
		if(!empty($tender_id)){
			$this->where .= " and tender_id=$tender_id ";
		}
		
		try {
			//承接金额490w预警, 仅报警，超过也不终止兑换脚本
			/*
			$send_ret = DebtService::getInstance()->sendAlarm($this->buyer_uid);
			$this->echoLog("actionRun sendAlarm return $send_ret ");*/

			//查询待自动债转的项目
			$criteria = new CDbCriteria;
			$criteria->condition = "  status=1 and successtime=0 {$this->where}";
			$criteria->limit = 50;
			$order_list = DebtExchangeLog::model()->findAll($criteria);
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

			//增加短信报警
			if($f>0){
				$error_info = "YJ_DEBT_ALARM：DebtTransaction_fail_count：$f";
				$send_ret = SmsIdentityUtils::fundAlarm($error_info, 'DebtTransaction');
				$this->echoLog("handelDebt sendAlarm return $send_ret ");
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
	public function actionHandelDebt($log_id){
		$this->echoLog("handelDebt start log_id:$log_id");
		if(empty($log_id) || !is_numeric($log_id)){
			$this->echoLog("handelDebt end log_id:$log_id params error");
			return false;
		}
		Yii::app()->db->beginTransaction();
		try{
			//兑换记录
			$exchange_log = DebtExchangeLog::model()->findBySql("select * from firstp2p_debt_exchange_log where id=:log_id for update", array(':log_id' => $log_id));
			if(!$exchange_log || $exchange_log->status != 1 || $exchange_log->successtime != 0){
				$this->echoLog("handelDebt end id:$log_id status!= 0 or successtime!=0 ");
				Yii::app()->db->rollback();
				return false;
			} 

			//获取回购人
            $this->buyer_uid = !empty($exchange_log->buyer_uid) ? $exchange_log->buyer_uid : DebtService::getInstance()->getBuyerUid($exchange_log->debt_account);
			if(empty($this->buyer_uid) || !is_numeric($this->buyer_uid)){
				$this->echoLog("handelDebt end id:$log_id buyer_uid empty! ");
				Yii::app()->db->rollback();
				return false;
			}

			$assignee_info = AgWxAssigneeInfo::model()->findBySql("select * from ag_wx_assignee_info where user_id={$this->buyer_uid}  and status=2 for update");
			$use_money = bcsub($assignee_info->transferability_limit, $assignee_info->transferred_amount, 2);
			if(!$assignee_info || FunctionUtil::float_bigger($exchange_log->debt_account, $use_money, 2)){
				$this->echoLog("handelDebt end id:$log_id ag_wx_assignee_info error ");
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
			$create_ret = DebtService::getInstance()->createDebt($c_data);
			if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
				$this->echoLog("handelDebt createDebt tender_id {$exchange_log->tender_id} false:".print_r($create_ret,true), 'error', 'email');
				Yii::app()->db->rollback();
				return false;
			}

			//创建成功日志
			$this->echoLog("handelDebt createDebt tender_id {$exchange_log->tender_id} success");

			//债权认购
			$debt_data = array();
			$debt_data['user_id'] = $this->buyer_uid;
			$debt_data['money'] = $exchange_log->debt_account;
			$debt_data['debt_id'] = $create_ret['data']['debt_id'];
			$debt_data['frozen_amount'] = $this->frozen_amount;
            $debt_data['platform_no'] = $exchange_log->platform_no;
			$debt_transaction_ret = DebtService::getInstance()->debtPreTransaction($debt_data);
			if($debt_transaction_ret['code'] != 0){
				$this->echoLog("handelDebt debtPreTransaction tender_id {$exchange_log->tender_id} false:".print_r($debt_transaction_ret,true), 'error', 'email');
				Yii::app()->db->rollback();
				return false;
			}

			//兑换记录数据更新
			$changeLogRet = BaseCrudService::getInstance()->update("DebtExchangeLog",array(
				"id" => $exchange_log->id,
				"successtime" => time(),
				"status" => 2,
				"buyer_uid"=>$this->buyer_uid,
                "new_deal_load_id"=>$debt_transaction_ret['data']['new_tender_id']
			), "id");
			if(!$changeLogRet){
				$this->echoLog("DebtExchangeLog update error, id=$exchange_log->id");
				Yii::app()->db->rollback();
				return false;
			}

			//更新收购额度
			$assignee_info->transferred_amount = bcadd($assignee_info->transferred_amount, $exchange_log->debt_account, 2);
			if($assignee_info->save(false, array('transferred_amount')) == false){
				$this->echoLog("DebtExchangeLog update ag_wx_assignee_info transferred_amount[$assignee_info->transferred_amount] error, id=$exchange_log->id");
				Yii::app()->db->rollback();
				return false;
			}

			//债转成功数据确认
			Yii::app()->db->commit();
			$this->echoLog("handelDebt tender_id:$exchange_log->tender_id success");
			return true;
		}catch (Exception $ee) {
			$this->echoLog("handelDebt log_id:$log_id; exception:".print_r($ee->getMessage(), true));
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
		echo date('Y-m-d H:i:s ')." ".microtime()." wx_zxTransferDebt {$yiilog} \n";
		$this->alarm_content .= $yiilog."<br/>";
		if($level == 'email') {
			$level = "error";
			$this->is_email = true;
		}
		Yii::log("transferDebt: {$yiilog}", $level, 'wx_zxTransferDebt');
	}

	//报警邮件
	public function warningEmail(){
        return true;
		if(!empty($this->alarm_content) && $this->is_email ) {
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
	 * 投资记录待还本金字段赋值，仅导入数据后执行一次
	 * @param int $type 1尊享 2普惠
	 * @return bool
	 */
	public function actionRepairDealLoad($type=1){
		self::echoLog(" repairDealLoad Start !!! ");
		//超级出借人2账户
		try{
			$model_name = $type == 1 ? 'DealLoanRepay' : 'PHDealLoanRepay';
			$dbname = $type == 1 ? 'db' : 'phdb';
			$repay_c_sql = "select count(DISTINCT deal_loan_id) as total_load from firstp2p_deal_loan_repay where status=0 and type=1 ";
			$count_repay = Yii::app()->$dbname->createCommand($repay_c_sql)->queryScalar();
			if($count_repay == 0){
				self::echoLog(" repairDealLoad End step02,  No data !!! ");
				return false;
			}

			$limit = 500; //每次处理条数
			$page = ceil($count_repay/$limit);
			$load_model_name = $type == 1 ? 'DealLoad' : 'PHDealLoad';
			for($i=0; $i<$page; $i++){
				$offset = $i*$page;
				//待修复本金数据
				$criteria = new CDbCriteria;
				$criteria->condition = " status=0 and type=1 ";
				$criteria->offset = $offset;
				$criteria->limit = $limit;
				$criteria->group = 'deal_loan_id';
				$borrow_tender = $model_name::model()->findAll($criteria);
				if(empty($borrow_tender)){
					self::echoLog(" repairDealLoad End step02-1,  No data !!! ");
					break;
				}
				//单笔修复
				foreach($borrow_tender as $tender){
					self::echoLog("tender_id[{$tender->deal_loan_id}] start");

					//投资记录
					$load_info = $load_model_name::model()->findByPk($tender->deal_loan_id);
					if(!$load_info || $load_info->wait_capital != 0 ){
						self::echoLog(" repairDealLoad End step03-1, tender_id[$tender->deal_loan_id] deal_loan error !!! ");
						continue;
					}

					//还款计划表待还本金
					$repay_sql = "select sum(money) as repay_wait_capital from firstp2p_deal_loan_repay where status=0 and deal_loan_id=$tender->deal_loan_id and type=1 ";
					$repay_wait_capital = Yii::app()->$dbname->createCommand($repay_sql)->queryScalar();
					if(empty($repay_wait_capital) || $repay_wait_capital<=0 ){
						self::echoLog(" repairDealLoad End step03, tender_id[$tender->deal_loan_id] deal_loan_repay error !!! ");
						continue;
					}


					//更新投资记录待还本金
					$load_info->wait_capital = $repay_wait_capital;
					if(false == $load_info->save()){
						self::echoLog("repairDealLoad step04: update $load_model_name Fail, tender_id: {$tender->deal_loan_id}");
						continue;
					}

					self::echoLog("tender_id[{$tender->deal_loan_id}] success");
				}
			}

			self::echoLog(" repairDealLoad End!!! ");
		}catch(Exception $e){
			self::echoLog(" repairDealLoad Exception:".print_r($e->getMessage(),true), 'error');
		}
	}


	/**
	 * 债转成功的交易邮件通知三方
	 * 债务方+融资经办机构+担保方
	 * @param string $debt_id
	 * @param int $type
	 * @return bool
	 */
	public function actionDebtSendMail($debt_id='',$type=1){
		self::echoLog(" debtSendMail Start !!! ");

		if(!in_array($type, [1,2])){
			self::echoLog(" debtSendMail type params error !!! ");
			return false;
		}
		//超级出借人2账户
		try{
			$dbname = $type == 1 ? 'db' : 'phdb';
			$where_debt = (!empty($debt_id) && is_numeric($debt_id)) ? " and debt.id=$debt_id" : "";
			$repay_c_sql = "select dl.create_time as debt_time,dt.new_tender_id, debt.type,debt.id,debt.borrow_id as deal_id,debt.user_id as seller_uid,dt.user_id as buyer_uid,dt.money as debt_account 
 							 from firstp2p_debt  debt 
 							 left join firstp2p_deal d on d.id=debt.borrow_id
 							 left join firstp2p_debt_tender dt on dt.debt_id=debt.id
 							 left join firstp2p_deal_load dl on dl.id=dt.new_tender_id 
 							 where d.open_mail=1 and debt.status=2 and dt.status=2 and debt.is_mail=0 $where_debt limit 100 ";
			$debt_list = Yii::app()->{$dbname}->createCommand($repay_c_sql)->queryAll();
			if(!$debt_list){
				self::echoLog(" debtSendMail End,  No data !!! ");
				return false;
			}

			//单笔修复
			$s_ids = $f_ids = [];
			foreach($debt_list as $debt){
				self::echoLog("debt_id[{$debt['id']}] start");

				$email_data = array();
				$email_data['deal_id'] = $debt['deal_id'];
				$email_data['buyer_uid'] = $debt['buyer_uid'];
				$email_data['seller_uid'] = $debt['seller_uid'];
				$email_data['debt_account'] = $debt['debt_account'];
				$email_data['debt_time'] = date("Y-m-d H:i:s", $debt['debt_time']);
				$email_data['contract_no'] = implode('-', [date('Ymd', $debt['debt_time']), $debt['type'], $debt['deal_id'], $debt['new_tender_id']]);
				//用户真实姓名
				$user_infos = User::model()->findAllByPk([$debt['buyer_uid'], $debt['seller_uid']]);
				foreach ($user_infos as $user){
					if($user->id == $debt['buyer_uid']){
						$email_data['buyer_name'] = $user->real_name;
						continue;
					}
					if($user->id == $debt['seller_uid']){
						$email_data['seller_name'] = $user->real_name;
						continue;
					}
				}
				$send_email = DebtService::getInstance()->sendDebtMail($email_data, $type);
				if($send_email == false){
					$f_ids[] = $debt['id'];
					self::echoLog("debt_id[{$debt['id']}] sendDebtMail return false", 'error');
					continue;
				}

				//集合成功债权ID
				$s_ids[] = $debt['id'];
				self::echoLog("debt_id[{$debt['id']}] success");
			}

			//批量更新债权记录表发送邮件状态
			if(!empty($s_ids)){
				$model_name = $type == 1 ? 'Debt' : 'PHDebt';
				$condition = " id in ('".implode("','", $s_ids)."') ";
				$edit_ret = $model_name::model()->updateAll(array('is_mail'=>1), $condition);
				if(!$edit_ret){
					self::echoLog(" debtSendMail edit debt error,data:".print_r($s_ids, true), 'error');
					return false;
				}
			}

			//失败短信报警
			if(!empty($f_ids)){
				$error_info = "debtSendMail f_count：".count($f_ids);
				$send_ret = SmsIdentityUtils::fundAlarm($error_info, 'debtSendMail');
				$this->echoLog("debtSendMail warningSms send_ret:{$send_ret['code']}");
			}
			self::echoLog(" debtSendMail End!!! s_count:".count($s_ids).'; f_count:'.count($f_ids).';');
		}catch(Exception $e){
			self::echoLog(" debtSendMail Exception:".print_r($e->getMessage(),true), 'error');
		}

	}

	/**
	 * 债转成功的交易邮件通知三方
	 * 债务方+融资经办机构+担保方
	 * @param  $id
	 * @param  $type 1自动发 2手动补单发送失败邮件
	 * @return bool
	 */
	public function actionManualSendMail($id='', $type=1){
		self::echoLog(" manualSendMail Start !!! ");

		if(!in_array($type, [1,2])){
			self::echoLog(" manualSendMail End,  type:$type not in (1,2) !!! ");
			return false;
		}
		//超级出借人2账户
		try{
			//待发送邮件记录
			$con = $type == 1 ? " status=2 " :  " status=3 " ;
			$con .= (!empty($id) && is_numeric($id)) ? " and id=$id " : "";
			$email_info = AgWxEmailNotice::model()->find($con);
			if(!$email_info){
				self::echoLog(" manualSendMail End,  No data !!! ");
				return false;
			}

			/*
			//拼接查询条件
			$where_debt = "";
			if(!empty($email_info->advisory_id)){
				$where_debt .= " and d.advisory_id = $email_info->advisory_id";
			}

			if(!empty($email_info->user_id)){
				$where_debt .= " and d.user_id = $email_info->user_id";
			}

			if(!empty($email_info->agency_id)){
				$where_debt .= " and d.agency_id = $email_info->agency_id";
			}

			if(!empty($email_info->debt_start_time)){
				$where_debt .= " and debt.successtime >= $email_info->debt_start_time";
			}

			if(!empty($email_info->debt_end_time)){
				$where_debt .= " and debt.successtime <= $email_info->debt_start_time";
			}
			*/
			//有效数据校验
			if(!in_array($email_info->platform_id, [1,2]) || $email_info->debt_number <=0
				|| empty($email_info->email_address) || $email_info->send_number >= $email_info->debt_number){
				self::echoLog(" manualSendMail End,  id={$email_info->id}  data error !!! ");
				return false;
			}

			$dbname = $email_info->platform_id == 1 ? 'db' : 'phdb';
			//校验总条数
			$repay_c_sql = "select count(1) as debt_number from firstp2p_debt where email_notice_id={$email_info->id} and is_mail=0  ";
			$debt_number = Yii::app()->{$dbname}->createCommand($repay_c_sql)->queryScalar();
			if(!$debt_number || $debt_number<=0){
				self::echoLog(" manualSendMail End,  id={$email_info->id}  debt_number:$debt_number error  !!! ");
				return false;
			}

			//更新为执行中
			if($type == 1 && $email_info->status==2){
				$edit_ret = AgWxEmailNotice::model()->updateByPk($email_info->id, ['status'=>3]);
				if(!$edit_ret){
					self::echoLog(" manualSendMail End,  id={$email_info->id} edit AgWxEmailNotice status=3 error !!! ");
					return false;
				}
				self::echoLog(" manualSendMail id={$email_info->id} edit AgWxEmailNotice status=3 !!! ");
			}

			$limit = 100; //每次处理条数
			$page = ceil($debt_number/$limit);
			$s_count = $f_count = 0;
			for($i=0; $i<$page; $i++) {
				self::echoLog(" manualSendMail i:$i start!!!");
				//查出明细循环发送
				$repay_c_sql = "select dl.create_time as debt_time,dt.new_tender_id, debt.type,debt.id,debt.borrow_id as deal_id,debt.user_id as seller_uid,dt.user_id as buyer_uid,dt.money as debt_account 
 							 from firstp2p_debt  debt 
 							 left join firstp2p_debt_tender dt on dt.debt_id=debt.id
 							 left join firstp2p_deal_load dl on dl.id=dt.new_tender_id 
 							 where debt.status=2 and dt.status=2 and debt.email_notice_id={$email_info->id} and debt.is_mail=0 limit $f_count,$limit ";
				$debt_list = Yii::app()->{$dbname}->createCommand($repay_c_sql)->queryAll();
				if(!$debt_list){
					self::echoLog(" manualSendMail End step03,  No data !!! ");
					return false;
				}

				//单笔修复
				$s_ids = $f_ids = [];
				foreach($debt_list as $debt){
					self::echoLog("debt_id[{$debt['id']}] start");

					$email_data = array();
					$email_data['email_address'] = $email_info->email_address;
					$email_data['deal_id'] = $debt['deal_id'];
					$email_data['buyer_uid'] = $debt['buyer_uid'];
					$email_data['seller_uid'] = $debt['seller_uid'];
					$email_data['debt_account'] = $debt['debt_account'];
					$email_data['debt_time'] = date("Y-m-d H:i:s", $debt['debt_time']);
					$email_data['contract_no'] = implode('-', [date('Ymd', $debt['debt_time']), $debt['type'], $debt['deal_id'], $debt['new_tender_id']]);
					//用户真实姓名
					$user_infos = User::model()->findAllByPk([$debt['buyer_uid'], $debt['seller_uid']]);
					foreach ($user_infos as $user){
						if($user->id == $debt['buyer_uid']){
							$email_data['buyer_name'] = $user->real_name;
							continue;
						}
						if($user->id == $debt['seller_uid']){
							$email_data['seller_name'] = $user->real_name;
							continue;
						}
					}
					$send_email = DebtService::getInstance()->sendDebtMailNew($email_data, $email_info->platform_id);
					if($send_email == false){
						$f_ids[] = $debt['id'];
						self::echoLog("debt_id[{$debt['id']}] sendDebtMail return false", 'error');
						continue;
					}

					//集合成功债权ID
					$s_ids[] = $debt['id'];
					self::echoLog("debt_id[{$debt['id']}] success");
				}

				//批量更新债权记录表发送邮件状态
				if(!empty($s_ids)){
					$model_name = $email_info->platform_id == 1 ? 'Debt' : 'PHDebt';
					$condition = " id in ('".implode("','", $s_ids)."') ";
					$edit_ret = $model_name::model()->updateAll(array('is_mail'=>1), $condition);
					if(!$edit_ret){
						self::echoLog(" manualSendMail edit debt error,data:".print_r($s_ids, true), 'error');
						return false;
					}
				}
				$s_ids_count = count($s_ids);
				$f_ids_count = count($f_ids);
				self::echoLog(" manualSendMail i:$i End!!! s_count:$s_ids_count; f_count:$f_ids_count;");
				$s_count += $s_ids_count;
				$f_count += $f_ids_count;
			}
			
			//失败短信报警
			if($f_count>0){
				$error_info = "manualSendMail f_count：$f_count";
				$send_ret = SmsIdentityUtils::fundAlarm($error_info, 'manualSendMail');
				$this->echoLog("manualSendMail warningSms send_ret:{$send_ret['code']}");
			}
			//记录成功
			if($s_count>0){
				$edit_data = array();
				$edit_data['send_number'] = $s_count+$email_info->send_number;
				if($edit_data['send_number'] == $email_info->debt_number){
					$edit_data['status'] = 4;
					$edit_data['success_time'] = time();
				}
				$edit_ret_t = AgWxEmailNotice::model()->updateByPk($email_info->id, $edit_data);
				if(!$edit_ret_t){
					$error_info = "manualSendMail edit status=4 error";
					SmsIdentityUtils::fundAlarm($error_info, 'manualSendMailEdit');
					$this->echoLog("manualSendMail edit AgWxEmailNotice status=4 error, data:".print_r($edit_data, true));
				}

				self::echoLog(" manualSendMail id={$email_info->id} edit AgWxEmailNotice data:".print_r($edit_data, true));
			}
			self::echoLog(" manualSendMail End!!! t_s_count:$s_count; t_f_count:$f_count ");
		}catch(Exception $e){
			self::echoLog(" manualSendMail Exception:".print_r($e->getMessage(),true), 'error');
		}

	}
	


	

}
