<?php

/**
 *
 * 资金相关报警
 * cd /data/web_doc_root/2v/itouzi.com/itouzi/protected/bin && /usr/bin/php yiic.php AccountAlert AccountMinute
 * cd /data/web_doc_root/2v/itouzi.com/itouzi/protected/bin && /usr/bin/php yiic.php accountalert AccountQuarter 
 * cd /data/web_doc_root/2v/itouzi.com/itouzi/protected/bin && /usr/bin/php yiic.php accountalert AccountDaily
 */

class AccountAlertCommand extends CConsoleCommand {
    public $account_log_msg = "";







public function actionA(){
 echo 'test online';
}







    /**
    * 还款取消预处理
    * pre_request_no: 取消冻结流水号
    * amount: 取消金额
    */
    public function actionRepaymentCancelPre($pre_request_no, $amount){
        if(empty($pre_request_no) || FunctionUtil::float_bigger_equal(0, $amount, 3)){
            Yii::log("params error, pre_request_no: $pre_request_no, amount: $amount", "error", __METHOD__);
            return false;
        }

        //取消冻结
        $request_data = array(
                'serviceName' => 'CANCEL_PRE_TRANSACTION',
                'userDevice' => 'PC',
                'reqData' => array(
                    'requestNo' => FunctionUtil::getRequestNo('BC'),
                    'amount' => (string)$amount,                            
                    'preTransactionNo' => (string)$pre_request_no,
                    ),
                );
        $xw_result = CurlService::getInstance()->service($request_data);
        if(empty($xw_result) || $xw_result['code'] != 0){
            //新网返回交易失败, 报警
            Yii::log("xw_result: ". print_r($xw_result, true), "error", __METHOD__);
        }

        Yii::log("end, xw_result: ". print_r($xw_result, true), "info", __METHOD__);
    }
    
    /**
     * 发券事故涉及用户购买项目还本监控报警
     * 每日9点、13点各执行一次
     */
    public function actionCouponErrorTender(){
    	$coupon_error_info = Yii::app()->params['coupon_error_info'];
    	
    	if(empty($coupon_error_info)){
    		return false;
    	}
    	$ids = array();
    	foreach ($coupon_error_info as $value){
    		$ids[] = $value['borrow_id'];
    	}
    	
    	$borrow_ids = array_unique($ids);
    	$now_time = time();
    	$e_time = $now_time + 4*24*60*60;
    	
    	$ids_z = array();
    	$msgs = '';
    	//提前还款
    	foreach ($borrow_ids as $id){
    		$sql_t = "select borrow_id,process_time from itz_advance_repayment where borrow_id={$id} and addtime>{$now_time} and addtime<{$e_time}";
    		$info_t = Yii::app()->db->createCommand($sql_t)->queryRow();
    		if(empty($info_t)){
    			$ids_z[] = $id;
    		}else{
    			$msgs.='项目'.$id.'于'.date('Y/m/d',$info_t['process_time']).'还本,';
    		}
    	}
    	
    	//正常项目还本
    	if($ids_z){
    		foreach ($ids_z as $id){
    			$sql_z = "select id,repayment_time from dw_borrow where id={$id} and repayment_time>{$now_time} and repayment_time<{$e_time}";
    			$info_z = Yii::app()->db->createCommand($sql_t)->queryRow();
    			if(!empty($info_z)){
    				$msgs.='项目'.$id.'于'.date('Y/m/d',$info_t['repayment_time']).'还本,';
    			}
    		}
    	}
    	//发短信
    	if($msgs){
    		$msg = '2018/3/8的发券事故涉及项目近日还本，具体如下：'.$msgs.' 请关注.';
    		//报警给相关人
    		FunctionUtil::alertToAccountTeam($msg, array(), true);
    	}
    	
    }
    

   	/**
    * 用户信息查询接口
    */
    public function actionQueryUser($user_id){
        $request_data = array(
            'serviceName' => 'QUERY_USER_INFORMATION',
            'userDevice' => 'PC',
            'reqData' => array(
                'requestNo' => FunctionUtil::getRequestNo('QU'),
                'platformUserNo' => (string)$user_id,                                            
                ),
            );
        $result = CurlService::getInstance()->service($request_data);
        var_dump($result);
    }

    /**
    * 资金相关报警, 5min一执行
    */
    public function actionAccountMinute(){
        $msg = "";
        $today_time = date('Y-m-d');
        $hour = date("H");
        $now_time = time();

        /********************监控BorrowAddTender脚本*******************/
        //监控filter
        $filter = DwBorrowPre::model()->findBySql("select * from dw_borrow_pre where status=0 order by id asc limit 1");
        //监控tender
        $tender = DwBorrowPre::model()->findBySql("select * from dw_borrow_pre where status=100 order by id asc limit 1");
        if(!empty($filter)){
            if(($now_time-$filter->addtime)>300){
                $msg .= "borrow_pre filter脚本 Warning: borrow_pre存在status=0的数据，5min还未被处理; ".json_encode($filter->getAttributes(array('id', 'user_id', 'borrow_id','addtime')));
            }
        }
        
        if(!empty($tender)){
            if(($now_time-$tender->addtime)>600){
                $msg .= "borrow_pre tender脚本 Warning: borrow_pre存在status=100的数据，5-10min还未被处理; ".json_encode($tender->getAttributes(array('id', 'user_id', 'borrow_id', 'invest_type', 'addtime')));
            }
        }

        /****************提现银企直连相关: 对银企直连提现订单表(dw_account_cash_cmbc_pay)的检查****************/
        //status说明: 0 未处理; 1 提现成功; 2 提现失败; 5 取消，采用其他方式提现; 11 确认成功; 12 确认失败; 21 请求成功; 
        //22 请求失败; 100 支付进行中; 101 支付成功; 102 反馈异常; 103 无反馈; 201 支付成功查不到订单; 202 反馈异常查不到订单; 203 无反馈查不到订单

        //(1)不能存在多条status=22的数据, 一般情况一天可能存在几条, 暂定30条
        //(2)不能存在多条status in(103,200,201,202,203)的数据, 一般情况一天可能存在几条, 暂定50条
        //(3)当天18点, 不能存在status not in(1,2,5)的数据

        //每天的9~21监控
        // if($hour >= 9 && $hour <= 21){
        //     //不能存在多条status=22的数据, 一般情况一天可能存在几条, 暂定30条
        //     $fail_count = AccountCashCmbcPay::model()->count("add_times>=:add_times and status = 22", array(":add_times"=>$today_time));
        //     if($fail_count >= 30){
        //         $msg .= "dw_account_cash_cmbc_pay Warning: 银企直连超过30条提现请求失败的记录, 条数为: $fail_count; ";
        //     }

        //     //不能存在多条status in(103,200,201,202,203)的数据, 一般情况一天可能存在几条, 暂定50条
        //     $exception_count = AccountCashCmbcPay::model()->count("add_times>=:add_times and status in(103,200,201,202,203)", array(":add_times"=>$today_time));
        //     if($exception_count >= 50){
        //         $msg .= "dw_account_cash_cmbc_pay Warning: 银企直连超过50条提现异常的记录, 条数为: $exception_count; ";
        //     }
            
        //     if($hour >= 18){
        //         //当天18点, 不能存在status not in(1,2,5)的数据
        //         $count = AccountCashCmbcPay::model()->count("add_times=:add_times and status not in(1,2,5)", array(":add_times"=>$today_time));
        //         if($count > 0){
        //             $msg .= "dw_account_cash_cmbc_pay Warning: 银企直连存在非最终状态的数据,status not in(1,2,5) , 时间:".date('Y-m-d H:i:s')." 条数为: $count; ";
        //         }

        //         /*********************对提现记录表(dw_account_cash)的检查********************/
        //         //status说明: 0-待审核，1-审核通过，待转账，2-审核拒绝，3-转账成功，4-转账失败，5-银行退票，6-提现取消， 
        //         //100-银行处理中
                
        //         //(1)当天18点, 存在status=100的数据
        //         $bank_count = AccountCash::model()->count("status = 100");
        //         if($bank_count > 0){
        //             $msg .= "dw_account_cash Warning: 提现记录表存在银行处理中的提现记录, 时间:".date('Y-m-d H:i:s')." 条数为: $bank_count; ";
        //         }   
        //     }
        // }
        // var_dump($msg);
        //报警给相关人
        FunctionUtil::alertToAccountTeam($msg,array(), true);
    }

