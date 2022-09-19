<?php

class DebtService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 5; //最低起投金额
    public  $borrow_type = [2,3];//尊享支持2/3
    public $table_prefix;
    public $db_name = 'db';

    /**
     * 发起债转
     * @param $data [
     *  'user_id' => 发布债转用户ID,//必传
     *  'money' => 转让金额,//必传
     *  'discount' => 债转折扣4~10,//必传
     *  'deal_loan_id' => 投资记录ID,//必传
     *  'debt_src' => 债转来源(债转来源，1-权益兑换、2-债转交易、3债权划扣 4、一键下车)//必传
     *  'is_orient' => 定向转让(1是 2不是) //非必传默认2
     *  'effect_days' =>债权有效期,//必传
     *  'payee_name' =>收款人姓名,//必传
     *  'payee_bankzone' =>收款人开户行,//必传
     *  'payee_bankcard' =>收款人卡号,//必传
     * ]
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
            Yii::log("createDebt user_id=[{$data['user_id']}] User error", 'error');
            $return_result['code'] = 2000;
            return $return_result;
        }

        //计算债权过期时间
        $expected_end_time = strtotime("now +3 days");
        //参数简单校验
        $user_id = $data['user_id'];
        $money = $data['money'];
        $discount = $data['discount'];
        $time = time();
        $tender_id = $data['deal_loan_id'];
        $debt_src = $data['debt_src'];
        if(!in_array($debt_src, [1,2,3,4]) || empty($tender_id)|| empty($money) || !is_numeric($money)){
            Yii::log("createDebt tender_id[$tender_id]: params error:" .json_encode($data), 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //权益兑换与债权划扣折扣金额必须为0
        if(in_array($debt_src, [1,3,4]) && !FunctionUtil::float_equal($discount, 0, 2)){
            Yii::log("createDebt tender_id[$tender_id]: debt_src in (1,3,4)  discount[$discount]!=0", 'error');
            $return_result['code'] = 2042;
            return $return_result;
        }

        //权益兑换商城来源校验
        if(in_array($debt_src, [1,4,5]) && !in_array( $data['platform_no'], [1,2,4,5]) ){
            Yii::log("createDebt tender_id[$tender_id]: debt_src in (1,4,5)  platform_no[{$data['platform_no']}] not in (1,2,4,5)", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //自主债转交易
        if($debt_src == 2){
            //折扣必须在4~10
            if(bccomp($discount , 0.1) === -1 || bccomp($discount , 10) === 1){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2,  discount[$discount] error ", 'error');
                $return_result['code'] = 7019;
                return $return_result;
            }
            //债权有效期必传
            if(empty($data['effect_days']) || !is_numeric($data['effect_days'])){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2,  effect_days[{$data['effect_days']}] error ", 'error');
                $return_result['code'] = 2002;
                return $return_result;
            }

            //定向转让
            if(empty($data['is_orient']) || !is_numeric($data['is_orient']) || !in_array($data['is_orient'], [1,2])){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2,  is_orient[{$data['is_orient']}] error ", 'error');
                $return_result['code'] = 2113;
                return $return_result;
            }

            //收款人信息校验
            if(empty($data['payee_name']) || empty($data['payee_bankzone']) || empty($data['payee_bankcard'])){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2, payee_info error ", 'error');
                $return_result['code'] = 2300;
                return $return_result;
            }

            //是否有兑换中数据
            $exchange_sql = "select id from firstp2p_debt_exchange_log where tender_id=$tender_id and status=1 ";
            $exchange_info = Yii::app()->db->createCommand($exchange_sql)->queryRow();
            if($exchange_info){
                Yii::log("createDebt tender_id[$tender_id]: change_log status=1  error ");
                $return_result['code'] = 5006;
                return $return_result;
            }

            //过期时间 精确到秒
            $expected_end_time = strtotime("now +{$data['effect_days']} days");
            $buy_code = $data['is_orient'] == 1 ? rand(1000, 9999) : '';
        }

        //投资记录信息
        $borrow_tender = DealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array(':id' => $tender_id));
        if(!$borrow_tender || $borrow_tender->debt_status != 0 ) {
            Yii::log("createDebt tender_id[$tender_id]: debt_status !=0 ", 'error');
            $return_result['code'] = 2003;
            return $return_result;
        }

        //收购债权禁止债转
        if($borrow_tender->exclusive_purchase_id > 0){
            Yii::log("createDebt tender_id[$tender_id]: exclusive_purchase_id[$borrow_tender->exclusive_purchase_id] > 0 ", 'error');
            $return_result['code'] = 3010;
            return $return_result;
        }

        //黑名单
        if($borrow_tender->black_status == 2){
            Yii::log("createDebt tender_id[$tender_id]: black_status[$borrow_tender->black_status] = 2 ", 'error');
            $return_result['code'] = 2096;
            return $return_result;
        }

        //冻结债权禁止债转
        if($borrow_tender->xf_status == 1){
            Yii::log("createDebt tender_id[$tender_id]: xf_status[$borrow_tender->xf_status] = 1 ", 'error');
            $return_result['code'] = 7017;
            return $return_result;
        }

        //确权状态【债权划扣、一键下车不校验是否确权】
        /*
        if($borrow_tender->is_debt_confirm != 1 && in_array($debt_src,[1,2]) ){
            Yii::log("createDebt tender_id[$tender_id]: is_debt_confirm[$borrow_tender->is_debt_confirm] != 1 ", 'error');
            $return_result['code'] = 2309;
            return $return_result;
        }*/
        //用户信息校验
        if($user_id != $borrow_tender->user_id){
            Yii::log("createDebt tender_id[$tender_id]: user_id[$user_id] != tender_user[$borrow_tender->user_id] ", 'error');
            $return_result['code'] = 2041;
            return $return_result;
        }

        //兑换金额必须大于0
        if(FunctionUtil::float_bigger_equal(0, $money, 2)){
            Yii::log("createDebt tender_id[$tender_id]: debt_account:$money<=0 ", 'error');
            $return_result['code'] = 2004;
            return $return_result;
        }

        //投资记录待还本金
        $wait_capital = $borrow_tender->wait_capital;
        //待还本金必须大于0
        if(FunctionUtil::float_bigger_equal(0, $wait_capital, 2)){
            Yii::log("createDebt tender_id[$tender_id]: wait_capital:$wait_capital<=0 ", 'error');
            $return_result['code'] = 2005;
            return $return_result;
        }

        //禁止指定项目债转
        $disable_zx = AgWxDebtBlackList::model()->find("deal_id=$borrow_tender->deal_id and status=1 and type=1");
        if($disable_zx){
            Yii::log("createDebt tender_id[$tender_id]: deal_id[$borrow_tender->deal_id] in disable_zx error ", 'error');
            $return_result['code'] = 2301;
            return $return_result;
        }

        //待处理线下还款校验
        if($this->checkTenderRepay($tender_id) == false){
            Yii::log("createDebt tender_id[$tender_id]: checkTenderRepay return false ", 'error');
            $return_result['code'] = 5003;
            return $return_result;
        }

        /*
        //个人CA证书申请校验
        if($this->checkFddUserId($user_id) == false){
            Yii::log("createDebt tender_id[$tender_id]: checkFddUserId[$user_id] return false ", 'error');
            $return_result['code'] = 5004;
            return $return_result;
        }*/

        //还款计划表待还本金
        $repay_sql = "select sum(money) as repay_wait_capital from firstp2p_deal_loan_repay where status=0 and deal_loan_id=$borrow_tender->id and type=1 and money>0 ";
        $repay_wait_capital = Yii::app()->db->createCommand($repay_sql)->queryScalar();
        if(empty($repay_wait_capital) || $repay_wait_capital<=0 ){
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_deal_loan_repay error!!!", 'error');
            $return_result['code'] = 2006;
            return $return_result;
        }
        //校验待还本金一致性
        if(!FunctionUtil::float_equal($repay_wait_capital, $wait_capital, 2)){
            Yii::log("createDebt tender_id[$tender_id]: repay_wait_capital:$repay_wait_capital != wait_capital:$wait_capital ", 'error');
            $return_result['code'] = 2007;
            return $return_result;
        }

        //兑换后剩余本金
        $s_money = bcsub($wait_capital, $money, 2);
        //剩余本金必须大于或等于O
        if(FunctionUtil::float_bigger(0, $s_money, 2)){
            Yii::log("createDebt tender_id[$tender_id]: s_money:$s_money<0 ", 'error');
            $return_result['code'] = 2008;
            return $return_result;
        }
        //当剩余金额大于0时，必须大于起投金额
        if(FunctionUtil::float_bigger($s_money, 0, 2) && FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $s_money, 2)){
            Yii::log("createDebt tender_id[$tender_id]: s_money:$s_money < ".self::MIN_LOAN_AMOUNT.", debt_account:$money, wait_capital:$wait_capital", 'error');
            $return_result['code'] = 2009;
            return $return_result;
        }

        //项目信息校验
        $borrow = Deal::model()->findByPk($borrow_tender->deal_id);
        //有效子表，普通类型还款中
        if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || !in_array($borrow->deal_type, [2,3]) || $borrow->deal_status != 4){
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_deal[$borrow_tender->deal_id] error", 'error');
            $return_result['code'] = 2010;
            return $return_result;
        }

        //查投资记录是否重复转让
        $SucNum = Debt::model()->count("user_id=:user_id and tender_id={$tender_id} and status=1", array(':user_id'=>$user_id));
        if ($SucNum > 0) {
            Yii::log("createDebt tender_id[$tender_id]: repeat request ", 'error');
            $return_result['code'] = 2011;
            return $return_result;
        }

        //还款日限制【每日下午16点】
        /*
        $repay_time = strtotime('midnight') + 57600;
        $today_repay = DealLoanRepay::model()->find( "status=0 and deal_loan_id=$borrow_tender->id and `time`=$repay_time and money>0");
        if($today_repay){
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_deal_loan_repay repay_time error!!!", 'error');
            $return_result['code'] = 2039;
            return $return_result;
        }*/
        
        //firstp2p_debt表数据组成
        $debt['user_id'] = $user_id;
        $debt['type'] = $borrow->deal_type;
        $debt['tender_id'] = $tender_id;
        $debt['borrow_id'] = $borrow_tender->deal_id;
        $debt['money'] = $money;
        $debt['sold_money'] = 0;
        $debt['discount'] = $discount;
        $debt['starttime'] = $time;
        $debt['endtime'] = $expected_end_time;
        $debt['successtime'] = 0;
        $debt['borrow_apr'] = $borrow->income_fee_rate;
        $debt['status'] = 1;
        $debt['debt_type'] = 1;
        $debt['debt_src'] = $debt_src;
        $debt['addtime'] = $time;
        $debt['addip'] = FunctionUtil::ip_address();
        $debt['serial_number'] = FunctionUtil::getAgRequestNo('ZXDT');

        //自主债转交易
        if($debt_src == 2){
            $debt['buy_code'] = $buy_code;
            $debt['payee_name'] = trim($data['payee_name']);
            $debt['payee_bankzone'] = trim($data['payee_bankzone']);
            $debt['payee_bankcard'] = trim($data['payee_bankcard']);
        }

        //权益兑换一键下车及退回
        if(in_array($debt_src, [1,4,5])){
            $debt['platform_no'] = $data['platform_no'];
        }

        $ret = BaseCrudService::getInstance()->add('Debt', $debt);
        if(false == $ret){//添加失败
            Yii::log("createDebt tender_id[$tender_id]: AddDebt error ", 'error');
            $return_result['code'] = 2012;
            return $return_result;
        }

        //投资记录债权状态变更
        $borrow_tender->debt_status = 1;
        if($borrow_tender->save(false, array('debt_status')) == false){
            Yii::log("createDebt tender_id[$tender_id]: update firstp2p_deal_load.debt_status fail ", 'error');
            $return_result['code'] = 2013;
            return $return_result;
        }

        $return_result['code'] = 0;
        $return_result['data']['debt_id'] = $ret['id'];
        return $return_result;
    }

    /**
     * 尊享/普惠承接债转
     * @param $debt_data [
     *  'user_id' => 承接人用户ID,//必传
     *  'money' => 承接金额,//必传
     *  'debt_id' => 承接的债转ID,//必传
     *  'buy_code'=>认购码,//非必传
     *  'products'=>所属产品1尊享 2普惠供应链,//必传
     * ]
     * @return array
     */
    public function undertakeDebt($debt_data){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        //用户未登录
        if('' == $debt_data['user_id']){
            Yii::log("undertakeDebt {$debt_data['user_id']} User error", 'error');
            $return_result['code'] = 2000;
            return $return_result;
        }
        //债权id非空校验
        if( $this->emp($debt_data['debt_id']) || $this->emp($debt_data['money']) || !is_numeric($debt_data['debt_id']) || !in_array($debt_data['products'], Yii::app()->c->xf_config['platform_type'])) {
            Yii::log("undertakeDebt {$debt_data['user_id']} params error", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }
        //交易金额数据校验
        if(!is_numeric($debt_data['money']) || FunctionUtil::float_bigger_equal(0, $debt_data['money'], 2) || !ItzUtil::checkMoney($debt_data['money'])){
            Yii::log("undertakeDebt {$debt_data['user_id']} money[{$debt_data['money']}] error", 'error');
            $return_result['code'] = 2302;
            return $return_result;
        }
        //数据库配置
        $model_prefix = "firstp2p_";
        if($debt_data['products'] == 2){
            $this->table_prefix = "PH";
            $this->db_name = "phdb";
        }elseif(in_array($debt_data['products'], Yii::app()->c->xf_config['offline_products'])){
            $this->table_prefix = "Offline";
            $this->db_name = "offlinedb";
            $model_prefix = "offline_";
        }

        $debt_id = $debt_data['debt_id'];
        $account_money = $debt_data['money'];
        $user_id = $debt_data['user_id'];
        $debt_model = "{$this->table_prefix}Debt";
        $deal_model = "{$this->table_prefix}Deal";
        $deal_load_model = "{$this->table_prefix}DealLoad";
        $debt_tender_model = "{$this->table_prefix}DebtTender";

        //事务开启
        Yii::app()->{$this->db_name}->beginTransaction();
        try{
            // 获取债权信息数组
            $debt = $debt_model::model()->findBySql("select * from {$model_prefix}debt where id=:id for update", array('id' => $debt_id))->attributes;
            if(!$debt){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; firstp2p_debt.id[{$debt_id}] not exist", 'error');
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2017;
                return $return_result;
            }

            $check_debt_ret = $this->checkDebt($debt, $debt_data);
            if($check_debt_ret['code'] != 0){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; checkDebt return:".print_r($check_debt_ret, true), 'error');
                Yii::app()->{$this->db_name}->rollback();
                return $check_debt_ret;
            }

            //校验用户信息
            $user_info = $this->checkUser($user_id);
            if($user_info == false){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id: checkUser[$user_id] return false ", 'error');
                $return_result['code'] = 5005;
                return $return_result;
            }

            //待处理线下还款校验
            if($this->checkTenderRepay($debt['tender_id'], $debt_data['products']) == false){
                Yii::log("undertakeDebt tender_id[{$debt['tender_id']}]: checkTenderRepay return false ", 'error');
                $return_result['code'] = 5003;
                return $return_result;
            }

            /*
            //个人CA证书申请校验
            if($this->checkFddUserId($user_id) == false){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id: checkFddUserId[$user_id] return false ", 'error');
                $return_result['code'] = 5004;
                return $return_result;
            }*/

            //项目信息校验
            $borrow = $deal_model::model()->findByPk($debt['borrow_id']);
            //有效子标，普通类型还款中
            if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || $borrow->deal_status != 4){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; {$model_prefix}deal[{$debt['borrow_id']}] error", 'error');
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2028;
                return $return_result;
            }

            //debt_type 校验
            if(($debt_data['products'] == 1 && !in_array($borrow->deal_type, [2,3])) || ($debt_data['products'] == 2 && $borrow->deal_type !=0)){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; products[{$debt_data['products']}] deal_type[$borrow->deal_type] error ", 'error');
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2303;
                return $return_result;
            }

            //禁止指定项目债转
            $disable_zx = AgWxDebtBlackList::model()->find("deal_id={$debt['borrow_id']} and status=1 ");
            if($disable_zx){
                Yii::log("undertakeDebt user_id:$user_id, deal_id[{$debt['borrow_id']}] in disable_zx error ", 'error');
                $return_result['code'] = 2301;
                return $return_result;
            }

            //买家实际支付金额计算
            $investMoney = round($debt['discount']*$account_money*0.1, 2);
            //卖家debt更新数据
            $debt_edit_res = $debt_model::model()->updateByPk($debt['id'], ['status'=>5]);
            if(!$debt_edit_res){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; debt.id[{$debt['id']}] edit status=5 error", 'error');
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2275;
                return $return_result;
            }

            //卖家firstp2p_deal_load数据
            $borrow_tender = $deal_load_model::model()->findBySql("select * from {$model_prefix}deal_load where id=:id ", array('id' => $debt['tender_id']));
            if($borrow_tender->debt_status != 1){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; {$model_prefix}deal_load.debt_status[{$borrow_tender->debt_status}] != 1", "error");
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2029;
                return $return_result;
            }

            //黑名单
            if($borrow_tender->black_status == 2){
                Yii::log("undertakeDebt  user_id:$user_id, debt_id:$debt_id; black_status[$borrow_tender->black_status] = 2 ", 'error');
                $return_result['code'] = 2096;
                return $return_result;
            }

            //剩余本金校验
            $s_money = bcsub($borrow_tender['wait_capital'], $account_money, 2);
            if(FunctionUtil::float_bigger(0, $s_money,2)){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; wait_capital error ", "error");
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2030;
                return $return_result;
            }

            //添加买家debt_tender
            unset($data);
            $undertake_endtime = ConfUtil::get('youjie-undertake-endtime');
            $data['debt_id'] = $debt_id;
            $data['money'] = $account_money;//投资额
            $data['account'] = $account_money;
            $data['action_money'] = $investMoney;//本期是指支付金额
            $data['user_id'] = $user_id;
            $data['status'] = 1;//1-待付款 2-交易成功 6-待卖方收款 3-手动交易取消 4-待付款过期 5-客服判定无效
            $data['type'] = 2;//债权
            $data['addtime'] = time();
            $data['addip'] = FunctionUtil::ip_address();
            $data['debt_type'] = 0;
            $data['new_tender_id'] = 0;
            $data['cancel_time'] = strtotime ("+{$undertake_endtime} second");
            $data['purchase_id'] = $debt['purchase_id'];
            $data['area_id'] = $debt['area_id'];
            if(in_array($debt_data['products'], Yii::app()->c->xf_config['offline_products'])){
                $data['platform_id'] = $borrow_tender->platform_id;
            }
            $debt_tender = BaseCrudService::getInstance()->add($debt_tender_model, $data);//添加债权tender
            if(!$debt_tender){
                Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; $debt_tender_model add error :". print_r($data, true), "error");
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2032;
                return $return_result;
            }

            $return_result['data']['debt_tender_id'] = $debt_tender['id'];

            //汇源求购专区短信不走这里
            if($debt['purchase_id'] == 0){
                //发送卖方短信通知
                $remind = array();
                $remind['sms_code'] = "wx_seller_order_create";
                $remind['mobile'] = $this->getPhone($debt['user_id']);
                //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/2?products={$debt_data['products']}&debt_id={$debt_tender['id']}";
                $smaClass = new XfSmsClass();
                $send_ret = $smaClass->sendToUserByPhone($remind);
                if($send_ret['code'] != 0){
                    Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; sendToUser seller error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
                }


                //发送买方短信通知
                $remind = array();
                $remind['sms_code'] = "wx_buyer_order_create";
                $remind['mobile'] = $this->getPhone($user_id);
                //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/1?products={$debt_data['products']}&debt_id={$debt_tender['id']}";
                $smaClass = new XfSmsClass();
                $send_ret = $smaClass->sendToUserByPhone($remind);
                if($send_ret['code'] != 0){
                    Yii::log("undertakeDebt user_id:$user_id, debt_id:$debt_id; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
                }
            }

            Yii::app()->{$this->db_name}->commit();
            $return_result['code'] = 0;
            return $return_result;
        }catch (Exception $ee) {
            Yii::log("undertakeDebt Exception user_id:$user_id, debt_id:$debt_id; exception:".print_r($ee->getMessage(), true));
            Yii::app()->{$this->db_name}->rollback();
            $return_result['code'] = 5000;
            return $return_result;
        }
    }

    private function getPhone($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            return false;
        }
        //用户信息
        $userInfo = User::model()->findByPk($user_id);
        if(empty($userInfo)){
            return false;
        }

        return GibberishAESUtil::dec($userInfo->mobile, Yii::app()->c->contract['idno_key']);
    }

    /**
     * 尊享/普惠确认交易成功
     * @param $debt_data [
     *  'products'=>所属产品1尊享 2普惠供应链,//必传
     *  'debt_tender_id' => 认购记录ID,//必传
     *  'decision_src' => 判定来源1-用户自主确认 2客服判定 3申请客服介入后用户自主判定//必传
     *  'decision_maker' =>判定人ID//自主确认的传卖方用户ID 客服判定的传客服ID必传
     * ]
     * @return array
     */
    public function confirmDebt($debt_data){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        //非空校验
        if( $this->emp($debt_data['debt_tender_id']) || $this->emp($debt_data['decision_maker']) || !in_array($debt_data['decision_src'], [1,2,3]) || !in_array($debt_data['products'], Yii::app()->c->xf_config['platform_type'])) {
            Yii::log("confirmDebt {$debt_data['debt_tender_id']} params error：".print_r($debt_data, true), 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //数据库配置
        $service_name = "DebtService";
        $debt_tender_name = "firstp2p_debt_tender";
        if($debt_data['products'] == 2){
            $this->table_prefix = "PH";
            $this->db_name = "phdb";
            $service_name = "PhDebtService";
        }elseif(in_array($debt_data['products'], Yii::app()->c->xf_config['offline_products'])){
            $this->table_prefix = "Offline";
            $this->db_name = "offlinedb";
            $service_name = "OfflineDebtService";
            $debt_tender_name = "offline_debt_tender";
        }
        $debt_tender_model = "{$this->table_prefix}DebtTender";
        $debt_appeal_model = "AgWxDebtAppeal";
        $debt_tender_id = $debt_data['debt_tender_id'];
        //事务开启
        Yii::app()->db->beginTransaction();
        Yii::app()->{$this->db_name}->beginTransaction();
        try{
            // 获取债权信息数组
            $debt_tender = $debt_tender_model::model()->findBySql("select * from $debt_tender_name where id=:id for update", array('id' => $debt_tender_id));
            if(!$debt_tender || $debt_tender->status != 6){
                Yii::log("confirmDebt debt_tender_id:$debt_tender_id; $debt_tender_name.id[{$debt_tender_id}] not exist or status[$debt_tender->status] != 6", 'error');
                Yii::app()->db->rollback();
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2017;
                return $return_result;
            }

            //有求购ID的，更新求购数据，目前仅普惠支持求购操作
            if($debt_tender->purchase_id > 0 && $debt_data['products'] == 2){
                $purchase_info = XfPlanPurchase::model()->findBySql("select * from xf_plan_purchase where id=:id for update", array('id' => $debt_tender->purchase_id));
                if(!$purchase_info || FunctionUtil::float_bigger($debt_tender->money, $purchase_info->trading_amount, 2)){
                    Yii::log("confirmDebt purchase_id:$debt_tender->purchase_id exception ", 'error');
                    Yii::app()->db->rollback();
                    Yii::app()->{$this->db_name}->rollback();
                    $return_result['code'] = 7005;
                    return $return_result;
                }

                //受让人数据校验
                $assignee_info = XfDebtAssigneeInfo::model()->findBySql("select * from xf_debt_assignee_info where user_id=$purchase_info->user_id for update");
                if(!$assignee_info || FunctionUtil::float_bigger($debt_tender->money, $assignee_info->trading_amount, 2)){
                    Yii::log("confirmDebt xf_debt_assignee_info user_id:$purchase_info->user_id exception ", 'error');
                    Yii::app()->db->rollback();
                    Yii::app()->{$this->db_name}->rollback();
                    $return_result['code'] = 7007;
                    return $return_result;
                }

                //求购数据更更新
                $purchase_info->trading_amount = bcsub($purchase_info->trading_amount, $debt_tender->money, 2);
                $purchase_info->purchased_amount = bcadd($purchase_info->purchased_amount, $debt_tender->money, 2);
                $purchase_info->traded_num += 1;
                $purchase_info->trading_num -= 1;
                if($purchase_info->save(false, array('trading_amount', 'trading_num', 'purchased_amount','traded_num')) == false){
                    $this->echoLog("confirmDebt update xf_plan_purchase trading_amount[$assignee_info->trading_amount], trading_num[$assignee_info->trading_num] error, id=$assignee_info->id");
                    Yii::app()->db->rollback();
                    Yii::app()->{$this->db_name}->rollback();
                    $return_result['code'] = 7013;
                    return $return_result;
                }

                //受让人数据更更新
                $assignee_info->trading_amount = bcsub($assignee_info->trading_amount, $debt_tender->money, 2);
                $assignee_info->transferred_amount = bcadd($assignee_info->transferred_amount, $debt_tender->money, 2);
                if($assignee_info->save(false, array('trading_amount','transferred_amount')) == false){
                    $this->echoLog("confirmDebt update xf_debt_assignee_info trading_amount[$assignee_info->trading_amount] error, id=$assignee_info->id");
                    Yii::app()->db->rollback();
                    Yii::app()->{$this->db_name}->rollback();
                    $return_result['code'] = 7014;
                    return $return_result;
                }
            }

            //客服介入的，校验判定记录
            if(in_array($debt_data['decision_src'], [2,3])){
                // 获取债权信息数组
                $debt_appeal = $debt_appeal_model::model()->findBySql("select * from ag_wx_debt_appeal where debt_tender_id=$debt_tender->id and products={$debt_data['products']} for update");
                if($debt_data['decision_src'] == 2){
                    if(!$debt_appeal || $debt_appeal->status != 1){
                        Yii::log("confirmDebt debt_tender_id:$debt_tender_id; ag_wx_debt_appeal not exist or status[$debt_appeal->status] != 1", 'error');
                        Yii::app()->db->rollback();
                        Yii::app()->{$this->db_name}->rollback();
                        $return_result['code'] = 2304;
                        return $return_result;
                    }
                }
                if(!empty($debt_appeal)){
                    //更新判定表状态
                    $debt_appeal->status = 2;
                    $debt_appeal->decision_maker = $debt_data['decision_maker'];
                    $debt_appeal->decision_time = time();
                    //判定结果
                    if(!empty($debt_data['decision_outcomes'])){
                        $debt_appeal->decision_outcomes = trim($debt_data['decision_outcomes']);
                    }
                    if($debt_appeal->save() == false){
                        Yii::log("confirmDebt debt_tender_id:$debt_tender_id; ag_wx_debt_appeal edit error ", 'error');
                        Yii::app()->db->rollback();
                        Yii::app()->{$this->db_name}->rollback();
                        $return_result['code'] = 2305;
                        return $return_result;
                    }
                }
            }

            //债权认购
            $debt_params = array();
            $debt_params['user_id'] = $debt_tender->user_id;
            $debt_params['money'] = $debt_tender->money;
            $debt_params['debt_id'] = $debt_tender->debt_id;
            $debt_params['frozen_amount'] = $debt_tender->action_money;
            $debt_params['decision_src'] = $debt_data['decision_src'];
            $debt_params['decision_maker'] = $debt_data['decision_maker'];
            $debt_transaction_ret = $service_name::getInstance()->debtPreTransaction($debt_params);
            if($debt_transaction_ret['code'] != 0 || empty($debt_transaction_ret['data']['new_tender_id'])){
                Yii::log("confirmDebt debt_tender_id:$debt_tender_id; debtPreTransaction return:".print_r($debt_transaction_ret,true), 'error');
                Yii::app()->db->rollback();
                Yii::app()->{$this->db_name}->rollback();
                return $debt_transaction_ret;
            }

            //更新认购记录状态
            $debt_tender->status = 2;
            $debt_tender->new_tender_id = $debt_transaction_ret['data']['new_tender_id'];
            if($debt_tender->save(false, array('status', 'new_tender_id')) == false){
                Yii::log("confirmDebt debt_tender_id:$debt_tender_id; $debt_tender_name edit status = 2 ", 'error');
                Yii::app()->db->rollback();
                Yii::app()->{$this->db_name}->rollback();
                $return_result['code'] = 2306;
                return $return_result;
            }


            //短信模板选择
            $seller_sms_code = 'wx_seller_seller_receive_money_success';
            $buyer_sms_code = 'wx_buyer_seller_receive_money_success';
            $serial_number = $debt_transaction_ret['data']['serial_number'];
            if($debt_data['decision_src'] == 2){
                $seller_sms_code = 'wx_seller_trade_success';
                $buyer_sms_code = 'wx_buyer_trade_success';
            }

            //发送卖方短信通知
            $remind = array();
            $remind['sms_code'] = $seller_sms_code;
            $remind['mobile'] = $this->getPhone($debt_transaction_ret['data']['seller_user_id']);
            //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/2?products={$debt_data['products']}&debt_id={$debt_tender->id}";
            $remind['data']['order_no'] = $serial_number;
            $smaClass = new XfSmsClass();
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("confirmDebt debt_tender_id:$debt_tender_id; sendToUser seller error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }

            //发送买方短信通知
            $remind = array();
            $remind['sms_code'] = $buyer_sms_code;
            $remind['mobile'] = $this->getPhone($debt_tender->user_id);
            //$remind['data']['url'] = Yii::app()->c->youjie_base_url."/debt/#/subscribeDetail/1?products={$debt_data['products']}&debt_id={$debt_tender->id}";
            $remind['data']['order_no'] = $serial_number;
            $smaClass = new XfSmsClass();
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("confirmDebt debt_tender_id:$debt_tender_id; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }
            Yii::app()->db->commit();
            Yii::app()->{$this->db_name}->commit();
            $return_result['code'] = 0;
            return $return_result;
        }catch (Exception $ee) {
            Yii::log("confirmDebt Exception debt_tender_id:$debt_tender_id; exception:".print_r($ee->getMessage(), true));
            Yii::app()->db->rollback();
            Yii::app()->{$this->db_name}->rollback();
            $return_result['code'] = 5000;
            return $return_result;
        }
    }

    private function checkDebt($debt, $debt_data){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        $account_money = $debt_data['money'];
        $user_id = $debt_data['user_id'];
        //项目类型校验
        if($debt_data['products'] == 1 && !in_array($debt['type'], $this->borrow_type)){
            Yii::log("checkDebt user_id[{$debt_data['user_id']}]} products=1 firstp2p_debt.type[{$debt['type']}] error ", 'error');
            $return_result['code'] = 2018;
            return $return_result;
        }

        //普惠项目类型校验
        if($debt_data['products'] == 2 && $debt['type'] != 0){
            Yii::log("checkDebt user_id[{$debt_data['user_id']}]} products=2 firstp2p_debt.type[{$debt['type']}] error ", 'error');
            $return_result['code'] = 2018;
            return $return_result;
        }

        //不能认购自己发布的债权
        if ($debt['user_id'] == $user_id) {
            Yii::log("checkDebt {$debt_data['user_id']} firstp2p_debt.user_id[{$debt['user_id']}] = user_id[{$user_id}]", 'error');
            $return_result['code'] = 2227;
            return $return_result;
        }

        //已被认购完
        if(FunctionUtil::float_bigger_equal($debt['sold_money'], $debt['money'], 3)){
            Yii::log("checkDebt {$debt_data['user_id']} code=2204", 'error');
            $return_result['code'] = 2019;
            return $return_result;
        }

        //认购额超过债权剩余额度
        if(FunctionUtil::float_bigger(round($account_money, 2), round($debt['money'] - $debt['sold_money'], 2), 2)){
            Yii::log("checkDebt {$debt_data['user_id']} code=2205", 'error');
            $return_result['code'] = 2020;
            return $return_result;
        }

        //债权已取消
        if(3 == $debt['status']){
            Yii::log("checkDebt {$debt_data['user_id']} code=2228", 'error');
            $return_result['code'] = 2021;
            return $return_result;
        }

        //债权已过期
        if(4 == $debt['status'] || $debt['endtime'] < time()){
            Yii::log("checkDebt {$debt_data['user_id']} code=2229", 'error');
            $return_result['code'] = 2022;
            return $return_result;
        }

        //已认购满额
        if(5 == $debt['status']){
            Yii::log("checkDebt {$debt_data['user_id']} status=5", 'error');
            $return_result['code'] = 2023;
            return $return_result;
        }

        //已经认购完成
        if(2 == $debt['status']){
            Yii::log("checkDebt {$debt_data['user_id']} status=2", 'error');
            $return_result['code'] = 2024;
            return $return_result;
        }

        //状态异常
        if($debt['status'] != 1){
            Yii::log("checkDebt {$debt_data['user_id']} status!=1", 'error');
            $return_result['code'] = 2025;
            return $return_result;
        }

        //认购码校验
        if(($debt['debt_src'] == 2 && !empty($debt['buy_code'])) && $debt['buy_code'] != $debt_data['buy_code']){
            Yii::log("checkDebt {$debt_data['user_id']} buy_code error, debt_code[{$debt['buy_code']}], debt_data_code[{$debt_data['buy_code']}] ", 'error');
            $return_result['code'] = 2310;
            return $return_result;
        }
        //当前业务模式 必须一笔全部认购，后期调整可放开
        if(!FunctionUtil::float_equal($debt['money'], $debt_data['money'], 2)){
            Yii::log("checkDebt {$debt_data['user_id']} debt.money[{$debt['money']}] != debt_data.money[{$debt_data['money']}] error", 'error');
            $return_result['code'] = 2312;
            return $return_result;
        }
        return $return_result;
    }

    /**
     * 认购债权处理
     * @param $debt_data [
     * $money 认购金额
     * $user_id 买家user_id
     * $debt_id 被认购债权ID
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
        if(!is_numeric($debt_data['money']) || FunctionUtil::float_bigger_equal(0, $debt_data['money'], 2) || !ItzUtil::checkMoney($debt_data['money'])){
            Yii::log("debtPreTransaction  step03 {$debt_data['user_id']} money[{$debt_data['money']}] error", 'error');
            $return_result['code'] = 2016;
            return $return_result;
        }

        $debt_id = $debt_data['debt_id'];
        $account_money = $debt_data['money'];
        $user_id = $debt_data['user_id'];
        $platform_no = $debt_data['platform_no'];

        // 获取债权信息数组
        $debt = Debt::model()->findBySql("select * from firstp2p_debt where id=:id for update", array('id' => $debt_id))->attributes;
        if(!$debt){
            Yii::log("debtPreTransaction  step04 {$debt_data['user_id']} firstp2p_debt.id[{$debt_id}] not exist", 'error');
            $return_result['code'] = 2017;
            return $return_result;
        }

        //自主判定的校验判定用户ID
        if(in_array($debt_data['decision_src'], [1,3]) && $debt_data['decision_maker'] != $debt['user_id']){
            Yii::log("debtPreTransaction decision_maker[{$debt_data['decision_maker']}] != debt.user_id[{$debt['user_id']}]  ", 'error');
            $return_result['code'] = 2311;
            return $return_result;
        }

        $debt_src = $debt['debt_src'];
        //权益兑换与债权划扣交易时，债转状态必须为转让中
        if(in_array($debt_src, [1,3,4]) && $debt['status'] != 1){
            Yii::log("debtPreTransaction  step28 {$debt_data['user_id']} code=2221", 'error');
            $return_result['code'] = 2307;
            return $return_result;
        }

        //债转交易确认时，债转状态必须为待确认
        if($debt_src == 2 && $debt['status'] != 6){
            Yii::log("debtPreTransaction  step28 {$debt_data['user_id']} code=2221", 'error');
            $return_result['code'] = 2308;
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
        $borrow = Deal::model()->findByPk($debt['borrow_id']);
        //有效子标，普通类型还款中
        if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || !in_array($borrow->deal_type, [2,3]) || $borrow->deal_status != 4){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, firstp2p_deal[{$debt['borrow_id']}] error", 'error');
            $return_result['code'] = 2028;
            return $return_result;
        }

        //禁止指定项目债转
        $disable_zx = AgWxDebtBlackList::model()->find("deal_id={$debt['borrow_id']} and status=1 and type=1");
        if($disable_zx){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, deal_id[{$debt['borrow_id']}] in disable_zx error ", 'error');
            $return_result['code'] = 2301;
            return $return_result;
        }

        //买家实际支付金额计算
        $investMoney = $debt_data['frozen_amount'];

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
        $debt_edit_res = Debt::model()->updateByPk($debt['id'], $edit_debt);
        if(!$debt_edit_res){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, debt.id[{$debt['id']}] edit error:".json_encode($edit_debt), 'error');
            $return_result['code'] = 2275;
            return $return_result;
        }

        //卖家firstp2p_deal_load数据
        $borrow_tender = DealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array('id' => $debt['tender_id']));
        if($borrow_tender->debt_status != 1){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, firstp2p_deal_load.debt_status[{$borrow_tender->debt_status}] != 1", "error");
            $return_result['code'] = 2029;
            return $return_result;
        }

        //黑名单
        if($borrow_tender->black_status == 2){
            Yii::log("debtPreTransaction  {$debt_data['user_id']}, black_status[$borrow_tender->black_status] = 2 ", 'error');
            $return_result['code'] = 2096;
            return $return_result;
        }

        //卖家债转前剩余本金
        $seller_capital = $borrow_tender['wait_capital'];
        //卖家投资记录待还本金数据变更
        $_data = array();
        $_data['wait_capital'] = bcsub($borrow_tender['wait_capital'], $account_money, 2);
        $_data['id'] = $borrow_tender['id'];
        if(FunctionUtil::float_bigger_bc(0.02, $_data['wait_capital'])) {
            $_data['debt_status'] = 15;
            $_data['wait_interest'] = 0;
            $_data['status'] = 15;
        }else{
            //如果待还本金大于0，债转数据已转让完成
            $_data['debt_status'] = $edit_debt['status'] == 2 ? 0 : $borrow_tender['debt_status'];
        }

        if(FunctionUtil::float_bigger(0, $_data['wait_capital'],2)){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, wait_capital error ", "error");
            $return_result['code'] = 2030;
            return $return_result;
        }
        $edit_load = BaseCrudService::getInstance()->update('DealLoad', $_data,'id');
        if(!$edit_load){
            Yii::log('debtPreTransaction DealLoad edit error, data:'.  print_r($_data,true));
            return false;
        }

        //wx特殊数据处理
        if($_data['debt_status'] == 15){
            $special_capital = DealLoanRepay::model()->find("deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1");
            if($special_capital){
                $update_repay_sql = "update firstp2p_deal_loan_repay set status=15 where deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1 ";
                $r_edit_ret = Yii::app()->db->createCommand($update_repay_sql)->execute();
                if(!$r_edit_ret){
                    Yii::log("debtPreTransaction seller firstp2p_deal_loan_repay edit error, sql:$update_repay_sql");
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
        $tender['status'] = 1;
        $tender['create_time'] = time();
        $tender['is_repay'] = 0;
        $tender['from_deal_id'] = $borrow_tender['from_deal_id'];
        $tender['deal_parent_id'] = $borrow_tender['deal_parent_id'];
        $tender['site_id'] = $borrow_tender['site_id'];
        $tender['source_type'] = 9;//0元认购商城兑换的债权
        $tender['deal_type'] = $borrow_tender['deal_type'];
        $tender['debt_type'] = 2;
        $tender['is_debt_confirm'] = $borrow_tender['is_debt_confirm'];
        $tender['debt_confirm_time'] = time();
        $tender_ret = BaseCrudService::getInstance()->add('DealLoad', $tender);
        if(!$tender_ret){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, Buyers DealLoad add error :". print_r($tender, true), "error");
            $return_result['code'] = 2031;
            return $return_result;
        }

        //新生成投资记录ID
        $return_result['data']['new_tender_id'] = $tender_ret['id'];
        //权益兑换与债权划扣
        if(in_array($debt_src, [1,3,4])){
            //添加买家debt_tender[为以后一笔债权支持多用户购买做准备]
            unset($data);
            $data['debt_id'] = $debt_id;
            $data['money'] = $account_money;//投资额
            $data['account'] = $account_money;
            $data['action_money'] = $investMoney;//本期是指支付金额为0
            $data['user_id'] = $user_id;
            $data['status'] = 2;//1表示认购中2表示认购成功3表示认购取消
            $data['type'] = 2;//债权
            $data['addtime'] = time();
            $data['addip'] = FunctionUtil::ip_address();
            $data['debt_type'] = 0;//0定期 1活期拆散标债权
            $data['new_tender_id'] = $return_result['data']['new_tender_id'];
            $debt_tender = BaseCrudService::getInstance()->add('DebtTender', $data);//添加债权tender
            if(!$debt_tender){
                Yii::log("debtPreTransaction {$debt_data['user_id']}, DebtTender add error :". print_r($data, true), "error");
                $return_result['code'] = 2032;
                return $return_result;
            }
        }

        //买家还款计划生成
        $loan_repay_sql = "select * from firstp2p_deal_loan_repay where deal_loan_id ={$debt['tender_id']} and status=0 and type in (1, 2) and money>0 ";
        $seller_loan_repay = Yii::app()->db->createCommand($loan_repay_sql)->queryAll();
        if(!$seller_loan_repay){
            Yii::log(" debtPreTransaction firstp2p_deal_loan_repay  not exist");
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
            Yii::log(" debtPreTransaction firstp2p_deal_loan_repay.sum_capital[{$repay_surplus_capital}] != deal_loan.wait_capital[{$seller_capital}]  not exist");
            $return_result['code'] = 2034;
            return $return_result;
        }

        //购买比例
        $rate = round($account_money/$seller_capital, 8);
        $buy_capital = 0.00;
        $repay_values = '';
        $create_num = 0;
        $buyer_interest = 0.00;
        foreach ($seller_loan_repay as $k => $v) {
            $repay_money = round($rate * $v['money'], 2);
            if($v['type'] == 1){
                //最后一期本金误差消除
                if($capital_num == $create_num){
                    $repay_money = bcsub($account_money, $buy_capital, 2);
                }else{
                    $buy_capital = bcadd($buy_capital, $repay_money, 2);
                    $create_num++;
                }
            }

            //卖家利息统计
            if($v['type'] == 2){
                $buyer_interest = bcadd($buyer_interest, $repay_money, 2);
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
                Yii::log("debtPreTransaction buyer firstp2p_deal_loan_repay money less than 0 data:".json_encode($buyer_loan_repay));
                $return_result['code'] = 2035;
                return $return_result;
            }
            $add_loan_repay_ret = BaseCrudService::getInstance()->add('DealLoanRepay', $buyer_loan_repay);
            if(!$add_loan_repay_ret) {
                Yii::log("debtPreTransaction buyer firstp2p_deal_loan_repay add error data:".json_encode($buyer_loan_repay));
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
            Yii::log("debtPreTransaction collection_values error");
            $return_result['code'] = 2037;
            return $return_result;
        }

        //拼接卖家还款计划并更新
        $collection_values = rtrim($repay_values, ",");
        $status_con = $edit_seller_data['status'] == 15 ? ',status=VALUES(status)' : '';
        $update_repay_sql = "INSERT INTO firstp2p_deal_loan_repay (id, money, status) VALUES $collection_values ON DUPLICATE KEY".
            " UPDATE  money=VALUES(money) $status_con ";
        $seller_w_ret = Yii::app()->db->createCommand($update_repay_sql)->execute();
        if(!$seller_w_ret){
            Yii::log("debtPreTransaction seller firstp2p_deal_loan_repay edit error, sql:$update_repay_sql");
            $return_result['code'] = 2037;
            return $return_result;
        }
        
        //买卖双方待还利息更新
        if(FunctionUtil::float_bigger($buyer_interest, 0, 2)){
            if($_data['debt_status'] != 15){
                $seller_interest = bcsub($borrow_tender['wait_interest'], $buyer_interest, 2);
                if(FunctionUtil::float_bigger(0, $seller_interest, 2)){
                    $seller_interest = 0;
                }
                //卖方更新
                $edit_seller_ret = DealLoad::model()->updateByPk($borrow_tender['id'], ['wait_interest'=>$seller_interest]);
                if($edit_seller_ret == false){
                    Yii::log("debtPreTransaction seller DealLoad edit wait_interest error");
                    $return_result['code'] = 2037;
                    return $return_result;
                }
            }

            //买方更新
            $edit_buyer_ret = DealLoad::model()->updateByPk($return_result['data']['new_tender_id'], ['wait_interest'=>$buyer_interest]);
            if($edit_buyer_ret == false){
                Yii::log("debtPreTransaction buyer DealLoad edit wait_interest error");
                $return_result['code'] = 2037;
                return $return_result;
            }
        }


        //合同类型
        $contract_type = $debt_src == 2 ? 1 : 0;
        //买家债权认购合同生成
        $add_contract_ret = ContractService::getInstance()->addContract($user_id, $contract_type, $tender_ret['id'], $borrow_tender['deal_id'], 1, $platform_no);
        if($add_contract_ret == false){
            Yii::log("debtPreTransaction addContract error, data:($user_id, 0, {$tender_ret['id']}, {$borrow_tender['deal_id']}, 1)");
            $return_result['code'] = 2040;
            return $return_result;
        }
        $return_result['data']['seller_user_id'] = $debt['user_id'];
        $return_result['data']['serial_number'] = $debt['serial_number'];
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
    /**
     * 验证整数或小数二位的正则
     */
    public function checkMoney($money)
    {
        if (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $money)) {
            return true;
        }else {
            return false;
        }
    }

    /**
     * 受让人已收债权监控
     * @param $buyer_uid
     * @return bool
     */
    public function sendAlarm($buyer_uid){
        if(empty($buyer_uid) || !is_numeric($buyer_uid)){
            Yii::log("sendAlarm params error");
            return false;
        }
        $flag_cache_id = "15810571697sms_send_flagdebt";
        if(Yii::app()->rcache->get($flag_cache_id)){
            Yii::log("sendAlarm flag_cache_id:$flag_cache_id already sent");
            return false;
        }

        $max_amount_debt = 10000000;
        //尊享                                                                                                                                                                          
        $log_sql = "select sum(debt_account) as total_debt from firstp2p_debt_exchange_log where status=2 and return_status=0 and buyer_uid={$buyer_uid}  ";
        $zx_capital = Yii::app()->db->createCommand($log_sql)->queryScalar();

        //普惠
        $ph_capital = Yii::app()->phdb->createCommand($log_sql)->queryScalar();

        $total_capital = bcadd($zx_capital, $ph_capital, 2);
        if(empty($total_capital) || $total_capital<$max_amount_debt){
            Yii::log("sendAlarm flag_cache_id:$flag_cache_id no need to send: $total_capital");
            return false;
        }

        //短信报警
        $error_info = "BUYER_UID_ALARM： $total_capital";
        $send_ret = SmsIdentityUtils::fundAlarm($error_info, 'debt');
        Yii::log("sendAlarm flag_cache_id:$flag_cache_id, sms_info:$error_info; return:".print_r($send_ret, true));
        return true;
    }

    /**
     * 校验投资记录是否可发起债转
     * @param $deal_load_id
     * @param int $type 1尊享 2普惠 3金融工场 4智多新
     * @return bool
     */
    public function checkTenderRepay($deal_load_id, $type=1){
        if(empty($deal_load_id) || !is_numeric($deal_load_id) || !in_array($type, Yii::app()->c->xf_config['platform_type'])){
            Yii::log("checkTenderRepay deal_load_id:$deal_load_id params error ");
            return false;
        }

        //库表选择
        $table_prefix = $db_prefix = '';
        if($type == 2){
            $table_prefix = 'PH';
            $db_prefix = 'ph';
        }
        //线下产品
        if(in_array($type, Yii::app()->c->xf_config['offline_products'])){
            $table_prefix = 'Offline';
            $db_prefix = 'offline';
        }
        $deal_load_model = $table_prefix."DealLoad" ;
        $plan_model = $table_prefix."WxRepaymentPlan";
        $db_name = $db_prefix."db";

        //投资记录信息
        $loan_info = $deal_load_model::model()->findByPk($deal_load_id);
        if(!$loan_info){
            Yii::log("checkTenderRepay deal_load_id:$deal_load_id DealLoad error ");
            return false;
        }

        //常规与特殊还款
        $repay_info = $plan_model::model()->findAll("deal_id=$loan_info->deal_id and status in (0,1)");
        if($repay_info){
            foreach ($repay_info as $value){
                //常规还款
                if($value->repay_type == 1 ){
                    Yii::log("checkTenderRepay deal_load_id:$deal_load_id WxRepaymentPlan repay_type=1: id=$value->id error ");
                    return false;
                }

                //特殊还款
                $loan_users = explode(',', $value->loan_user_id);
                $loan_ids = explode(',', $value->deal_loan_id);
                if($value->repay_type == 2 && (in_array($deal_load_id, $loan_ids) || in_array($loan_info->user_id, $loan_users))){
                    Yii::log("checkTenderRepay deal_load_id:$deal_load_id WxRepaymentPlan repay_type=2: id=$value->id error ");
                    return false;
                }
            }
        }

        //仅校验(1:待审核2:审核已通过)，还款状态未还，导入状态成功
        if(in_array($type, Yii::app()->c->xf_config['offline_products'])){
            $sum_repay_money_sql = " select d.id from offline_partial_repay_detail d 
                                  left join offline_partial_repay r on d.partial_repay_id=r.id 
                                  where d.deal_loan_id = {$deal_load_id}  and d.status = 1
                                  and d.repay_status=0 and r.status in (1,2) ";
        }else{
            $sum_repay_money_sql = " select d.id from ag_wx_partial_repay_detail d 
                                  left join ag_wx_partial_repayment r on d.partial_repay_id=r.id 
                                  where d.deal_loan_id = {$deal_load_id}  and d.status = 1
                                  and d.repay_status=0 and r.status in (1,2) ";
        }
        //所有导入成功的还款金额之和
        $part_repay = Yii::app()->$db_name->createCommand($sum_repay_money_sql)->queryRow();
        if($part_repay){
            Yii::log("checkTenderRepay deal_load_id:$deal_load_id AgWxPartialRepayDetail: id=$part_repay->id error ");
            return false;
        }

        return true;
    }

    public function checkFddUserId($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            Yii::log("checkFddUserId user_id:$user_id params error ");
            return false;
        }
        //卖方用户信息
        $user_info = User::model()->findByPk($user_id)->attributes;
        if(empty($user_info)){
            Yii::log("checkFddUserId user_id:$user_id does not exist ");
            return false;
        }

        //是否已注册
        if(!empty($user_info['yj_fdd_customer_id'])){
            Yii::log("checkFddUserId user_id:$user_id yj_fdd_customer_id[{$user_info['yj_fdd_customer_id']}] return true ");
            return true;
        }


        //证件号
        $idno = GibberishAESUtil::dec($user_info['idno'], Yii::app()->c->contract['idno_key']);
        $mobile = GibberishAESUtil::dec($user_info['mobile'], Yii::app()->c->contract['idno_key']);
        $id_type = $this->convertCardType($user_info['id_type']);
        //法大大个人CA申请
        $user_result = FddService::getInstance()->invokeSyncPersonAuto($user_info['real_name'], $user_info['email'], $idno, $id_type, $mobile);
        if(empty($user_result) || !isset($user_result['customer_id'])){
            Yii::log("checkFddUserId user_id:$user_id invokeSyncPersonAuto return false ");
            return false;
        }
        $fdd_customer_id = $user_result['customer_id'];
        $update_sql = "update firstp2p_user set yj_fdd_customer_id = '{$fdd_customer_id}' where id = {$user_id}";
        $edit_fdd = Yii::app()->db->createCommand($update_sql)->execute();
        if(!$edit_fdd){
            Yii::log("checkFddUserId user_id:$user_id edit yj_fdd_customer_id[$fdd_customer_id] return false ");
            return false;
        }

        return true;

    }

    /**
     * 证件类型转化
     */
    public function convertCardType($id_type ){
        //网信：1内陆2护照3军官4港澳6台湾99其他
        //：1-身份证，2-军官证，3-港澳台通行证，4-护照，5-营业执照（企业用户才有），6-外国人永久居留证
        switch ($id_type) {
            case 1: $id_type = 1; break;
            case 2: $id_type = 4; break;
            case 3: $id_type = 2; break;
            case 4: $id_type = 3; break;
            case 5: $id_type = 5; break;
            case 6: $id_type = 3; break;
            case 99: $id_type = 6; break;
            default:$id_type = 1; break;
        }
        return $id_type;
    }


    /**
     * 校验用户是否有承接债权资格
     * @param $user_id
     * @return bool
     */
    public function checkUser($user_id){
        if(empty($user_id) || !is_numeric($user_id)){
            Yii::log("checkUser user_id:$user_id params error ");
            return false;
        }
        //校验用户是否存在
        $user_info = User::model()->findByPk($user_id);
        //校验用户是否存在
        if(empty($user_info)){
            Yii::log("checkUser user_id:$user_id not exist", 'error');
            return false;
        }
        //校验购买者账户状态是否有效
        if (0 == $user_info['is_effect']) {
            Yii::log("checkUser user_id:$user_id, firstp2p_user.is_effect[{$user_info['is_effect']}] = 0", 'error');
            return false;
        }

        //是否在受让人白名单
        if(in_array($user_id, Yii::app()->c->itouzi['debt_buyer_white_list'])){
            Yii::log("checkUser user_id:$user_id in debt_buyer_white_list ");
            return $user_info;
        }

        $minimum_amount_debt = Yii::app()->c->itouzi['minimum_amount_debt'];
        //尊享
        $loan_sql = "select sum(wait_capital) as wait_capital from firstp2p_deal_load where user_id=$user_id and wait_capital>0 ";
        $offline_loan_sql = "select sum(wait_capital) as wait_capital from offline_deal_load where user_id=$user_id and wait_capital>0 ";
        $zx_capital = Yii::app()->db->createCommand($loan_sql)->queryScalar();

        //普惠
        $ph_capital = Yii::app()->phdb->createCommand($loan_sql)->queryScalar();

        //线下产品
        $offline_capital = Yii::app()->offlinedb->createCommand($offline_loan_sql)->queryScalar();

        $total_wait_capital = round($zx_capital+$ph_capital+$offline_capital, 2);
        if(empty($total_wait_capital) || $total_wait_capital<$minimum_amount_debt){
            Yii::log("checkUser user_id:$user_id, total_wait_capital:$total_wait_capital < minimum_amount_debt:$minimum_amount_debt", 'error');
            return false;
        }
        return $user_info;
    }

    /**
     * 获取通用受让人ID
     * @param $debt_money
     * @return bool
     */
    public function getBuyerUid($debt_money){
		if(empty($debt_money) || !is_numeric($debt_money)){
			return false;
		}
        $criteria = new CDbCriteria;
        $criteria->condition = " status=2 and type=1 and buyer_type=1 and (transferability_limit-transferred_amount)>=$debt_money ";
        $criteria->order = " transferred_amount asc ";
        $criteria->limit  = 1;
        $buyer_list = AgWxAssigneeInfo::model()->find($criteria);
        if(false == $buyer_list){
            $error_info = "BUYER_UID_ALARM： User not obtained";
            $send_ret = SmsIdentityUtils::fundAlarm($error_info, 'debt');
            Yii::log("sendAlarm sms_info:$error_info; return:".print_r($send_ret, true));
            return false;
        }

        return $buyer_list->user_id;
    }

    /**
     * 有解债转成功邮件通知债务方
     * @param $data
     * @param $type 1尊享2普惠
     * @return bool
     */
    public function sendDebtMail($data, $type=1){
        if(empty($data) || !is_array($data) || !in_array($type, [1,2]) || empty($data['buyer_name']) || empty($data['contract_no']) ||
            empty($data['seller_name']) || empty($data['debt_account']) || !is_numeric($data['debt_account']) ||
            $data['debt_account']<=0 || empty($data['deal_id']) || !is_numeric($data['deal_id']) || empty($data['seller_uid'])
            || empty($data['buyer_uid']) ||  empty($data['debt_time']) ){
            Yii::log("sendDebtMail params error:type[$type] not in (1,2) or data error: ".print_r($data, true));
            return false;
        }
        $table_prefix = $type == 1 ? '' : 'PH';
        $send_to_mail = array();
        $company_names = '';
        //项目信息查询
        $deal_model = $table_prefix."Deal";
        $deal_info = $deal_model::model()->findByPk($data['deal_id']);
        if(!$deal_info){
            Yii::log("sendDebtMail deal_id:{$data['deal_id']} not find");
            return false;
        }

        //债务方邮箱查询， 普惠消费贷 个体经营贷不通知债务方
        if($type != 2 || !in_array($deal_info->product_class_type, [5, 232])){
            $user_sql = "select u.id,u.user_type,e.company_name,uc.name,u.debt_email 
							from firstp2p_user u 
							left join firstp2p_enterprise e on e.user_id=u.id 
							left join firstp2p_user_company uc on uc.user_id=u.id 
							where u.id={$deal_info['user_id']}";
            $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
            if(empty($user_info)){
                Yii::log("sendDebtMail user_id:{$deal_info->user_id} not find");
                return false;
            }
            //借款企业名称
            $company_name = $user_info['user_type'] == 1 ? $user_info['company_name'] : $user_info['name'];
            //企业收债转通知的邮箱
            if(empty($user_info['debt_email'])){
                Yii::log("sendDebtMail user_id:{$deal_info->user_id} debt_email empty");
                return false;
            }
            $send_to_mail['user_debt_email'] = $user_info['debt_email'];
            $company_names .= "$company_name";
        }

        //咨询方担保方
        $agency_model = $table_prefix."DealAgency";
        $agency_infos = $agency_model::model()->findAll( " id in ($deal_info->advisory_id, $deal_info->agency_id) " );
        if(!$agency_infos || count($agency_infos) != 2){
            Yii::log("sendDebtMail advisory_id:{$deal_info->advisory_id} or agency_id:{$deal_info->agency_id} not find");
            return false;
        }

        foreach ($agency_infos as $agency){
            if(empty($agency->debt_email)){
                Yii::log("sendDebtMail agency_id:{$agency->id} debt_email empty");
                return false;
            }

            if($agency->id == $deal_info->agency_id){
                $send_to_mail['agency_debt_mail'] = $agency->debt_email;
                $company_names .= empty($company_names) ? "$agency->name" : "、{$agency->name}";
                continue;
            }

            if($agency->id == $deal_info->advisory_id){
                $send_to_mail['advisory_debt_mail'] = $agency->debt_email;
                $company_names .= empty($company_names) ? "$agency->name" : "、{$agency->name}";
                continue;
            }
        }

        //咨询方+担保方 邮箱校验
        if(empty($send_to_mail['agency_debt_mail']) || empty($send_to_mail['advisory_debt_mail'])){
            Yii::log("sendDebtMail agency_debt_mail:{$send_to_mail['agency_debt_mail']} empty or advisory_debt_mail:{$send_to_mail['advisory_debt_mail']} empty");
            return false;
        }

        //有解发送邮件
        $MailClass = new MailClass();
        $title = "债转成功通知";
        $content = "尊敬的 {$company_names}:
        根据网信（北京东方联合投资管理有限公司和北京经讯时代科技有限公司）与有解（北京有解科技有限公司）合作协议，网信投资人/出借人 {$data['seller_name']}（{$data['seller_uid']}）于 {$data['debt_time']} 将其持有的 {$deal_info->name} 的本金{$data['debt_account']}债权，通过有解商城平台（www.youjiemall.com），与受让人双方自愿签署了债转协议 {$data['contract_no']}，将其转让给了{$data['buyer_name']}（{$data['buyer_uid']}）。现正式通知债务人，请各方知晓！
        有解商城
        网信";

        $send_to_mail = explode(";", $send_to_mail);
        $result = $MailClass->yjSend($send_to_mail,$title, $content);
        if(!$result){
            Yii::log("sendDebtMail send return false,send_mail:".print_r($send_to_mail, true));
            return false;
        }

        return true;
    }

    /**
     * 指定邮箱发送邮件
     * @param $data
     * @param $type 1尊享2普惠
     * @return bool
     */
    public function sendDebtMailNew($data, $type=1){
        if(empty($data) || !is_array($data) || !in_array($type, [1,2]) || empty($data['buyer_name']) || empty($data['contract_no']) ||
            empty($data['seller_name']) || empty($data['debt_account']) || !is_numeric($data['debt_account']) ||
            $data['debt_account']<=0 || empty($data['deal_id']) || !is_numeric($data['deal_id']) || empty($data['seller_uid'])
            || empty($data['buyer_uid']) ||  empty($data['debt_time']) || empty($data['email_address'])){
            Yii::log("sendDebtMailNew params error:type[$type] not in (1,2) or data error: ".print_r($data, true));
            return false;
        }
        $table_prefix = $type == 1 ? '' : 'PH';
        $company_names = '';
        //项目信息查询
        $deal_model = $table_prefix."Deal";
        $deal_info = $deal_model::model()->findByPk($data['deal_id']);
        if(!$deal_info){
            Yii::log("sendDebtMailNew deal_id:{$data['deal_id']} not find");
            return false;
        }

        //债务方邮箱查询， 普惠消费贷 个体经营贷不通知债务方
        if($type != 2 || !in_array($deal_info->product_class_type, [5, 232])){
            $user_sql = "select u.id,u.user_type,e.company_name,uc.name,u.debt_email 
							from firstp2p_user u 
							left join firstp2p_enterprise e on e.user_id=u.id 
							left join firstp2p_user_company uc on uc.user_id=u.id 
							where u.id={$deal_info['user_id']}";
            $user_info = Yii::app()->db->createCommand($user_sql)->queryRow();
            if(empty($user_info)){
                Yii::log("sendDebtMailNew user_id:{$deal_info->user_id} not find");
                return false;
            }
            //借款企业名称
            $company_name = $user_info['user_type'] == 1 ? $user_info['company_name'] : $user_info['name'];
            $company_names .= "$company_name";
        }

        //咨询方担保方
        $agency_model = $table_prefix."DealAgency";
        $agency_infos = $agency_model::model()->findAll( " id in ($deal_info->advisory_id, $deal_info->agency_id) " );
        if(!$agency_infos || count($agency_infos) != 2){
            Yii::log("sendDebtMailNew advisory_id:{$deal_info->advisory_id} or agency_id:{$deal_info->agency_id} not find");
            return false;
        }

        foreach ($agency_infos as $agency){
            if($agency->id == $deal_info->agency_id){
                $company_names .= empty($company_names) ? "$agency->name" : "、{$agency->name}";
                continue;
            }

            if($agency->id == $deal_info->advisory_id){
                $company_names .= empty($company_names) ? "$agency->name" : "、{$agency->name}";
                continue;
            }
        }


        //有解发送邮件
        $MailClass = new MailClass();
        $title = "债转成功通知";
        $content = "尊敬的 {$company_names}: 
        根据网信（北京东方联合投资管理有限公司和北京经讯时代科技有限公司）与有解（北京有解科技有限公司）合作协议，网信投资人/出借人 {$data['seller_name']}（{$data['seller_uid']}）于 {$data['debt_time']} 将其持有的 {$deal_info->name} 的本金{$data['debt_account']}债权，通过有解商城平台（www.youjiemall.com），与受让人双方自愿签署了债转协议 {$data['contract_no']}，将其转让给了{$data['buyer_name']}（{$data['buyer_uid']}）。现正式通知债务人，请各方知晓！
        有解商城
        网信";
        $result = $MailClass->yjSend($data['email_address'],$title, $content);
        if($result['code'] != 0){
            Yii::log("sendDebtMailNew send return code:[{$result['code']}], send_mail: $content, {$data['email_address']}");
            return false;
        }

        return true;
    }


    public function getShopName($id){
        $shop_name = '有解';
        if(empty($id) || !is_numeric($id)){
            return $shop_name;
        }

        $shop_info = XfDebtExchangePlatform::model()->findByPk($id);
        if($shop_info){
            $shop_name = $shop_info->name;
        }
        return $shop_name;
    }


    //汇源专区确认出售
    public function confirmSale($info){
        //返回数据
        $return_result = array(
            'code' => 0,
            'info' => '',
            'data' => array()
        );

        $deal_load_ids = explode(',', $info['deal_load_id'] );
        $user_id = $info['user_id'];
        $purchase_id = $info['purchase_id'];
        $transaction_password = $info['transaction_password'];
        $bankcard_id = $info['bankcard_id'];
        $sms_code = "confirm_sale_b";
        //出售投资记录ID
        if (!is_array($deal_load_ids) || empty($deal_load_ids)) {
            $return_result['code'] = 7004;
            return $return_result;
        }
        //出售用户ID
        if (empty($user_id) || !is_numeric($user_id)) {
            $return_result['code'] = 2057;
            return $return_result;
        }
        $userInfo = Yii::app()->db->createCommand("select * from firstp2p_user where id = $user_id")->queryRow();
        if(!$userInfo){
            $return_result['code'] = 2026;
            return $return_result;
        }

        //求购记录ID
        if (empty($purchase_id) || !is_numeric($purchase_id)) {
            $return_result['code'] = 7002;
            return $return_result;
        }
        //交易密码校验
        $checkInfo = DebtGardenYoujieQuestionService::getInstance()->checkPassWord($user_id, $transaction_password);
        if($checkInfo['code'] != 0){
            $return_result['code'] = $checkInfo['code'];
            return $return_result;
        }

        //银行卡信息
        $bankCardInfo = Yii::app()->db->createCommand("select ub.id,ub.bankzone,ub.bankcard,ub.card_name,b.name from firstp2p_user_bankcard ub left join firstp2p_bank b on b.id = ub.bank_id where ub.id = $bankcard_id and verify_status = 1")->queryRow();
        if (empty($bankCardInfo)) {
            $return_result['code'] = 3008;
            return $return_result;
        }
        if (empty($bankCardInfo['name'])) {
            $return_result['code'] = 2212;
            return $return_result;
        }

        //开启事务
        Yii::app()->phdb->beginTransaction();
        try{
            // 获取债权信息数组
            $purchase_info = XfPlanPurchase::model()->findBySql("select * from xf_plan_purchase where id=:id for update", array(':id' => $purchase_id));
            if(!$purchase_info || empty($purchase_info->user_id)){
                Yii::log("confirmSale user_id:$user_id, purchase_id:$purchase_id; xf_plan_purchase.id[{$purchase_id}] not exist", 'error');
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7005;
                return $return_result;
            }
            //校验状态是否求购中
            if($purchase_info->status != 1){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7006;
                return $return_result;
            }
            //校验是否过期
            if($purchase_info->endtime < time()){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7016;
                return $return_result;
            }
            //受让人状态校验
            $assignee_info = XfDebtAssigneeInfo::model()->findBySql("select * from xf_debt_assignee_info where user_id=$purchase_info->user_id  and area_id=$purchase_info->area_id and status=2 for update ");
            if(!$assignee_info ){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7007;
                return $return_result;
            }
            //校验受让金额
            $deal_loan_sql = "select sum(wait_capital) as t_wait_capital from firstp2p_deal_load dl left join firstp2p_deal deal on dl.deal_id = deal.id where dl.black_status = 1 and dl.status = 1 and dl.id in ({$info['deal_load_id']}) and deal.product_class_type = 223";
            $t_wait_capital = Yii::app()->phdb->createCommand($deal_loan_sql)->queryScalar();
            if(!$t_wait_capital){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7008;
                return $return_result;
            }

            //受让人可受让金额
            $assignee_amount = bcsub($assignee_info->transferability_limit, $assignee_info->transferred_amount+$assignee_info->trading_amount, 2);
            //求购记录待求购金额
            $purchase_amount = bcsub($purchase_info->total_amount, $purchase_info->purchased_amount+$purchase_info->trading_amount, 2);
            //求购完成短信模板变更
            if(FunctionUtil::float_equal($t_wait_capital, $purchase_amount, 2)){
                $sms_code = "confirm_sale_b_01";
            }
            //校验收购可受让金额及
            if(FunctionUtil::float_bigger($t_wait_capital, $purchase_amount, 2)){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7009;
                return $return_result;
            }
            if(FunctionUtil::float_bigger($t_wait_capital, $assignee_amount, 2)){
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7010;
                return $return_result;
            }

            //逐一出售(发布债转+承接)
            $sold_money = 0;
            foreach ($deal_load_ids as $deal_load_id){
                $deal_load_info = PHDealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array(':id' => $deal_load_id));
                if($deal_load_info->area_id != $purchase_info->area_id){
                    Yii::app()->phdb->rollback();
                    $return_result['code'] = 7011;
                    return $return_result;
                }

                //发布债权
                $c_data = [
                    'user_id' => $deal_load_info->user_id,
                    'money' => $deal_load_info->wait_capital,
                    'discount' => $purchase_info->discount,
                    'deal_loan_id' => $deal_load_id,
                    'debt_src' => 2,
                    'is_orient' => 2,
                    'effect_days' => 10,
                    'payee_name' => $userInfo['real_name'] == $bankCardInfo['card_name'] ? $userInfo['real_name'] : $bankCardInfo['card_name'],
                    'payee_bankzone' => $bankCardInfo['name'],
                    'payee_bankcard' => $bankCardInfo['bankcard'],
                    'purchase_id' => $purchase_id
                ];
                $create_ret = PhDebtService::getInstance()->createDebt($c_data);
                if ($create_ret === false || $create_ret['code'] != 0 || empty($create_ret['data'])) {
                    Yii::log("confirmSale createDebt deal_loan_id {$deal_load_id} false:".print_r($create_ret,true), 'error');
                    Yii::app()->phdb->rollback();
                    return $create_ret;
                }

                //创建成功日志
                Yii::log("confirmSale createDebt deal_loan_id {$deal_load_id} success");

                //债权承接
                $params = [
                    'user_id' => $purchase_info->user_id,
                    'money' => $deal_load_info->wait_capital,
                    'debt_id' => $create_ret['data']['debt_id'],
                    'products' => 2,
                ];
                $undertake_ret = DebtService::getInstance()->undertakeDebt($params);
                if ($undertake_ret['code'] != 0) {
                    Yii::log("confirmSale undertakeDebt deal_loan_id {$deal_load_id} false:".print_r($create_ret,true), 'error');
                    Yii::app()->phdb->rollback();
                    return $undertake_ret;
                }

                $sold_money = bcadd($sold_money, $deal_load_info->wait_capital, 4);
            }

            //批量出售债权必须全部成功，否则全部回滚
            if(!FunctionUtil::float_equal($sold_money, $t_wait_capital, 2)){
                Yii::log("confirmSale purchase_id:$purchase_id; sold_money[$sold_money] != t_wait_capital[$t_wait_capital] ", 'error');
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7012;
                return $return_result;
            }

            //求购记录数据更新
            $edit_purchase = [];
            $edit_purchase['id'] = $purchase_info->id;
            $edit_purchase['trading_amount'] = bcadd($purchase_info->trading_amount, $t_wait_capital, 2);
            $edit_purchase['trading_num'] = $purchase_info->trading_num+count($deal_load_ids);
            $purchase_money = bcadd($edit_purchase['trading_amount'], $purchase_info->purchased_amount, 2);
            $edit_purchase['scale'] = round($purchase_money/$purchase_info->total_amount, 2);
            if(FunctionUtil::float_equal($edit_purchase['scale'], 1, 2)){
                $edit_purchase['status'] = 2;
            }
            $changeLogRet = BaseCrudService::getInstance()->update("XfPlanPurchase", $edit_purchase, "id");
            if(!$changeLogRet){
                $this->echoLog("confirmSale XfPlanPurchase update error, id=$purchase_info->id");
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7013;
                return $return_result;
            }

            //受让人数据更更新
            $assignee_info->trading_amount = bcadd($assignee_info->trading_amount, $t_wait_capital, 2);
            if($assignee_info->save(false, array('trading_amount')) == false){
                $this->echoLog("confirmSale update xf_debt_assignee_info trading_amount[$assignee_info->trading_amount] error, id=$assignee_info->id");
                Yii::app()->phdb->rollback();
                $return_result['code'] = 7014;
                return $return_result;
            }

            //发送卖方短信通知
            $remind = array();
            $remind['sms_code'] = "confirm_sale_s";
            $remind['mobile'] = $this->getPhone($user_id);
            $remind['data']['num'] = count($deal_load_ids);
            $smaClass = new XfSmsClass();
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("confirmSale confirm_sale_s user_id:$user_id ; sendToUser seller error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }

            //发送买方短信通知
            $remind = array();
            $remind['sms_code'] = $sms_code;
            $remind['mobile'] = $this->getPhone($purchase_info->user_id);
            $smaClass = new XfSmsClass();
            $send_ret = $smaClass->sendToUserByPhone($remind);
            if($send_ret['code'] != 0){
                Yii::log("confirmSale $sms_code user_id:$purchase_info->user_id ; sendToUser buyer error:".print_r($remind, true)."; return:".print_r($send_ret, true), "error");
            }

            Yii::app()->phdb->commit();
            $return_result['code'] = 0;
            return $return_result;
        }catch (Exception $ee) {
            Yii::log("confirmSale Exception user_id:$user_id; exception:".print_r($ee->getMessage(), true));
            Yii::app()->phdb->rollback();
            $return_result['code'] = 5000;
            return $return_result;
        }
    }

}
