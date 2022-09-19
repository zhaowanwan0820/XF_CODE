<?php

class ReturnDebtService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 100; //最低起投金额
    public $borrow_type = [0];
    private $table_prefix;
    private $service_prefix;
    private $db_name;
    public $frozen_amount = 0.00;//交易金额

    /**
     * 根据order_id退回指定债权
     * @param $order_ids string
     * @param $type 1尊享 2普惠
     * @return array
     */
    public function processOrder($order_ids, $type){
        set_time_limit(0);
        //返回数据预定义
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        try {
            Yii::log("processOrder start, params: $order_ids");
            //必传order_ids非空校验
            if(empty($order_ids)){
                $return_result['code'] = 100001;
                $return_result['info'] = '订单ID不能为空';
                return $return_result;
            }

            //数据库选择非空校验
            if(empty($type) || !is_numeric($type) || !in_array($type, [1,2])){
                $return_result['code'] = 100006;
                $return_result['info'] = '指定数据库不能为空';
                return $return_result;
            }

            //转为数据
            $order_array = json_decode($order_ids, true);
            $order_num = count($order_array);
            if($order_num == 0 || !is_array($order_array)){
                $return_result['code'] = 100002;
                $return_result['info'] = '订单ID参数格式有误';
                return $return_result;
            }

            //普惠供应链
            if($type == 2){
                $this->db_name = "phdb";
                $this->table_prefix = "PH";
                $this->service_prefix = "Ph";
            }

            //$this->use_money_alarm_key .= $this->buyer_uid;
            //查询符合条件债权
            $model_name = "{$this->table_prefix}DebtExchangeLog";
            $criteria = new CDbCriteria;
            $criteria->condition = "status=2 and return_status=0 and order_id in (".implode(',', $order_array).") ";
            $debt_list = $model_name::model()->findAll($criteria);
            if(empty($debt_list)) {
                $return_result['code'] = 100003;
                $return_result['info'] = '对应债权不存在';
                return $return_result;
            }

            //处理失败的订单ID
            $f = $s = 0;
            foreach($debt_list as $key => $value){
                //根据规则筛选要退回的投资记录ID
                $tender_info = $this->getTender($value->borrow_id, $value->debt_account, $value->buyer_uid);
                if($tender_info['code'] != 0 || empty($tender_info['data'])){
                    Yii::log("getTender return false: id[$value->id],order_id[$value->order_id]; error_info: ".print_r($tender_info, true), "error");
                    $return_result['data'][] = $value->order_id;
                    $f++;
                    continue;
                }

                //执行退回
                $return_debt_ret = $this->actionRun($tender_info['data'], $value->id);
                if($return_debt_ret == false){
                    Yii::log("actionRun return false: id[$value->id],order_id[$value->order_id];", "error");
                    $return_result['data'][] = $value->order_id;
                    $f++;
                    continue;
                }
                sleep(1);
                $s++;
            }

            Yii::log("processOrder end, success_count:$s; fail_count:$f; fail_order:[".implode(',', $return_result['data'])."]");
            if($f>0){
                $return_result['code'] = 100004;
                $return_result['info'] = '订单退回失败信息';
            }
            //退回完成
            return $return_result;
        } catch (Exception $e) {
            Yii::log("processOrder Exception,error_msg:".print_r($e->getMessage(),true), "error");
            $return_result['code'] = 100005;
            $return_result['info'] = '债权退回异常';
            return $return_result;
        }
    }

    /**
     * 冻结/发起债转/认购
     * @param $tender_id
     * @param $log_id
     * @return bool
     */
    public function actionRun($tender_id, $log_id){
        Yii::log("actionRun start, log_id:$log_id ");

        //参数校验
        if(empty($log_id) || !is_numeric($log_id) || empty($tender_id) || !is_numeric($tender_id)){
            Yii::log("actionRun: log_id:$log_id params error", 'error');
            return false;
        }

        Yii::app()->{$this->db_name}->beginTransaction();
        try{
            //兑换记录
            $model_name = "{$this->table_prefix}DebtExchangeLog";
            $exchange_log = $model_name::model()->findBySql("select * from firstp2p_debt_exchange_log where id=:log_id for update", array(':log_id' => $log_id));
            if(!$exchange_log || $exchange_log->status != 2 || $exchange_log->return_status != 0){
                Yii::log("actionRun: log_id:$log_id  status!= 2 or return_status!=0 ", 'error');
                Yii::app()->{$this->db_name}->rollback();
                return false;
            }

            //获取退回的受让人信息
            $assignee_info = AgWxAssigneeInfo::model()->findBySql("select * from ag_wx_assignee_info where user_id={$exchange_log->buyer_uid} ");
            if(!$assignee_info || FunctionUtil::float_bigger($exchange_log->debt_account, $assignee_info->transferred_amount, 2)){
                $this->echoLog("handelDebt end id:$log_id ag_wx_assignee_info error ");
                Yii::app()->{$this->db_name}->rollback();
                return false;
            }

            //创建债权
            $c_data = array();
            $c_data['user_id'] = $exchange_log->buyer_uid;
            $c_data['money'] = $exchange_log->debt_account;
            $c_data['discount'] = 0;
            $c_data['deal_loan_id'] = $tender_id;
            $c_data['debt_src'] = $exchange_log->debt_src;
            $service_name = "{$this->service_prefix}DebtService";
            $create_ret = $service_name::getInstance()->createDebt($c_data);
            if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
                Yii::log("actionRun: log_id:$log_id createDebt tender_id {$tender_id} false:".print_r($create_ret,true), 'error');
                Yii::app()->{$this->db_name}->rollback();
                return false;
            }

            //创建成功日志
            Yii::log("actionRun: log_id:$log_id  createDebt tender_id {$tender_id} success");

            //债权认购
            $debt_data = array();
            $debt_data['user_id'] = $exchange_log->user_id;
            $debt_data['money'] = $exchange_log->debt_account;
            $debt_data['debt_id'] = $create_ret['data']['debt_id'];
            $debt_data['frozen_amount'] = $this->frozen_amount;
            $debt_transaction_ret = $service_name::getInstance()->debtPreTransaction($debt_data);
            if($debt_transaction_ret['code'] != 0){
                Yii::log("actionRun: log_id:$log_id  debtPreTransaction tender_id {$tender_id} false:".print_r($debt_transaction_ret,true), 'error');
                Yii::app()->{$this->db_name}->rollback();
                return false;
            }

            //兑换记录数据更新
            $changeLogRet = BaseCrudService::getInstance()->update($model_name,array(
                "id" => $exchange_log->id,
                "return_time" => time(),
                "return_status" => 1
            ), "id");
            if(!$changeLogRet){
                Yii::log("actionRun: log_id:$log_id $model_name update return_status error", 'error');
                Yii::app()->{$this->db_name}->rollback();
                return false;
            }

            //更新收购额度
            $assignee_info->transferred_amount = bcsub($assignee_info->transferred_amount, $exchange_log->debt_account, 2);
            if($assignee_info->save(false, array('transferred_amount')) == false){
                $this->echoLog("actionRun update ag_wx_assignee_info transferred_amount[$assignee_info->transferred_amount] error, id=$exchange_log->id");
                Yii::app()->db->rollback();
                return false;
            }

            //债转成功数据确认
            Yii::app()->{$this->db_name}->commit();
            Yii::log("actionRun: log_id:$log_id  success");
            return true;
        }catch (Exception $ee) {
            Yii::log("actionRun: log_id:$log_id ; exception:".print_r($ee->getMessage(), true), 'error');
            Yii::app()->{$this->db_name}->rollback();
            return false;
        }
    }

    /**
     * 获取要退回的投资记录ID
     * @param int $borrow_id
     * @param int $debt_account
     * @param int $buyer_uid
     * @return array
     */
    private function getTender($borrow_id, $debt_account, $buyer_uid){
        //返回数据预定义
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>''
        );
        if(empty($borrow_id) || empty($debt_account) || empty($buyer_uid)){
            $return_result['code'] = 200000;
            $return_result['info'] = 'params empty';
            return $return_result;
        }

        $where = " and deal_id=$borrow_id and user_id=$buyer_uid";
        //查找金额一致债权份额退回
        $tender_model = "{$this->table_prefix}DealLoad";
        $tender_eq = $tender_model::model()->find(" wait_capital=$debt_account $where ");
        //金额一致返回成功
        if($tender_eq){
            $return_result['data'] = $tender_eq->id;
            return $return_result;
        }

        //中安债权单笔最大额
        $tender_sql = "select * from firstp2p_deal_load where wait_capital>$debt_account $where order by wait_capital desc limit 1 ";
        $buyer_tender = Yii::app()->{$this->db_name}->createCommand($tender_sql)->queryRow();
        if(!$buyer_tender){
            $return_result['code'] = 200001;
            $return_result['info'] = 'borrow_tender error';
            return $return_result;
        }

        //剩余金额大于等于100 返回成功
        $s_account = bcsub($buyer_tender['wait_capital'], $debt_account, 2);
        if(FunctionUtil::float_bigger_equal($s_account, 100, 2)){
            $return_result['data'] = $buyer_tender['id'];
            return $return_result;
        }

        $return_result['code'] = 200002;
        $return_result['info'] = '未匹配到合适的债权ID，请及时处理';
        return $return_result;
    }


    /**
     * 发起债转
     * @param $data
     * @return array
     */
    public function  createDebt($data){
        //返回数据预定义
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        Yii::log("createDebt  params :".json_encode($data), 'info');

        //用户登录校验
        if(empty($data['user_id']) || !is_numeric($data['user_id'])){
            Yii::log("createDebt  step01 user_id=[{$data['user_id']}] User error", 'error');
            $return_result['code'] = 2000;
            return $return_result;
        }

        //参数简单校验
        $user_id = $data['user_id'];
        $money = $data['money'];
        $discount_money = $data['discount_money'];
        $time = time();
        $tender_id = $data['tender_id'];
        $debt_src = $data['debt_src'];
        if(!in_array($debt_src, [1,2,3]) || empty($tender_id)|| empty($money) || !is_numeric($money) || !isset($discount_money) || !is_numeric($discount_money)){
            Yii::log("createDebt  step02 params error:" .json_encode($data), 'error');
            $return_result['code'] = 2001;
            return $return_result;
        }

        //折让金不可大于债转金额
        if(FunctionUtil::float_bigger($discount_money, $money, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, discount_money：$discount_money > debt_account:$money ", 'error');
            $return_result['code'] = 2002;
            return $return_result;
        }

        //投资记录信息
        $borrow_tender = PHDealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array(':id' => $tender_id));
        if(!$borrow_tender || $borrow_tender->debt_status != 0 ) {
            Yii::log("handelDebt end tender_id:$tender_id  debt_status !=0 ", 'error');
            $return_result['code'] = 2003;
            return $return_result;
        }

        //黑名单
        if($borrow_tender->black_status == 2){
            Yii::log("handelDebt end tender_id:$tender_id; black_status[$borrow_tender->black_status] = 2 ", 'error');
            $return_result['code'] = 2096;
            return $return_result;
        }

        //用户信息校验
        if($user_id != $borrow_tender->user_id){
            Yii::log("handelDebt end tender_id:$tender_id; user_id[$user_id] != tender_user[$borrow_tender->user_id] ", 'error');
            $return_result['code'] = 2041;
            return $return_result;
        }

        //兑换金额必须大于0
        if(FunctionUtil::float_bigger_equal(0, $money, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, debt_account:$money<=0 ", 'error');
            $return_result['code'] = 2004;
            return $return_result;
        }

        //投资记录待还本金
        $wait_capital = $borrow_tender->wait_capital;
        //待还本金必须大于0
        if(FunctionUtil::float_bigger_equal(0, $wait_capital, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, wait_capital:$wait_capital<=0 ", 'error');
            $return_result['code'] = 2005;
            return $return_result;
        }

        //禁止指定项目债转
        $disable_ph = AgWxDebtBlackList::model()->find("deal_id=$borrow_tender->deal_id and status=1 and type=2");
        if($disable_ph){
            Yii::log("handelDebt end tender_id:$tender_id, deal_id[$borrow_tender->deal_id] in disable_ph error ", 'error');
            $return_result['code'] = 2039;
            return $return_result;
        }

        //还款计划表待还本金
        $repay_sql = "select sum(money) as repay_wait_capital from firstp2p_deal_loan_repay where status=0 and deal_loan_id=$borrow_tender->id and type=1 and money>0";
        $repay_wait_capital = Yii::app()->phdb->createCommand($repay_sql)->queryScalar();
        if(empty($repay_wait_capital) || $repay_wait_capital<=0 ){
            Yii::log("handelDebt end tender_id:$tender_id firstp2p_deal_loan_repay error!!!", 'error');
            $return_result['code'] = 2006;
            return $return_result;
        }
        //校验待还本金一致性
        if(!FunctionUtil::float_equal($repay_wait_capital, $wait_capital, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, repay_wait_capital:$repay_wait_capital != wait_capital:$wait_capital ", 'error');
            $return_result['code'] = 2007;
            return $return_result;
        }

        //兑换后剩余本金
        $s_money = bcsub($wait_capital, $money, 2);
        //剩余本金必须大于或等于O
        if(FunctionUtil::float_bigger(0, $s_money, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, s_money:$s_money<0 ", 'error');
            $return_result['code'] = 2008;
            return $return_result;
        }
        //当剩余金额大于0时，必须大于起投金额
        if(FunctionUtil::float_bigger($s_money, 0, 2) && FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $s_money, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, s_money:$s_money < ".self::MIN_LOAN_AMOUNT.", debt_account:$money, wait_capital:$wait_capital", 'error');
            $return_result['code'] = 2009;
            return $return_result;
        }

        //项目信息校验
        $borrow = PHDeal::model()->findByPk($borrow_tender->deal_id);
        //有效子表，普通类型还款中 第一期仅支持供应链
        if(!$borrow || $borrow->product_class_type !=223 || $borrow->is_effect != 1 || $borrow->parent_id == 0 || $borrow->deal_type !=0 || $borrow->deal_status != 4 ){
            Yii::log("handelDebt end tender_id:$tender_id, firstp2p_deal[$borrow_tender->deal_id] error", 'error');
            $return_result['code'] = 2010;
            return $return_result;
        }

        //禁止智多鑫项目债转
        $zdx_check = PHDealTag::model()->find("deal_id=$borrow_tender->deal_id");
        if($zdx_check && $zdx_check->tag_id == 42){
            Yii::log("handelDebt end tender_id:$tender_id, PHDealTag[tag_id=$borrow_tender->tag_id] error ", 'error');
            $return_result['code'] = 2038;
            return $return_result;
        }


        //查投资记录是否重复转让
        $SucNum = PHDebt::model()->count("user_id=:user_id and tender_id={$tender_id} and status=1", array(':user_id'=>$user_id));
        if ($SucNum > 0) {
            Yii::log("createDebt step07 firstp2p_debt.tender_id[{$tender_id}] repeat request ", 'error');
            $return_result['code'] = 2011;
            return $return_result;
        }

        //还款日限制【每日下午16点】
        $repay_time = strtotime('midnight') + 57600;
        $today_repay = PHDealLoanRepay::model()->find( "status=0 and deal_loan_id=$borrow_tender->id and `time`=$repay_time and money>0");
        if($today_repay){
            Yii::log("handelDebt end tender_id:$tender_id firstp2p_deal_loan_repay repay_time error!!!", 'error');
            $return_result['code'] = 2039;
            return $return_result;
        }

        //计算债权过期时间
        $expected_end_time = strtotime("today +3 days") - 1;
        //firstp2p_debt表数据组成
        $debt['user_id'] = $user_id;
        $debt['type'] = $borrow->deal_type;
        $debt['tender_id'] = $tender_id;
        $debt['borrow_id'] = $borrow_tender->deal_id;
        $debt['money'] = $money;
        $debt['sold_money'] = 0;
        $debt['discount_money'] = $discount_money;
        $debt['starttime'] = $time;
        $debt['endtime'] = $expected_end_time;
        $debt['successtime'] = 0;
        $debt['borrow_apr'] = $borrow->income_fee_rate;
        $debt['status'] = 1;
        $debt['debt_type'] = 1;
        $debt['debt_src'] = $debt_src;
        $debt['addtime'] = $time;
        $debt['addip'] = FunctionUtil::ip_address();
        $ret = BaseCrudService::getInstance()->add('PHDebt', $debt);
        if(false == $ret){//添加失败
            Yii::log("createDebt  step08 tender_id=[{$tender_id}] AddDebt error ", 'error');
            $return_result['code'] = 2012;
            return $return_result;
        }

        //投资记录债权状态变更
        $borrow_tender->debt_status = 1;
        if($borrow_tender->save(false, array('debt_status')) == false){
            Yii::log("createDebt step12 tender_id=[{$tender_id}] update firstp2p_deal_load.debt_status fail ", 'error');
            $return_result['code'] = 2013;
            return $return_result;
        }

        $return_result['code'] = 0;
        $return_result['data']['debt_id'] = $ret['id'];
        return $return_result;
    }

    /**
     * 认购债权处理
     * @param $debt_data [
     * $money 认购金额
     * $user_id 买家user_id
     * $recharge_money 需要充值金额
     * $debt_id 被认购债权ID
     * $invest_device 认购设备pc/android/ios/wap
     * $redirect_url 回调地址
     * ]
     * @return array
     */
    public function debtPreTransaction($debt_data){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        //用户未登录
        if('' == $debt_data['user_id']){
            Yii::log("debtPreTransaction step01 {$debt_data['user_id']} User error", 'error');
            $return_result['code'] = 2014;
            return $return_result;
        }
        //债权id非空校验
        if( $this->emp($debt_data['debt_id']) || $this->emp($debt_data['money']) || !is_numeric($debt_data['debt_id']) || !is_numeric($debt_data['frozen_amount'])) {
            Yii::log("debtPreTransaction  step02 {$debt_data['user_id']} params error", 'error');
            $return_result['code'] = 2015;
            return $return_result;
        }
        //交易金额数据校验
        if(!is_numeric($debt_data['money']) || FunctionUtil::float_bigger_equal(0, $debt_data['money'], 2)){
            Yii::log("debtPreTransaction  step03 {$debt_data['user_id']} money[{$debt_data['money']}] error", 'error');
            $return_result['code'] = 2016;
            return $return_result;
        }

        $debt_id = $debt_data['debt_id'];
        $account_money = $debt_data['money'];
        $user_id = $debt_data['user_id'];

        // 获取债权信息数组
        $debt = PHDebt::model()->findBySql("select * from firstp2p_debt where id=:id for update", array('id' => $debt_id))->attributes;
        if(!$debt){
            Yii::log("debtPreTransaction  step04 {$debt_data['user_id']} firstp2p_debt.id[{$debt_id}] not exist", 'error');
            $return_result['code'] = 2017;
            return $return_result;
        }

        //项目类型校验
        if(!in_array($debt['type'], $this->borrow_type)){
            Yii::log("debtPreTransaction  step05 user_id[{$debt_data['user_id']}]} firstp2p_debt.type[{$debt['type']}] error ", 'error');
            $return_result['code'] = 2018;
            return $return_result;
        }

        //不能认购自己发布的债权
        if ($debt['user_id'] == $user_id) {
            Yii::log("debtPreTransaction  step06 {$debt_data['user_id']} firstp2p_debt.user_id[{$debt['user_id']}] = user_id[{$user_id}]", 'error');
            $return_result['code'] = 2227;
            return $return_result;
        }

        //已被认购完
        if(FunctionUtil::float_bigger_equal($debt['sold_money'], $debt['money'], 3)){
            Yii::log("debtPreTransaction  step13 {$debt_data['user_id']} code=2204", 'error');
            $return_result['code'] = 2019;
            return $return_result;
        }

        //认购额超过债权剩余额度
        if(FunctionUtil::float_bigger(round($account_money, 2), round($debt['money'] - $debt['sold_money'], 2), 2)){
            Yii::log("debtPreTransaction  step14 {$debt_data['user_id']} code=2205", 'error');
            $return_result['code'] = 2020;
            return $return_result;
        }

        //债权已取消
        if(3 == $debt['status']){
            Yii::log("debtPreTransaction  step15 {$debt_data['user_id']} code=2228", 'error');
            $return_result['code'] = 2021;
            return $return_result;
        }

        //债权已过期
        if(4 == $debt['status'] || $debt['endtime'] < time()){
            Yii::log("debtPreTransaction  step16 {$debt_data['user_id']} code=2229", 'error');
            $return_result['code'] = 2022;
            return $return_result;
        }

        //已认购满额
        if(5 == $debt['status']){
            Yii::log("debtPreTransaction  step17 {$debt_data['user_id']} code=2230-1", 'error');
            $return_result['code'] = 2023;
            return $return_result;
        }

        //已经认购完成
        if(2 == $debt['status']){
            Yii::log("debtPreTransaction  step18 {$debt_data['user_id']} code=2230-2", 'error');
            $return_result['code'] = 2024;
            return $return_result;
        }

        //状态异常
        if($debt['status'] != 1){
            Yii::log("debtPreTransaction  step28 {$debt_data['user_id']} code=2221", 'error');
            $return_result['code'] = 2025;
            return $return_result;
        }

        //校验用户是否存在
        $user_info = User::model()->findByPk($user_id);
        //校验用户是否存在
        if(empty($user_info)){
            Yii::log("debtPreTransaction  step07 {$debt_data['user_id']} user_id[{$user_id}] not exist", 'error');
            $return_result['code'] = 2026;
            return $return_result;
        }
        //校验购买者账户状态是否有效
        if (0 == $user_info['is_effect']) {
            Yii::log("debtPreTransaction  step08 {$debt_data['user_id']} firstp2p_user.is_effect[{$user_info['is_effect']}] = 0", 'error');
            $return_result['code'] = 2027;
            return $return_result;
        }

        //项目信息校验
        $borrow = PHDeal::model()->findByPk($debt['borrow_id']);
        //有效子标，普通类型还款中
        if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || $borrow->deal_type !=0 || $borrow->deal_status != 4){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, firstp2p_deal[{$debt['borrow_id']}] error", 'error');
            $return_result['code'] = 2028;
            return $return_result;
        }

        //买家实际支付金额计算
        $investMoney = $debt_data['frozen_amount'];
        //支付金额大于0时买卖双方交易流水记录
        if(FunctionUtil::float_bigger($investMoney, 0, 2)){
            //第一期暂时只支持0元购买
        }

        //组合卖家debt更新数据
        $edit_debt = array();
        $surplus_capital = bcsub($debt['money'], $debt['sold_money'], 2);
        $balance = bcsub($surplus_capital, $account_money, 2);
        $sold_money = FunctionUtil::float_equal($balance, 0.00, 2) ? $debt['money'] : bcadd($debt['sold_money'], $account_money, 2);
        $edit_debt['sold_money'] = $sold_money;        //卖家wise_debt更新
        $edit_debt['successtime'] = $debt['successtime'];
        $edit_debt['scale'] = round(floatval($edit_debt['sold_money']) / floatval($debt['money']), 4);
        //如已认购完成变更状态为2
        if(FunctionUtil::float_bigger_equal($edit_debt['sold_money'], $debt['money'], 2) || FunctionUtil::float_equal($edit_debt['scale'], 1)){
            $edit_debt['status'] = 2;
            $edit_debt['successtime'] = time();
            $edit_debt['scale'] = 1.00;
        }
        $debt_edit_res = PHDebt::model()->updateByPk($debt['id'], $edit_debt);
        if(!$debt_edit_res){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, debt.id[{$debt['id']}] edit error:".json_encode($edit_debt), 'error');
            $return_result['code'] = 2275;
            return $return_result;
        }

        //卖家firstp2p_deal_load数据
        $borrow_tender = PHDealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array('id' => $debt['tender_id']));
        if($borrow_tender->debt_status != 1){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, firstp2p_deal_load.status[{$borrow_tender->status}] != 1", "error");
            $return_result['code'] = 2029;
            return $return_result;
        }
        //卖家债转前剩余本金
        $seller_capital = $borrow_tender['wait_capital'];
        //卖家投资记录待还本金数据变更
        $_data = array();
        $_data['wait_capital'] = bcsub($borrow_tender['wait_capital'], $account_money, 2);
        $_data['id'] = $borrow_tender['id'];
        //买卖双方初始待还本金字段计算
        $rate = round($borrow_tender['repay_capital_init']/$borrow_tender['wait_capital'], 6);
        $_data['repay_capital_init'] = round($_data['wait_capital']*$rate, 2);
        $buyer_repay_capital_init = bcsub($borrow_tender['repay_capital_init'], $_data['repay_capital_init'], 2);
        if(FunctionUtil::float_bigger_bc(0.02, $_data['wait_capital'])) {
            $_data['debt_status'] = 15;
        }else{
            //如果待还本金大于0，债转数据已转让完成
            $_data['debt_status'] = $edit_debt['status'] == 2 ? 0 : $borrow_tender['debt_status'];
        }

        if(FunctionUtil::float_bigger(0, $_data['wait_capital'],2)){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, wait_capital error ", "error");
            $return_result['code'] = 2030;
            return $return_result;
        }
        $edit_load = BaseCrudService::getInstance()->update('PHDealLoad', $_data,'id');
        if(!$edit_load){
            Yii::log('updateSellerData DealLoad edit error, data:'.  print_r($_data,true));
            return false;
        }

        //卖方操作日志记录
        $op_log_c = [
            'user_id' => $borrow_tender['user_id'],
            'log_type' => 'exchange_capital',
            'direction' => 1,
            'deal_load_id' => $borrow_tender['id'],
            'deal_id' => $borrow_tender['deal_id'],
            'money' => $account_money,
            'remark' => '权益兑换本金',
        ];
        $add_op_c  =  UserService::getInstance()->addWxOplog($op_log_c, 2);
        if($add_op_c == false){
            Yii::log('debtPreTransaction addWxOplog capital error, data:'.  print_r($op_log_c,true));
            return false;
        }

        //wx特殊数据处理
        if($_data['debt_status'] == 15){
            //全部债权完成时，卖家还款计划里的待还本金为0的特殊数据更新为已债转
            $special_capital = DealLoanRepay::model()->find("deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1");
            if($special_capital){
                $update_repay_sql = "update firstp2p_deal_loan_repay set status=15 where deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1 ";
                $r_edit_ret = Yii::app()->phdb->createCommand($update_repay_sql)->execute();
                if(!$r_edit_ret){
                    Yii::log("updateSellerData seller firstp2p_deal_loan_repay edit error, sql:$update_repay_sql");
                    $return_result['code'] = 2040;
                    return $return_result;
                }
            }
        }

        
        //出借人真实姓名转换
        $user_deal_name = mb_substr($user_info->real_name, 0, 1, 'utf-8');
        $user_deal_name .= $user_info->sex == 1 ? '先生' : '女士';

        //添加买家firstp2p_deal_load
        $tender = array();
        $tender['deal_id'] = $borrow_tender['deal_id'];
        $tender['user_id'] = $user_id;
        $tender['user_name'] = $user_info->user_name;
        $tender['user_deal_name'] = $user_deal_name;
        $tender['money'] = $account_money;
        $tender['wait_capital'] = $account_money;
        $tender['create_time'] = time();
        $tender['is_repay'] = 0;
        $tender['from_deal_id'] = $borrow_tender['from_deal_id'];
        $tender['deal_parent_id'] = $borrow_tender['deal_parent_id'];
        $tender['site_id'] = $borrow_tender['site_id'];
        $tender['source_type'] = 9;//0元认购商城兑换的债权
        $tender['deal_type'] = $borrow_tender['deal_type'];
        $tender['debt_type'] = 2;
        //新还款方式继承
        $tender['repay_way'] = $borrow_tender['repay_way'];
        $tender['repay_plan_id'] = $borrow_tender['repay_plan_id'];
        $tender['confirm_repay_time'] = $borrow_tender['confirm_repay_time'];
        $tender['repay_capital_init'] = $buyer_repay_capital_init;
        $tender_ret = BaseCrudService::getInstance()->add('PHDealLoad', $tender);
        if(!$tender_ret){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, Buyers DealLoad add error :". print_r($tender, true), "error");
            $return_result['code'] = 2031;
            return $return_result;
        }

        //添加买家debt_tender[为以后一笔债权支持多用户购买做准备]
        unset($data);
        $data['debt_id'] = $debt_id;
        $data['money'] = $account_money;//投资额
        $data['account'] = $account_money;
        $data['action_money'] = $investMoney;//本期是指支付金额为0
        $data['user_id'] = $user_id;
        $data['status'] = 2;//1表示认购中2表示认购成功3表示认购取消
        $data['type'] = 2;//债权
        //$data['money_detail'] = '';
        $data['addtime'] = time();
        $data['addip'] = '127.0.0.1';
        $data['debt_type'] = 0;//0定期 1活期拆散标债权
        $data['new_tender_id'] = $tender_ret['id'];
        $debt_tender = BaseCrudService::getInstance()->add('PHDebtTender', $data);//添加债权tender
        if(!$debt_tender){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, DebtTender add error :". print_r($data, true), "error");
            $return_result['code'] = 2032;
            return $return_result;
        }

        //买家还款计划生成
        $loan_repay_sql = "select * from firstp2p_deal_loan_repay where deal_loan_id ={$debt['tender_id']} and status=0 and type in (1, 2) and money>0 ";
        $seller_loan_repay = Yii::app()->phdb->createCommand($loan_repay_sql)->queryAll();
        if(!$seller_loan_repay){
            Yii::log(" addBuyerData firstp2p_deal_loan_repay  not exist");
            $return_result['code'] = 2033;
            return $return_result;
        }

        //还款计划待还本
        $repay_surplus_capital = 0.00;
        $capital_num = 0;
        foreach ($seller_loan_repay as $v) {
            if($v['type'] == 1){
                $repay_surplus_capital= bcadd($repay_surplus_capital, $v['money'], 2);
                $capital_num++;
            }
        }
        //还款计划投资记录待还本金一致性校验
        if(!FunctionUtil::float_equal($repay_surplus_capital, $seller_capital, 2)){
            Yii::log(" addBuyerData firstp2p_deal_loan_repay.sum_capital[{$repay_surplus_capital}] != deal_loan.wait_capital[{$seller_capital}]  not exist");
            $return_result['code'] = 2034;
            return $return_result;
        }

        //购买比例
        $rate = round($debt_tender['account']/$seller_capital, 8);
        $seller_op_interest = $buy_capital = 0.00;
        $repay_values = '';
        $create_num = 0;
        foreach ($seller_loan_repay as $k => $v) {
            $repay_money = round($rate * $v['money'], 2);
            if($v['type'] == 1){
                //最后一期本金误差消除
                if($capital_num == $create_num){
                    $repay_money = bcsub($debt_tender['account'], $buy_capital, 2);
                }else{
                    $buy_capital = bcadd($buy_capital, $repay_money, 2);
                    $create_num++;
                }
            }

            //买方还款计划新增
            $buyer_loan_repay = array();
            $buyer_loan_repay['deal_id'] = $v['deal_id'];
            $buyer_loan_repay['deal_repay_id'] = $v['deal_repay_id'];
            $buyer_loan_repay['deal_loan_id'] = $tender_ret['id'];
            $buyer_loan_repay['loan_user_id'] = $user_id;
            $buyer_loan_repay['borrow_user_id'] = $v['borrow_user_id'];
            $buyer_loan_repay['money'] = $repay_money;
            $buyer_loan_repay['type'] = $v['type'];
            $buyer_loan_repay['time'] = $v['time'];
            $buyer_loan_repay['real_time'] = $v['real_time'];
            $buyer_loan_repay['status'] = 0;
            $buyer_loan_repay['deal_type'] = $v['deal_type'];
            $buyer_loan_repay['create_time'] = time();
            $buyer_loan_repay['update_time'] = time();

            //待还金额不能小于0
            if($buyer_loan_repay['money'] < 0) {
                Yii::log("addBuyerData buyer firstp2p_deal_loan_repay money less than 0 data:".json_encode($buyer_loan_repay));
                $return_result['code'] = 2035;
                return $return_result;
            }
            $add_loan_repay_ret = BaseCrudService::getInstance()->add('PHDealLoanRepay', $buyer_loan_repay);
            if(!$add_loan_repay_ret) {
                Yii::log("addBuyerData buyer firstp2p_deal_loan_repay add error data:".json_encode($buyer_loan_repay));
                $return_result['code'] = 2036;
                return $return_result;
            }

            //卖方还款计划更新数据
            $edit_seller_data = array();
            $edit_seller_data['id'] = $v['id'];
            $edit_seller_data['money'] = bcsub($v['money'], $buyer_loan_repay['money'], 2);
            $edit_seller_data['status'] = $edit_seller_data['money'] == 0 ? 15 : 0;
            $repay_values .= "('".implode("','", $edit_seller_data)."'),";
        }

        if(empty($repay_values)){
            Yii::log("addBuyerData collection_values error");
            $return_result['code'] = 2037;
            return $return_result;
        }

        //如果卖方利息有相应减少
        if(FunctionUtil::float_bigger($seller_op_interest, 0, 2)){
            $op_log_i = [
                'user_id' => $borrow_tender['user_id'],
                'log_type' => 'exchange_capital',
                'direction' => 1,
                'deal_load_id' => $borrow_tender['id'],
                'deal_id' => $borrow_tender['deal_id'],
                'money' => $seller_op_interest,
                'remark' => '权益兑换自动放弃的利息',
            ];
            $add_op_i  =  UserService::getInstance()->addWxOplog($op_log_i, 2);
            if($add_op_i == false){
                Yii::log('debtPreTransaction addWxOplog interest error, data:'.  print_r($op_log_i,true));
                return false;
            }
        }

        //拼接卖家还款计划并更新
        $collection_values = rtrim($repay_values, ",");
        $status_con = $edit_seller_data['status'] == 15 ? ',status=VALUES(status)' : '';
        $update_repay_sql = "INSERT INTO firstp2p_deal_loan_repay (id, money, status) VALUES $collection_values ON DUPLICATE KEY".
            " UPDATE  money=VALUES(money) $status_con ";
        $seller_w_ret = Yii::app()->phdb->createCommand($update_repay_sql)->execute();
        if(!$seller_w_ret){
            Yii::log("addBuyerData seller firstp2p_deal_loan_repay edit error, sql:$update_repay_sql");
            $return_result['code'] = 2038;
            return $return_result;
        }

        //买家债权认购合同生成
        $add_contract_ret = ContractService::getInstance()->addContract($user_id, 0, $tender_ret['id'], $borrow_tender['deal_id'], 2);
        if($add_contract_ret == false){
            Yii::log("addBuyerData addContract error, data:($user_id, 0, {$tender_ret['id']}, {$borrow_tender['deal_id']}, 2)");
            $return_result['code'] = 2040;
            return $return_result;
        }

        $return_result['code'] = 0;
        return $return_result;
    }
    

    /**
     * 判断参数是不是空
     * @param $a
     * @return bool true为空，false为非空
     */
    private function emp($a)
    {
        if (!isset($a) || (empty($a) && $a != 0)) {
            return true;
        } else {
            return false;
        }
    }
}
