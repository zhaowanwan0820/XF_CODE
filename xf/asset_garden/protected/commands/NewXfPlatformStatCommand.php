<?php

class NewXfPlatformStatCommand extends CConsoleCommand {
    //受让人信息，需要排除
    protected  $assignee_uid = [];
    private $stat_end_time;
    private $stat_start_time;
    private $handel_platform_list=[];
    private $handel_platform_id = '';

    /**
     * 先锋数据处理
     * 尊享普惠数据统计
     * @param $stat_date “例:2020-05-01”
     * @return bool
     */
    public function actionStatistics($stat_date = ''){
        $this->echoLog("NewXfPlatformStat statistics run start $stat_date");

        //默认统计昨日数据
        if($stat_date === ''){
            $stat_date = date("Y-m-d", strtotime("-1 day"));
        }
        //指定日期统计
        $this->stat_start_time = strtotime($stat_date);
        $this->stat_end_time = $this->stat_start_time+86400;
        Yii::app()->db->beginTransaction();
        try {
            //需要排除的受让人
            $this->getAssigneeUid();
            
            //需要统计的平台
            $this->handel_platform_list = Yii::app()->c->itouzi['xf_statistics_platform'];
            if(empty($this->handel_platform_list)){
                $this->echoLog("statistics: statistics_platform empty ");
                Yii::app()->db->rollback();
                return false;
            }

            //获取各平台统计数据
            $platform_insert_info = $this->getInsertData();
            if($platform_insert_info == false){
                $this->echoLog("statistics: getInsertData return false ");
                Yii::app()->db->rollback();
                return false;
            }

            Yii::app()->db->commit();
            $this->echoLog("statistics end ");
        } catch (Exception $e) {
            self::echoLog("statistics Exception,error_msg:".print_r($e->getMessage(),true), "email");
            Yii::app()->db->rollback();
        }
    }


