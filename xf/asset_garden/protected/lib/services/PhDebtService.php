<?php

class PhDebtService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 5; //最低起投金额
    public $borrow_type = [0];

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
            Yii::log("createDebt  user_id=[{$data['user_id']}] User error", 'error');
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
        $debt_src = $data['debt_src'];//6专属收购 7置换债权
        $purchase_id = $data['purchase_id'] ?: 0;
        $exclusive_purchase_id = $data['exclusive_purchase_id'] ?: 0;
        if(!in_array($debt_src, [1,2,3,4,6,7]) || empty($tender_id)|| empty($money) || !is_numeric($money)){
            Yii::log("createDebt tender_id[$tender_id]: params error:" .json_encode($data), 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }

        //权益兑换与债权划扣折扣金额必须为0 收购
        if(in_array($debt_src, [1,3,4,6,7]) && !FunctionUtil::float_equal($discount, 0, 2)){
            Yii::log("createDebt tender_id[$tender_id]: debt_src in (1,3,4,6)  discount[$discount]!=0", 'error');
            $return_result['code'] = 2042;
            return $return_result;
        }


        //权益兑换商城来源校验
        if(in_array($debt_src, [1,4,5]) && !in_array( $data['platform_no'], [1,2,4,5,6])){
            Yii::log("createDebt tender_id[$tender_id]: debt_src in (1,4,5)  platform_no[{$data['platform_no']}] not in (1,2,4,5)", 'error');
            $return_result['code'] = 2056;
            return $return_result;
        }


        //自主债转交易
        if($debt_src == 2){
            //折扣必须在4~10
            if($purchase_id == 0 && (bccomp($discount , 0.1) === -1 || bccomp($discount , 10) === 1)){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2,  discount[$discount] error ", 'error');
                $return_result['code'] = 7019;
                return $return_result;
            }
            if($purchase_id > 0 && (bccomp($discount , 0.01) === -1 || bccomp($discount , 10) === 1)){
                Yii::log("createDebt tender_id[$tender_id]: debt_src=2,  discount[$discount] error ", 'error');
                $return_result['code'] = 7015;
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
                $return_result['code'] = 2100;
                return $return_result;
            }

            //是否有兑换中数据
            $exchange_sql = "select id from firstp2p_debt_exchange_log where tender_id=$tender_id and status=1 ";
            $exchange_info = Yii::app()->phdb->createCommand($exchange_sql)->queryRow();
            if($exchange_info){
                Yii::log("createDebt tender_id[$tender_id]: change_log status=1  error ");
                $return_result['code'] = 5006;
                return $return_result;
            }

            //过期时间 精确到秒
            $expected_end_time = strtotime("now +{$data['effect_days']} days");;
            $buy_code = $data['is_orient'] == 1 ? rand(1000, 9999) : '';
        }
        
        //投资记录信息
        $borrow_tender = PHDealLoad::model()->findBySql("select * from firstp2p_deal_load where id=:id for update", array(':id' => $tender_id));
        if(!$borrow_tender || $borrow_tender->debt_status != 0 ) {
            Yii::log("createDebt tender_id[$tender_id]: debt_status !=0 ", 'error');
            $return_result['code'] = 2003;
            return $return_result;
        }

        //收购债权禁止债转
        if($debt_src != 6 && $borrow_tender->exclusive_purchase_id > 0){
            Yii::log("createDebt tender_id[$tender_id]: exclusive_purchase_id[$borrow_tender->exclusive_purchase_id] > 0 ", 'error');
            $return_result['code'] = 3010;
            return $return_result;
        }

        //置换债权禁止债转
        if($debt_src != 7 && $borrow_tender->displace_id > 0){
            Yii::log("createDebt tender_id[$tender_id]: displace_id[$borrow_tender->displace_id] > 0 ", 'error');
            $return_result['code'] = 3035;
            return $return_result;
        }

        //置换债权禁止债转
        if($debt_src == 7 && $borrow_tender->displace_id == 0){
            Yii::log("createDebt tender_id[$tender_id]: displace_id[$borrow_tender->displace_id] = 0 ", 'error');
            $return_result['code'] = 3036;
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


        //确权状态
        /*
        if($borrow_tender->is_debt_confirm != 1 && in_array($debt_src,[1,2])){
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
            Yii::log("createDebt tender_id[$tender_id]:  debt_account:$money<=0 ", 'error');
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
        $disable_ph = AgWxDebtBlackList::model()->find("deal_id=$borrow_tender->deal_id and status=1 and type=2");
        if($disable_ph){
            Yii::log("createDebt tender_id[$tender_id]: deal_id[$borrow_tender->deal_id] in disable_ph error ", 'error');
            $return_result['code'] = 2301;
            return $return_result;
        }

        //待处理线下还款校验
        if(DebtService::getInstance()->checkTenderRepay($tender_id, 2) == false){
            Yii::log("createDebt tender_id[$tender_id]: checkTenderRepay return false ", 'error');
            $return_result['code'] = 5003;
            return $return_result;
        }

        /*
        //个人CA证书申请校验
        if(DebtService::getInstance()->checkFddUserId($user_id) == false){
            Yii::log("createDebt tender_id[$tender_id]: checkFddUserId[$user_id] return false ", 'error');
            $return_result['code'] = 5004;
            return $return_result;
        }*/
        //还款计划表待还本金
        $repay_sql = "select sum(money) as repay_wait_capital from firstp2p_deal_loan_repay where status=0 and deal_loan_id=$borrow_tender->id and type=1 and money>0";
        $repay_wait_capital = Yii::app()->phdb->createCommand($repay_sql)->queryScalar();
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
        $borrow = PHDeal::model()->findByPk($borrow_tender->deal_id);
        //有效子表，普通类型还款中
        if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || $borrow->deal_status != 4 ){
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_deal[$borrow_tender->deal_id] error", 'error');
            $return_result['code'] = 2010;
            return $return_result;
        }

        //求购专区债转需要校验专区信息
        if($purchase_id > 0 && is_numeric($purchase_id)){
            //$area_ids = array_keys(Yii::app()->c->xf_config['area_list']);
            $deal_ids = DebtGardenYoujieQuestionService::getInstance()->getPurchaseDeal($purchase_id);
            if(empty($deal_ids) || !in_array($borrow_tender->deal_id, $deal_ids)){
                Yii::log("createDebt purchase_id[$purchase_id] error", 'error');
                $return_result['code'] = 7005;
                return $return_result;
            }
        }


        //禁止智多鑫项目债转
        $zdx_check = PHDealTag::model()->find("deal_id=$borrow_tender->deal_id");
        if($zdx_check && in_array($zdx_check->tag_id, [42,44])){
            Yii::log("createDebt tender_id[$tender_id]: PHDealTag[tag_id=$borrow_tender->tag_id] error ", 'error');
            $return_result['code'] = 2038;
            return $return_result;
        }

        //禁止债转的咨询方债权
        $deal_agency = PHDealAgency::model()->findByPk($borrow->advisory_id);
        $agency_name = Yii::app()->c->contract['agency_name'];
        if($data['platform_no'] != 4 && $deal_agency && in_array($deal_agency->name, $agency_name)){
            Yii::log("createDebt tender_id[$tender_id]: advisory_id[$deal_agency->name] in agency_name ", 'error');
            $return_result['code'] = 5007;
            return $return_result;
        }

        ///*放开悠融项目类型限制限制
        if( $data['platform_no'] != 4 && in_array($debt_src, [1,2]) && ($deal_agency->name != '悠融资产管理（上海）有限公司' && $borrow->product_class_type != 223)){
            Yii::log("createDebt tender_id[$tender_id]: advisory_id[$deal_agency->name] in agency_name ", 'error');
            $return_result['code'] = 5007;
            return $return_result;
        }

        //查投资记录是否重复转让
        $SucNum = PHDebt::model()->count("user_id=:user_id and tender_id={$tender_id} and status=1", array(':user_id'=>$user_id));
        if ($SucNum > 0) {
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_debt.tender_id[{$tender_id}] repeat request ", 'error');
            $return_result['code'] = 2011;
            return $return_result;
        }

        //还款日限制【每日下午16点】
        /*
        $repay_time = strtotime('midnight') + 57600;
        $today_repay = PHDealLoanRepay::model()->find( "status=0 and deal_loan_id=$borrow_tender->id and `time`=$repay_time and money>0");
        if($today_repay){
            Yii::log("createDebt tender_id[$tender_id]: firstp2p_deal_loan_repay repay_time error!!!", 'error');
            $return_result['code'] = 2039;
            return $return_result;
        }*/

        //firstp2p_debt表数据组成
        $area_id = $borrow->area_id;
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
        $debt['debt_src'] = ($debt_src==6) ? 2 : $debt_src ;
        $debt['addtime'] = $time;
        $debt['purchase_id'] = $purchase_id;
        $debt['exclusive_purchase_id'] = $exclusive_purchase_id;
        $debt['displace_id'] = $borrow_tender->displace_id;;
        $debt['area_id'] = $area_id;
        $debt['addip'] = FunctionUtil::ip_address();
        $debt['serial_number'] = FunctionUtil::getAgRequestNo('PHDT');
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

        $ret = BaseCrudService::getInstance()->add('PHDebt', $debt);
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
        $debt = PHDebt::model()->findBySql("select * from firstp2p_debt where id=:id for update", array('id' => $debt_id))->attributes;
        if(!$debt){
            Yii::log("debtPreTransaction  step04 {$debt_data['user_id']} firstp2p_debt.id[{$debt_id}] not exist", 'error');
            $return_result['code'] = 2017;
            return $return_result;
        }

        $debt_src = $debt['debt_src'];
        //权益兑换与债权划扣交易时，债转状态必须为转让中
        if((in_array($debt_src, [1,3,4]) || ($debt_src == 2 && $debt['exclusive_purchase_id']>0) || ($debt_src == 7 && $debt['displace_id']>0)) && $debt['status'] != 1){
            Yii::log("debtPreTransaction  step28 {$debt_data['user_id']} code=2221", 'error');
            $return_result['code'] = 2107;
            return $return_result;
        }

        //债转交易确认时，债转状态必须为待确认
        if($debt_src == 2 && $debt['status'] != 6 && $debt['exclusive_purchase_id'] == 0){
            Yii::log("debtPreTransaction  step28 {$debt_data['user_id']} code=2221", 'error');
            $return_result['code'] = 2108;
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
        $borrow = PHDeal::model()->findByPk($debt['borrow_id']);
        //有效子标，普通类型还款中
        if(!$borrow || $borrow->is_effect != 1 || $borrow->parent_id == 0 || $borrow->deal_status != 4){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, firstp2p_deal[{$debt['borrow_id']}] error", 'error');
            $return_result['code'] = 2028;
            return $return_result;
        }

        //禁止指定项目债转
        $disable_ph = AgWxDebtBlackList::model()->find("deal_id={$debt['borrow_id']} and status=1 and type=2");
        if($disable_ph){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, deal_id[{$debt['borrow_id']}] in disable_ph error ", 'error');
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
        if($borrow_tender->displace_id != $debt['displace_id']){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, firstp2p_deal_load.displace_id error", "error");
            $return_result['code'] = 3037;
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
        $edit_load = BaseCrudService::getInstance()->update('PHDealLoad', $_data,'id');
        if(!$edit_load){
            Yii::log('debtPreTransaction DealLoad edit error, data:'.  print_r($_data,true));
            return false;
        }

        //wx特殊数据处理
        if($_data['debt_status'] == 15){
            //全部债权完成时，卖家还款计划里的待还本金为0的特殊数据更新为已债转
            $special_capital = PHDealLoanRepay::model()->find("deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1");
            if($special_capital){
                $update_repay_sql = "update firstp2p_deal_loan_repay set status=15 where deal_loan_id = {$_data['id']} and status=0 and money=0 and type=1 ";
                $r_edit_ret = Yii::app()->phdb->createCommand($update_repay_sql)->execute();
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
        $tender['area_id'] = $debt['area_id'];
        $tender['purchase_id'] = $debt['purchase_id'];
        $tender['from_displace_id'] = $debt['displace_id'];
        $tender['province_name'] = $borrow_tender['province_name'];
        $tender['card_address'] = $borrow_tender['card_address'];
        $tender['product_class'] = $borrow_tender['product_class'];
        $tender['contract_path'] = $debt_data['oss_contract_url'];
        $tender_ret = BaseCrudService::getInstance()->add('PHDealLoad', $tender);
        if(!$tender_ret){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, Buyers DealLoad add error :". print_r($tender, true), "error");
            $return_result['code'] = 2031;
            return $return_result;
        }

        //新生成投资记录ID
        $return_result['data']['new_tender_id'] = $tender_ret['id'];
        //权益兑换与债权划扣
        if((in_array($debt_src, [1,3,4,7])) || ($debt_src == 2 && $debt['exclusive_purchase_id']>0)){
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
            $data['exclusive_purchase_id'] = $debt['exclusive_purchase_id'];
            $debt_tender = BaseCrudService::getInstance()->add('PHDebtTender', $data);//添加债权tender
            if(!$debt_tender){
                Yii::log("debtPreTransaction {$debt_data['user_id']}, DebtTender add error :". print_r($data, true), "error");
                $return_result['code'] = 2032;
                return $return_result;
            }
        }

        //买家还款计划生成
        $loan_repay_sql = "select * from firstp2p_deal_loan_repay where deal_loan_id ={$debt['tender_id']} and status=0 and type in (1, 2) and money>0 ";
        $seller_loan_repay = Yii::app()->phdb->createCommand($loan_repay_sql)->queryAll();
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
        $buyer_interest = 0;
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
            $add_loan_repay_ret = BaseCrudService::getInstance()->add('PHDealLoanRepay', $buyer_loan_repay);
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
        $seller_w_ret = Yii::app()->phdb->createCommand($update_repay_sql)->execute();
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
                $edit_seller_ret = PHDealLoad::model()->updateByPk($borrow_tender['id'], ['wait_interest'=>$seller_interest]);
                if($edit_seller_ret == false){
                    Yii::log("debtPreTransaction seller PHDealLoad edit wait_interest error");
                    $return_result['code'] = 2037;
                    return $return_result;
                }
            }

            //买方更新
            $edit_buyer_ret = PHDealLoad::model()->updateByPk($return_result['data']['new_tender_id'], ['wait_interest'=>$buyer_interest]);
            if($edit_buyer_ret == false){
                Yii::log("debtPreTransaction buyer PHDealLoad edit wait_interest error");
                $return_result['code'] = 2037;
                return $return_result;
            }
        }

        //收购计划不需要生成合同
        if($debt['exclusive_purchase_id'] == 0 && $debt['displace_id'] == 0){
            //合同类型
            $contract_type = $debt_src == 2 ? 1 : 0;
            //买家债权认购合同生成
            $add_contract_ret = ContractService::getInstance()->addContract($user_id, $contract_type, $tender_ret['id'], $borrow_tender['deal_id'], 2, $platform_no);
            if($add_contract_ret == false){
                Yii::log("debtPreTransaction addContract error, data:($user_id, 0, {$tender_ret['id']}, {$borrow_tender['deal_id']}, 2)");
                $return_result['code'] = 2040;
                return $return_result;
            }
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
}