   /**
    * 资金相关报警, 15min一执行
    */
    public function actionAccountQuarter(){
        $msg = "";
        $now_s = time();
        $last_check_time = $now_s - 1800;
        $last_check_time_night = $last_check_time;
        $midnight = strtotime("midnight");
        $connection = Yii::app()->db;
        $hour = date("H");
        if ($hour>=0 && $hour<=8) {
            $last_check_time_night = $now_s - 7200;
        }
        /*******************************************资金账户以及流水相关********************************/   
        // 对account_log表的重复流水检查
        $account_log_sql = "select
                              id,related_id,related_type,log_type,user_id,borrow_type,count(1) as num
                              from dw_account_log
                              where borrow_type<>3100 and addtime>=".$last_check_time.
                              " and log_type<>'debt_finish' and log_type<>'debt_frost' and log_type<>'realname' and log_type<>'interest_reward'
                              group by related_id,related_type,log_type,user_id,money 
                              having num > 1";
        $account_log_result = $connection->createCommand($account_log_sql)->queryAll();
        if(!empty($account_log_result)){
            $msg .= "account_log存在重复流水！速度处理！".print_r($account_log_result, true).";";
        }
        unset($account_log_result);

        //对account_log表检查: 半小时内account_log表的关键字段不能小于0
        // $account_log_sql = "select id,user_id,total,money,virtual_money,use_money,no_use_money,collection,withdraw_free,use_virtual_money,no_use_virtual_money,invested_money,recharge_amount from dw_account_log where addtime>='$last_check_time' and (total < 0 or money< 0 or virtual_money < 0 or use_money < 0 or no_use_money < 0 or collection < 0 or withdraw_free < 0 or use_virtual_money < 0 or no_use_virtual_money <0 or invested_money < 0 or recharge_amount < 0);";
        // $account_log_result = $connection->createCommand($account_log_sql)->queryAll();
        // if(!empty($account_log_result)){
        //     $msg .= "dw_account_log Warning: 用户资金流水出现负数！，速度处理！".print_r($account_log_result, true).";";
        // }
        // unset($account_log_result);

        //对account表检查: 可用余额=免提现的本息+充值的钱; 账户余额=可用资金+冻结资金; 免提现的金额 >= 回来的本金  
        $account_sql = "select user_id,total,use_money,no_use_money,withdraw_free,recharge_amount,collection,invested_money 
                        from dw_account where `use_money`<>(`withdraw_free`+`recharge_amount`) or `total`<>(`use_money`+`no_use_money`) or withdraw_free < invested_money;";
        $account_result = $connection->createCommand($account_sql)->queryAll();
        if(!empty($account_result)){
            $msg .= "dw_account Warning: account表use_money<>withdraw_free+recharge_amount or total<>use_money+no_use_money or withdraw_free < invested_money，速度处理！".print_r($account_result, true).";";
        }
        unset($account_result);

        //对account表检查: account表的关键字段不能小于0
        $account_sql = "select id,user_id,total,use_money,no_use_money,collection,withdraw_free,use_virtual_money,no_use_virtual_money,invested_money,recharge_amount from dw_account where total < 0 or use_money < 0 or no_use_money < 0 or collection < 0 or withdraw_free < 0 or use_virtual_money < 0 or invested_money < 0 or recharge_amount < 0;";
        $account_result = $connection->createCommand($account_sql)->queryAll();
        if(!empty($account_result)){
            $msg .= "dw_account Warning: 用户资金出现负数！，速度处理！".print_r($account_result, true).";";
        }

        foreach ($account_result as $account_value) {
            if(FunctionUtil::float_bigger_equal($account_value['collection'], -500, 3) && !FunctionUtil::float_bigger_equal($account_value['collection'], 0, 3)){
                #如果-0.4<=dw_account.collection<0, 则把collection加回delta值
                Yii::app()->db->beginTransaction();
                try{
                    //用户实际collection
                    $collection_sql = "select sum(wait_account) as wait_account from dw_borrow_tender where user_id={$account_value['user_id']} and status=1";
                    $wait_account = Yii::app()->db->createCommand($collection_sql)->queryScalar();
                    $collection = ( $wait_account && $wait_account>0) ? $wait_account : 0;
                    //更新collection
                    $account_collection = Account::model()->findBySql('select * from dw_account where user_id=:user_id for update', array(':user_id'=>$account_value['user_id']));
                    $account_collection->collection = $collection;
                    if(false == $account_collection->save()){
                        Yii::log("update dw_account.collection error, account_result: ".print_r($account_collection->attributes, true), "error", "AccountAlertCommand");
                        Yii::app()->db->rollback();
                    }
                    Yii::app()->db->commit();
                }catch(Exception $e){
                    Yii::log(print_r($e->getMessage(),true), "error", "AccountAlertCommand");
                    Yii::app()->db->rollback();
                }                
            }
        }
        unset($account_result);

        /*******************************************充值相关********************************/ 
        //rechargeDetail中use_recharge_money>0的数据大于100条时，报警
        $detail_sql = "select user_id, count(id) as num from dw_account_recharge_detail where use_recharge_money>0 group by user_id having num>100";
        $result = $connection->createCommand($detail_sql)->queryAll();
        if(!empty($result)){
            $msg .= "dw_account_recharge_detail Warning: 存在use_recharge_money>0且超过100条未使用的充值金额的记录用户，用户为:{$result[0]['user_id']};条数为: {$result[0]['num']} 条;";
        }
        unset($result);
        
        //dw_account.recharge_amount != dw_account_recharge_detail.sum(use_recharge_money)
        //$detail_sql = "select recharge_amount,user_id from dw_account where recharge_amount>=0 and  recharge_amount != (select sum(use_recharge_money) as a from dw_account_recharge_detail where user_id = dw_account.user_id)";
        $detail_sql = "select user_id,da.recharge_amount,dw.detail_recharge_amount from dw_account  da
                        join 
                        (select sum(use_recharge_money) as detail_recharge_amount,user_id as ui from dw_account_recharge_detail group by user_id ) dw
                        on dw.ui=da.user_id  and da.recharge_amount!=dw.detail_recharge_amount
                        where recharge_amount>=0 ;";
        $result = $connection->createCommand($detail_sql)->queryAll();
        if(!empty($result)){
            $msg .= "dw_account_recharge_detail Warning: 存在dw_account.recharge_amount != dw_account_recharge_detail.sum(use_recharge_money)".print_r($result, true).";";
        }
        unset($result);

        
        //对detail表检查: recharge_total != use_recharge_money + no_use_recharge_money+invest_recharge_money+current_recharge_money+cash_money
        $account_sql = "select id, user_id from dw_account_recharge_detail where recharge_total != use_recharge_money + no_use_recharge_money+invest_recharge_money+current_recharge_money+cash_money+system_recovery;";
        $result = $connection->createCommand($account_sql)->queryAll();
        if(!empty($result)){
            $msg .= "dw_account_recharge_detail Warning: detail表存在recharge_total != use_recharge_money + no_use_recharge_money+invest_recharge_money+current_recharge_money+cash_money+system_recovery".print_r($result, true)." ;";
        }
        unset($result);  

        //对detail表检查: recharge_total关键字段小于0
        $account_sql = "select * from dw_account_recharge_detail where recharge_total<0 or use_recharge_money<0 or no_use_recharge_money<0 or invest_recharge_money<0 or current_recharge_money<0 or cash_money<0";
        $result = $connection->createCommand($account_sql)->queryAll();
        if(!empty($result)){
            $msg .= "dw_account_recharge_detail Warning: detail表存在关键字段<0".print_r($result,true).";";
        }
        unset($result);  

        //监控充值记录投资记录的时间由半小时调整为1小时
        $recharge_tender_check_time = $last_check_time_night-1800;

        //半小时内没有充值成功的记录
        /* 充值监控暂停
        $recharge_sql = "select count(1) as num from dw_account_recharge where verify_time>'$recharge_tender_check_time' and status = 1";
        $recharge_result = $connection->createCommand($recharge_sql)->queryAll();
        if($recharge_result[0]["num"] == 0){
            $msg .= "dw_account_recharge warning:".date("Y-m-d H:i:s",$recharge_tender_check_time)."到".date("Y-m-d H:i:s",$now_s)." 内没有充值成功的记录 ;";
        }
        unset($recharge_result); 
        */
        /*******************************************直投相关********************************/
        //dw_borrow_tender中存在同一个用户,同一个项目,同样的投资金额,同样的投资时间,同一个设备的数据
        $tender_sql = "select id,user_id,borrow_id,account_init,invest_device,FROM_UNIXTIME(addtime),count(1) as count from dw_borrow_tender where addtime>=".$last_check_time." group by user_id, borrow_id, account_init, invest_device, addtime,request_no having count(1)>1;";
        $tender_result = $connection->createCommand($tender_sql)->queryAll();
        if(!empty($tender_result)){
            $msg .= "dw_borrow_tender Warning: borrow_tender存在user_id,borrow_id,account_init,invest_device,addtime,request_no一样的数据 ".print_r($tender_result, true).";";
        }
        unset($tender_result);

        //半小时内没有产生新的投资记录
        $tender_sql = "select count(1) as num from dw_borrow_tender where addtime>=$recharge_tender_check_time";
        $tender_result = $connection->createCommand($tender_sql)->queryAll(); 
        if($tender_result[0]["num"] == 0){
            $msg .= "dw_borrow_tender Warning: ".date("Y-m-d H:i:s",$recharge_tender_check_time)."到".date("Y-m-d H:i:s",$now_s)."内没有产生新的投资记录，速度处理！;";
        }
        unset($tender_result);

        $tender_sql = "select id, account, money, repayment_account, interest, wait_account, wait_interest from dw_borrow_tender where status!=2 and (account<-1 or money<-1 or repayment_account<-1 or interest<-1 or wait_account<-1 or wait_interest<-1);";
        $tender_result = $connection->createCommand($tender_sql)->queryAll();
        if(!empty($tender_result)){
            $msg .= "dw_borrow_tender Warning: 存在 tender 关键字段小于-1的投资记录".print_r($tender_result, true).";";
        }
        unset($tender_result);

        //投满的项目, 验证dw_borrow, dw_borrow_pre, dw_borrow_tender, dw_borrow_collection
        $end_check_time = $now_s - 1200;
        $begin_check_time = $now_s - 3600;
        //查询投满的项目
        $borrow_sql = "select id,account_yes,apr,last_tender_time,formal_time,repayment_time,style from dw_borrow where status = 3 and last_tender_time >= $begin_check_time and last_tender_time <= $end_check_time";
        $borrow_result = $connection->createCommand($borrow_sql)->queryAll();
        if (!empty($borrow_result)) {
            foreach ($borrow_result as $borrow_info) {
                //验证dw_borrow_pre的success_money总和与dw_borrow表的account_yes是否相等
                $pre_sql = "select sum(success_money) as success_money from dw_borrow_pre where borrow_id={$borrow_info["id"]} and success_money >0";
                $pre_result = $connection->createCommand($pre_sql)->queryAll();
                if (!FunctionUtil::float_equal($borrow_info["account_yes"], $pre_result[0]["success_money"], 3)) {
                    $msg .= "dw_borrow_pre Warning: borrow_pre表的投资成功总额和borrow表的account_yes不相等, 请速速查看, borrow_id:{$borrow_info["id"]},borrow_account_yes:{$borrow_info["account_yes"]},pre_success_money:{$pre_result[0]["success_money"]};";
                }
                
                //验证dw_borrow_tender的account_init总和与dw_borrow的account_yes是否相等
                $tender_sql = "select sum(account_init) as account_init from dw_borrow_tender where borrow_id={$borrow_info["id"]} and status=1";
                $tender_result = $connection->createCommand($tender_sql)->queryAll();
                if(!FunctionUtil::float_equal($borrow_info["account_yes"], $tender_result[0]["account_init"], 3)){
                    $msg .= "dw_borrow_tender Warning: borrow_tender表的投资总额和borrow表的account_yes不相等, 请速速查看, borrow_id:{$borrow_info["id"]},borrow_account_yes:{$borrow_info["account_yes"]},account_init:{$tender_result[0]["account_init"]};";
                }

                //根据dw_borrow的account_yes计算产生的总利息和dw_borrow_collection产生的利息和做对比
                //根据borrow表的account_yes估算利息
                if($borrow_info['style'] != 5){
                    $formal_time = strtotime("midnight", $borrow_info["formal_time"]);      //项目上线时间凌晨时间戳
                    $days = (int)($borrow_info["repayment_time"] - $formal_time)/86400;     //项目天数
                    $borrow_cal_interest = $borrow_info["account_yes"] * $borrow_info["apr"] /100 * $days /365 ;    //此值对于多天投资的项目偏大, 等额本息项目不适用此公式
                    //计算borrow_collection产生利息的总额, 不包含加息奖励
                    $collection_sql = "select sum(interest) as interest from dw_borrow_collection where borrow_id={$borrow_info["id"]} and status in(0,2,3) and type!=5";
                    $collection_result = $connection->createCommand($collection_sql)->queryAll();
                    if(FunctionUtil::float_bigger(abs($borrow_cal_interest-$collection_result[0]["interest"]), 10000, 3)){    
                        $msg .= "dw_borrow_collection产生的利息和dw_borrow估算的利息总额值相差1w，请速速查看, borrow_id:{$borrow_info["id"]}, account_yes: {$borrow_info["account_yes"]}, 
                                 apr:{$borrow_info["apr"]}, formal_time:{$borrow_info["formal_time"]}, repayment_time:{$borrow_info["repayment_time"]}, 根据borrow估算的利息: {$borrow_cal_interest}, collection产生的利息: {$collection_result[0]["interest"]};";
                    }
                }
            }
        }

        /*******************************************债权相关********************************/
        //debt表中不能存在status=2（转让成功）,但是money!=sold_money（发起金额不等于已经被认购的钱）
        $debt_sql = "select id,user_id,borrow_id,tender_id,status,money,sold_money,discount_money,FROM_UNIXTIME(addtime) from dw_debt where successtime>=$last_check_time and status=2 and money<>sold_money;";
        $debt_result = $connection->createCommand($debt_sql)->queryAll();
        if(!empty($debt_result)){
            $msg .= "dw_debt Warning: 存在dw_debt记录已经转让成功，但转让金额!=已被认购金额".print_r($debt_result, true);
        }
        unset($debt_result);

        //半个小时内没有发起债权数据
        $debt_sql = "select count(1) as num from dw_debt where addtime > $last_check_time_night";
        $debt_result = $connection->createCommand($debt_sql)->queryAll();
        if($debt_result[0]["num"] == 0){
            $msg .= "dw_debt Warning:".date("Y-m-d H:i:s",$last_check_time_night)."到".date("Y-m-d H:i:s",$now_s)." 内没有发起债权的数据; ";
        }
        unset($debt_result);

        /*******************************************提现相关********************************/
        //半个小时内没有发起提现请求
        $cash_sql = "select count(1) as num from dw_account_cash where addtime > $last_check_time_night";
        $cash_result = $connection->createCommand($cash_sql)->queryAll();
        if($cash_result[0]["num"] == 0){
            $msg .= "dw_debt Warning:".date("Y-m-d H:i:s",$last_check_time_night)."到".date("Y-m-d H:i:s",$now_s)."  内没有发起提现的数据; ";
        }
        unset($cash_result);
        
        /******************************************************************/
        //投满时间距离当前时间超过30min未颁奖, 报警
        $yesterday_time = time()-3*3600;
        //获取总的颁奖数量
        $reward_list = Yii::app()->c->linkconfig["reward_list_config"];
        $num = $reward_list['first_reward'][0]['num']+$reward_list['first_reward'][1]['num']+$reward_list['rich_reward'][0]['num']+$reward_list['rich_reward'][1]['num']+$reward_list['last_reward'][0]['num'];
        //查询存续期项目, 当前时间-3h<项目投满时间<当前时间-30min
        $borrow_sql = "select id, formal_time, repayment_time, last_tender_time from dw_borrow where status=3 and is_join_reward=1 and project_duration_type = 0 and last_tender_time<=$last_check_time and last_tender_time>=$yesterday_time";
        $borrow_result = Yii::app()->db->createCommand($borrow_sql)->queryAll();
        if(!empty($borrow_result)){
            foreach ($borrow_result as $borrow_value) {
                //除去3个月以内的项目
                $time_limit = BorrowService::getInstance()->handleTimelimit($borrow_value['formal_time'], $borrow_value['repayment_time']);
                if($time_limit < 3){
                    continue;
                }

                //3个月以上项目, 验证颁奖是否正确
                $reward_sql = "select borrow_id, count(1) as num from itz_reward_user where borrow_id=:borrow_id and is_pay=1 and status=1";  
                $reward_result = Yii::app()->db->createCommand($reward_sql)->bindValue(':borrow_id', $borrow_value['id'], PDO::PARAM_INT)->queryRow();
                if(empty($reward_result) || $reward_result['num'] != $num){
                    $msg .= "borrow_id: {$borrow_value['id']}, last_tender_time: ".date('Y-m-d H:i:s', $borrow_value['last_tender_time']).", config_num:$num, itz_reward_user: is_pay=1 and status=1 count: {$reward_result['num']};";
                }
            }
        }

        /*****************************每天的16:00~19:00检查未还款的数据, 通知相关人*****************************/
        if ($hour>=16 && $hour<=19) {
            $collection_sql = "select status, count(1) as num from dw_borrow_collection where repay_time=$midnight and status in(0,2,3) group by status";
            $collection_result = Yii::app()->db->createCommand($collection_sql)->queryAll();
            if(!empty($collection_result)){
                $msg .= date('Y-m-d H:i:s')." borrow_collection未还款的数据".print_r($collection_result, true);
            }
        }

        /***********************每天的14:00~19:00检查智选计划类未还款的数据, 通知相关人**************************/
        if($hour>=14 && $hour<=19){
            $plan_sql = "select status, count(1) as num from itz_wise_plan_collection where repay_time=$midnight and status in(0,2,3,4) group by status";
            $plan_result = Yii::app()->db->createCommand($plan_sql)->queryAll();
            if(!empty($plan_result)){
                $msg .= date('Y-m-d H:i:s')." itz_wise_plan_collection未还款的数据".print_r($plan_result, true);
            }
        }

        //$this->exceptionUser();
        
        $key = 'rkey_wise_prepayments';
        $redis_info = RedisService::getInstance()->get($key);
        if( empty($redis_info) && ($hour>=10 && $hour<=17) ){
	        $advance_sql = "SELECT id,borrow_id,interest,capital,wise_borrow_id,addtime,borrow_type FROM itz_advance_repayment WHERE borrow_type in (2, 3, 4) AND status=0";
	        $advance_result = Yii::app()->db->createCommand($advance_sql)->queryAll();
	        if(!empty($advance_result)){
	        	RedisService::getInstance()->set($key, 1, 3000);
                $jh_msg = $xd_msg = $yz_msg = '';
                foreach($advance_result as $advance_value){
                    if($advance_value['borrow_type'] == 2){
                        $jh_msg .= print_r($advance_value, true)."\n";
                    }
                    if($advance_value['borrow_type'] == 3){
                        $xd_msg .= print_r($advance_value, true)."\n";
                    }
                    if($advance_value['borrow_type'] == 4){
                        $yz_msg .= print_r($advance_value, true)."\n";
                    }
                }
                if(!empty($jh_msg)){
                    $msg .= "阳光智选散标提前还款, 速度查看并处理, advance_data:".$jh_msg."\n";
                }
                if(!empty($xd_msg)){
                    $msg .= "智选计划小贷类提前还款, 速度查看并处理, advance_data:".$xd_msg."\n";
                }
                if(!empty($yz_msg)){
                    $msg .= "智选计划雁阵类提前还款, 速度查看并处理, advance_data:".$yz_msg."\n";
                }
	        }
        }

        //发送报警
        FunctionUtil::alertToAccountTeam($msg);
    }

