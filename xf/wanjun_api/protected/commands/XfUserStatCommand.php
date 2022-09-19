<?php

class XfUserStatCommand extends CConsoleCommand {
    //受让人信息，需要排除
    protected  $assignee_uid = [];

    /**
     * 先锋用户充提差数据统计
     * 尊享普惠数据统计
     * @param $user_id
     * @param $limit
     * @return bool
     */
    public function actionStatistics($user_id='', $limit=0){
        self::echoLog(" XfUserStat statistics run start !!! ");
        try {
            //需要排除的受让人
            $this->getAssigneeUid();
            $user_id_c = '';
            if(!empty($user_id)){
                $user_id_c = " and user_id=$user_id ";
            }

            //总数
            $count_sql = "select user_id,is_online,zx_recharge,ph_recharge,zx_withdraw,ph_withdraw from xf_user_recharge_withdraw WHERE  user_id not in (". implode(',',$this->assignee_uid).")  $user_id_c limit $limit,100000 ";
            $user_list = Yii::app()->db->createCommand($count_sql)->queryAll();
            if(empty($user_list)){
                self::echoLog(" XfUserStat statistics user_list empty ");
                return false;
            }

            //逐一处理用户信息
            foreach ($user_list as $value){
                $h_user_id = $value['user_id'];
                self::echoLog(" XfUserStat user_id:$h_user_id start ");
                $loan_info = [];
                //在途用户
                if($value['is_online'] == 1){
                    //各平台在途本金,利息计算
                    $loan_info = $this->getLoanInfo($h_user_id);
                }
                //认购债权总额统计
                $debt_info = $this->getDebtInfo($h_user_id);
                //累计债转金额
                $sell_debt_info = $this->getSellDebtInfo($h_user_id);
                //累计线下还款
                $repay_info = $this->getRepayInfo($h_user_id);
                $edit_data = array_merge($loan_info, $debt_info, $sell_debt_info, $repay_info);
                //充提差计算
                $edit_data['zx_increase'] = round($value['zx_recharge']+$edit_data['zx_buy_debt'], 2);//尊享充值总额（尊享历史充值金额+尊享认购债权金额）
                $edit_data['ph_increase'] = round($value['ph_recharge']+$edit_data['ph_buy_debt']+$edit_data['ph_zdx_buy_debt'], 2);;//普惠充值总额（普惠历史充值金额+普惠认购债权金额）
                $edit_data['zx_reduce'] = round($value['zx_withdraw']+$edit_data['zx_sell_debt']+$edit_data['zx_exchange']+$edit_data['zx_deduct']+$edit_data['zx_repay'], 2); //尊享提现总额（尊享历史提现金额+尊享转让债权金额+尊享债权兑换金额+尊享债权划扣金额+尊享线下还款金额）
                $edit_data['ph_reduce'] = round($value['ph_withdraw']+$edit_data['ph_sell_debt']+$edit_data['ph_exchange']+$edit_data['ph_deduct']+$edit_data['ph_repay']+$edit_data['ph_zdx_sell_debt']+$edit_data['ph_zdx_exchange']+$edit_data['ph_zdx_deduct']+$edit_data['ph_zdx_repay'], 2);;//普惠提现总额（普惠历史提现金额+普惠转让债权金额+普惠债权兑换金额+普惠债权划扣金额+普惠线下还款金额
                $edit_data['zx_increase_reduce'] = bcsub($edit_data['zx_increase'], $edit_data['zx_reduce'], 2);//尊享充提差
                $edit_data['ph_increase_reduce'] = bcsub($edit_data['ph_increase'], $edit_data['ph_reduce'], 2);//普惠充提差
                $edit_data['user_id'] = $h_user_id;
                $edit_ret = BaseCrudService::getInstance()->update('XfUserRechargeWithdraw', $edit_data, "user_id");
                if(!$edit_ret){
                    self::echoLog(" XfUserStat user_id:$h_user_id edit error ");
                    continue;
                }
                self::echoLog(" XfUserStat user_id:$h_user_id success ");
            }

            $this->echoLog("XfUserStat statistics end ");
        } catch (Exception $e) {
            self::echoLog("XfUserStat statistics Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }

    /**
     * 先锋用户充提差数据统计,每日一次
     * 尊享普惠数据统计
     * @return bool
     */
    public function actionDayStatistics(){
        self::echoLog(" XfUserStat DayStatistics run start !!! ");
        try {
            //需要排除的受让人
            $this->getAssigneeUid();
            //获取今日发生数据变更的用户
            $today_repay_user_list = $this->todayChangeUserList();
            if(count($today_repay_user_list) == 0){
                self::echoLog(" XfUserStat DayStatistics end, todayChangeUserList is empty !!! ");
                return false;
            }

            //逐一同步变更
            foreach($today_repay_user_list as $h_user_id){
                self::echoLog(" XfUserStat DayStatistics user_id：$h_user_id start ");
                $user_sql = "select user_id,is_online,zx_recharge,ph_recharge,zx_withdraw,ph_withdraw from xf_user_recharge_withdraw WHERE  user_id=$h_user_id  ";
                $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
                if(!$user_info){
                    self::echoLog(" XfUserStat DayStatistics user_id：$h_user_id end, xf_user_recharge_withdraw error !!! ");
                    continue;
                }

                $loan_info = $frozen_loan_info = [];
                //在途用户
                if($user_info['is_online'] == 1){
                    //各平台在途本金,利息计算
                    $loan_info = $this->getLoanInfo($h_user_id);
                    //冻结在途本金
                    $frozen_loan_info = $this->getFrozenLoanInfo($h_user_id);
                }
                //认购债权总额统计
                $debt_info = $this->getDebtInfo($h_user_id);
                //累计债转金额
                $sell_debt_info = $this->getSellDebtInfo($h_user_id);
                //累计线下还款
                $repay_info = $this->getRepayInfo($h_user_id);
                $edit_data = array_merge($frozen_loan_info,$loan_info, $debt_info, $sell_debt_info, $repay_info);
                //充提差计算
                $edit_data['zx_increase'] = round($user_info['zx_recharge']+$edit_data['zx_buy_debt'], 2);//尊享充值总额（尊享历史充值金额+尊享认购债权金额）
                $edit_data['ph_increase'] = round($user_info['ph_recharge']+$edit_data['ph_buy_debt']+$edit_data['ph_zdx_buy_debt'], 2);;//普惠充值总额（普惠历史充值金额+普惠认购债权金额）
                $edit_data['zx_reduce'] = round($user_info['zx_withdraw']+$edit_data['zx_sell_debt']+$edit_data['zx_exchange']+$edit_data['zx_deduct']+$edit_data['zx_repay'], 2); //尊享提现总额（尊享历史提现金额+尊享转让债权金额+尊享债权兑换金额+尊享债权划扣金额+尊享线下还款金额）
                $edit_data['ph_reduce'] = round($user_info['ph_withdraw']+$edit_data['ph_sell_debt']+$edit_data['ph_exchange']+$edit_data['ph_deduct']+$edit_data['ph_repay']+$edit_data['ph_zdx_sell_debt']+$edit_data['ph_zdx_exchange']+$edit_data['ph_zdx_deduct']+$edit_data['ph_zdx_repay'], 2);;//普惠提现总额（普惠历史提现金额+普惠转让债权金额+普惠债权兑换金额+普惠债权划扣金额+普惠线下还款金额
                $edit_data['zx_increase_reduce'] = bcsub($edit_data['zx_increase'], $edit_data['zx_reduce'], 2);//尊享充提差
                $edit_data['ph_increase_reduce'] = bcsub($edit_data['ph_increase'], $edit_data['ph_reduce'], 2);//普惠充提差
                $edit_data['user_id'] = $h_user_id;
                $edit_ret = BaseCrudService::getInstance()->update('XfUserRechargeWithdraw', $edit_data, "user_id");
                if(!$edit_ret){
                    self::echoLog(" XfUserStat DayStatistics user_id:$h_user_id edit error ");
                    continue;
                }
                self::echoLog(" XfUserStat DayStatistics user_id:$h_user_id success ");
            }

            $this->echoLog("XfUserStat DayStatistics end ");
        } catch (Exception $e) {
            self::echoLog("XfUserStat DayStatistics Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }


    /**
     * 线下还款数据同步
     * @return bool
     */
    private function todayChangeUserList(){
        self::echoLog(" todayChangeUserList start ");
        $change_time = strtotime('yesterday');
        $repay_change_time = $change_time-8*60*60;
        //部分还本
        $capital_sql= "select distinct user_id from ag_wx_partial_repay_detail where status=1 and repay_status=1 and repay_yestime>=$change_time   ";
        $zdx_capital_sql= "select distinct user_id from offline_partial_repay_detail where platform_id=4 and status=1 and repay_status=1 and repay_yestime>=$change_time   ";
        //常规+特殊
        $repay_sql= "SELECT distinct loan_user_id  as user_id from firstp2p_deal_loan_repay WHERE  type in (1,2)  and real_time>=$repay_change_time and repaid_amount>0 and last_part_repay_time=0    ";
        $zdx_repay_sql= "SELECT distinct loan_user_id as user_id  from offline_deal_loan_repay WHERE platform_id=4 and type in (1,2)  and real_time>=$repay_change_time and repaid_amount>0 and last_part_repay_time=0   ";
        //尊享数据查询
        $zx_part_list = Yii::app()->db->createCommand($capital_sql)->queryAll();
        $zx_repay_list = Yii::app()->db->createCommand($repay_sql)->queryAll();
        //普惠数据查询
        $ph_part_list = Yii::app()->phdb->createCommand($capital_sql)->queryAll();
        $ph_repay_list = Yii::app()->phdb->createCommand($repay_sql)->queryAll();
        //智多鑫数据查询
        $zdx_part_list = Yii::app()->offlinedb->createCommand($zdx_capital_sql)->queryAll();
        $zdx_repay_list = Yii::app()->offlinedb->createCommand($zdx_repay_sql)->queryAll();


        //债权变更用户
        $debt_sql= "select distinct user_id from firstp2p_debt where user_id not in (". implode(',',$this->assignee_uid).") and status=2 and sold_money>0 and successtime>=$change_time  ";
        $zdx_debt_sql= "select distinct user_id from offline_debt where user_id not in (". implode(',',$this->assignee_uid).") and platform_id=4 and status=2 and sold_money>0 and successtime>=$change_time   ";
        $new_loan_sql= "select distinct user_id from firstp2p_deal_load where user_id not in (". implode(',',$this->assignee_uid).") and debt_type=2 and create_time>=$change_time  ";
        $zdx_new_loan_sql= "select distinct user_id from offline_deal_load where user_id not in (". implode(',',$this->assignee_uid).") and platform_id=4 and debt_type=2 and create_time>=$change_time   ";
        $zx_debt_list = Yii::app()->db->createCommand($debt_sql)->queryAll();
        $ph_debt_list = Yii::app()->phdb->createCommand($debt_sql)->queryAll();
        $zdx_debt_list = Yii::app()->offlinedb->createCommand($zdx_debt_sql)->queryAll();
        $zx_new_loan_list = Yii::app()->db->createCommand($new_loan_sql)->queryAll();
        $ph_new_loan_list = Yii::app()->phdb->createCommand($new_loan_sql)->queryAll();
        $zdx_new_loan_list = Yii::app()->offlinedb->createCommand($zdx_new_loan_sql)->queryAll();

        //债权冻结变更
        $frozen_capital_sql= "select distinct user_id from firstp2p_deal_load where xf_status=1 and frozen_time>=$change_time   ";
        $zdx_frozen_capital_sql= "select distinct user_id from offline_deal_load where xf_status=1 and frozen_time>=$change_time and platform_id=4 ";
        $zx_f_wait_list = Yii::app()->db->createCommand($frozen_capital_sql)->queryAll();
        $ph_f_wait_list = Yii::app()->phdb->createCommand($frozen_capital_sql)->queryAll();
        $zdx_f_wait_list = Yii::app()->offlinedb->createCommand($zdx_frozen_capital_sql)->queryAll();

        //合并用户
        $change_user_list = array_merge($zx_f_wait_list,$ph_f_wait_list,$zdx_f_wait_list,$zx_part_list ,$zx_repay_list ,$ph_part_list ,$ph_repay_list ,$zdx_part_list ,$zdx_repay_list,$zx_debt_list,$ph_debt_list,$zdx_debt_list,$zx_new_loan_list,$ph_new_loan_list,$zdx_new_loan_list);
        $user_list = $this->array_unique_fb($change_user_list);
        return $user_list;
    }

    /**
     * 获取冻结在途信息
     * @param $user_id
     * @return array
     */
    private function getFrozenLoanInfo($user_id){
        $return_data = [
            'zx_wait_capital_frozen'=>0.00,//尊享冻结在途本金
            'ph_wait_capital_frozen'=>0.00,//普惠冻结在途本金
            'ph_zdx_wait_capital_frozen'=>0.00//智多新冻结在途本金
        ];
        $loan_sql = "select sum(wait_capital) as capital_total   from firstp2p_deal_load where status=1 and wait_capital>0 and xf_status=1 and user_id=$user_id  ";
        $offline_loan_sql = "select sum(wait_capital) as capital_total   from offline_deal_load where status=1 and wait_capital>0 and xf_status=1 and user_id=$user_id and platform_id=4 ";
        //尊享数据查询
        $zx_loan_info = Yii::app()->db->createCommand($loan_sql)->queryRow();
        if(!empty($zx_loan_info)){
            $return_data['zx_wait_capital_frozen'] = $zx_loan_info['capital_total'];
        }
        //普惠数据查询
        $ph_loan_info = Yii::app()->phdb->createCommand($loan_sql)->queryRow();
        if(!empty($ph_loan_info)){
            $return_data['ph_wait_capital_frozen'] = $ph_loan_info['capital_total'];
        }
        //智多新数据
        $zdx_loan_info = Yii::app()->offlinedb->createCommand($offline_loan_sql)->queryRow();
        if(!empty($zdx_loan_info)){
            $return_data['ph_zdx_wait_capital_frozen'] = $zdx_loan_info['capital_total'];
        }
        return $return_data;
    }


    /**
     * 获取在途信息
     * @param $user_id
     * @return array
     */
    private function getLoanInfo($user_id){
        $return_data = [
            'zx_wait_capital'=>0.00,//尊享直投在途本金
            'zx_new_wait_capital'=>0.00,//尊享认购债转在途本金
            'zx_wait_interest'=>0.00,//尊享在途利息
            'ph_wait_capital'=>0.00,//普惠在途本金
            'ph_new_wait_capital'=>0.00,//普惠新增在途本金
            'ph_wait_interest'=>0.00,//普惠在途利息
            'ph_zdx_wait_capital'=>0.00,//智多新历史在途本金
            'ph_zdx_new_wait_capital'=>0.00,//智多新逾期后新增在途本金
        ];
        $loan_sql = "select sum(wait_capital) as capital_total,sum(wait_interest) as interest_total,debt_type from firstp2p_deal_load where status=1 and user_id=$user_id group by debt_type";
        //尊享数据查询
        $zx_loan_info = Yii::app()->db->createCommand($loan_sql)->queryAll();
        if(!empty($zx_loan_info)){
            foreach ($zx_loan_info as $zx_v){
                if($zx_v['debt_type'] == 1){
                    $return_data['zx_wait_capital'] = $zx_v['capital_total'];
                    $return_data['zx_wait_interest'] = bcadd($return_data['zx_wait_interest'], $zx_v['interest_total'], 2);
                }
                if($zx_v['debt_type'] == 2){
                    $return_data['zx_new_wait_capital'] = $zx_v['capital_total'];
                    $return_data['zx_wait_interest'] = bcadd($return_data['zx_wait_interest'], $zx_v['interest_total'], 2);
                }
            }
        }
        //普惠数据查询
        $ph_loan_info = Yii::app()->phdb->createCommand($loan_sql)->queryAll();
        if(!empty($ph_loan_info)){
            foreach ($ph_loan_info as $ph_v){
                if($ph_v['debt_type'] == 1){
                    $return_data['ph_wait_capital'] = $ph_v['capital_total'];
                    $return_data['ph_wait_interest'] = bcadd($return_data['ph_wait_interest'], $ph_v['interest_total'], 2);
                }
                if($ph_v['debt_type'] == 2){
                    $return_data['ph_new_wait_capital'] = $ph_v['capital_total'];
                    $return_data['ph_wait_interest'] = bcadd($return_data['ph_wait_interest'], $ph_v['interest_total'], 2);
                }
            }
        }
        //智多新数据
        $offline_loan_sql = "select sum(wait_capital) as capital_total,sum(wait_interest) as interest_total,debt_type from offline_deal_load where status=1 and user_id=$user_id and platform_id=4 group by debt_type";
        $zdx_loan_info = Yii::app()->offlinedb->createCommand($offline_loan_sql)->queryAll();
        if(!empty($zdx_loan_info)){
            foreach ($zdx_loan_info as $zdx_v){
                if($zdx_v['debt_type'] == 1){
                    $return_data['ph_zdx_wait_capital'] = $zdx_v['capital_total'];
                }
                if($zdx_v['debt_type'] == 2){
                    $return_data['ph_zdx_new_wait_capital'] = $zdx_v['capital_total'];
                }
            }
        }
        return $return_data;
    }

    /**
     * 获取债权认购信息
     * @param $user_id
     * @return array
     */
    private function getDebtInfo($user_id){
        $return_data = [
            'zx_buy_debt'=>0.00,//尊享认购债权金额
            'ph_buy_debt'=>0.00,//普惠不含智多新认购债权金额
            'ph_zdx_buy_debt'=>0.00,//智多新认购债权金额
        ];
        $loan_sql = "select sum(money) as money_total from firstp2p_deal_load where debt_type=2 and  user_id=$user_id ";
        //尊享数据查询
        $zx_money_total = Yii::app()->db->createCommand($loan_sql)->queryScalar();
        if(!empty($zx_money_total) && $zx_money_total>0) {
            $return_data['zx_buy_debt'] = $zx_money_total;
        }

        //普惠数据查询
        $ph_money_total = Yii::app()->phdb->createCommand($loan_sql)->queryScalar();
        if(!empty($ph_money_total) && $ph_money_total>0) {
            $return_data['ph_buy_debt'] = $ph_money_total;
        }
        //智多新数据
        $offline_loan_sql = "select sum(money) as money_total from offline_deal_load where debt_type=2 and  user_id=$user_id and platform_id=4 ";
        $zdx_money_total = Yii::app()->offlinedb->createCommand($offline_loan_sql)->queryScalar();
        if(!empty($zdx_money_total) && $zdx_money_total>0) {
            $return_data['ph_zdx_buy_debt'] = $zdx_money_total;
        }
        return $return_data;
    }

    /**
     * 获取债转信息
     * @param $user_id
     * @return array
     */
    private function getSellDebtInfo($user_id){
        $return_data = [
            'zx_sell_debt'=>0.00,//尊享转让债权金额
            'zx_exchange'=>0.00,//尊享债权兑换金额
            'zx_deduct'=>0.00,//尊享债权划扣金额
            'ph_sell_debt'=>0.00,//普惠不含智多新转让债权金额
            'ph_exchange'=>0.00,//普惠不含智多新债权兑换金额
            'ph_deduct'=>0.00,//普惠不含智多新债权划扣金额
            'ph_zdx_sell_debt'=>0.00,//智多新转让债权金额
            'ph_zdx_exchange'=>0.00,//智多新转让债权金额
            'ph_zdx_deduct'=>0.00,//智多新债权划扣金额
        ];
        $loan_sql = "SELECT sum(sold_money) as sold_total,debt_src from firstp2p_debt WHERE status=2 and  user_id=$user_id group by debt_src";
        //尊享数据查询
        $zx_loan_info = Yii::app()->db->createCommand($loan_sql)->queryAll();
        if(!empty($zx_loan_info)){//1-权益兑换、2-债转交易、3债权划扣 4、一键下车
            foreach ($zx_loan_info as $zx_v){
                if($zx_v['debt_src'] == 1){
                    $return_data['zx_exchange'] = $zx_v['sold_total'];
                }
                if($zx_v['debt_src'] == 2){
                    $return_data['zx_sell_debt'] = $zx_v['sold_total'];
                }
                if($zx_v['debt_src'] == 3){
                    $return_data['zx_deduct'] = $zx_v['sold_total'];
                }
            }
        }
        //普惠数据查询
        $ph_loan_info = Yii::app()->phdb->createCommand($loan_sql)->queryAll();
        if(!empty($ph_loan_info)){
            foreach ($ph_loan_info as $ph_v){
                if($ph_v['debt_src'] == 2){
                    $return_data['ph_sell_debt'] = $ph_v['sold_total'];
                }
                if($ph_v['debt_src'] == 3){
                    $return_data['ph_deduct'] = $ph_v['sold_total'];
                }
                if(in_array($ph_v['debt_src'], [1,4])){
                    $return_data['ph_exchange'] = bcadd($ph_v['sold_total'], $return_data['ph_exchange'], 2);
                }
            }
        }
        //智多新数据
        $offline_loan_sql = "SELECT sum(sold_money) as sold_total,debt_src from offline_debt WHERE status=2 and user_id=$user_id and platform_id=4 group by debt_src";
        $zdx_loan_info = Yii::app()->offlinedb->createCommand($offline_loan_sql)->queryAll();
        if(!empty($zdx_loan_info)){
            foreach ($zdx_loan_info as $zdx_v){
                if($zdx_v['debt_src'] == 1){
                    $return_data['ph_zdx_exchange'] = $zdx_v['sold_total'];
                }
                if($zdx_v['debt_src'] == 2){
                    $return_data['ph_zdx_sell_debt'] = $zdx_v['sold_total'];
                }
                if($zdx_v['debt_src'] == 3){
                    $return_data['ph_zdx_deduct'] = $zdx_v['sold_total'];
                }
            }
        }
        return $return_data;
    }

    /**
     * 获取债权认购信息
     * @param $user_id
     * @return array
     */
    private function getRepayInfo($user_id){
        $return_data = [
            'zx_repay'=>0.00,//尊享线下还款金额
            'ph_repay'=>0.00,//普惠不含智多新线下还款金额
            'ph_zdx_repay'=>0.00,//智多新线下还款金额
        ];
        //部分还本
        $capital_sql= "select sum(repay_money) from ag_wx_partial_repay_detail where status=1 and repay_status=1 and user_id= $user_id ";
        //常规+特殊
        $repay_sql= "SELECT sum(repaid_amount) from firstp2p_deal_loan_repay WHERE loan_user_id= $user_id and type in (1,2)  and real_time>1570118400 and repaid_amount>0 and last_part_repay_time=0   ";
        //尊享数据查询
        $zx_part_total = Yii::app()->db->createCommand($capital_sql)->queryScalar();
        $zx_repay_total = Yii::app()->db->createCommand($repay_sql)->queryScalar();
        if(!empty($zx_part_total) && $zx_part_total>0) {
            $return_data['zx_repay'] = $zx_part_total;
        }
        if(!empty($zx_repay_total) && $zx_repay_total>0) {
            $return_data['zx_repay'] = bcadd($return_data['zx_repay'], $zx_repay_total, 2);
        }

        //普惠数据查询
        $ph_part_total = Yii::app()->phdb->createCommand($capital_sql)->queryScalar();
        $ph_repay_total = Yii::app()->phdb->createCommand($repay_sql)->queryScalar();
        if(!empty($ph_part_total) && $ph_part_total>0) {
            $return_data['ph_repay'] = $ph_part_total;
        }
        if(!empty($ph_repay_total) && $ph_repay_total>0) {
            $return_data['ph_repay'] = bcadd($return_data['ph_repay'], $ph_repay_total, 2);
        }

        //部分还本
        $capital_sql= "select sum(repay_money) from offline_partial_repay_detail where status=1 and platform_id=4 and repay_status=1 and user_id= $user_id ";
        //常规+特殊
        $repay_sql= "SELECT sum(repaid_amount) from offline_deal_loan_repay WHERE loan_user_id= $user_id and platform_id=4 and type in (1,2)  and real_time>1570118400 and repaid_amount>0 and last_part_repay_time=0   ";
        $zdx_part_total = Yii::app()->offlinedb->createCommand($capital_sql)->queryScalar();
        $zdx_repay_total = Yii::app()->offlinedb->createCommand($repay_sql)->queryScalar();
        if(!empty($zdx_part_total) && $zdx_part_total>0) {
            $return_data['ph_zdx_repay'] = $zdx_part_total;
        }
        if(!empty($zdx_repay_total) && $zdx_repay_total>0) {
            $return_data['ph_zdx_repay'] = bcadd($return_data['ph_zdx_repay'], $zdx_repay_total, 2);
        }
        return $return_data;
    }

    /**
     * 获取有解商城受让人
     */
    private function getAssigneeUid(){
        $user_sql = "select user_id from ag_wx_assignee_info WHERE transferred_amount>0 ";
        $user_info = Yii::app()->db->createCommand($user_sql)->queryAll();
        if(!empty($user_info)){
            foreach ($user_info as $u){
                $this->assignee_uid[] = $u['user_id'];
            }
        }
    }

    //二维数组去重
    public function array_unique_fb($array2D) {
        $temp = [];
        foreach ($array2D as $v) {
            $v = join(",", $v); //降维
            $temp[] = $v;
        }
        $temp = array_unique($temp);//去掉重复的字符串
        return $temp;
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
     * 在途标的冻结金额更新,每日一次
     * 尊享普惠数据统计
     * @return bool
     */
    public function actionDayDealFrozenCapital(){
        self::echoLog(" XfUserStat DayDealFrozenCapital run start !!! ");
        try {
            //获取今日发生数据变更的用户
            $today_deal_list = $this->todayChangeDealList();
            if(count($today_deal_list) == 0){
                self::echoLog(" XfUserStat DayDealFrozenCapital end, todayChangeDealList is empty !!! ");
                return false;
            }

            //逐一同步变更
            $table_prefix = '';
            foreach($today_deal_list as $db_name => $value){
                self::echoLog(" XfUserStat DayDealFrozenCapital db_name：{$db_name} start ");
                if($db_name == 'phdb'){
                    $table_prefix = "PH";
                }elseif($db_name == 'offlinedb'){
                    $table_prefix = "Offline";
                }
                //标的详情
                $deal_model = $table_prefix."Deal";

                //逐一更新
                foreach($value as $deal_value){
                    self::echoLog(" XfUserStat DayDealFrozenCapital deal_id：{$deal_value['deal_id']} start ");
                    $deal_info = $deal_model::model()->findByPk($deal_value['deal_id']);
                    if(!$deal_info){
                        self::echoLog(" XfUserStat DayDealFrozenCapital deal_id：{$deal_value['deal_id']} end, $deal_model error !!! ");
                        continue;
                    }

                    $frozen_capital = bcadd($deal_info->frozen_wait_capital, $deal_value['frozen_wait_capital'], 2);
                    $edit_ret = $deal_model::model()->updateByPk($deal_value['deal_id'], ['frozen_wait_capital'=>$frozen_capital]);
                    if(!$edit_ret){
                        self::echoLog(" XfUserStat DayDealFrozenCapital deal_id:{$deal_value['deal_id']} edit error ");
                        continue;
                    }
                    self::echoLog(" XfUserStat DayDealFrozenCapital deal_id:{$deal_value['deal_id']} success ");
                }
                self::echoLog(" XfUserStat DayDealFrozenCapital db_name：{$db_name} end ");
            }

            $this->echoLog("XfUserStat DayDealFrozenCapital end ");
        } catch (Exception $e) {
            self::echoLog("XfUserStat DayDealFrozenCapital Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }


    private function todayChangeDealList(){
        self::echoLog(" todayChangeDealList start ");
        $change_time = strtotime('yesterday');
        //$change_time = 0;
        //债权冻结变更
        $frozen_capital_sql= "select sum(wait_capital) as frozen_wait_capital, deal_id from firstp2p_deal_load where status=1 and xf_status=1 and frozen_time>=$change_time  group by deal_id  ";
        $zdx_frozen_capital_sql= "select sum(wait_capital) as frozen_wait_capital, deal_id from offline_deal_load where status=1 and xf_status=1 and frozen_time>=$change_time and platform_id=4 group by deal_id ";
        $zx_f_wait_list = Yii::app()->db->createCommand($frozen_capital_sql)->queryAll();
        $ph_f_wait_list = Yii::app()->phdb->createCommand($frozen_capital_sql)->queryAll();
        $zdx_f_wait_list = Yii::app()->offlinedb->createCommand($zdx_frozen_capital_sql)->queryAll();

        //合并用户
        $change_deal_list['db'] = $zx_f_wait_list;
        $change_deal_list['phdb'] = $ph_f_wait_list;
        $change_deal_list['offlinedb'] = $zdx_f_wait_list;
        //$change_deal_list  = array_merge($zx_f_wait_list,$ph_f_wait_list,$zdx_f_wait_list);
        return $change_deal_list;
    }

    /**
     * 在途标的冻结金额更新,每日一次
     * 尊享普惠数据统计
     * @return bool
     */
    public function actionDayUserFrozenCapital(){
        self::echoLog(" XfUserStat DayUserFrozenCapital run start !!! ");
        try {
            //获取今日发生数据变更的用户
            $today_user_list = $this->todayChangeUserListT();
            if(count($today_user_list) == 0){
                self::echoLog(" XfUserStat DayUserFrozenCapital end, todayChangeUserListT is empty !!! ");
                return false;
            }

            //逐一同步变更
            foreach($today_user_list as $db_name => $value){
                self::echoLog(" XfUserStat DayUserFrozenCapital db_name：{$db_name} start ");
                //逐一更新
                foreach($value as $user_value){
                    self::echoLog(" XfUserStat DayUserFrozenCapital user_id：{$user_value['user_id']} start ");
                    if($db_name == 'offlinedb'){
                        $sql = "select user_id,frozen_wait_capital from  offline_user_platform where user_id={$user_value['user_id']} and platform_id=4 ";
                    }else{
                        $sql = "select id as user_id,frozen_wait_capital from  firstp2p_user where id={$user_value['user_id']}  ";
                    }
                    $user_info = Yii::app()->$db_name->createCommand($sql)->queryRow();
                    if(!$user_info){
                        self::echoLog(" XfUserStat DayUserFrozenCapital user_id：{$user_value['user_id']}  user_info  error !!! ");
                        continue;
                    }

                    $frozen_capital = bcadd($user_info['frozen_wait_capital'], $user_value['frozen_wait_capital'], 2);
                    if($db_name == 'offlinedb'){
                        $edit_sql = "update offline_user_platform set frozen_wait_capital=$frozen_capital where user_id={$user_value['user_id']} and platform_id=4 ";
                    }else{
                        $edit_sql = "update firstp2p_user set frozen_wait_capital=$frozen_capital where id={$user_value['user_id']}  ";
                    }
                    $edit_ret = Yii::app()->$db_name->createCommand($edit_sql)->execute();
                    if(!$edit_ret){
                        self::echoLog(" XfUserStat DayUserFrozenCapital user_id:{$user_value['user_id']} edit error ");
                        continue;
                    }
                    self::echoLog(" XfUserStat DayUserFrozenCapital user_id:{$user_value['user_id']} success ");
                }
                self::echoLog(" XfUserStat DayUserFrozenCapital db_name：{$db_name} end ");
            }

            $this->echoLog("XfUserStat DayUserFrozenCapital end ");
        } catch (Exception $e) {
            self::echoLog("XfUserStat DayUserFrozenCapital Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
    }

    private function todayChangeUserListT(){
        self::echoLog(" todayChangeUserList start ");
        $change_time = strtotime('yesterday');
        //$change_time = 0;
        //债权冻结变更
        $frozen_capital_sql= "select sum(wait_capital) as frozen_wait_capital, user_id from firstp2p_deal_load where status=1 and xf_status=1 and frozen_time>=$change_time  group by user_id  ";
        $zdx_frozen_capital_sql= "select sum(wait_capital) as frozen_wait_capital, user_id from offline_deal_load where status=1 and xf_status=1 and frozen_time>=$change_time and platform_id=4 group by user_id ";
        $zx_f_wait_list = Yii::app()->db->createCommand($frozen_capital_sql)->queryAll();
        $ph_f_wait_list = Yii::app()->phdb->createCommand($frozen_capital_sql)->queryAll();
        $zdx_f_wait_list = Yii::app()->offlinedb->createCommand($zdx_frozen_capital_sql)->queryAll();

        //合并用户
        $change_deal_list['db'] = $zx_f_wait_list;
        $change_deal_list['phdb'] = $ph_f_wait_list;
        $change_deal_list['offlinedb'] = $zdx_f_wait_list;
        //$change_deal_list  = array_merge($zx_f_wait_list,$ph_f_wait_list,$zdx_f_wait_list);
        return $change_deal_list;
    }

}