    /**
     * 按平台统计
     * @return bool
     */
    private function getInsertData(){
        $this->echoLog("getInsertData run start，platform_ids：".json_encode($this->handel_platform_list));

        //累计初始值
        $total_data['capital_total'] = $total_data['interest_total'] = $total_data['shop_debt_money_total'] = $total_data['shop_debt_user_total'] = 0;
        $total_data['cash_repayment_total'] = $total_data['offline_debt_money_total'] = $total_data['shop_debt_money'] = $total_data['cash_repayment'] = $total_data['offline_debt_money'] = 0;
        $total_data['shop_debt_user'] = $total_data['cash_repayment_user'] = $total_data['offline_debt_user'] = $total_data['repayment_capital_total'] = $total_data['repayment_interest_total'] = 0;
        $total_data['repayment_capital'] = $total_data['repayment_interest'] = $total_data['repayment_user'] = $total_data['repayment_clear_user'] = $total_data['repayment_clear_user_total'] = 0;


        //先锋数据
        $deal_load_user = $shop_debt_user_list = $offline_debt_user_list = $cash_repayment_user_list = $shop_user_total_list = array();
        //网信数据
        $wx_total_data = $wx_deal_load_user = $shop_debt_user_list = $offline_debt_user_list = $cash_repayment_user_list = $shop_user_total_list = array();
        foreach($this->handel_platform_list as $platform_id){
            $this->echoLog("getInsertData platform_id: $platform_id start");
            //开始处理平台
            $this->handel_platform_id = $platform_id;
            //需要排除的受让人
            $this->getAssigneeUid();
            //查询待处理数据，每次处理一条
            $stat_info = XfDataStatistics::model()->find(" platform_id=$this->handel_platform_id and add_time=$this->stat_start_time ");
            if($stat_info){
                $this->echoLog("statistics: ".date("Y-m-d")." already statistics ");
                return false;
            }

            //网信维度数据统计
            if($platform_id == 5){
                $wx_total_data = $total_data;
                $wx_deal_load_user = $deal_load_user;
                $wx_shop_debt_user_list = $shop_debt_user_list;
                $wx_offline_debt_user_list = $offline_debt_user_list;
                $wx_cash_repayment_user_list = $cash_repayment_user_list;
                $wx_shop_user_total_list = $shop_user_total_list;
            }

            $insert_data = [];
            $insert_data['platform_id'] = $this->handel_platform_id;//所属平台 0-全平台 1-尊享 2-普惠 3-金融工厂,4-智多新,5-东方红,6-中国龙
            $insert_data['add_time']  = $this->stat_start_time;//添加时间
            $insert_data['handle_time'] = time();//处理时间

            //在途人数数据获取
            $deal_load = $this->getDealLoad();
            $insert_data['distinct_user_total'] = count($deal_load);//在途总人数

            //全平台在途总人数
            $deal_load_user = array_merge($deal_load_user, $deal_load);

            //在途本金利息数据获取
            $loan_info = $this->getLoanRepay();//在途数据
            $insert_data['capital_total'] = !empty($loan_info[1]) ? $loan_info[1] : 0;//在途本金总额
            $insert_data['interest_total'] = !empty($loan_info[2]) ? $loan_info[2] : 0;//在途利息总额

            //全平台在途本金利息数据获取
            $total_data['capital_total'] = bcadd($total_data['capital_total'], $insert_data['capital_total'], 2);
            $total_data['interest_total'] = bcadd($total_data['interest_total'], $insert_data['interest_total'], 2);

            //有解化债金额
            $insert_data['shop_debt_money_total'] = $this->getShopTotal();//有解商城累计化债金额
            $insert_data['shop_debt_money'] = $this->getShopTotal($this->stat_start_time);//当日有解商城化债金额

            //全平台有解化债金额
            $total_data['shop_debt_money_total'] = bcadd($total_data['shop_debt_money_total'], $insert_data['shop_debt_money_total'], 2);
            $total_data['shop_debt_money'] = bcadd($total_data['shop_debt_money'], $insert_data['shop_debt_money'], 2);

            //商城累计化债人数
            $total_shop_debt_user = $this->getShopUserTotal();//累计有解商城兑付人数
            $insert_data['shop_debt_user_total'] = count($total_shop_debt_user);//有解商城累计化债金额
            $shop_user_total_list = array_merge($shop_user_total_list, $total_shop_debt_user);//全平台商城累计化债用户

            //商城当日用户信息
            $shop_debt_user  = $this->getShopUserTotal($this->stat_start_time);
            $insert_data['shop_debt_user'] = count($shop_debt_user);//当日有解商城兑付人数
            $shop_debt_user_list = array_merge($shop_debt_user_list, $shop_debt_user);//全平台当日有解商城兑付人数

            //债权划扣
            $t_offline_debt_money_total = $this->getDebtDeduct();
            $day_offline_debt_money = $this->getDebtDeduct($this->stat_start_time);
            $insert_data['offline_debt_money_total'] = $t_offline_debt_money_total['debt_account_t'];//线下咨询权益化债总金额
            $insert_data['offline_debt_money'] = $day_offline_debt_money['debt_account_t'];//当日线下咨询权益化债金额
            $insert_data['offline_debt_user'] = count($day_offline_debt_money['user_list']);//当日线下咨询权益化债人数

            //全平台债权划扣
            $offline_debt_user_list = array_merge($offline_debt_user_list, $day_offline_debt_money['user_list']);
            $total_data['offline_debt_money_total'] = bcadd($total_data['offline_debt_money_total'], $insert_data['offline_debt_money_total'], 2);
            $total_data['offline_debt_money'] = bcadd($total_data['offline_debt_money'], $insert_data['offline_debt_money'], 2);

            //现金兑付统计
            $t_repayment_total = $this->getRepaymentTotal();
            $day_repayment_total = $this->getRepaymentTotal($this->stat_start_time);
            $insert_data['cash_repayment_total'] = bcadd($t_repayment_total['capital_total'],$t_repayment_total['interest_total'], 2);//现金累计兑付金额
            $insert_data['cash_repayment'] = bcadd($day_repayment_total['capital_total'],$day_repayment_total['interest_total'], 2);//当日现金兑付金额
            $insert_data['cash_repayment_user'] = count($day_repayment_total['user_list']);//当日现金兑付人数

            //全平台兑付统计
            $cash_repayment_user_list = array_merge($cash_repayment_user_list, $day_repayment_total['user_list']);
            $total_data['cash_repayment_total'] = bcadd($total_data['cash_repayment_total'], $insert_data['cash_repayment_total'], 2);
            $total_data['cash_repayment'] = bcadd($total_data['cash_repayment'], $insert_data['cash_repayment'], 2);


            //累计兑付本金
            $repayment_user = array_merge($day_repayment_total['user_list'], $day_offline_debt_money['user_list'], $shop_debt_user);
            $repayment_user = array_unique($repayment_user);
            $insert_data['repayment_capital_total'] = round($t_repayment_total['capital_total']+$insert_data['offline_debt_money_total']+$insert_data['shop_debt_money_total'], 2);//累计兑付本金 ( 现金+有解+ 划扣)
            $insert_data['repayment_interest_total'] = $t_repayment_total['interest_total'];//累计兑付利息( 现金+有解+ 划扣)
            $insert_data['repayment_capital'] = round($day_repayment_total['capital_total']+$insert_data['shop_debt_money']+$insert_data['offline_debt_money'], 2);//当日兑付本金 ( 现金+有解+ 划扣)
            $insert_data['repayment_interest'] = $day_repayment_total['interest_total'];//当日兑付利息( 现金+有解+ 划扣)
            $insert_data['repayment_user'] = count($repayment_user);//当日兑付出借人数
            unset($day_repayment_total, $day_offline_debt_money, $shop_debt_user);

            //全平台累计
            $total_data['repayment_capital_total'] = bcadd($total_data['repayment_capital_total'], $insert_data['repayment_capital_total'], 2);
            $total_data['repayment_interest_total'] = bcadd($total_data['repayment_interest_total'], $insert_data['repayment_interest_total'], 2);
            $total_data['repayment_capital'] = bcadd($total_data['repayment_capital'], $insert_data['repayment_capital'], 2);
            $total_data['repayment_interest'] = bcadd($total_data['repayment_interest'], $insert_data['repayment_interest'], 2);

            //出清数据
            $yesterday_user_data = $this->getClearUser($this->stat_start_time-86400);
            $total_user_data = $this->getClearUser(1562169600);
            $repayment_clear_user = $yesterday_user_data ? bcsub($yesterday_user_data['distinct_user_total'], $insert_data['distinct_user_total'], 0) : 0;
            $repayment_clear_user_total = $total_user_data ? bcsub($total_user_data['distinct_user_total'], $insert_data['distinct_user_total'], 0) : 0;
            $insert_data['repayment_clear_user'] = $repayment_clear_user;//当日出清出借人数
            $insert_data['repayment_clear_user_total'] = $repayment_clear_user_total;//累计出清出借人数(待孙芳提供2019年7月4号数据）

            //当日兑付项目ID
            $repay_info = $this->getRepayDeal($this->stat_start_time);
            if($repay_info){
                $insert_data['company'] = $repay_info['user_data'];//当日兑付借款企业
                $insert_data['guarantee_company'] = $repay_info['agency_data'];//当日兑付担保企业
                $insert_data['cooperation_company'] = $repay_info['advisory_data'];//当日兑付资产合作机构
            }

            //新增各平台数据
            $add_result = BaseCrudService::getInstance()->add("XfDataStatistics", $insert_data);
            if(false == $add_result){
                $this->echoLog("add XfDataStatistics platform_id:$this->handel_platform_id error, insert_data: ".print_r($insert_data, true));
                return false;
            }
            $this->echoLog("getInsertData platform_id: $platform_id end ");
        }

        //先锋汇总数据处理================================================================
        $total_data['platform_id'] = 99;//所属平台 99先锋维度全平台 0-网信维度全平台 1-尊享 2-普惠 3-金融工厂,4-智多新,5-东方红,6-中国龙
        $total_data['add_time'] = $this->stat_start_time;//统计时间
        $total_data['handle_time'] = time();//处理时间

        //去重后人数
        $deal_load_user = $this->array_unique_fb($deal_load_user);
        $total_data['distinct_user_total'] = count($deal_load_user);

        //当日有解商城兑付人数
        $shop_debt_user_list = array_unique($shop_debt_user_list);
        $total_data['shop_debt_user'] = count($shop_debt_user_list);

        //当日划扣人数
        $offline_debt_user_list = array_unique($offline_debt_user_list);
        $total_data['offline_debt_user'] = count($offline_debt_user_list);

        //当日现金兑付
        $cash_repayment_user_list = array_unique($cash_repayment_user_list);
        $total_data['cash_repayment_user'] = count($cash_repayment_user_list);

        //累计兑付去重后人数
        $shop_user_total_list = array_unique($shop_user_total_list);
        $total_data['shop_debt_user_total'] = count($shop_user_total_list);

        //当日累计兑付
        $repayment_user_list = array_merge($shop_debt_user_list, $offline_debt_user_list, $cash_repayment_user_list);
        $repayment_user_list = array_unique($repayment_user_list);
        $total_data['repayment_user'] = count($repayment_user_list);//当日兑付出借人数

        //全平台出清数据
        $this->handel_platform_id = 0;
        $p_yesterday_user_data = $this->getClearUser($this->stat_start_time-86400);
        $p_total_user_data = $this->getClearUser(1562169600);
        $p_repayment_clear_user = $p_yesterday_user_data ? bcsub($p_yesterday_user_data['distinct_user_total'], $total_data['distinct_user_total'], 0) : 0;
        $p_repayment_clear_user_total = $p_total_user_data ? bcsub($p_total_user_data['distinct_user_total'], $total_data['distinct_user_total'], 0) : 0;
        $total_data['repayment_clear_user'] = $p_repayment_clear_user;//当日出清出借人数
        $total_data['repayment_clear_user_total'] = $p_repayment_clear_user_total;//累计出清出借人数(待孙芳提供2019年7月4号数据）

        //企业维度统计
        $total_data['company'] = ''; //当日兑付借款企业JSON [{"id": "1","name": "abc","money": "1.11"}]',
        $total_data['guarantee_company'] = ''; //当日兑付担保企业JSON [{"id": "1","name": "abc","money": "1.11"}]',
        $total_data['cooperation_company'] = ''; //当日兑付资产合作机构JSON [{"id": "1","name": "abc","money": "1.11"}]',

        //新增全平台数据
        $add_result = BaseCrudService::getInstance()->add("XfDataStatistics", $total_data);
        if(false == $add_result){
            $this->echoLog("add XfDataStatistics platform_id:99 error, insert_data: ".print_r($total_data, true));
            return false;
        }



        //网信汇总数据处理================================================================
        $wx_total_data['platform_id'] = 0;//所属平台 99先锋维度全平台 0-网信维度全平台 1-尊享 2-普惠 3-金融工厂,4-智多新,5-东方红,6-中国龙
        $wx_total_data['add_time'] = $this->stat_start_time;//统计时间
        $wx_total_data['handle_time'] = time();//处理时间


        //去重后人数
        $wx_deal_load_user = $this->array_unique_fb($wx_deal_load_user);
        $wx_total_data['distinct_user_total'] = count($wx_deal_load_user);

        //当日有解商城兑付人数
        $wx_shop_debt_user_list = array_unique($wx_shop_debt_user_list);
        $wx_total_data['shop_debt_user'] = count($wx_shop_debt_user_list);

        //当日划扣人数
        $wx_offline_debt_user_list = array_unique($wx_offline_debt_user_list);
        $wx_total_data['offline_debt_user'] = count($wx_offline_debt_user_list);

        //当日现金兑付
        $wx_cash_repayment_user_list = array_unique($wx_cash_repayment_user_list);
        $wx_total_data['cash_repayment_user'] = count($wx_cash_repayment_user_list);

        //累计兑付去重后人数
        $wx_shop_user_total_list = array_unique($wx_shop_user_total_list);
        $wx_total_data['shop_debt_user_total'] = count($wx_shop_user_total_list);

        //当日累计兑付
        $wx_repayment_user_list = array_merge($wx_shop_debt_user_list, $wx_offline_debt_user_list, $wx_cash_repayment_user_list);
        $wx_repayment_user_list = array_unique($wx_repayment_user_list);
        $wx_total_data['repayment_user'] = count($wx_repayment_user_list);//当日兑付出借人数

        //全平台出清数据
        $this->handel_platform_id = 0;
        $wx_p_yesterday_user_data = $this->getClearUser($this->stat_start_time-86400);
        $wx_p_total_user_data = $this->getClearUser(1562169600);
        $wx_p_repayment_clear_user = $wx_p_yesterday_user_data ? bcsub($wx_p_yesterday_user_data['distinct_user_total'], $wx_total_data['distinct_user_total'], 0) : 0;
        $wx_p_repayment_clear_user_total = $wx_p_total_user_data ? bcsub($wx_p_total_user_data['distinct_user_total'], $wx_total_data['distinct_user_total'], 0) : 0;
        $wx_total_data['repayment_clear_user'] = $wx_p_repayment_clear_user;//当日出清出借人数
        $wx_total_data['repayment_clear_user_total'] = $wx_p_repayment_clear_user_total;//累计出清出借人数(待孙芳提供2019年7月4号数据）

        //企业维度统计
        $wx_total_data['company'] = ''; //当日兑付借款企业JSON [{"id": "1","name": "abc","money": "1.11"}]',
        $wx_total_data['guarantee_company'] = ''; //当日兑付担保企业JSON [{"id": "1","name": "abc","money": "1.11"}]',
        $wx_total_data['cooperation_company'] = ''; //当日兑付资产合作机构JSON [{"id": "1","name": "abc","money": "1.11"}]',

        //新增全平台数据
        $add_wx_result = BaseCrudService::getInstance()->add("XfDataStatistics", $wx_total_data);
        if(false == $add_wx_result){
            $this->echoLog("add XfDataStatistics platform_id:0 error, insert_data: ".print_r($wx_total_data, true));
            return false;
        }

        return true;
    }

    
    /**
     * 处理firstp2p_user表is_online字段数据，半小时执行一次
     */
    public function actionUserStatus(){
        $this->echoLog("UserStatus start");
        $cdb = new CDbCriteria;
        $cdb->select = " id ";
        $cdb->condition = "  is_online = 1 ";
        $cdb->limit = 200;
        $i = 0;
        while (true){
            $cdb->offset = $i;
            $users = User::model()->findAll($cdb);
            //没有数据了
            if (count($users) <= 0) {
                $this->echoLog("UserStatus end");
                return false;
            }

            $this->echoLog("UserStatus handel start:".$i);

            foreach ($users as $user) {
                //尊享
                $zx_ret = DealLoad::model()->find("user_id=$user->id and status=1 ");
                if($zx_ret){
                    continue;
                }
                //普惠
                $ph_ret = PHDealLoad::model()->find("user_id=$user->id and status=1");
                if($ph_ret){
                    continue;
                }
                //线下产品
                $offline_ret = OfflineDealLoad::model()->find("user_id=$user->id and status=1");
                if($offline_ret){
                    continue;
                }

                $this->echoLog(" user_id:$user->id  edit is_online=0 start ");
                //尊享用户
                $zx_update = User::model()->updateByPk($user->id,['is_online'=>0]);
                if($zx_update===false){
                    $this->echoLog('update zx_user error user_ids :'.$user->id);
                }
                //普惠用户
                $ph_update = PHUser::model()->updateByPk($user->id,['is_online'=>0]);
                if($ph_update===false){
                    $this->echoLog('update ph_user error user_ids :'.$user->id);
                }
                $this->echoLog(" user_id:$user->id edit is_online=0 end success ");

            }
            $i +=200;
        }
        $this->echoLog("UserStatus end");
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


    /**
     * 现金+有解+以资化债 还款项目
     * @param $time
     * @return array
     */
    private function getRepayDeal($time){
        $this->echoLog("getRepayDeal platform_id:$this->handel_platform_id  start, time:".date("Y-m-d", $time));
        if(empty($time) || !is_numeric($time)){
            return [];
        }

        $todo_data = [
            'user_data' => [],
            'agency_data' => [],
            'advisory_data' => [],

        ];

        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        $loan_end_time  = $this->stat_end_time-8*60*60;
        $loan_start_time = $this->stat_start_time-8*60*60;
        if(in_array($this->handel_platform_id, [3,4,5])){
            $part_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(rd.repay_money) as repay_money_t
                        from offline_partial_repay_detail rd 
                        left join offline_deal d on d.id=rd.deal_id 
                        where rd.platform_id=$this->handel_platform_id and rd.repay_status=1 and rd.repay_yestime>=$time and rd.repay_yestime<$this->stat_end_time group by d.user_id,d.agency_id,d.advisory_id";
            $loan_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(lr.money) as repay_money_t
                        from offline_deal_loan_repay lr 
                        left join offline_deal d on d.id=lr.deal_id 
                        where lr.platform_id=$this->handel_platform_id and lr.type in (1,2) and lr.status=1 and lr.real_time>=$loan_start_time and lr.real_time<$loan_end_time group by d.user_id,d.agency_id,d.advisory_id";
            $debt_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(debt.sold_money) as repay_money_t
                        from offline_debt debt 
                        left join offline_deal d on d.id=debt.borrow_id 
                        where debt.platform_id=$this->handel_platform_id and debt.status=2 and debt.debt_src in (1,3) and successtime>=$time and successtime<$this->stat_end_time  group by d.user_id,d.agency_id,d.advisory_id";
        }else{
            $part_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(rd.repay_money) as repay_money_t
                        from ag_wx_partial_repay_detail rd 
                        left join firstp2p_deal d on d.id=rd.deal_id 
                        where rd.repay_status=1 and rd.repay_yestime>=$time and rd.repay_yestime<$this->stat_end_time group by d.user_id,d.agency_id,d.advisory_id";
            $loan_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(lr.money) as repay_money_t
                        from firstp2p_deal_loan_repay lr 
                        left join firstp2p_deal d on d.id=lr.deal_id 
                        where lr.type in (1,2) and lr.status=1 and lr.real_time>=$loan_start_time and lr.real_time<$loan_end_time group by d.user_id,d.agency_id,d.advisory_id";
            $debt_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(debt.sold_money) as repay_money_t
                        from firstp2p_debt debt 
                        left join firstp2p_deal d on d.id=debt.borrow_id 
                        where debt.status=2 and debt.debt_src in (1,3) and successtime>=$time and successtime<$this->stat_end_time  group by d.user_id,d.agency_id,d.advisory_id";

        }

        //今日部分还款本金
        $part_repay = Yii::app()->{$db_name}->createCommand($part_sql)->queryAll();

        //常规+特殊还款
        $loan_repay = Yii::app()->{$db_name}->createCommand($loan_sql)->queryAll();

        //尊享债权划扣+权益兑换
        $debt = Yii::app()->{$db_name}->createCommand($debt_sql)->queryAll();

        //数据整合
        $all_company_data = array_merge($part_repay, $loan_repay, $debt);
        if(empty($all_company_data)){
            return false;
        }
        //企业信息
        $c_user_id = $agency_id = [];
        foreach ($all_company_data as $value){
            $c_user_id[$value['user_id']] = $value['user_id'];
        }
        //获取企业名称
        $c_user_info = $this->getUserInfo($c_user_id);
        //尊享担保机构咨询方信息
        if($all_company_data){
            foreach ($all_company_data as $zx_v){
                $agency_id[$zx_v['agency_id']] = $zx_v['agency_id'];
                $agency_id[$zx_v['advisory_id']] = $zx_v['advisory_id'];
            }
            $agency_info = $this->getAgencyInfo($agency_id);
            foreach ($all_company_data as $zx_v){
                $user_total = round($todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['name'] =  $c_user_info[$zx_v['user_id']];
                $todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['repay_money_t'] = $user_total;

                $agency_total = round($todo_data['agency_data'][$agency_info[$zx_v['agency_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['agency_data'][$agency_info[$zx_v['agency_id']]]['name'] =  $agency_info[$zx_v['agency_id']];
                $todo_data['agency_data'][$agency_info[$zx_v['agency_id']]]['repay_money_t'] =  $agency_total;

                $advisory_total = round($todo_data['advisory_data'][$agency_info[$zx_v['advisory_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['advisory_data'][$agency_info[$zx_v['advisory_id']]]['name'] =  $agency_info[$zx_v['advisory_id']];
                $todo_data['advisory_data'][$agency_info[$zx_v['advisory_id']]]['repay_money_t'] =  $advisory_total;
            }
        }

        $todo_data['user_data'] = json_encode(array_values($todo_data['user_data']));
        $todo_data['agency_data'] = json_encode(array_values($todo_data['agency_data']));
        $todo_data['advisory_data'] = json_encode(array_values($todo_data['advisory_data']));
        return $todo_data;
    }

    /**
     * 借款企业信息获取
     * @param $user_id
     * @return array
     */
    private function getUserInfo($user_id){
        $this->echoLog("getUserInfo start");
        if(empty($user_id)){
            return [];
        }
        //借款人信息获取
        $company_info = [];
        $user_sql = "select u.id,u.user_type,e.company_name,uc.name 
							from firstp2p_user u 
							left join firstp2p_enterprise e on e.user_id=u.id 
							left join firstp2p_user_company uc on uc.user_id=u.id 
							where u.id in (".implode(',', $user_id).") ";
        $user_info = Yii::app()->db->createCommand($user_sql)->queryAll();
        if(empty($user_info)){
            return [];
        }

        foreach ($user_info as $user){
            if($user['user_type'] == 1){
                $company_info[$user['id']] = $user['company_name'];
                continue;
            }
            $company_info[$user['id']] = $user['name'];
        }
        return $company_info;
    }

    /**
     * 咨询方担保方信息获取
     * @param $ag_id
     * @return array
     */
    private function getAgencyInfo($ag_id){
        $this->echoLog("getAgencyInfo platform_id:$this->handel_platform_id start");
        if(empty($ag_id)){
            return [];
        }
        //借款人信息获取
        $company_info = [];
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        if(in_array($this->handel_platform_id, [3,4,5])){
            $ag_sql = "select id,name from offline_deal_agency WHERE  id in (".implode(',', $ag_id).") ";
        }else{
            $ag_sql = "select id,name from firstp2p_deal_agency WHERE  id in (".implode(',', $ag_id).") ";
        }
        $ag_info = Yii::app()->{$db_name}->createCommand($ag_sql)->queryAll();
        if(empty($ag_info)){
            return [];
        }
        foreach ($ag_info as $agency){
            $company_info[$agency['id']] = $agency['name'];
        }
        return $company_info;
    }

    /**
     * 当日出清人数
     * @param int $time
     * @return array
     */
    private function getClearUser($time){
        $this->echoLog("getClearUser platform_id:$this->handel_platform_id start, time:".date("Y-m-d", $time));
        if(empty($time) || !is_numeric($time)){
            return [];
        }
        //查询时间
        $stat_sql= "select *  from xf_data_statistics where platform_id=$this->handel_platform_id and add_time=$time ";
        $stat_info = Yii::app()->db->createCommand($stat_sql)->queryRow();
        return $stat_info;
    }

    private function getDebtDeduct($time=0){
        $this->echoLog("getDebtDeduct platform_id:$this->handel_platform_id start, ".date("Y-m-d", $time));
        //初始
        $deduct_ret = ['debt_account_t'=>0, 'total_uid'=>0 , 'user_list' => [] ];
        //查询时间
        $condition = $time>0 ? " and successtime>=$time and successtime<$this->stat_end_time " : '';
        $deduct_sql= "select sum(debt_account) as debt_account_t from firstp2p_debt_deduct_log where status=2 and deal_type=$this->handel_platform_id $condition  ";
        //尊享+普惠划扣
        $deduct_info = Yii::app()->db->createCommand($deduct_sql)->queryScalar();
        if($deduct_info && $deduct_info >0 ){
            $deduct_ret['debt_account_t'] = $deduct_info;
        }
        //当日用户明细
        if($time == 0){
            return $deduct_ret;
        }

        //统计今日用户信息
        $debt_sql= "select distinct user_id  from firstp2p_debt_deduct_log where status=2 $condition  ";
        $all_user_list = Yii::app()->db->createCommand($debt_sql)->queryAll();
        $all_user_list = $this->array_unique_fb($all_user_list);
        /*
        if($all_user_list){
            $deduct_ret['user_list'] = $all_user_list;
            $deduct_ret['total_uid'] = count($all_user_list);
        }*/
        $deduct_ret['user_list'] = !empty($all_user_list) ? $all_user_list : [];
        return $deduct_ret;
    }

    /**
     * 商城消债人数
     * @param int $time
     * @return string
     */
    private function getShopUserTotal($time=0){
        $this->echoLog("getShopUserTotal platform_id:$this->handel_platform_id start , time:".date("Y-m-d", $time));
        //$shop_ret = ['total_uid'=>0 , 'user_list' => [] ];
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        //查询时间
        $condition = $time>0 ? " and addtime>=$time and addtime<$this->stat_end_time " : '';
        if(in_array($this->handel_platform_id, [3,4,5])){
            $shop_sql= "select distinct user_id from offline_debt_exchange_log where platform_id=$this->handel_platform_id and status=2 $condition  ";
        }else{
            $shop_sql= "select distinct user_id from firstp2p_debt_exchange_log where status=2 $condition  ";
        }

        $debt_users = Yii::app()->{$db_name}->createCommand($shop_sql)->queryAll();
        $user_info = $this->array_unique_fb($debt_users);
        //$shop_ret['total_uid'] = count($debt_users);
        //$shop_ret['user_list'] = $debt_users;
        return !empty($user_info) ? $user_info : [];
    }

    /**
     * 现金兑付
     * @param int $time
     * @return string
     */
    private function getRepaymentTotal($time=0){
        $this->echoLog("getRepaymentTotal platform_id:$this->handel_platform_id  start, time:".date("Y-m-d", $time));
        $repay_ret = ['capital_total'=>0, 'interest_total'=>0, 'total_uid'=>0 , 'user_list' => [] ];
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');

        //还款时间
        $loan_real_time = $time-60*60*8;
        $loan_end_real_time = $this->stat_end_time - 60*60*8;
        if(in_array($this->handel_platform_id, [3,4,5])){
            $capital_sql= "select sum(repay_money) from offline_partial_repay_detail where platform_id=$this->handel_platform_id and repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            $user_sql= "select distinct user_id from offline_partial_repay_detail where platform_id=$this->handel_platform_id and repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            $repay_sql= "select sum(money) as repaid_amount_t, type  from offline_deal_loan_repay where last_part_repay_time=0 and platform_id=$this->handel_platform_id and type in (1,2) and status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time group by type  ";
            $r_user_sql= "select distinct loan_user_id as user_id from offline_deal_loan_repay where platform_id=$this->handel_platform_id and status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time  and type in (1,2)   ";
            $total_repay_sql= "select sum(repaid_amount) as repaid_amount_t,type  from offline_deal_loan_repay where platform_id=$this->handel_platform_id and type in (1,2) and repaid_amount>0 and is_zdx=0 group by type";
        }else{
            $capital_sql= "select sum(repay_money) from ag_wx_partial_repay_detail where repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            $user_sql= "select distinct user_id from ag_wx_partial_repay_detail where repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            $repay_sql= "select sum(money) as repaid_amount_t, type  from firstp2p_deal_loan_repay where  type in (1,2) and status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time and last_part_repay_time=0 group by type  ";
            $r_user_sql= "select distinct loan_user_id as user_id from firstp2p_deal_loan_repay where  status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time  and type in (1,2)   ";
            $total_repay_sql= "select sum(repaid_amount) as repaid_amount_t,type  from firstp2p_deal_loan_repay where type in (1,2) and repaid_amount>0 and is_zdx=0 and ( real_time>1562140800 or last_part_repay_time>0 ) group by type";
        }

        //当日兑付
        if($time > 0){
            //今日尊享部分还款本金
            $part_capital = Yii::app()->{$db_name}->createCommand($capital_sql)->queryScalar();
            //部分还款用户信息
            $part_user_info = Yii::app()->{$db_name}->createCommand($user_sql)->queryAll();
            //常规还款用户
            $repay_user_info = Yii::app()->{$db_name}->createCommand($r_user_sql)->queryAll();
            //今日常规还款+特殊还款
            $zx_repay = Yii::app()->{$db_name}->createCommand($repay_sql)->queryAll();
            if($zx_repay){
                foreach ($zx_repay as $zx_value){
                    if($zx_value['type'] == 1){
                        $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $zx_value['repaid_amount_t'], 2);
                    }
                    if($zx_value['type'] == 2){
                        $repay_ret['interest_total'] = bcadd($repay_ret['interest_total'], $zx_value['repaid_amount_t'], 2);
                    }
                }
            }
            //数据组合
            $end_user_info = array_merge($part_user_info, $repay_user_info);
            $end_user_info = $this->array_unique_fb($end_user_info);
            //$repay_ret['total_uid'] = count($end_user_info);
            $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $part_capital, 2);
            $repay_ret['user_list'] = $end_user_info;
            unset($part_user_info, $repay_user_info, $end_user_info);
            return $repay_ret;
        }
        //累计兑付
        $repay_total = Yii::app()->{$db_name}->createCommand($total_repay_sql)->queryAll();
        foreach ($repay_total as $value){
            if($value['type'] == 1){
                $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $value['repaid_amount_t'], 2);
            }
            if($value['type'] == 2){
                $repay_ret['interest_total'] = bcadd($repay_ret['interest_total'], $value['repaid_amount_t'], 2);
            }
        }
        return $repay_ret;
    }

    /**
     * 商城消债信息
     * @param int $time
     * @return string
     */
    private function getShopTotal($time=0){
        $this->echoLog("getShopTotal platform_id:$this->handel_platform_id start, time:".date("Y-m-d", $time));


        //数据库选择
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        //查询时间
        $condition = $time>0 ? " and addtime>=$time and addtime<$this->stat_end_time  " : '';
        if(in_array($this->handel_platform_id, [3,4,5])){
            $shop_sql= "select sum(debt_account) as zx_account_total  from offline_debt_exchange_log where platform_id = $this->handel_platform_id and status=2 $condition  ";
        }else{
            $shop_sql= "select sum(debt_account) as zx_account_total  from firstp2p_debt_exchange_log where status=2 $condition  ";
        }
        $account_total = Yii::app()->{$db_name}->createCommand($shop_sql)->queryScalar();
        return !empty($account_total) ? $account_total : 0;
    }

    /**
     *在途待还金额
     * @return string
     */
    private function getLoanRepay(){
        $this->echoLog("getLoanRepay platform_id:$this->handel_platform_id start");

        $repay_info = [];
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        $condition = !empty($this->assignee_uid) ? " and loan_user_id not in (". implode(',',$this->assignee_uid).") " : '';
        if(in_array($this->handel_platform_id, [3,4,5])){
            $loan_sql = "select sum(money) as money_total,type from offline_deal_loan_repay where platform_id = $this->handel_platform_id and  money>0 and status=0 and type in (1,2) $condition group by type";
        }else{
            $loan_sql = "select sum(money) as money_total,type from firstp2p_deal_loan_repay where time>=1562054400 and money>0 and status=0 and type in (1,2) and is_zdx=0 $condition group by type";
        }
        $loan_info = Yii::app()->{$db_name}->createCommand($loan_sql)->queryAll();
        if(!$loan_info){
            return [];
        }

        foreach ($loan_info  as $value){
            $repay_info[$value['type']] = $value['money_total'];
        }
        return $repay_info;
    }

    /**
     *在途投资明细
     * @return string
     */
    private function getDealLoad(){
        $this->echoLog("getDealLoad platform_id:$this->handel_platform_id start");

        //数据库选择
        $db_name = $this->handel_platform_id == 1 ? 'db' : ($this->handel_platform_id == 2 ? 'phdb' : 'offlinedb');
        //排除受让人
        $condition = !empty($this->assignee_uid) ? " and dl.user_id not in (". implode(',',$this->assignee_uid).") " : '';
        if(in_array($this->handel_platform_id, [3,4,5])){
            $load_sql = "select distinct dl.user_id from offline_deal_load dl where dl.platform_id=$this->handel_platform_id and  dl.wait_capital>0  $condition  ";
        }else{
            $load_sql = "select distinct dl.user_id from firstp2p_deal_load dl left join firstp2p_deal d on dl.deal_id=d.id  where dl.wait_capital>0 and d.is_zdx=0 $condition  ";
        }
        $load_list = Yii::app()->{$db_name}->createCommand($load_sql)->queryAll();
        return !empty($load_list) ? $load_list : [];
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


}