    /**
    * 积分异常用户增加债权转让监控, 20160914之后可下掉
    */
    public function exceptionUser(){
        $tender_id = 2173711;
        $start_time = strtotime("midnight");
        $end_time = strtotime("2016-09-15");
        $result = Debt::model()->findAllBySql("select * from dw_debt where tender_id=:tender_id and addtime>=$start_time and addtime<=$end_time", array(":tender_id"=>$tender_id));
        if(!empty($result)){
            $msg = "积分异常用户发起了债权转让, user_id={$result[0]['user_id']}, tender_id=$tender_id";
            $msg_phones = NewRemindService::getInstance()->getPrinInfo('credit_debt');
            FunctionUtil::alertToAccountTeam($msg, $msg_phones);     
        }
     }

   /**
    * 资金相关报警, 一天一执行
    */
    public function actionAccountDaily(){
        $msg = "";
        $connection = Yii::app()->db;
        $timestamp = strtotime('midnight');
        $gktime = $timestamp-86400*3;
        /*******************************************资金账户以及流水相关********************************/ 
        //对account表检查: 检查冻结奖励金额
        $account_sql = "select id,user_id,use_virtual_money,no_use_virtual_money from dw_account where no_use_money=0 and no_use_virtual_money<>0";
        $account_result = $connection->createCommand($account_sql)->queryAll();
        if(!empty($account_result)){
            $msg .= "dw_account Warning: 用户virtual money不为0！，速度处理！".print_r($account_result, true).";";
        }
        unset($account_result);

        /*******************************************债权相关********************************/ 
        //(1)debt表的sold_money和debt_tender的account之和做比较
        $debt_sql = "select
                    c.id,
                    c.user_id,
                    c.tender_id,
                    c.borrow_id,
                    c.status,
                    c.sold_money,
                    c.buy_money,
                    FROM_UNIXTIME(c.addtime)
                    from
                    (
                    select
                    a.id,
                    a.user_id,
                    a.tender_id,
                    a.borrow_id,
                    a.status,
                    a.sold_money,
                    sum(b.account) as buy_money,
                    a.addtime
                    from
                    dw_debt as a,
                    dw_debt_tender as b
                    where a.addtime>=$gktime and a.addtime<=$timestamp and a.sold_money > 0 and b.status = 2 and a.id = b.debt_id
                    group by b.debt_id
                    )as c
                    where c.sold_money!=c.buy_money;";
        $debt_result = $connection->createCommand($debt_sql)->queryAll();
        if(count($debt_result) > 0){       
           $msg .= "dw_debt Warning:debt表的sold_money 不等于debt_tender的account之和，疑似超卖，请查看;".print_r($debt_result, true).";";
        }
        unset($debt_result); 

        //获取之前3天发起的债权数据
        $tender_str = "";
        $debt_sql = "select tender_id from dw_debt where addtime>=$gktime and addtime<=$timestamp group by tender_id";
        $debt_result = $connection->createCommand($debt_sql)->queryAll();
        if (!empty($debt_result)) {
            foreach ($debt_result as $debt) {
                $tender_str .= ",".$debt["tender_id"];
            }
            $tender_str = ltrim($tender_str, ",");
        }
        unset($debt_result);

        if (!empty($tender_str)) {
            //(2)存在债权已全部转让, 但borrow_collection的status不为15（等额本息）
            //$debt_sql ="select count(1) as num from dw_borrow_collection as c, dw_borrow as b where c.status in (0,1,5,8) and (c.interest+c.capital)<0.10 and c.tender_type in (5,6) and c.borrow_id=b.id and b.style=5;";
            $debt_sql ="select c.id, c.tender_id, c.user_id, c.borrow_id, c.status, c.capital, c.interest from dw_borrow_collection as c, dw_borrow as b where c.status in (0,5,8) and (c.interest+c.capital)<0.10 and c.tender_type in (5,6) and c.borrow_id=b.id and c.type!=5 and b.style=5 and c.tender_id in($tender_str);";
            $debt_result = $connection->createCommand($debt_sql)->queryAll();
            if(!empty($debt_result)){       
               $msg .= "dw_borrow_collection Waring: 存在债权已全部转让，但collectoin.status!=15的collection记录 ".print_r($debt_result, true).";";
            }
            unset($debt_result);

            //(3)存在collection.status=15, 但tender.status!=15的tender记录
            //$debt_sql ="select count( distinct c.tender_id) as num from dw_borrow_tender as t , dw_borrow_collection as c where c.tender_id=t.id and c.status=15 and t.status!=15;";
            //$debt_sql = "select count( distinct c.tender_id) as num from dw_borrow_tender as t LEFT JOIN dw_debt d on d.tender_id = t.id LEFT JOIN dw_borrow_collection as c ON c.tender_id=t.id where d.addtime >= $gktime and d.addtime < $timestamp  and c.status=15 and t.status!=15;";
            $debt_sql ="select distinct c.tender_id from dw_borrow_tender as t , dw_borrow_collection as c where c.tender_id=t.id and c.status=15 and t.status!=15 and c.tender_id in($tender_str);";
            $debt_result = $connection->createCommand($debt_sql)->queryAll();
            if(!empty($debt_result)){       
               $msg .= "dw_borrow_tender Waring: 存在collection.status=15, 但tender.status!=15的tender记录, tender_id:".print_r($debt_result, true).";";
            }
            unset($debt_result);
        }

        //dw_debt, 债权被全部认购, 但money!=sold_money
        $debt_sql = "select * from dw_debt where addtime>=:addtime and status=2 and money!=sold_money;";
        $debt_result = $connection->createCommand($debt_sql)->bindValue(':addtime', $gktime, PDO::PARAM_INT)->queryAll();
        if(!empty($debt_result)){
            $msg .= "dw_debt Warning:dw_debt.status=2, but money!=sold_money ".print_r($debt_result, true);
        }   
        unset($debt_result);

        //发送报警
        FunctionUtil::alertToAccountTeam($msg);
    }

