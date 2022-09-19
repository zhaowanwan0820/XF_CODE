<?php
/**
 * 赎回申请API
 *
 * @author 
 */
class RedeemCurrent extends ItzApi{
    public $logcategory = "redeem.current";

    /**
    * money: 赎回金额
    * device: 设备信息 0.pc 1.wap 2.ios 3.android
    */
    public function run($user_id, $money, $device=0){
        Yii::log("RequestData: user_id=$user_id; money=$money; device:$device", "info", $this->logcategory);
        $midnight_time = strtotime("midnight");
        $now_time = time();
        $lowest_account = (int)Yii::app()->c->linkconfig['linghuo_config']['lowest_account'];       //最低起投金额
        $invest_step = (int)Yii::app()->c->linkconfig['linghuo_config']['invest_step'];             //递增金额
        $data = $current_log = array();                
        //简单参数验证
        if(empty($user_id) || !is_numeric($user_id) || $user_id < 0 || empty($money) || !is_numeric($money) 
            || FunctionUtil::float_bigger($lowest_account, $money, 3)){
            Yii::log("params illegal user_id:$user_id, money:$money, device:$device", "error", $this->logcategory);
            $this->code = 7200;
            return $this;
        }

        //赎回金额是invest_step的整数倍
        $remainder = fmod(floatval($money), $invest_step);
        if(!FunctionUtil::float_equal_bc($remainder, 0)){
            Yii::log("increasing amount is $invest_step user_id:$user_id, money:$money, device:$device", "error", $this->logcategory);
            $this->code = 7200;   
            return $this;
        }

        Yii::app()->db->beginTransaction();
        try{
            // 资金的使用顺序为（针对用户持有的整体活期资产）：到期回本，息，满15天充值部分，未满15天充值部分
            //（针对未满15天的部分，优先赎回充值天数多的，即针对所有的充值资金，按照充值天数从大到小赎回）；
            // 充值部分资金的使用顺序按照充值天数从大到小;
            $current_result = DwCurrentAccount::model()->findBySql('select * from dw_current_account where user_id=:user_id for update', array(':user_id'=>$user_id));
            if(empty($current_result)){
                Yii::log("current account is empty user_id:$user_id", 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7207;
                return $this;
            }

            //判断用户有没有额度, 赎回金额<=用户当前持有的零活计划余额
            if(FunctionUtil::float_bigger_bc($money, ($current_result->total-$current_result->redeeming_money))){
                Yii::log("more than user can be redeem amount user_id:$user_id, money:$money, redeem_amount:".($current_result->total-$current_result->redeeming_money), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code_data[1] = (int)($current_result->total-$current_result->redeeming_money);
                $this->code = 7203;
                return $this;
            }

            //在事务内做必要验证, 判断总额度, 今日剩余的赎回配额足够用户的赎回金额
            $redeem_result = ItzCurrentRedeemConf::model()->findBySql('select * from itz_current_redeem_conf where effect_time=:effect_time for update', array(':effect_time'=>$midnight_time));
            if(FunctionUtil::float_bigger_bc($money, ($redeem_result->redeem_quota-$redeem_result->redeem_amount), 3)){
                Yii::log("today redemption quota is not enough user_id: $user_id, money:$money, redeem_amount:".($redeem_result->redeem_quota-$redeem_result->redeem_amount), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code_data[1] = (int)($redeem_result->redeem_quota-$redeem_result->redeem_amount);
                $this->code = 7205;
                return $this;
            }

            //判断个人额度, 赎回金额<=个人每日赎回总额度-当日已赎回金额
            //用户当日已申请赎回金额
            $today_redeem_total = CurrentService::getInstance()->getRedeemInfo($user_id, 1, $midnight_time, $now_time);
            if(FunctionUtil::float_bigger_bc($money, ($redeem_result->user_redeem_quota-$today_redeem_total))){
                Yii::log("more than user can redeem the day limit user_id:$user_id, money:$money, redeem_amount:".($redeem_result->user_redeem_quota-$today_redeem_total), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code_data[1] = (int)($redeem_result->user_redeem_quota-$today_redeem_total);
                $this->code = 7204;
                return $this;   
            }

            //使用的本金
            $invested_money = min($money, $current_result['invested_money']);
            //使用的利息
            $interest = min(($money-$invested_money), $current_result['interest_money']);
            //使用的充值金额
            $recharge_amount = min(($money-$invested_money-$interest), $current_result['recharge_amount']);
            if(!FunctionUtil::float_equal_bc($money, ($invested_money+$interest+$recharge_amount))){
                Yii::log("money!=(invested_money+interest+recharge_amount)", 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7201;
                return $this;
            }

            //本金, 利息, 充值金额保留两位小数
            $invested_money = round($invested_money, 2);
            $interest = round($interest, 2);
            $recharge_amount = round($recharge_amount, 2);
            
            //集齐数据更新活期账户表
            $current_result->invested_money  = $current_result->invested_money-$invested_money;
            $current_result->interest_money  = $current_result->interest_money-$interest;
            $current_result->recharge_amount = $current_result->recharge_amount-$recharge_amount;
            $current_result->redeeming_money = $current_result->redeeming_money+$money;
            $update_current = $current_result->save();
            if($update_current == false){
                Yii::log('update dw_account_current error '.print_r($current_result->getErrors(), true), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7232;
                return $this;
            }

            //集齐数据, 插入一条数据到赎回列表中
            $current_list['user_id']         = $user_id;
            $current_list['redeem_conf_id']  = $redeem_result->id;      //活期赎回额度表的id
            $current_list['status']          = 1;                       //赎回请求成功
            $current_list['money']           = $money;
            $current_list['capital']         = $invested_money;
            $current_list['interest']        = $interest;
            $current_list['recharge']        = $recharge_amount;
            $current_list['device']          = (int)$device;
            $current_list['addtime']         = $now_time;
            $current_list['addip']           = FunctionUtil::ip_address();
            $add_current_list = BaseCrudService::getInstance()->add("ItzCurrentRedeem", $current_list);
            if($add_current_list == false){
                Yii::log('add itz_current_redeem error '.print_r($add_current_list->errors, true), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7233;
                return $this;
            }

            //更新赎回额度表的今日累计赎回金额
            $redeem_result->redeem_amount = $redeem_result->redeem_amount+$money;
            $update_redeem = $redeem_result->save();
            if($update_redeem == false){
                Yii::log('update itz_current_redeem_conf error '.print_r($redeem_result->getErrors(), true), 'error', $this->logcategory);
                Yii::app()->db->rollback();
                $this->code = 7234;
                return $this;
            }

            //记录current_account的日志, 记录的内容参考的dw_account_log
            $current_log = $current_result->attributes;
            $current_log['related_id'] = $add_current_list['id'];
            $current_log['related_type'] = "redeem";
            $current_log['borrow_id'] = $add_current_list['redeem_conf_id'];
            $current_log['log_type'] = "redeem_apply";
            $current_log['direction'] = "1";
            $current_log['money'] = $money;
            Yii::log("addCurrentAccountLog: ".json_encode($current_log), 'info', $this->logcategory);
            Yii::app()->db->commit();
            $this->code      = 0;        //赎回申请成功
            $data['user_id'] = $current_list['user_id'];
            $data['money']   = $current_list['money'];
            $data['status']  = $current_list['status'];
            $data['addtime'] = $current_list['addtime'];
            $this->data = $data;
            return $this;
        }catch(Exception $e){
            Yii::log(print_r($e->getMessage(),true), "error", $this->logcategory);
            Yii::app()->db->rollback();
            $this->code = 7231;
            return $this;
        }
    }
}