<?php

class XfDataHandleCommand extends CConsoleCommand {
    //受让人信息，需要排除
    protected  $assignee_uid = [];
    private $stat_end_time;
    private $stat_start_time;

    /**
     * 先锋数据处理
     * 尊享普惠数据统计
     * @param $stat_date “例:2020-05-01”
     * @return bool
     */
    public function actionStatistics($stat_date = ''){
        $this->echoLog("statistics run start $stat_date");

        //默认统计昨日数据
        if($stat_date === ''){
            $stat_date = date("Y-m-d", strtotime("-1 day"));
        }
        //指定日期统计
        $this->stat_start_time = strtotime($stat_date);
        $this->stat_end_time = $this->stat_start_time+86400;
        try {
            //需要排除的受让人
            $this->getAssigneeUid();
            //查询待处理数据，每次处理一条
            $stat_info = XfDataStatistics::model()->find("add_time=$this->stat_start_time");
            if($stat_info){
                $this->echoLog("statistics: ".date("Y-m-d")." already statistics ");
                return false;
            }

            $insert_data = array();
            $insert_data['add_time'] = $this->stat_start_time;//添加时间
            $insert_data['handle_time'] = time();//处理时间

            //在途人数数据获取
            $zx_deal_load = $this->getDealLoad(1);//尊享在途数据
            $ph_deal_load = $this->getDealLoad(2);//普惠在途数据
            $all_deal_load = array_merge($zx_deal_load, $ph_deal_load);
            $all_deal_load = $this->array_unique_fb($all_deal_load);
            $insert_data['zx_user_total'] = count($zx_deal_load);//尊享在途总人数
            $insert_data['ph_user_total'] = count($ph_deal_load);//普惠在途总人数
            $insert_data['distinct_user_total'] = count($all_deal_load);//去重后总人数
            unset($zx_deal_load, $ph_deal_load, $all_deal_load);


            //在途本金利息数据获取
            $zx_loan_info = $this->getLoanRepay(1);//尊享在途数据
            $ph_loan_info = $this->getLoanRepay(2);//普惠在途数据
            $insert_data['zx_capital_total'] = $zx_loan_info[1];// 尊享在途本金总额
            $insert_data['zx_interest_total'] = $zx_loan_info[2];// 尊享在途利息总额
            $insert_data['ph_capital_total'] = $ph_loan_info[1];// 普惠在途本金总额
            $insert_data['ph_interest_total'] = $ph_loan_info[2];// 普惠在途利息总额

            //有解化债金额
            $insert_data['shop_debt_money_total'] = $this->getShopTotal();// 有解商城累计化债金额
            $insert_data['shop_debt_money'] = $this->getShopTotal($this->stat_start_time);// 当日有解商城化债金额

            //有解用户信息
            $shop_debt_user  = $this->getShopUserTotal($this->stat_start_time);
            $insert_data['shop_debt_user'] = $shop_debt_user['total_uid'];// 当日有解商城兑付人数

            //债权划扣
            $t_offline_debt_money_total = $this->getDebtDeduct();
            $day_offline_debt_money = $this->getDebtDeduct($this->stat_start_time);
            $insert_data['offline_debt_money_total'] = $t_offline_debt_money_total['debt_account_t'];// 线下咨询权益化债总金额
            $insert_data['offline_debt_money'] = $day_offline_debt_money['debt_account_t'];// 当日线下咨询权益化债金额
            $insert_data['offline_debt_user'] = $day_offline_debt_money['total_uid'];// 当日线下咨询权益化债人数

            //现金兑付统计
            $t_repayment_total = $this->getRepaymentTotal();
            $day_repayment_total = $this->getRepaymentTotal($this->stat_start_time);
            $insert_data['cash_repayment_total'] = bcadd($t_repayment_total['capital_total'],$t_repayment_total['interest_total'], 2);// 现金累计兑付金额
            $insert_data['cash_repayment'] = bcadd($day_repayment_total['capital_total'],$day_repayment_total['interest_total'], 2);// 当日现金兑付金额
            $insert_data['cash_repayment_user'] = $day_repayment_total['total_uid'];// 当日现金兑付人数

            //累计兑付本金
            $repayment_user = array_merge($day_repayment_total['user_list'], $day_offline_debt_money['user_list'], $shop_debt_user['user_list']);
            $repayment_user = array_unique($repayment_user);
            $insert_data['repayment_capital_total'] = round($t_repayment_total['capital_total']+$insert_data['offline_debt_money_total']+$insert_data['shop_debt_money_total'], 2);// 累计兑付本金 ( 现金+有解+ 划扣)
            $insert_data['repayment_interest_total'] = $t_repayment_total['interest_total'];// 累计兑付利息( 现金+有解+ 划扣)
            $insert_data['repayment_capital'] = round($day_repayment_total['capital_total']+$insert_data['shop_debt_money']+$insert_data['offline_debt_money'], 2);// 当日兑付本金 ( 现金+有解+ 划扣)
            $insert_data['repayment_interest'] = $day_repayment_total['interest_total'];// 当日兑付利息( 现金+有解+ 划扣)
            $insert_data['repayment_user'] = count($repayment_user);// 当日兑付出借人数
            unset($day_repayment_total, $day_offline_debt_money, $shop_debt_user);

            //出清数据
            $yesterday_user_data = $this->getClearUser($this->stat_start_time-86400);
            $total_user_data = $this->getClearUser(1562169600);
            $repayment_clear_user = $yesterday_user_data ? bcsub($yesterday_user_data['distinct_user_total'], $insert_data['distinct_user_total'], 0) : 0;
            $repayment_clear_user_total = $total_user_data ? bcsub($total_user_data['distinct_user_total'], $insert_data['distinct_user_total'], 0) : 0;
            $insert_data['repayment_clear_user'] = $repayment_clear_user;// 当日出清出借人数
            $insert_data['repayment_clear_user_total'] = $repayment_clear_user_total;// 累计出清出借人数(待孙芳提供2019年7月4号数据）

            //当日兑付项目ID
            $repay_info = $this->getRepayDeal($this->stat_start_time);
            if($repay_info){
                $insert_data['company'] = $repay_info['user_data'];//当日兑付借款企业
                $insert_data['guarantee_company'] = $repay_info['agency_data'];//当日兑付担保企业
                $insert_data['cooperation_company'] = $repay_info['advisory_data'];//当日兑付资产合作机构
            }

            $add_result = BaseCrudService::getInstance()->add("XfDataStatistics", $insert_data);
            if(false == $add_result){
                $this->echoLog("add XfDataStatistics error, insert_data: ".print_r($insert_data, true));
            }

            $this->echoLog("statistics end ");
        } catch (Exception $e) {
            self::echoLog("statistics Exception,error_msg:".print_r($e->getMessage(),true), "email");
        }
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
                $offline_ret = OfflineDealLoad::model()->find("user_id=$user->id and status in (1,3)");
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
        $this->echoLog("getRepayDeal start, time:$time");
        if(empty($time) || !is_numeric($time)){
            return [];
        }

        $todo_data = [
            'user_data' => [],
            'agency_data' => [],
            'advisory_data' => [],

        ];
        $loan_end_time  = $this->stat_end_time-8*60*60;
        $loan_start_time = $this->stat_start_time-8*60*60;
        //================今日尊享部分还款本金
        $part_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(rd.repay_money) as repay_money_t
                        from ag_wx_partial_repay_detail rd 
                        left join firstp2p_deal d on d.id=rd.deal_id 
                        where rd.repay_status=1 and rd.repay_yestime>=$time and rd.repay_yestime<$this->stat_end_time group by d.user_id,d.agency_id,d.advisory_id";
        $zx_pat_repay = Yii::app()->db->createCommand($part_sql)->queryAll();
        //普惠部分还款
        $ph_pat_repay = Yii::app()->phdb->createCommand($part_sql)->queryAll();


        //================尊享常规+特殊还款
        $loan_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(lr.money) as repay_money_t
                        from firstp2p_deal_loan_repay lr 
                        left join firstp2p_deal d on d.id=lr.deal_id 
                        where lr.type in (1,2) and lr.status=1 and lr.real_time>=$loan_start_time and lr.real_time<$loan_end_time group by d.user_id,d.agency_id,d.advisory_id";
        $zx_loan_repay = Yii::app()->db->createCommand($loan_sql)->queryAll();
        //普惠常规+特殊还款
        $ph_loan_repay = Yii::app()->phdb->createCommand($loan_sql)->queryAll();

        //================尊享债权划扣+权益兑换
        $debt_sql = "select d.user_id,d.agency_id,d.advisory_id , sum(debt.sold_money) as repay_money_t
                        from firstp2p_debt debt 
                        left join firstp2p_deal d on d.id=debt.borrow_id 
                        where debt.status=2 and debt.debt_src in (1,3) and successtime>=$time and successtime<$this->stat_end_time  group by d.user_id,d.agency_id,d.advisory_id";
        $zx_debt = Yii::app()->db->createCommand($debt_sql)->queryAll();
        //普惠债权划扣+权益兑换
        $ph_debt = Yii::app()->phdb->createCommand($debt_sql)->queryAll();


        //================数据整合
        $zx_company_data = array_merge($zx_pat_repay,$zx_loan_repay,$zx_debt);
        $ph_company_data = array_merge($ph_pat_repay,$ph_loan_repay,$ph_debt);
        $all_company_data = array_merge($zx_company_data,$ph_company_data);
        if(empty($all_company_data)){
            return false;
        }
        //企业信息
        $c_user_id = $zx_agency_id = $px_agency_id = [];
        foreach ($all_company_data as $value){
            $c_user_id[$value['user_id']] = $value['user_id'];
        }
        //获取企业名称
        $c_user_info = $this->getUserInfo($c_user_id);
        //尊享担保机构咨询方信息
        if($zx_company_data){
            foreach ($zx_company_data as $zx_v){
                $zx_agency_id[$zx_v['agency_id']] = $zx_v['agency_id'];
                $zx_agency_id[$zx_v['advisory_id']] = $zx_v['advisory_id'];
            }
            $zx_agency_info = $this->getAgencyInfo($zx_agency_id);
            foreach ($zx_company_data as $zx_v){
                $user_total = round($todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['name'] =  $c_user_info[$zx_v['user_id']];
                $todo_data['user_data'][$c_user_info[$zx_v['user_id']]]['repay_money_t'] = $user_total;

                $agency_total = round($todo_data['agency_data'][$zx_agency_info[$zx_v['agency_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['agency_data'][$zx_agency_info[$zx_v['agency_id']]]['name'] =  $zx_agency_info[$zx_v['agency_id']];
                $todo_data['agency_data'][$zx_agency_info[$zx_v['agency_id']]]['repay_money_t'] =  $agency_total;

                $advisory_total = round($todo_data['advisory_data'][$zx_agency_info[$zx_v['advisory_id']]]['repay_money_t']+$zx_v['repay_money_t'], 2);
                $todo_data['advisory_data'][$zx_agency_info[$zx_v['advisory_id']]]['name'] =  $zx_agency_info[$zx_v['advisory_id']];
                $todo_data['advisory_data'][$zx_agency_info[$zx_v['advisory_id']]]['repay_money_t'] =  $advisory_total;
            }
        }

        //普惠担保机构咨询方信息
        if($ph_company_data){
            foreach ($ph_company_data as $ph_v){
                $px_agency_id[$ph_v['agency_id']] = $ph_v['agency_id'];
                $px_agency_id[$ph_v['advisory_id']] = $ph_v['advisory_id'];
            }
            $ph_agency_info = $this->getAgencyInfo($px_agency_id, 2);
            foreach ($ph_company_data as $ph_v){
                $user_total = round($todo_data['user_data'][$c_user_info[$ph_v['user_id']]]['repay_money_t']+$ph_v['repay_money_t'], 2);
                $todo_data['user_data'][$c_user_info[$ph_v['user_id']]]['name'] =  $c_user_info[$ph_v['user_id']];
                $todo_data['user_data'][$c_user_info[$ph_v['user_id']]]['repay_money_t'] = $user_total;

                $agency_total = round($todo_data['agency_data'][$ph_agency_info[$ph_v['agency_id']]]['repay_money_t']+$ph_v['repay_money_t'], 2);
                $todo_data['agency_data'][$ph_agency_info[$ph_v['agency_id']]]['name'] =  $ph_agency_info[$ph_v['agency_id']];
                $todo_data['agency_data'][$ph_agency_info[$ph_v['agency_id']]]['repay_money_t'] =  $agency_total;

                $advisory_total = round($todo_data['advisory_data'][$ph_agency_info[$ph_v['advisory_id']]]['repay_money_t']+$ph_v['repay_money_t'], 2);
                $todo_data['advisory_data'][$ph_agency_info[$ph_v['advisory_id']]]['name'] =  $ph_agency_info[$ph_v['advisory_id']];
                $todo_data['advisory_data'][$ph_agency_info[$ph_v['advisory_id']]]['repay_money_t'] =  $advisory_total;
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
     * @param int $type 1尊享2普惠
     * @return array
     */
    private function getAgencyInfo($ag_id, $type=1){
        $this->echoLog("getAgencyInfo start");
        if(empty($ag_id)){
            return [];
        }
        //借款人信息获取
        $company_info = [];
        $db_name = $type==1 ? "db" : "phdb";
        $ag_sql = "select id,name from firstp2p_deal_agency WHERE  id in (".implode(',', $ag_id).") ";
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
        $this->echoLog("getClearUser start, time:$time");
        if(empty($time) || !is_numeric($time)){
            return [];
        }
        //查询时间
        $stat_sql= "select *  from xf_data_statistics where add_time=$time ";
        $stat_info = Yii::app()->db->createCommand($stat_sql)->queryRow();
        return $stat_info;
    }

    private function getDebtDeduct($time=0){
        $this->echoLog("getDebtDeduct start, time:$time");
        //初始
        $deduct_ret = ['debt_account_t'=>0, 'total_uid'=>0 , 'user_list' => [] ];
        //查询时间
        $condition = $time>0 ? " and successtime>=$time and successtime<$this->stat_end_time " : '';
        $deduct_sql= "select sum(debt_account) as debt_account_t from firstp2p_debt_deduct_log where status=2 $condition  ";
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
        if($all_user_list){
            $deduct_ret['user_list'] = $all_user_list;
            $deduct_ret['total_uid'] = count($all_user_list);
        }
        return $deduct_ret;
    }

    /**
     * 商城消债人数
     * @param int $time
     * @return string
     */
    private function getShopUserTotal($time=0){
        $this->echoLog("getShopUserTotal start , time:$time");
        $shop_ret = ['total_uid'=>0 , 'user_list' => [] ];
        //查询时间
        $condition = $time>0 ? " and addtime>=$time and addtime<$this->stat_end_time " : '';
        $shop_sql= "select distinct user_id from firstp2p_debt_exchange_log where status=2 $condition  ";
        $zx_users = Yii::app()->db->createCommand($shop_sql)->queryAll();//尊享
        $ph_users = Yii::app()->phdb->createCommand($shop_sql)->queryAll();//普惠
        $user_info = array_merge($zx_users, $ph_users);
        $user_info = $this->array_unique_fb($user_info);
        $shop_ret['total_uid'] = count($user_info);
        $shop_ret['user_list'] = $user_info;
        unset($zx_users, $ph_users, $user_info);
        return $shop_ret;
    }

    /**
     * 现金兑付
     * @param int $time
     * @return string
     */
    private function getRepaymentTotal($time=0){
        $this->echoLog("getRepaymentTotal start, time:$time");
        $repay_ret = ['capital_total'=>0, 'interest_total'=>0, 'total_uid'=>0 , 'user_list' => [] ];

        //当日兑付
        if($time > 0){
            //今日尊享部分还款本金
            $capital_sql= "select sum(repay_money) from ag_wx_partial_repay_detail where repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            echo $capital_sql;
            $zx_capital_total = Yii::app()->db->createCommand($capital_sql)->queryScalar();
            //今日普惠部分还款本金
            $ph_capital_total = Yii::app()->phdb->createCommand($capital_sql)->queryScalar();
            $part_capital = bcadd($zx_capital_total, $ph_capital_total, 2);

            //部分还款用户信息
            $p_user_sql= "select distinct user_id from ag_wx_partial_repay_detail where repay_status=1 and repay_yestime>=$time and repay_yestime<$this->stat_end_time  ";
            echo $p_user_sql;
            $zx_users = Yii::app()->db->createCommand($p_user_sql)->queryAll();//尊享
            $ph_users = Yii::app()->phdb->createCommand($p_user_sql)->queryAll();//普惠
            $part_user_info = array_merge($zx_users, $ph_users);
            unset($zx_users, $ph_users);


            //今日常规还款+特殊还款
            $loan_real_time = $time-60*60*8;
            $loan_end_real_time = $this->stat_end_time - 60*60*8;
            $repay_sql= "select sum(money) as repaid_amount_t, type  from firstp2p_deal_loan_repay where  type in (1,2) and status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time group by type  ";
            echo $repay_sql;
            $zx_repay = Yii::app()->db->createCommand($repay_sql)->queryAll();
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
            //普惠当日现金兑付
            $ph_repay = Yii::app()->phdb->createCommand($repay_sql)->queryAll();
            if($ph_repay){
                foreach ($ph_repay as $ph_value){
                    if($ph_value['type'] == 1){
                        $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $ph_value['repaid_amount_t'], 2);
                    }
                    if($ph_value['type'] == 2){
                        $repay_ret['interest_total'] = bcadd($repay_ret['interest_total'], $ph_value['repaid_amount_t'], 2);
                    }
                }
            }

            //常规还款用户
            $r_user_sql= "select distinct loan_user_id as user_id from firstp2p_deal_loan_repay where  status=1 and real_time>=$loan_real_time and real_time<$loan_end_real_time  and type in (1,2)   ";
            echo $r_user_sql;
            $zx_users = Yii::app()->db->createCommand($r_user_sql)->queryAll();//尊享
            $ph_users = Yii::app()->phdb->createCommand($r_user_sql)->queryAll();//普惠
            $repay_user_info = array_merge($zx_users, $ph_users);
            unset($zx_users, $ph_users);

            $end_user_info = array_merge($part_user_info, $repay_user_info);
            $end_user_info = $this->array_unique_fb($end_user_info);
            $repay_ret['total_uid'] = count($end_user_info);
            $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $part_capital, 2);
            $repay_ret['user_list'] = $end_user_info;
            unset($part_user_info, $repay_user_info, $end_user_info);
            return $repay_ret;
        }

        //累计兑付
        $repay_sql= "select sum(repaid_amount) as repaid_amount_t,type  from firstp2p_deal_loan_repay where type in (1,2) and repaid_amount>0 and is_zdx=0 group by type";
        echo $repay_sql;
        $zx_total = Yii::app()->db->createCommand($repay_sql)->queryAll();//尊享
        $ph_total = Yii::app()->phdb->createCommand($repay_sql)->queryAll();//普惠
        //尊享累计兑付
        foreach ($zx_total as $zx_value){
            if($zx_value['type'] == 1){
                $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $zx_value['repaid_amount_t'], 2);
            }
            if($zx_value['type'] == 2){
                $repay_ret['interest_total'] = bcadd($repay_ret['interest_total'], $zx_value['repaid_amount_t'], 2);
            }
        }
        //普惠累计兑付
        foreach ($ph_total as $ph_value){
            if($ph_value['type'] == 1){
                $repay_ret['capital_total'] = bcadd($repay_ret['capital_total'], $ph_value['repaid_amount_t'], 2);
            }
            if($ph_value['type'] == 2){
                $repay_ret['interest_total'] = bcadd($repay_ret['interest_total'], $ph_value['repaid_amount_t'], 2);
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
        $this->echoLog("getShopTotal start, time:$time");

        //查询时间
        $condition = $time>0 ? " and addtime>=$time and addtime<$this->stat_end_time  " : '';
        $shop_sql= "select sum(debt_account) as zx_account_total  from firstp2p_debt_exchange_log where status=2 $condition  ";
        //尊享
        $zx_account_total = Yii::app()->db->createCommand($shop_sql)->queryScalar();
        //普惠
        $ph_account_total = Yii::app()->phdb->createCommand($shop_sql)->queryScalar();

        $sum_account = bcadd($ph_account_total, $zx_account_total, 2);
        return $sum_account;
    }

    /**
     *在途待还金额（尊享+普惠）
     * @param $type 1尊享2普惠
     * @return string
     */
    private function getLoanRepay($type=1){
        $this->echoLog("getLoanRepay type:$type start");
        if(!in_array($type, [1,2])){
            $this->echoLog("getLoanRepay type:$type not in 1,2 ");
            return false;
        }

        $repay_info = [];
        $db_name = $type == 1 ? 'db' : 'phdb';
        $condition = !empty($this->assignee_uid) ? " and loan_user_id not in (". implode(',',$this->assignee_uid).") " : '';
        $loan_sql = "select sum(money) as money_total,type from firstp2p_deal_loan_repay where time>=1562054400 and money>0 and status=0 and type in (1,2) and is_zdx=0 $condition group by type";
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
     *在途投资明细（尊享+普惠）
     * @param $type 1尊享2普惠
     * @return string
     */
    private function getDealLoad($type=1){
        $this->echoLog("getDealLoad type:$type start");
        if(!in_array($type, [1,2])){
            $this->echoLog("dealLoad type:$type not in 1,2 ");
            return false;
        }
        //数据库选择
        $db_name = $type == 1 ? 'db' : 'phdb';
        //排除受让人
        $condition = !empty($this->assignee_uid) ? " and dl.user_id not in (". implode(',',$this->assignee_uid).") " : '';
        $load_sql = "select distinct dl.user_id from firstp2p_deal_load dl left join firstp2p_deal d on dl.deal_id=d.id  where dl.wait_capital>0 and d.is_zdx=0 $condition  ";
        $load_list = Yii::app()->{$db_name}->createCommand($load_sql)->queryAll();
        return $load_list;
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
     * 更新在途标的金额
     * @param int $type
     * @return bool
     */
    public function actionStatDealWaitCapital($type=1){
        $this->echoLog("StatDealWaitCapital start");

        //普惠
        $deal_mode = 'Deal';
        $db_name = 'db';
        if($type == 2){
            $db_name = "phdb";
            $deal_mode = 'PHDeal';
        }

        //查询涉及标的
        $deal_sql= "SELECT  deal_id, sum(wait_capital) as t_wait_capital from firstp2p_deal_load  where status=1  group by deal_id ";
        $deal_list = Yii::app()->{$db_name}->createCommand($deal_sql)->queryAll();
        if(!$deal_list){
            $this->echoLog("StatDealWaitCapital end");
            return false;
        }

        //受让人
        $assignee_sql= "SELECT DISTINCT user_id from ag_wx_assignee_info where transferred_amount>0 ";
        $assignee_info = Yii::app()->db->createCommand($assignee_sql)->queryAll();
        $assignee_user_id = [];
        foreach ($assignee_info as $val){
            $assignee_user_id[] = $val['user_id'];
        }
        $purchase_sql= "SELECT DISTINCT user_id from xf_purchase_assignee where transferred_amount>0 ";
        $purchase_info = Yii::app()->phdb->createCommand($purchase_sql)->queryAll();
        foreach ($purchase_info as $val){
            $assignee_user_id[] = $val['user_id'];
        }
        foreach ($deal_list as $value){
            $this->echoLog("deal_id:[{$value['deal_id']}] start");
            /*
            if($value['t_wait_capital'] == 0 ){
                continue;
            }*/

            //标的信息
            $deal_info = $deal_mode::model()->findByPk($value['deal_id']);
            if(!$deal_info){
                continue;
            }

            //在途sql
            $u_deal_sql= "SELECT  count(distinct  user_id) as c_user_num, sum(wait_capital) as u_wait_capital from firstp2p_deal_load  where deal_id={$value['deal_id']} and status=1   ";
            $edit_data = [];
            //查询受让人数和金额
            $assignee_user_sql = $u_deal_sql." and user_id in (". implode(',', $assignee_user_id).") ";
            $a_deal_load_info = Yii::app()->{$db_name}->createCommand($assignee_user_sql)->queryRow();
            if($a_deal_load_info['u_wait_capital'] >= 0 && ($deal_info->recipient_number != $a_deal_load_info['c_user_num'] ||  $deal_info->recipient_wait_capital != $a_deal_load_info['u_wait_capital'])){
                $edit_data['recipient_number'] = $a_deal_load_info['c_user_num'] ?: 0;
                $edit_data['recipient_wait_capital'] = $a_deal_load_info['u_wait_capital'] ?: 0;
            }

            /*

            //排除冻结在途sql
            $f_deal_sql= "SELECT  count(distinct  user_id) as c_user_num, sum(wait_capital) as u_wait_capital from firstp2p_deal_load  where deal_id={$value['deal_id']} and status=1   ";

            //查询受让人数和金额
            $assignee_user_sql = $f_deal_sql." and user_id not in (". implode(',', $assignee_user_id).") ";
            $a_deal_load_info = Yii::app()->{$db_name}->createCommand($assignee_user_sql)->queryRow();
            if($a_deal_load_info['u_wait_capital'] > 0 && ($deal_info->recipient_number != $a_deal_load_info['c_user_num'] ||  $deal_info->recipient_wait_capital != $a_deal_load_info['u_wait_capital'])){
                $edit_data['recipient_number'] = $a_deal_load_info['c_user_num'];
                $edit_data['recipient_wait_capital'] = $a_deal_load_info['u_wait_capital'];
            }
            */
            //查询投资人数
            $invest_user_sql = $u_deal_sql." and user_id not in (". implode(',', $assignee_user_id).") ";
            $i_deal_load_info = Yii::app()->{$db_name}->createCommand($invest_user_sql)->queryRow();
            if($i_deal_load_info['u_wait_capital'] >= 0 && ($deal_info->investor_number != $i_deal_load_info['c_user_num'] ||  $deal_info->investor_wait_capital != $i_deal_load_info['u_wait_capital']) ){
                $edit_data['investor_number'] = $i_deal_load_info['c_user_num'] ?: 0;
                $edit_data['investor_wait_capital'] = $i_deal_load_info['u_wait_capital'] ?: 0;
            }

            if(empty($edit_data)){
                continue;
            }

            //更新标的数据
            $edit_ret = $deal_mode::model()->updateByPk($value['deal_id'], $edit_data);
            if(!$edit_ret){
                $this->echoLog("StatDealWaitCapital deal_id:{$value['deal_id']} edit error, edit_data:".print_r($edit_data, true));
                continue;
            }
            $this->echoLog("StatDealWaitCapital deal_id:{$value['deal_id']} edit success ");
        }

        $this->echoLog("StatDealWaitCapital end ");
    }


}