    /**
    * 修复dw_borrow_tender数据: 项目已结息, 但tender是存续期status的数据
    * 也可根据指定borrow_id修复tender表数据
    */
    public function actionFixTender($borrow_id = 0, $days = 0){
        Yii::log("fixTender start", "info", __METHOD__);
        
        //不根据指定borrow_id修复数据
        if(empty($borrow_id)){
            $today_time = strtotime("midnight")-$days*86400;
            $now_time = time();
            //查询今天结息的项目
            $borrow_sql = "select id from dw_borrow where status=4 and repayment_time>=$today_time and repayment_time<$now_time";
        }else{
            //根据指定borrow_id修复数据
            $borrow_sql = "select id from dw_borrow where id=$borrow_id";
        }

        $borrow_result = Borrow::model()->findAllBySql($borrow_sql);
        if(empty($borrow_result)){
            Yii::log("borrow_result is empty", "info", __METHOD__);
            return false;
        }

        foreach ($borrow_result as $key => $value) {
            //根据项目ID查询对应的投资记录
            $tender_sql = "select id from dw_borrow_tender where borrow_id=:borrow_id and status=1";
            $tender_result = BorrowTender::model()->findAllBySql($tender_sql, array(":borrow_id"=>$value->id));
            if(empty($tender_result)){
                //不存在status=1的数据, 不需要修复
                Yii::log("borrow_tender not exists status=1, borrow_id:{$value->id}, no need to fix", "info", __METHOD__);
                continue;
            }

            //修复borrow_tender的状态为2
            $update_sql = "update dw_borrow_tender set status=2 where borrow_id={$value->id} and status=1";
            $result = Yii::app()->db->createCommand($update_sql)->execute();
            Yii::log("update dw_borrow_tender status=2, update_sql:$update_sql", "info", __METHOD__);
        }

        Yii::log("fixTender end", "info", __METHOD__);
    }

