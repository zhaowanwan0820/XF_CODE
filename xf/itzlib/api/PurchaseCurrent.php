<?php
/**
 * 申购活期
 * @wangxj 
 */
class PurchaseCurrent extends ItzApi{
    public $logcategory = "purchase.current";
    public $list_key = 'current_purchase';

    /**
     * 申购活期
     * $device:设备：0.pc，1.wap 2.ios  3.android
     **/
    public function run($user_id, $money, $device=0){
        $time = time();
        Yii::log("RequestData: user_id=$user_id; money=$money; time:$time; device:$device", "info", $this->logcategory);
        $data = $recharge_detail = array();
        $detail_sqls = "";
        //必要参数判断
        if(!is_numeric($user_id) || $user_id<=0 || !is_numeric($money) || $money<=0){
            Yii::log("user_id/money is Illegal; user_id=$user_id; money=$money;", "error", $this->logcategory);
            $this->code=7200;
            return $this;
        }

        //起投金额与递增金额
        $lowest_account = (int)Yii::app()->c->linkconfig['linghuo_config']['lowest_account'];
        $invest_step = (int)Yii::app()->c->linkconfig['linghuo_config']['invest_step'];

        //小于起投金额
        if(FunctionUtil::float_bigger_bc($lowest_account,$money)) {
            Yii::log("money<lowest_account: $money:$lowest_account; user_id=$user_id;", "error", $this->logcategory);
            $this->code=7223;
            return $this;
        }

        //金额是否为整数
        $int_money = (int)$money;
        if(!FunctionUtil::float_equal($money,$int_money)){
            Yii::log("money is int number: $money:$int_money", "error", $this->logcategory);
            $this->code=7222;
            return $this;
        }

        //获取本期项目时间（每日的10点到10点）
        $current_borrow_time = strtotime("midnight") + 10*60*60;
        if($time > $current_borrow_time) { //今日10点
            $pubtime = $current_borrow_time;
        } else { //昨日10点
            $pubtime = $current_borrow_time - 86400;
        }

        Yii::app()->db->beginTransaction();
        try{
            //账户加锁
            $accountInfo = Account::model()->findBySql('select * from dw_account where user_id=:user_id for update',array(':user_id'=>$user_id));
            if(empty($accountInfo)) {
                Yii::log("Account is empty; user_id:$user_id","error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7221;
                return $this;
            }

            //可用余额是否可用
            if(FunctionUtil::float_bigger_bc(0,$accountInfo['use_money']-$money)) {
                Yii::log("Account use_money<money; use_money:{$accountInfo['use_money']}; money:$money","error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7224;
                return $this;
            }

            //获取本期剩余额度
            $sql = "select * from itz_current_borrow where pubtime=:pubtime;";
            $currentBorrowInfo = ItzCurrentBorrow::model()->findBySql($sql, array(":pubtime"=>$pubtime));
            if(empty($currentBorrowInfo)) {
                Yii::log("currentBorrowInfo is empty; time:$time; sql:$sql", "error", $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7218;
                return $this;
            }
            if($currentBorrowInfo->status != 1) {
                Yii::log("currentBorrowInfo status != 1", "error", $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7219;
                return $this;
            }
            
            //判断项目配额
            $left_purchase_quota = $currentBorrowInfo->purchase_quota - $currentBorrowInfo->purchase_amount - $money;
            //超过项目总配额，返回错误
            if(FunctionUtil::float_bigger_bc(0,$left_purchase_quota)) {
                Yii::log("left_purchase_quota<0; left_purchase_quota:$left_purchase_quota;", "error", $this->logcategory);
                Yii::app()->db->rollback();
                $this->code_data[1] = (int)($currentBorrowInfo->purchase_quota - $currentBorrowInfo->purchase_amount);
                $this->code = 7220;
                return $this;
            }

            //活期申购资金使用顺序：
            //未满15天充值部分（针对未满15天的部分，优先提现充值天数少的），满15天充值部分，息，到期回本
            $need_money = 0; //还需要多少钱,中间状态
            $use_recharge = 0;//使用到充值的钱
            $use_interest = 0;//使用到利息的钱
            $use_capital = 0;//使用到充值的钱
            //充值的钱
            if(FunctionUtil::float_bigger_bc($accountInfo['recharge_amount'],0)) {
                //要使用的充值的钱
                $use_recharge = min($accountInfo['recharge_amount'], $money);
                //获取充值详情
                $rechargeDetailInfo = AccountService::getInstance()->getRechargeDetail($user_id, $use_recharge);
                if(empty($rechargeDetailInfo)){
                    Yii::log("dw_account_recharge_detail empty","error",$this->logcategory);
                    Yii::app()->db->rollback();
                    $this->code = 7225;
                    return $this;
                }
                //处理充值详情入表 
                foreach ($rechargeDetailInfo['recharge_detail'] as $detail) {
                    $recharge_detail[$detail['id']] = (string)round($detail['money'], 2);//收集使用了每笔充值金额的钱
                    $tmp = array();
                    $tmp['id'] = $detail['id'];
                    $tmp['use_recharge_money'] = $detail['use_recharge_money'] - $detail['money'];
                    $tmp['no_use_recharge_money'] = $detail['no_use_recharge_money'] + $detail['money'];
                    $detail_sqls .= "('".implode("','", $tmp)."'),";        #拼凑sql的value值
                }
                $detail_sqls = rtrim($detail_sqls , ",");
                if(!empty($detail_sqls)) {
                    $detail_sql = "INSERT INTO dw_account_recharge_detail (id, use_recharge_money, no_use_recharge_money) VALUES $detail_sqls ON DUPLICATE KEY".
                    " UPDATE use_recharge_money=VALUES(use_recharge_money), no_use_recharge_money=VALUES(no_use_recharge_money)";
                    $command = Yii::app()->db->createCommand($detail_sql)->execute();
                    Yii::log("dw_account_recharge_detail SQl:$detail_sql","error",$this->logcategory);
                    if (false == $command) {
                        Yii::log("dw_account_recharge_detail update fail, ".print_r($command->errors,true)." \n","error",$this->logcategory);
                        Yii::app()->db->rollback();
                        $this->code = 7225;
                        return $this;
                    }
                }
            }

            //利息
            $need_money = $money - $use_recharge; 
            if(FunctionUtil::float_bigger_bc($need_money, 0)) {
                $use_interest = min($accountInfo['withdraw_free']-$accountInfo['invested_money'], $need_money);
            }

            //本金
            $use_capital = $money - $use_recharge - $use_interest;
            if(FunctionUtil::float_bigger_bc(0, $accountInfo['invested_money']-$use_capital)) {
                Yii::log("account invested_money<use_capital, user_id:$user_id, invested_money:{$accountInfo['invested_money']}; use_capital:$use_capital","error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7226;
                return $this;
            }

            $use_capital = round($use_capital,2);
            $use_interest = round($use_interest,2);
            $use_recharge = round($use_recharge,2);
            
            //插入申购记录
            $purchase = new ItzCurrentPurchase();
            $purchase->user_id = $user_id;
            $purchase->money = $money;
            $purchase->capital = $use_capital;
            $purchase->interest = $use_interest;
            $purchase->recharge = $use_recharge;
            $purchase->borrow_id = $currentBorrowInfo->id;
            $purchase->recharge_detail = empty($recharge_detail)?"":json_encode($recharge_detail);
            $purchase->device = (int)$device;
            $purchase->addtime = $time;
            $purchase->addip = FunctionUtil::ip_address();
            $result = $purchase->save();
            if(false === $result) {
                Yii::log("addpurchase save fail, user_id:$user_id ".print_r($purchase->getErrors(),true),"error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7227;
                return $this;
            }
            $purchase_id = $purchase->id;

            //更改资金及增加log
            $log['related_id'] = $purchase_id;
            $log['related_type'] = 'purchase';
            $log['log_type'] = 'purchase_frost';
            $log['user_id'] = $user_id;
            $log['type'] = $log['log_type'];
            $log['direction'] = 0;
            $log['transid'] = $log['related_type']."_".$log['related_id'] ;
            $log['money'] = $money;
            $log['total'] = $accountInfo['total'];
            $log['use_money'] = $accountInfo['use_money'] - $money;
            $log['no_use_money'] = $accountInfo['no_use_money'] + $money;
            $log['collection'] = $accountInfo['collection'];
            $log['withdraw_free'] = $accountInfo['withdraw_free'] - $use_interest - $use_capital;
            $log['to_user'] = "0";
            $log['recharge_amount'] = $accountInfo['recharge_amount'] - $use_recharge;
            $log['invested_money']  = $accountInfo['invested_money'] - $use_capital;
            $log['remark'] = "活期申购冻结";
            $log_result = AccountService::getInstance()->addLog($log);
            if(false == $log_result) {
                Yii::log("addlog fail: ".json_encode($log),"error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7228;
                return $this;
            }

            //加入redis队列
            $redis_data['purchase_id'] = $purchase_id;
            $redis_data['user_id'] = $user_id;
            $redis_data['money'] = $money;
            $redis_data['borrow_id'] = $currentBorrowInfo->id;
            $redis_data['status'] = 0;
            $redis_result = Yii::app()->dqueue->rpush($this->list_key, json_encode($redis_data));
            Yii::log("redis rpush: ".json_encode($redis_data),"info",$this->logcategory);
            if($redis_result == null || $redis_result == false) {
                Yii::log("redis rpush fail: $redis_result","error",$this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7229;
                return $this;
            }
            Yii::app()->db->commit();

            $data = array();
            $data['user_id'] = $user_id;
            $data['money'] = $money;
            $data['purchase_list_id'] = $purchase_id;
            $data['status'] = 0;
            $data['addtime'] = $time;
            $this->code = 0;
            $this->data = $data;
            return $this;
        }catch(Exception $e){
            Yii::log("PurchaseCurrent Fail:".print_r($e->getMessage(),true),"error", $this->logcategory);
            Yii::app()->db->rollback();
            $this->code = 7231;
            return $this;
        }  
    }

}
