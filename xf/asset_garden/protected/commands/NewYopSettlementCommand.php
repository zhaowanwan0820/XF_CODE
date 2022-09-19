<?php


class NewYopSettlementCommand extends CConsoleCommand {
	public $fnLock_pre = '/tmp/NewYopSettlement';	//项目ID文件锁的前缀
	public $fnLock_deal_id = '';//投资记录ID文件
	public $alarm_content = '';//报警内容
	public $is_email = false;
	public $global_conditions = '';
	private $stop_advisory_id = [360,361,396,421,422,429,433];//暂停掌众与大树的还款
    private $t_midnight = 0;


    /**
     * 易宝新还款脚本
     * @param $deal_id int 编号ID
     * @return bool
     */
    public function actionRepayment($deal_id=0){
        self::echoLog("repayment Start deal_id:$deal_id");

        try {
            //指定分配ID
            $where_id = ($deal_id>0 && is_numeric($deal_id)) ? " and deal_id=$deal_id " : '';
            //创建新还款计划的标的
            $this->t_midnight = strtotime("midnight");
            $plan_sql = " select distinct deal_id from firstp2p_create_new_repay_log where status=1 $where_id order by  deal_id asc";
            $repay_deal_ids = Yii::app()->rcms->createCommand($plan_sql)->queryColumn();
            if(empty($repay_deal_ids)){
                self::echoLog("repayment: No data!!!");
                return false;
            }

            $this->echoLog("repayment: repay deal count:".count($repay_deal_ids));

            //逐个标的处理
            foreach ($repay_deal_ids as $deal_id ){
                $this->echoLog("repayment: deal_id[$deal_id] start");

                //校验计划还款表的数据
                $check_ret = $this->checkRepaymentDeal($deal_id);
                if($check_ret == false ){
                    self::echoLog("repayment: checkRepaymentDeal deal_id[$deal_id]  error code={$check_ret['code']};");
                    continue;
                }

                //根据标的ID加文件锁,不允许同一个标的同时执行
                $fpLock = $this->enterBorrowIdFnLock($deal_id);

                //根据项目ID, 还款确认
                $result = $this->accordingToLoanRepay($deal_id);
                if($result == false){
                    self::echoLog("repayment: deal_id[{$deal_id}] accordingToLoanRepay return false;", "email");
                }

                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));
                $this->echoLog("repayment: deal_id[{$deal_id}] end");
            }