    /**
    * 13笔平台充值记录, 查询对应的13笔充值详情, 根据use_recharge_money冲正到用户账户, 仅执行一次
    **/
    public function actionFixAccount(){
        return false;
        $start_time = strtotime("2017-05-15");
        $end_time = $start_time+86400;

        //查询需要充值冲正的所有充值记录
        $recharge_sql = "SELECT * FROM dw_account_recharge WHERE type=7 AND status=4 AND addtime>=:start_time and addtime<:end_time and id not in(4838851, 4838977)";
        $recharge_result = AccountRecharge::model()->findAllBySql($recharge_sql, array(":start_time"=>(int)$start_time,":end_time"=>(int)$end_time));
        if(empty($recharge_result)){
            Yii::log("recharge_result is empty", "info", __METHOD__);
            return false;
        }

        foreach ($recharge_result as $value) {
            //开启事务
            Yii::app()->db->beginTransaction();
            try{
                //锁用户账户
                $account_result = Account::model()->findBySql('select * from dw_account where user_id=:user_id for update', array(':user_id'=>$value['user_id']));
                if(empty($account_result)){
                    Yii::log("account is empty user_id: {$value['user_id']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //查询recharge_id的充值详情
                $detail_result = DwAccountRechargeDetail::model()->findBySql('select * from dw_account_recharge_detail where recharge_id=:recharge_id and use_recharge_money>0', array(":recharge_id"=>$value['id']));
                if(empty($detail_result)){
                    Yii::log("recharge_detail is empty, recharge_id: {$value['id']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }
                //更新用户的充值详情表
                $detail_sql = "update dw_account_recharge_detail set use_recharge_money=use_recharge_money-{$detail_result['use_recharge_money']}, cash_money=cash_money+{$detail_result['use_recharge_money']} where recharge_id={$value['id']} and user_id={$value['user_id']}";
                $exec_result = Yii::app()->db->createCommand($detail_sql)->execute();
                Yii::log("detail_sql: $detail_sql", "info", __METHOD__);
                if($exec_result == false){
                    Yii::log("update recharge_detail return false recharge_id: {$value['id']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //集齐数据, 更新account, account_log
                $log = array();
                $log['related_id'] = $detail_result['id'];
                $log['related_type'] = 'recharge_detail';
                $log['log_type'] = 'reversal_success';                 //平台奖励到期扣除
                $log['user_id'] = $account_result['user_id'];
                $log['type'] = $log['log_type'];
                $log['direction'] = 2;
                $log['transid'] = 'recharge_detail_'.$log['related_id'];
                $log['money'] = $detail_result['use_recharge_money'];
                $log['total'] = $account_result['total']-$detail_result['use_recharge_money'];
                $log['use_money'] = $account_result['use_money']-$detail_result['use_recharge_money'];
                $log['no_use_money'] = $account_result['no_use_money'];
                $log['collection'] = $account_result['collection'];
                $log['withdraw_free'] = $account_result['withdraw_free'];
                $log['to_user'] = "0";
                $log['recharge_amount'] = $account_result['recharge_amount']-$detail_result['use_recharge_money'];
                $log['invested_money']  = $account_result['invested_money'];
                $log['remark'] = "平台奖励冲正";
                $addlogret = AccountService::getInstance()->addLog($log);
                if(false == $addlogret){
                    Yii::log("actionFixAccount: addLog failed".print_r($log, true), 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //关键字段不能小于0
                if(FunctionUtil::float_bigger(0, $log['total'], 3) || FunctionUtil::float_bigger(0, $log['use_money'], 3) || FunctionUtil::float_bigger(0, $log['recharge_amount'], 3)){
                    Yii::log("actionFixAccount: total<0 or use_money<0 or recharge_amount<0, total: {$log['total']}, use_money: {$log['use_money']}, recharge_amount: {$log['recharge_amount']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                Yii::app()->db->commit();
                Yii::log("deal success, user_id:{$value['user_id']} recharge_id: {$value['id']}, money:{$detail_result['use_recharge_money']}", "info", __METHOD__);
            }catch(Exception $ee){
                Yii::log("actionFixAccount Exception".print_r($ee->getMessage(), true), 'error', __METHOD__);
                Yii::app()->db->rollback();
                continue;
            }
        }
    }
    

    /*
    * 489158,489184,489216,489303,489322,489345,490479,490647
    * 冲正以上8个异常用户, dw_account表有账户余额, 但dw_user表不存在, 是红包充值用户(type='hongbao_recharge')
    */
    public function actionFixExceptionUser(){
        //查询需要被冲正的账户
        $query_result = Account::model()->findAllBySql("SELECT * FROM dw_account WHERE user_id IN(489158,489184,489216,489303,489322,489345,490479,490647) AND total>0");
        if(empty($query_result)){
            Yii::log("query_result is empty", 'info', __METHOD__);
            return false;
        }

        foreach ($query_result as $value) {
            //开启事务
            Yii::app()->db->beginTransaction();
            try{
                //锁用户账户
                $account_result = Account::model()->findBySql('select * from dw_account where user_id=:user_id for update', array(':user_id'=>$value['user_id']));
                if(empty($account_result)){
                    Yii::log("account is empty user_id: {$value['user_id']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //查询dw_account_recharge_detail
                $detail_result = DwAccountRechargeDetail::model()->findBySql("select * from dw_account_recharge_detail where user_id=:user_id", array(":user_id"=>$account_result['user_id']));
                $detail_sql = "update dw_account_recharge_detail set use_recharge_money=use_recharge_money-".$detail_result['use_recharge_money']." , system_recovery=system_recovery+".$detail_result['use_recharge_money']." where user_id=".$account_result['user_id']." and id=".$detail_result['id'];
                $exec_result = Yii::app()->db->createCommand($detail_sql)->execute();
                Yii::log("detail_sql: $detail_sql", "info", __METHOD__);
                if($exec_result == false){
                    Yii::log("update recharge_detail return false detail_id: {$detail_result['id']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //集齐数据, 更新account, account_log
                $log = array();
                $log['related_id'] = $detail_result['id'];
                $log['related_type'] = 'recharge_detail';
                $log['log_type'] = 'reversal_success';
                $log['user_id'] = $account_result['user_id'];
                $log['type'] = $log['log_type'];
                $log['direction'] = 2;
                $log['transid'] = $log['related_type'].'_'.$log['related_id'];
                $log['money'] = $detail_result['use_recharge_money'];
                $log['total'] = $account_result['total']-$detail_result['use_recharge_money'];
                $log['use_money'] = $account_result['use_money']-$detail_result['use_recharge_money'];
                $log['no_use_money'] = $account_result['no_use_money'];
                $log['collection'] = $account_result['collection'];
                $log['withdraw_free'] = $account_result['withdraw_free'];
                $log['to_user'] = "0";
                $log['recharge_amount'] = $account_result['recharge_amount']-$detail_result['use_recharge_money'];
                $log['invested_money']  = $account_result['invested_money'];
                $log['remark'] = "异常用户冲正,dw_account账户有余额,dw_user表不存在";
                $addlogret = AccountService::getInstance()->addLog($log);
                if(false == $addlogret){
                    Yii::log("addLog failed".print_r($log, true), 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                //关键字段不能小于0
                if(FunctionUtil::float_bigger(0, $log['total'], 3) || FunctionUtil::float_bigger(0, $log['use_money'], 3) || FunctionUtil::float_bigger(0, $log['recharge_amount'], 3)){
                    Yii::log("actionFixAccount: total<0 or use_money<0 or recharge_amount<0, total: {$log['total']}, use_money: {$log['use_money']}, recharge_amount: {$log['recharge_amount']}", 'error', __METHOD__);
                    Yii::app()->db->rollback();
                    continue;
                }

                Yii::app()->db->commit();
                Yii::log("deal success, user_id:{$value['user_id']} recharge_id: {$value['id']}, money:{$detail_result['use_recharge_money']}", "info", __METHOD__);
            }catch(Exception $ee){
                Yii::log("Exception".print_r($ee->getMessage(), true), 'error', __METHOD__);
                Yii::app()->db->rollback();
            }  
        }
    }

    /**
     * 智选计划项目数据监控脚本
     * @return bool
     */
    public function actionContinueInvest(){
        Yii::log("continueInvest Start !!!", 'info');
        Yii::app()->db->beginTransaction();
        try{
            $pre_sql = "select sum(success_money) from dw_borrow_pre where borrow_type=3100 and status=100 ";
            $pre_tender_info = Yii::app()->db->createCommand($pre_sql)->queryScalar();
            if(isset($pre_tender_info) && $pre_tender_info > 0){
                Yii::app()->db->rollback();
                Yii::log("continueInvest dw_borrow_pre data to be processed!!!", 'info');
                return false;
            }

            $sql = "select id,reserve_account,continue_account,debt_account from dw_borrow where id=24939 FOR UPDATE ";
            $plan_borrow_info = Yii::app()->db->createCommand($sql)->queryRow();

            //实际续投中金额
            $continue_account_init = Yii::app()->db->createCommand("select sum(account_init) as account_init from itz_continue_invest  WHERE status=1")->queryScalar();
            $continue_account_init = !empty($continue_account_init) ? $continue_account_init : 0;
            $borrow_edit_data = array();
            if($plan_borrow_info['continue_account'] != $continue_account_init && $continue_account_init >= 0){
                //预约金额大于0但与borrow表中记录不一致
                Yii::log("continueInvest edit info: borrow_continue_account[{$plan_borrow_info['continue_account']}], continue_account_init[{$continue_account_init}]", 'info');
                $borrow_edit_data['continue_account'] = $continue_account_init;
            }

            //实际债转中中金额
            $wait_quit_amount = Yii::app()->db->createCommand("select sum(wait_quit_amount) wait_quit_amount from itz_invest_exit where status=1")->queryScalar();
            $wait_quit_amount = !empty($wait_quit_amount) ? $wait_quit_amount : 0;
            if($plan_borrow_info['debt_account'] != $wait_quit_amount && $wait_quit_amount >= 0){
                //实际债转中中金额大于0但与borrow表中记录不一致
                Yii::log("continueInvest edit info: borrow_debt_account[{$plan_borrow_info['debt_account']}], wait_quit_amount[{$wait_quit_amount}]", 'info');
                $borrow_edit_data['debt_account'] = $wait_quit_amount;
            }

            //预约表中预约金额
            $reserve_account = Yii::app()->db->createCommand("select sum(account_init) as reserve_account from itz_borrow_reserve where status=1")->queryScalar();
            $reserve_account = !empty($reserve_account) ? $reserve_account : 0;
            if($plan_borrow_info['reserve_account'] != $reserve_account && $reserve_account >= 0){
                Yii::log("continueInvest edit info: borrow_reserve_account[{$plan_borrow_info['reserve_account']}], reserve_account[{$reserve_account}]", 'info');
                $borrow_edit_data['reserve_account'] = $reserve_account;
            }

            if(count($borrow_edit_data) > 0){
                $edit_ret = Borrow::model()->updateByPk(24939, $borrow_edit_data);
                if(!$edit_ret){
                    Yii::app()->db->rollback();
                    Yii::log("continueInvest edit error!!!", 'info');
                    return false;
                }
            }

            Yii::app()->db->commit();
            Yii::log("continueInvest processed end !!!", 'info');
            return false;
        }catch(Exception $e){
            self::echoLog(" interestPayment Exception:".print_r($e->getMessage(),true), 'email');exit;
        }
    }

    /**
     * 检测当天是否有正常还款未还完， 提前还款未还完
     */
    public function actionRepaymentFinish(){
        Yii::log("RepaymentFinish Check:", "info", __METHOD__);
        $today = strtotime("midnight");
        $needCheck = array(
            array(
                "name" => "省心计划待还",
                "sql" => "select count(1) as num from dw_borrow_collection where status in (0,2,3) and borrow_type not in (3000,3100,3200) and repay_time=".$today,
            ),
            array(
                "name" => "智选集合待还",
                "sql" => "select count(1) as num from dw_borrow_collection where status in (0,2,3) and borrow_type=3000 and repay_time=".$today,
            ),
            array(
                "name" => "阳光智选待还",
                "sql" => "select count(1) as num from dw_borrow_collection where status in (0,2,3) and borrow_type=3200 and repay_time=".$today,
            ),
            array(
                "name" => "提前还款未还",
                "sql" => "select count(1) as num from itz_advance_repayment where status in (0,2) and addtime>={$today} and addtime < ".($today+86400),
            ),
            array(
                "name" => "智选计划未付加息",
                "sql" => "select count(1) as num from itz_tender_reward where status=0 and repay_time=".$today,
            ),
            array(
                "name" => "智选计划未还",
                "sql" => "select count(1) as num from itz_wise_plan_collection where status in (0,2,3,4) and repay_time=".$today,
            ),
            array(
                "name" => "智选计划未还派息",
                "sql" => "select count(1) as num from itz_stat_repay where repay_status = 0 and (repay_type=2 or repay_type=3) and repay_time=".$today . " and interest!=0",
            ),
            array(
                "name" => "正常还息待处理",
                "sql" => "select count(1) as num from itz_repayment_plan where status!=4 and repay_time=$today and type=1",
            )
        );
        $content = "";
        foreach ($needCheck as $k => $v) {
           try{
                $result = Yii::app()->db->createCommand($v["sql"])->queryRow();
                if($result['num'] == 0){
                    continue;
                }
                $content .= $v['name'].":".$result['num']."条；\r\n";
           }catch(Exception $e){
                Yii::log(print_r($e->getMessage(),true), "error", __METHOD__);
                continue;
           } 
        }
        if(!empty($content)){
            Yii::log("RepaymentFinish content:".$content, "info", __METHOD__);
            FunctionUtil::alertToAccountTeam($content, [], true);
        }else{
            Yii::log("RepaymentFinish content is empty", "info", __METHOD__);
        }
        
        Yii::log("RepaymentFinish End", "info", __METHOD__);

    }
}
