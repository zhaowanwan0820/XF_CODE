<?php

/**
 * 债转系统
 * @date 2019-10-28
 * Class AgDebtService
 */
class AgDebtService extends ItzInstanceService
{
    const MIN_LOAN_AMOUNT = 100; //最低起投金额

    /**
     * 发起债转
     * @param array $data [
     * user_id 发起方用户ID
     * money 发起债转金额
     * debt_src 债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
     * purchase_order_id 求购计划ID debt_src=3时必传
     * discount 折扣金额debt_src=1(discount=0);debt_src=2(discount:0.01~10);debt_src=3非必传
     * effect_days 有效天数 10,20,30
     * tender_id 发起债转投资记录ID
     * ]
     * @return array
     */
    public function  createDebt($data){
        //返回数据
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
        $discount = $data['discount'];
        $debt_src = $data['debt_src'];//债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
        $discount_money = 0;//折让金
        $time = time();
        $effect_days = $data['effect_days'];
        $tender_id = $data['tender_id'];
        if(empty($effect_days) || empty($tender_id)|| empty($money) || !is_numeric($money) || !in_array($debt_src, [1,2,3])){
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
        $borrow_tender = AgTender::model()->findBySql("select * from ag_tender where id=:id for update", array(':id' => $tender_id));
        if(!$borrow_tender || $borrow_tender->status != 1 || $borrow_tender->debt_status != 0 ) {
            Yii::log("handelDebt end tender_id:$tender_id; status[{$borrow_tender->status}] or debt_status[{$borrow_tender->debt_status}] error ", 'error');
            $return_result['code'] = 2003;
            return $return_result;
        }

        //用户信息校验
        if($user_id != $borrow_tender->user_id){
            Yii::log("handelDebt end tender_id:$tender_id; user_id[$user_id] != tender_user[$borrow_tender->user_id] ", 'error');
            $return_result['code'] = 2041;
            return $return_result;
        }
        //兑换金额必须大于0
        if(FunctionUtil::float_bigger_equal(0, $money, 2) ){
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
        //兑换后剩余本金
        $s_money = bcsub($wait_capital, $money, 2);
        //剩余本金必须大于或等于O
        if(FunctionUtil::float_bigger(0, $s_money, 2)){
            Yii::log("handelDebt end tender_id:$tender_id, s_money:$s_money<0 ", 'error');
            $return_result['code'] = 2008;
            return $return_result;
        }
        //当剩余金额大于0时
        if(FunctionUtil::float_bigger($s_money, 0, 2)){
            //剩余金额必须大于起投金额
            if(FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $s_money, 2)){
                Yii::log("handelDebt end tender_id:$tender_id, s_money:$s_money < ".self::MIN_LOAN_AMOUNT.", debt_account:$money, wait_capital:$wait_capital", 'error');
                $return_result['code'] = 2009;
                return $return_result;
            }
            //非最后一笔债转，交易金额必须大于起投金额 [兼容历史数据在投金额小于起投情况]
            if(FunctionUtil::float_bigger(self::MIN_LOAN_AMOUNT, $money, 2)){
                Yii::log("handelDebt end tender_id:$tender_id, debt_account:$money < ".self::MIN_LOAN_AMOUNT.", wait_capital:$wait_capital", 'error');
                $return_result['code'] = 2044;
                return $return_result;
            }
        }

        //非权益兑换时，折扣金必须区间0.01~10
        if(in_array($debt_src, [2,3]) && (FunctionUtil::float_bigger_equal(0, $discount, 2) || FunctionUtil::float_bigger($discount, 10, 2))){
            Yii::log("createDebt is_from_shop=2, discount[$discount] error  ", 'error');
            $return_result['code'] = 2043;
            return $return_result;
        }
        //商城权益兑换，折扣金额必须为0
        if($debt_src == 1 && !FunctionUtil::float_equal($discount, 0, 2)){
            Yii::log("createDebt is_from_shop=1, discount[$discount] error  ", 'error');
            $return_result['code'] = 2042;
            return $return_result;
        }
        //求购计划来源债权，需校验求购计划信息
        if($debt_src == 3 ){
            $check_pur_ret = $this->checkPurchaseOrder($data['purchase_order_id'], $borrow_tender, $money);
            if($check_pur_ret['code'] != 0){
                Yii::log("checkPurchaseOrder return code:[{$check_pur_ret['code']}] ", 'error');
                return $check_pur_ret;
            }
            //求购详情
            $order_info = $check_pur_ret['data'];
            $discount = $order_info->discount;
        }
        //平台信息校验
        $check_p = $this->checkPlatform($borrow_tender->platform_id);
        if($check_p['code'] != 0){
            $return_result['code'] = $check_p['code'];
            return $return_result;
        }
        //项目信息校验
        $borrow = AgProject::model()->findByPk($borrow_tender->project_id);
        if(!$borrow){
            Yii::log("handelDebt end tender_id:$tender_id, ag_project[$borrow_tender->project_id] error", 'error');
            $return_result['code'] = 2010;
            return $return_result;
        }
        //校验用户是否存在
        $user_info = AgUser::model()->findByPk($user_id);
        //校验用户是否存在
        if(empty($user_info)){
            Yii::log("handelDebt end tender_id:$tender_id, user_id[{$user_id}] not exist", 'error');
            $return_result['code'] = 2026;
            return $return_result;
        }
        //协议验证
        $checkUserPlatForm = PurchService::getInstance()->transformationUserid($user_id, $borrow_tender->platform_id);
        if($checkUserPlatForm['code'] != 0){
            $return_result['code'] = $checkUserPlatForm['code'];
            return $return_result;
        }
        //查投资记录是否重复转让
        $SucNum = AgDebt::model()->count("user_id=:user_id and tender_id={$tender_id} and status=1", array(':user_id'=>$user_id));
        if ($SucNum > 0) {
            Yii::log("createDebt step07 ag_debt.tender_id[{$tender_id}] repeat request ", 'error');
            $return_result['code'] = 2011;
            return $return_result;
        }

        //计算债权过期时间
        $expected_end_time = strtotime("now +{$effect_days} days");;
        //ag_debt表数据组成
        $debt['user_id'] = $user_id;
        $debt['tender_id'] = $tender_id;
        $debt['project_id'] = $borrow_tender->project_id;
        $debt['project_type_id'] = $borrow->type_id;
        $debt['platform_id'] = $borrow_tender->platform_id;
        $debt['amount'] = $money;
        $debt['sold_amount'] = 0;
        $debt['discount'] = $discount;
        $debt['start_time'] = $time;
        $debt['end_time'] = $expected_end_time;
        $debt['success_time'] = 0;
        $debt['apr'] = $borrow->apr;
        $debt['status'] = 1;
        $debt['purchase_order_id'] = $data['purchase_order_id'];
        $debt['serial_number'] = FunctionUtil::getAgRequestNo('DEBT');
        $debt['debt_src'] = $debt_src;
        $debt['addtime'] = $time;
        $debt['addip'] = FunctionUtil::ip_address();
        $ret = BaseCrudService::getInstance()->add('AgDebt', $debt);
        if(false == $ret){//添加失败
            Yii::log("createDebt  step08 tender_id=[{$tender_id}] AddDebt error ", 'error');
            $return_result['code'] = 2012;
            return $return_result;
        }

        //投资记录债权状态变更
        $borrow_tender->debt_status = 1;
        if($borrow_tender->save(false, array('debt_status')) == false){
            Yii::log("createDebt step12 tender_id=[{$tender_id}] update ag_tender.debt_status fail ", 'error');
            $return_result['code'] = 2013;
            return $return_result;
        }
        $return_result['code'] = 0;
        $return_result['data']['debt_id'] = $ret['id'];
        return $return_result;
    }
    /**
     * 资方认购债权验证
     * @param array $debt_data [
     * money 认购金额
     * user_id 买方用户ID
     * debt_id 被认购债权ID
     * utype 1:资方用户认购 2：C1用户认购
     * debt_src债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
     * ]
     * @return array
     */
    private function AmcTransferBuyRule($debt_data)
    {
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
        if(!in_array($debt_data['debt_src'], [1,2,3]) || $this->emp($debt_data['debt_id']) || $this->emp($debt_data['money']) || !is_numeric($debt_data['debt_id'])) {
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
        $debt_src = $debt_data['debt_src'];
        $utype = $debt_data['utype'];
        //验证C1用户是否为在存用户
        if($utype == 2){
            $ag_tender = Yii::app()->agdb->createCommand("select count(*) as num from ag_tender where user_id = {$user_id} and status = 1 and wait_capital > 0")->queryScalar();
            if($ag_tender['num'] == 0){
                $return_result['code'] = 2090;
                return $return_result;
            }
        }
        // 获取债权信息数组
        $debt = AgDebt::model()->findBySql("select * from ag_debt where id=:id for update", array('id' => $debt_id))->attributes;
        if(!$debt){
            Yii::log("debtPreTransaction  step04 {$debt_data['user_id']} ag_debt.id[{$debt_id}] not exist", 'error');
            $return_result['code'] = 2017;
            return $return_result;
        }

        //不能认购自己发布的债权
        if ($debt['user_id'] == $user_id) {
            Yii::log("debtPreTransaction  step06 {$debt_data['user_id']} ag_debt.user_id[{$debt['user_id']}] = user_id[{$user_id}]", 'error');
            $return_result['code'] = 2227;
            return $return_result;
        }

        //已被认购完
        if(FunctionUtil::float_bigger_equal($debt['sold_amount'], $debt['amount'], 3)){
            Yii::log("debtPreTransaction  step13 {$debt_data['user_id']} code=2204", 'error');
            $return_result['code'] = 2019;
            return $return_result;
        }

        //认购额超过债权剩余额度
        if(FunctionUtil::float_bigger(round($account_money, 2), round($debt['amount'] - $debt['sold_amount'], 2), 2)){
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
        if(4 == $debt['status'] || $debt['end_time'] < time()){
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
        $user_info = AgUser::model()->findByPk($user_id);
        if(empty($user_info)){
            Yii::log("debtPreTransaction  step07 {$debt_data['user_id']} user_id[{$user_id}] not exist", 'error');
            $return_result['code'] = 2026;
            return $return_result;
        }

        //项目信息校验
        $borrow = AgProject::model()->findByPk($debt['project_id']);
        if(!$borrow){
            Yii::log("debtPreTransaction end  {$debt_data['user_id']}, ag_deal[{$debt['project_id']}] error", 'error');
            $return_result['code'] = 2028;
            return $return_result;
        }
        //卖家ag_tender数据
        $borrow_tender = AgTender::model()->findBySql("select * from ag_tender where id=:id for update", array('id' => $debt['tender_id']));
        if($borrow_tender->debt_status != 1){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, ag_tender.debt_status[{$borrow_tender->debt_status}] != 1", "error");
            $return_result['code'] = 2029;
            return $return_result;
        }

        //平台信息校验
        $check_p = $this->checkPlatform($borrow_tender->platform_id);
        if($check_p['code'] != 0){
            return $check_p;
        }
        //平台信息
        $plat_info = $check_p['data'];
        //权益兑换来源的债转，认购方必须与平台指定账户一致
        if($debt_src == 1 && $plat_info->buyback_user_id != $user_id){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, user_id[{$user_id}] != buyback_user_id[$plat_info->buyback_user_id]", "error");
            $return_result['code'] = 2048;
            return $return_result;
        }
        //协议验证
        $checkUserPlatForm = PurchService::getInstance()->transformationUserid($user_id, $borrow_tender->platform_id);
        if($checkUserPlatForm['code'] != 0){
            $return_result['code'] = $checkUserPlatForm['code'];
            return $return_result;
        }
        //实际支付金额 商城权益兑换，实际支付金额为0.01，非权益兑换时，支付金额根据折扣金计算
        $investMoney = $debt_src == 1 ? 0.01 : round($debt['discount']*$account_money*0.1, 2);
        //校验买方账户余额
        $buyer_account_info = AgUserAccount::model()->findBySql("select * from ag_user_account where user_id=:user_id for update", array(':user_id' => $user_id));
        if (!$buyer_account_info) {
            Yii::log("debtPreTransaction {$debt_data['user_id']}, ag_user_account.[{$user_id}] error", "error");
            $return_result['code'] = 2049;
            return $return_result;
        }

        //商城权益兑换订单与自主认购订单:校验账户余额必须大于等于实际支付金额
        if(in_array($debt_src, [1, 2]) && FunctionUtil::float_bigger($investMoney, $buyer_account_info->use_money, 3)){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, ag_user_account.[{$user_id}],use_money[$buyer_account_info->use_money]<$investMoney error", "error");
            $return_result['code'] = 2051;
            return $return_result;
        }
        //求购计划来源订单
        if($debt_src == 3){
            //账户冻结金额大于等于实际支付金额
            if(FunctionUtil::float_bigger($investMoney, $buyer_account_info->lock_money, 3)){
                Yii::log("debtPreTransaction {$debt_data['user_id']}, ag_user_account.[{$user_id}],lock_money[$buyer_account_info->lock_money]<$investMoney error", "error");
                $return_result['code'] = 2050;
                return $return_result;
            }

            //求购计划来源债权，需校验求购计划信息
            $check_pur_ret = $this->checkPurchaseOrder($debt['purchase_order_id'], $borrow_tender, $account_money, true);
            if($check_pur_ret['code'] != 0){
                Yii::log("debtPreTransaction checkPurchaseOrder return code:[{$check_pur_ret['code']}] ", 'error');
                return $check_pur_ret;
            }
            //求购信息
            $purchase_info = $check_pur_ret['data'];
            //求购信息用户信息校验
            if($user_id != $purchase_info->user_id){
                Yii::log("debtPreTransaction user_id:{$debt_data['user_id']}!=purchase_info.user_id[{$purchase_info->user_id}]", "error");
                $return_result['code'] = 2072;
                return $return_result;
            }
        }
        $return_result['info'] = "验证成功";
        $return_result['data']['purchase_info'] = $purchase_info;
        $return_result['data']['debt'] = $debt;
        $return_result['data']['investMoney'] = $investMoney;
        $return_result['data']['borrow'] = $borrow;
        $return_result['data']['borrow_tender'] = $borrow_tender;
        $return_result['data']['plat_info'] = $plat_info;
        $return_result['data']['buyer_account_info'] = $buyer_account_info;
        return $return_result;
    }
    /**
     * 认购债权处理
     * @param array $debt_data [
     * money 认购金额
     * user_id 买方用户ID
     * debt_id 被认购债权ID
     * debt_src债权来源：1商城换物转让 2债转市场自主转让 3求购计划出售
     * utype 1:资方用户认购 2：C1用户认购
     * ]
     * @return array
     */
    public function debtPreTransaction($debt_data){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        $debt_id = $debt_data['debt_id'];
        $account_money = $debt_data['money'];
        $user_id = $debt_data['user_id'];
        $debt_src = $debt_data['debt_src'];
        $ruleInfo = $this->AmcTransferBuyRule($debt_data);
        if($ruleInfo['code'] != 0){
            $return_result['code'] = $ruleInfo['code'];
            return $return_result;
        }
        $debt = $ruleInfo['data']['debt'];
        $borrow_tender = $ruleInfo['data']['borrow_tender'];
        $borrow = $ruleInfo['data']['borrow'];
        $investMoney = $ruleInfo['data']['investMoney'];
        $plat_info = $ruleInfo['data']['plat_info'];
        $buyer_account_info = $ruleInfo['data']['buyer_account_info'];
        if($debt_src == 3){
            $purchase_info = $ruleInfo['data']['purchase_info'];
            //更新已收购金额
            $edit_order = array();
            $edit_order['acquired_money'] = bcadd($purchase_info->acquired_money, $account_money, 2);
            if(FunctionUtil::float_equal($edit_order['acquired_money'], $purchase_info->money, 2)){
                $edit_order['status'] = 2;
                $edit_order['successtime'] = time();
            }
            $edit_order_ret = AgPurchaseOrder::model()->updateByPk($purchase_info->id, $edit_order);
            if(!$edit_order_ret){
                Yii::log("debtPreTransaction purchase_order_id：[{$debt['purchase_order_id']}] edit error ", "error");
                $return_result['code'] = 2068;
                return $return_result;
            }
        }
        //组合卖家debt更新数据
        $edit_debt = array();
        $surplus_capital = bcsub($debt['amount'], $debt['sold_amount'], 2);
        $balance = bcsub($surplus_capital, $account_money, 2);
        $sold_money = FunctionUtil::float_equal($balance, 0.00, 2) ? $debt['amount'] : bcadd($debt['sold_amount'], $account_money, 2);
        $edit_debt['sold_amount'] = $sold_money;
        $edit_debt['success_time'] = $debt['success_time'];
        //如已认购完成变更状态为2
        if(FunctionUtil::float_bigger_equal($edit_debt['sold_amount'], $debt['amount'], 2)){
            $edit_debt['status'] = 2;
            $edit_debt['success_time'] = time();
        }
        $debt_edit_res = AgDebt::model()->updateByPk($debt['id'], $edit_debt);
        if(!$debt_edit_res){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, debt.id[{$debt['id']}] edit error:".json_encode($edit_debt), 'error');
            $return_result['code'] = 2275;
            return $return_result;
        }

        //卖家投资记录待还本金数据变更
        $_data = array();
        $_data['wait_capital'] = bcsub($borrow_tender['wait_capital'], $account_money, 2);
        $_data['id'] = $borrow_tender['id'];
        if(FunctionUtil::float_bigger_bc(0.02, $_data['wait_capital'])) {
            $_data['debt_status'] = 15;
        }else{
            //如果待还本金大于0，债转数据已转让完成
            $_data['debt_status'] = $edit_debt['status'] == 2 ? 0 : $borrow_tender['debt_status'];
        }
        //待还本金非负限制
        if(FunctionUtil::float_bigger(0, $_data['wait_capital'],2)){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, wait_capital error ", "error");
            $return_result['code'] = 2030;
            return $return_result;
        }
        $edit_load = BaseCrudService::getInstance()->update('AgTender', $_data,'id');
        if(!$edit_load){
            Yii::log('updateSellerData AgTender edit error, data:'.  print_r($_data,true));
            return false;
        }
        $addtime = time();
        //添加买家ag_tender
        $tender = array();
        $tender['project_id'] = $borrow_tender['project_id'];
        $tender['user_id'] = $user_id;
        $tender['money'] = $account_money;
        $tender['serial_number'] = FunctionUtil::getAgRequestNo('OBUY');
        $tender['wait_capital'] = $account_money;
        $tender['platform_id'] = $borrow_tender['platform_id'];
        $tender['purchase_order_id'] = $debt['purchase_order_id'];
        $tender['debt_src'] = $debt_src;
        $tender['addtime'] = $addtime;
        $tender['addip'] = FunctionUtil::ip_address();
        $tender_ret = BaseCrudService::getInstance()->add('AgTender', $tender);
        if(!$tender_ret){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, Buyers AgTender add error :". print_r($tender, true), "error");
            $return_result['code'] = 2031;
            return $return_result;
        }

        //添加买家debt_tender[为以后一笔债权支持多用户购买做准备]
        unset($data);
        $data['debt_id'] = $debt_id;
        $data['user_id'] = $user_id;
        $data['debt_src'] = $debt_src;
        $data['new_tender_id'] = $tender_ret['id'];
        $data['amount'] = $account_money;//认购额
        $data['spend_amount'] = $investMoney;//实际支付金额
        $data['discount'] = $debt['discount'];
        $data['addtime'] = time();
        $data['addip'] = FunctionUtil::ip_address();
        $debt_tender = BaseCrudService::getInstance()->add('AgDebtTender', $data);
        if(!$debt_tender){
            Yii::log("debtPreTransaction {$debt_data['user_id']}, AgDebtTender add error :". print_r($data, true), "error");
            $return_result['code'] = 2032;
            return $return_result;
        }

        //债权认购合同生成
//        $add_contract_ret = ContractService::getInstance()->addContract($user_id, 0, $tender_ret['id'], $borrow_tender['project_id'], 3, $plat_info->e_debt_template);
//        if($add_contract_ret == false){
//            Yii::log("addBuyerData addContract error, data:($user_id, 0, {$tender_ret['id']}, {$borrow_tender['project_id']}, 1)");
//            $return_result['code'] = 2040;
//            return $return_result;
//        }

        //买家流水修改及账户变更
        unset($blog);
        $blog['related_id'] = $tender_ret['id'];
        $blog['project_id'] = $borrow_tender->project_id;
        $blog['user_id'] = $user_id;
        $blog['project_type_id'] = $borrow->type_id;
        $blog['platform_id'] = $borrow_tender->platform_id;
        $blog['log_type'] = 'debt_success';
        $blog['direction'] = '2';//0-冻结，1-加，2-减
        $blog['money'] = $investMoney;
        $blog['to_user'] = 0;
        $blog['addtime'] = time();
        $blog['serial_number'] = $tender['serial_number'];
        $blog['addip'] = FunctionUtil::ip_address();
        $blog['remark'] = "债权认购{$debt['tender_id']}扣款";
        //求购计划买方使用冻结资金
        if($debt_src == 3){
            $blog['lock_money'] = bcsub($buyer_account_info->lock_money, $blog['money'], 2);
        }else{
            $blog['use_money'] = bcsub($buyer_account_info->use_money, $blog['money'], 2);
            if(FunctionUtil::float_bigger(0, $blog['use_money'], 2)){
                Yii::log("addBuyerData use_money[{$blog['use_money']}] < 0 ", 'error');
                $return_result['code'] = 2052;
                return $return_result;
            }
        }      
        
        $_addlogret = UserService::getInstance()->addLog($blog);
        if ($_addlogret === false) {
            Yii::log("addBuyerData addLog error, data:".print_r($blog, true), 'error');
            $return_result['code'] = 2052;
            return $return_result;
        }

        //校验卖方账户信息
        $seller_account_info = AgUserAccount::model()->findBySql("select * from ag_user_account where user_id=:user_id for update", array(':user_id' => $debt['user_id']));
        if (!$seller_account_info) {
            Yii::log("debtPreTransaction {$debt['user_id']}, ag_user_account.[{$debt['user_id']}] error", "error");
            $return_result['code'] = 2053;
            return $return_result;
        }

        //卖方账户及流水记录
        unset($slog);
        $log_type = $debt_src == 1 ? "debt_exchange_finish" : "debt_finish";
        $slog['related_id'] = $tender_ret['id'];
        $slog['project_id'] = $borrow_tender->project_id;
        $slog['user_id'] = $debt['user_id'];
        $slog['serial_number'] = $debt['serial_number'];
        $slog['project_type_id'] = $borrow->type_id;
        $slog['platform_id'] = $borrow_tender->platform_id;
        $slog['log_type'] = $log_type;
        $slog['direction'] = '1';//0-冻结，1-加，2-减
        $slog['money'] = $investMoney;
        $slog['use_money'] = bcadd($seller_account_info['use_money'], $slog['money'], 2);
        $slog['withdraw_free'] = bcadd($seller_account_info['withdraw_free'], $slog['money'], 2);
        $slog['remark'] = "债权转让{$debt['id']}到账";
        $slog['to_user'] = 0;
        $slog['addtime'] = time();
        $data['addip'] = FunctionUtil::ip_address();
        $_addlogret = UserService::getInstance()->addLog($slog);
        if ($_addlogret === false) {
            Yii::log("addSellerData addLog error, data:".print_r($slog, true), 'error');
            $return_result['code'] = 2054;
            return $return_result;
        }
        $return_result['code'] = 0;
        return $return_result;
    }

    public function checkPlatform($platform_id){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        Yii::log("checkPlatform start: platform_id:$platform_id;");
        if(empty($platform_id) || !is_numeric($platform_id)){
            $return_result['code'] = 2046;
            return $return_result;
        }

        //平台信息
        $plat_info = AgPlatform::model()->findByPk($platform_id);
        if(!$plat_info ){
            Yii::log("checkPlatform ag_platform.platform_id[$platform_id] error", "error");
            $return_result['code'] = 2045;
            return $return_result;
        }

        //平台必须审核通过
        if($plat_info->status !=1){
            Yii::log("checkPlatform ag_platform.status[$plat_info->status] error", "error");
            $return_result['code'] = 2047;
            return $return_result;
        }

        //债转合同模板校验
        // if(empty($plat_info->e_debt_template)){
        //     Yii::log("checkPlatform ag_platform.e_debt_template[$plat_info->e_debt_template] error", "error");
        //     $return_result['code'] = 2055;
        //     return $return_result;
        // }
        // $return_result['data'] = $plat_info;
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
     * 债权取消or过期
     * @param  debt_id
     * @param  status 3取消4过期
     * @return array
     */
    public function CancelDebt($debt_id, $status, $user_id=''){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );
        if(empty($debt_id) || !in_array($status, [3,4])){
            $return_result['code'] = 2056;
            return  $return_result;
        }

        //取消时，用户ID必传
        if($status == 3 && empty($user_id)){
            $return_result['code'] = 2057;
            return  $return_result;
        }

        $debt_id = FunctionUtil::verify_id($debt_id);
        if(!empty($user_id)){
            $user_info = AgUser::model()->findByPk($user_id);
            if(empty($user_info)) {
                $return_result['code'] = 2058;
                return  $return_result;
            }
        }

        Yii::app()->agdb->beginTransaction();
        try {
            $debt = AgDebt::model()->findBySql("select * from ag_debt where id=:id for update", array('id' => $debt_id));
            if (empty($debt)) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2059;
                return $return_result;
            }

            //只能取消自己债权
            if ($status == 3 && $debt->user_id != $user_id) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2060;
                return $return_result;
            }

            //非可取消状态
            if (1 != $debt->status) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2061;
                return $return_result;
            }

            //变更ag_debt
            $new_debt['status'] = $status;
            $new_debt['end_time'] = time();
            $new_debt['id'] = $debt['id'];
            $new_debt_ret = BaseCrudService::getInstance()->update('AgDebt', $new_debt, 'id');
            if (!$new_debt_ret) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2062;
                return $return_result;
            }

            //ag_tender数据状态变更
            $tender_info = AgTender::model()->findBySql("select * from ag_tender where id=:id for update", array(':id' => $debt->tender_id));
            if (!$tender_info) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2063;
                return $return_result;
            }
            $new_tender['debt_status'] = 0;
            $new_tender['id'] = $debt->tender_id;
            $new_tender_ret = BaseCrudService::getInstance()->update('AgTender', $new_tender, 'id');
            if (!$new_tender_ret) {
                Yii::app()->agdb->rollback();
                $return_result['code'] = 2062;
                return $return_result;
            }
            //取消成功
            Yii::app()->agdb->commit();
            return $return_result;
        }catch (Exception $e) {
            Yii::app()->agdb->rollback();
            $return_result['code'] = 2089;
            return $return_result;
        }
    }

    /**
     * 求购计划校验
     * @param $purchase_order_id
     * @param $money
     * @param $tender_info
     * @param bool $is_lock
     * @return array
     */
    public function checkPurchaseOrder($purchase_order_id, $tender_info, $money, $is_lock=false){
        //返回数据
        $return_result = array(
            'code'=>0, 'info'=>'', 'data'=>array()
        );

        //基本参数校验
        if(empty($tender_info) || empty($purchase_order_id) || !is_numeric($purchase_order_id) || empty($money) ) {
            Yii::log("checkPurchaseOrder purchase_order_id[{$purchase_order_id}] error ", 'error');
            $return_result['code'] = 2064;
            return $return_result;
        }

        //求购计划信息
        $condition = $is_lock ? " for update " : "";
        $purchase_order = AgPurchaseOrder::model()->findBySql("select * from ag_purchase_order where id=:id $condition", array(':id' => $purchase_order_id));
        if (empty($purchase_order)) {
            Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id not exist ", 'error');
            $return_result['code'] = 2065;
            return $return_result;
        }

        //求购计划状态非求购中
        if ($purchase_order->status != 1 || $purchase_order->successtime != 0) {
            Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id; status[{$purchase_order->status}] or successtime[{$purchase_order->successtime}] error ", 'error');
            $return_result['code'] = 2066;
            return $return_result;
        }

        //剩余求购金额
        $surplus_pamount = bcsub($purchase_order->money, $purchase_order->acquired_money, 2);
        if(FunctionUtil::float_bigger($money, $surplus_pamount, 2)){
            Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, money:$money>surplus_pamount:$surplus_pamount ", 'error');
            $return_result['code'] = 2067;
            return $return_result;
        }

        //平台及项目信息一致性校验
        if(!empty($purchase_order->platform_id)){
            if($tender_info->platform_id != $purchase_order->platform_id){
                Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, tender_info.platform_id:$tender_info->platform_id!=purchase_order.platform_id:$purchase_order->platform_id ", 'error');
                $return_result['code'] = 2069;
                return $return_result;
            }

            //项目类型校验
            if(!empty($purchase_order->project_types)){
                //项目信息获取
                $project_info = AgProject::model()->findByPk($tender_info->project_id);
                if(!$project_info){
                    Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, AgProject.id:$tender_info->project_id not exist ", 'error');
                    $return_result['code'] = 2010;
                    return $return_result;
                }

                //项目类型
                if(!in_array($project_info->type_id, explode(',', $purchase_order->project_types))){
                    Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, type_id:$project_info->type_id not in $purchase_order->project_types ", 'error');
                    $return_result['code'] = 2071;
                    return $return_result;
                }
            }

            //项目ID校验
            if(!empty($purchase_order->project_ids) && !in_array($tender_info->project_id, explode(',', $purchase_order->project_ids))){
                Yii::log("checkPurchaseOrder end purchase_order_id:$purchase_order_id, tender_info.project_id:$tender_info->project_id not in $purchase_order->project_ids ", 'error');
                $return_result['code'] = 2070;
                return $return_result;
            }
        }

        $return_result['data'] = $purchase_order;
        return $return_result;
    }
}