            $this->echoLog("repayment end");
        } catch (Exception $ee) {
            self::echoLog("repayment Exception,error_msg:".print_r($ee->getMessage(),true), "email");
        }
        //$this->warningEmail();
    }


    /**
     * 校验待还信息，获取项目ID
     * @param $deal_id
     * @return false
     */
    private function checkRepaymentDeal($deal_id){
        self::echoLog("checkRepaymentDeal deal_id:$deal_id");

        //查询在途项目
        $deal_sql = " select * from firstp2p_deal where id=$deal_id and advisory_id not in (".implode(',', $this->stop_advisory_id).") and deal_status=4 and is_zdx=0 and repay_auth_flag=2 ";
        $deal_info = Yii::app()->rcms->createCommand($deal_sql)->queryRow();
        if(!$deal_info){
            self::echoLog("checkRepaymentDeal end, deal_id:$deal_id firstp2p_deal error");
            return false;
        }

        //校验项目待还本金，必须大于0， 利息本期不还
        /*
        $deal_load = PHDealLoad::model()->find("deal_id=$deal_info->id and status=1 and wait_capital>0");
        if(!$deal_load){
            self::echoLog("checkRepaymentUser end, user_id:$user_id firstp2p_deal_load error");
            return false;
        }*/

        //校验借款人还款计划
        $repay_sql = " SELECT * from  firstp2p_deal_repay where type=1 and deal_id={$deal_info['id']} and true_repay_time=0 and status=0 and new_principal>0 and paid_principal=0 and repay_time={$this->t_midnight} ";
        $repay_info = Yii::app()->rcms->createCommand($repay_sql)->queryRow();
        if(!$repay_info){
            self::echoLog("checkRepaymentDeal end,  deal_id:$deal_id firstp2p_deal_repay error");
            return false;
        }

        self::echoLog("checkRepaymentDeal end,  deal_id:$deal_id return deal_id:{$deal_info['id']}");
        return $deal_info['id'];
    }


	private function warningSms($repay_model_name, $id, $name){
		//短信报警
		//$error_info = "YJ_ERROR：{$name} id_$id：$this->task_remark ";
		//$send_ret = SmsIdentityUtils::fundAlarm($error_info, $id);
		//更新还款计划
		//$edit_ret = $repay_model_name::model()->updateByPk($id, ['status'=>4, 'task_remark'=>$this->task_remark]);
		//$this->echoLog("warningSms: id:$id; edit_ret:$edit_ret;send_ret:{$send_ret['code']}");
	}

	/*
	* 根据项目ID还本付息
	*/
	private function accordingToLoanRepay($deal_id){
		$this->echoLog("accordingToLoanRepay: deal_id:$deal_id");

		$GLOBALS['NEED_XSS_PREVENT'] = false;   //不做XSS过滤

		//开启事务
        Yii::app()->phdb->beginTransaction();
		Yii::app()->rcms->beginTransaction();
		try{

			//累计还款额
			//$this->repay_capital_total = 0.00;

			//获取还款计划信息
            $deal_sql = "SELECT * FROM firstp2p_deal WHERE id=$deal_id for update";
            $deal_info = Yii::app()->rcms->createCommand($deal_sql)->queryRow();
			if(!$deal_info || $deal_info['deal_status'] != 4 || $deal_info['xf_last_repay_time'] > $this->t_midnight){
                Yii::app()->rcms->rollback();
                Yii::app()->phdb->rollback();
				$this->echoLog("accordingToLoanRepay: deal_id[$deal_id]; firstp2p_deal error", "email");
				return false;
			}

			//获取还款统计表数据
			$user_sql = "select * from xf_borrower_bind_card_info_online where user_id={$deal_info['user_id']}  for update";
			$user_info = Yii::app()->phdb->createCommand($user_sql)->queryRow();
			if(!$user_info || $user_info['last_repay_time'] > $this->t_midnight){
                Yii::app()->rcms->rollback();
                Yii::app()->phdb->rollback();
				$this->echoLog("accordingToLoanRepay: xf_borrower_bind_card_info_online.user_id={$deal_info['user_id']} error", "email");
				return false;
			}

			//获取plan_repay_ids
			$plan_repay_ids = $this->getPlanRepayIds($deal_id);
			if($plan_repay_ids == false || empty($plan_repay_ids)) {
                Yii::app()->rcms->rollback();
                Yii::app()->phdb->rollback();
				$this->echoLog("accordingToLoanRepay: deal_id[$deal_id] getPlanRepayIds is empty", "email");
				return false;
			}

			$this->echoLog("accordingToLoanRepay count.getPlanRepayIds:".count($plan_repay_ids).";  ");

			//循环plan_repay_ids，还本付息
			foreach ($plan_repay_ids as $key => $repay_id) {
                $repay_ret = $this->repayLoanRepay($repay_id, $user_info);
                if($repay_ret == false){
                    $this->echoLog("accordingToLoanRepay repayLoanRepay[$repay_id] return false  ");
                    break;
                }
			}

			//记录今日划扣数据
            $now = time();
            $up_deal_sql = " update firstp2p_deal set xf_last_repay_time ={$now} where id=$deal_id";
            $ret_deal = Yii::app()->rcms->createCommand($up_deal_sql)->execute();
            if ($ret_deal===false) {
                Yii::app()->rcms->rollback();
                Yii::app()->phdb->rollback();
                $this->echoLog("accordingToLoanRepay edit firstp2p_deal:$deal_id error");
                return false;
            }

            //更新用户表
            $up_user_sql = " update xf_borrower_bind_card_info_online set last_repay_time ={$now} where user_id={$deal_info['user_id']}";
            $ret_user = Yii::app()->phdb->createCommand($up_user_sql)->execute();
            if ($ret_user===false) {
                Yii::app()->rcms->rollback();
                Yii::app()->phdb->rollback();
                $this->echoLog("accordingToLoanRepay edit xf_borrower_bind_card_info_online user_id[{$deal_info['user_id']}] error");
                return false;
            }

			//全部成功提交事务
            Yii::app()->rcms->commit();
            Yii::app()->phdb->commit();
			$this->echoLog("accordingToLoanRepay: deal_id:$deal_id");
			return true;
		}catch(Exception $e){
            Yii::app()->rcms->rollback();
            Yii::app()->phdb->rollback();
			$this->echoLog("accordingToLoanRepay Fail:".print_r($e->getMessage(),true),"email");
			return false;
		}
	}

	/**
	 *  还本付息Collection，
	 */
	private function repayLoanRepay($repay_id, $user_info){
		$this->echoLog("repayLoanRepay: repay_id:$repay_id ");
		if(!is_numeric($repay_id) || $repay_id<=0  || empty($user_info)){
			$this->echoLog("repayLoanRepay params error", "email");
			return false;
		}

		//要处理的还款计划
        $repay_sql = "SELECT * FROM firstp2p_deal_repay WHERE id=$repay_id for update ";
        $loan_repay_info = Yii::app()->rcms->createCommand($repay_sql)->queryRow();
		if(empty($loan_repay_info)) {
			$this->echoLog("repayLoanRepay: loan_repay_info is empty");
			return false;
		}

		//还款计划状态校验
		if($loan_repay_info['status'] != 0) {
			$this->echoLog("repayLoanRepay: firstp2p_deal_repay status != 0 :{$loan_repay_info['status']};");
			return false;
		}

		//暂时只支持正常还款与逾期还款, 不支持提前还款
		if($loan_repay_info['repay_time'] != $this->t_midnight) {
			$this->echoLog("repayLoanRepay: firstp2p_deal_repay repay_time illegal: {$loan_repay_info['repay_time']}");
			return false;
		}

        //还款金额异常
        if(FunctionUtil::float_bigger_equal(0.00, $loan_repay_info['new_principal'], 2)){
            $this->echoLog("repayLoanRepay: firstp2p_deal_repay new_principal illegal: {$loan_repay_info['new_principal']}");
            return false;
        }

        //已还金额异常
        if(FunctionUtil::float_bigger($loan_repay_info['paid_principal'],0.00, 2)){
            $this->echoLog("repayLoanRepay: firstp2p_deal_repay paid_principal illegal: {$loan_repay_info['paid_principal']}");
            return false;
        }

        //获取还款业绩归属公司
        $repay_company_info = $this->getCompanyInfo($loan_repay_info['user_id']);
        $repay_company_id = $distribution_id = 0;
        if($repay_company_info){
            $repay_company_id = $repay_company_info['company_id'];
            $distribution_id = $repay_company_info['id'];
        }


        //更新repay数据
        $edit_data = [];
        $edit_data['id'] = $repay_id;
        $loan_repay_info['cardtop'] = $user_info['cardtop'];
        $loan_repay_info['cardlast'] = $user_info['cardlast'];
        $edit_data['last_yop_requestno'] = $loan_repay_info['request_no'] = FunctionUtil::getRequestNo("YBRY");
        $edit_data['last_yop_repay_time'] = time();
        $edit_data['last_yop_repay_money'] = $loan_repay_info['new_principal'];
        $loan_repay_info['terminalno'] = $user_info['bind_type'] == 1 ? 'SQKKSCENEKJ010' : 'SQKKSCENE10';

        //易宝划扣执行
        $repay_ret = $this->yopRepay($loan_repay_info);
        if ($repay_ret) {
            $edit_data['last_yop_repay_status'] = self::$repay_status[$repay_ret['status']] ?: 10;
            $edit_data['last_yop_repay_remark'] = json_encode($repay_ret);
            $edit_data['company_id'] = $repay_company_id ?: 0;
            $edit_data['distribution_id'] = $distribution_id ?: 0;
            $edit_data['paid_type'] = 1;
            $edit_data['errormsg'] = $repay_ret['errormsg'] ?: '';
            if($edit_data['last_yop_repay_status'] == 2){
                $edit_data['paid_principal_time'] = $edit_data['last_yop_repay_time'];
                $edit_data['paid_principal'] = $loan_repay_info['new_principal'];

                //待还利息为0的数据执行更新还款完成
                if($loan_repay_info['new_interest'] == 0){
                    $edit_data['true_repay_time'] = $edit_data['last_yop_repay_time'];
                    $edit_data['status'] = 1;
                }
            }
        }

        //更新repay 记录易宝返回数据
        $changeLogRet = BaseCrudService::getInstance()->update("Firstp2pDealRepay", $edit_data, "id");
        if(!$changeLogRet){
            $this->echoLog("repayLoanRepay: firstp2p_deal_repay[$repay_id] update error, edit_data:".print_r($edit_data, true));
            return false;
        }

        //如果首期处理失败，暂停下期处理
        if(!in_array($edit_data['last_yop_repay_status'], [1,2])){
            $this->echoLog("repayLoanRepay: last_yop_repay_status not in (1,2), stop repay" );
            return false;
        }

		$this->echoLog("repayLoanRepay end:  repay_id:$repay_id");
		return true;
	}

    /**
     * 获取业绩归属公司
     * @param $user_id
     * @return int
     */
	public function getCompanyInfo($user_id){
        $this->echoLog("getCompanyInfo: user_id:$user_id ");
        $now_time = time();
        $sql = "SELECT a.company_id, a.id from firstp2p_borrower_distribution a LEFT JOIN firstp2p_borrower_distribution_detail b on a.id=b.distribution_id 
where b.user_id=$user_id and a.status=1 and a.success_num>0 and a.start_time<=$now_time AND a.end_time>$now_time and b.status=1 group by a.company_id";
        $company_info = Yii::app()->rcms->createCommand($sql)->queryRow();
        if(!$company_info){
            return false;
        }
        return $company_info;
    }

    public function yopRepay($data=[])
    {
        $request = new YopRequest(YopConfig::APP_KEY, YopConfig::PRIVATE_KEY);
        $request->addParam("merchantno", YopConfig::MERCHANT_NO);
        //加入请求参数
        $request->addParam("issms", 'false');
        $request->addParam("identityid", $data['user_id']);
        $request->addParam("identitytype", "USER_ID");
        $request->addParam("cardtop", $data['cardtop']);
        $request->addParam("cardlast", $data['cardlast']);
        $request->addParam("amount", $data['new_principal']);
        $request->addParam("terminalno", $data['terminalno']);
        $request->addParam("callbackurl", "https://api.xfuser.com/user/XFUser/YopApi");
        $request->addParam("requesttime", date('Y-m-d H:i:s'));
        $request->addParam("productname", $data['deal_id']);
        $request->addParam("requestno", $data['request_no']);

        try {
            //提交Post请求
            $response = YopClient3::post("/rest/v1.0/paperorder/unified/pay", $request);
            if ($response->state != "FAILURE") {
                //取得返回结果
                $result = $this->object_array($response->result);
                self::echoLog("YopSettlement yopRepay  yop return data:".print_r($result, true));
                return $result;
            } else {
                self::echoLog("YopSettlement yopRepay  yop return data 01:".print_r($response->error, true));
            }
        } catch (\Exception $e) {
            self::echoLog("YopSettlement Exception  when request api:".$e->getMessage());
        }
        return false;
    }

    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key=>$value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }



	/**
	 * 根据条件获取多有待还ID
	 */
	private function getPlanRepayIds($deal_id){
        $sql = "SELECT id FROM firstp2p_deal_repay WHERE type=1 and deal_id=$deal_id and true_repay_time=0 and status=0  and new_principal>0 and paid_principal=0 and repay_time={$this->t_midnight}  and last_yop_repay_status not in (1, 2) order by repay_time asc ";
        $deal_repay_ids = Yii::app()->rcms->createCommand($sql)->queryColumn();
        return $deal_repay_ids;
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

    public static $repay_status = [
        'PAY_FAIL'=>3,
        'PROCESSING'=>1,
        'TIME_OUT'=>4,
        'FAIL'=>5,
        'PAY_SUCCESS'=>2,
        'TO_VALIDATE'=>6,
    ];


    /**
     * 易宝新还款脚本
     * @param $distribution_id int 分配ID
     * @return bool
     */
    public function actionNewRepayment($distribution_id=0){
        self::echoLog("repayment Start distribution_id:$distribution_id");

        try {
            //指定分配ID
            $where_id = ($distribution_id>0 && is_numeric($distribution_id)) ? " and id=$distribution_id " : '';
            //仅处理零售系统  在易宝鉴权通过 且开启自动划扣的用户
            $this->t_midnight = strtotime("midnight");
            $plan_sql = " select id from firstp2p_borrower_distribution where is_set_retail=1 and auto_deduct_status=1 and status=1 and id_type=1 and last_repay_time<$this->t_midnight $where_id order by last_repay_time asc,user_id asc";
            $repay_user_info = Yii::app()->phdb->createCommand($plan_sql)->queryAll();
            if(empty($repay_user_info)){
                self::echoLog("repayment: No data!!!");
                return false;
            }

            $this->echoLog("repayment: repay_user count:".count($repay_user_info));

            //逐个用户处理
            foreach ($repay_user_info as $user_info ){
                $this->echoLog("repayment: user_id[{$user_info['user_id']}] start");
                //校验计划还款表的数据
                $check_user_ret = $this->checkRepaymentUser($user_info['user_id']);
                if($check_user_ret == false ){
                    self::echoLog("repayment: checkRepaymentPlan user_id[{$user_info['user_id']}] error code={$check_user_ret['code']};");
                    continue;
                }

                //根据用户ID加文件锁,不允许同一个用户同时执行
                $fpLock = $this->enterBorrowIdFnLock($user_info['user_id']);

                //根据项目ID, 还款确认
                $result = $this->accordingToLoanRepay($check_user_ret);
                if($result == false){
                    self::echoLog("repayment: user_id[{$user_info['user_id']}] accordingToLoanRepay return false;", "email");
                }

                //释放文件锁
                $this->releaseLock(array('fnLock'=>$this->fnLock_deal_id, 'fpLock'=>$fpLock));
                $this->echoLog("repayment: user_id[{$user_info['user_id']}] end");
            }

            $this->echoLog("repayment end");
        } catch (Exception $ee) {
            self::echoLog("repayment Exception,error_msg:".print_r($ee->getMessage(),true), "email");
        }
        //$this->warningEmail();
    }

}
