<?php
/**
 * @file AccountService.php
 * @author (kuangjun@xxx.com)
 * @date 2013/10/25
 * 用户账户类 
 **/

class DwAccountService extends  ItzInstanceService {
    
    protected $expire1 = 600;
    protected $expire2 = 86400;
    protected $secondaryFlag = false;
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
    *   计算充值满15天金额
    */
    public function withdrawfreeRecharge($user_id){
        #参数验证
        if(empty($user_id) || !is_numeric($user_id)){
            Yii::log("withdrawfreeRecharge error:user_id is illegal", "error", "withdrawfreeRecharge");
            return false;
        }
        
        $withdraw_free_days = (int)Yii::app()->c->linkconfig['withdraw_free_days']-1;   #充值满15天, 提现免收手续费
        $node_time = strtotime(date('Ymd',strtotime("-$withdraw_free_days day")));      #获取15天前的凌晨时间戳

        #获取充值满15天金额        
        $condition = new CDbCriteria;
        $condition->select = " sum(use_recharge_money) as use_recharge_money ";
        $condition->condition = " user_id = :user_id and use_recharge_money > 0 and addtime < :addtime";
        $condition->params[':user_id']  = $user_id;
        $condition->params[':addtime']  = $node_time;
        $recharge_detail_result = DwAccountRechargeDetail::model()->findAll($condition);
        $withdrawfree_recharge = $recharge_detail_result[0]->use_recharge_money;
        if(empty($recharge_detail_result[0]->use_recharge_money)) {
            $withdrawfree_recharge = 0;
        }
        return $withdrawfree_recharge;          #充值满15天金额
    }

    /**
     * 提前还款计算利息 
     *
     * @access public
     * @param int $borrow_id
     * @param int $advance_time (Y-m-d)
     * @return double
     */
    public function  calculateInterest($borrow_id, $advance_time){
        $advance_time = strtotime($advance_time);//提前还款的时间
        $today_time = strtotime('midnight');//今日零点
        $return_result = array('total_interest'=>0, 'repay_interest'=>0, 'interest_extra'=>0, 'extra_day'=>0);
        if (empty($borrow_id) || !is_numeric($borrow_id) || $borrow_id <= 0 || $today_time == false || $advance_time<$today_time) {
            Yii::log("error params :".print_r(func_get_args(),true), "error", "DwAccountService::calculateInterest");
            return false;
        }
        
        //获取项目信息
        $borrow_info = Borrow::model()->findByPk($borrow_id); 
        if (empty($borrow_info)) {
            Yii::log("borrow_info is empty, borrow_id:".$borrow_id, "error", "DwAccountService::calculateInterest");
            return false;
        }

        //等额本息项目利息计算公式：剩余本金*利率*持有天数/360
        if($borrow_info->style == 5){
            //等额本息项目剩余本金
            $collection_sql = "select sum(capital) as capital from dw_borrow_collection where borrow_id=:borrow_id and status=0";
            $collection_result = BorrowCollection::model()->findBySql($collection_sql, array(":borrow_id"=>$borrow_info->id));
            //获取tender_id
            $tender_sql = "select id from dw_borrow_tender where borrow_id=:borrow_id and status=1 and (wait_account-wait_interest)>0 order by addtime desc limit 1";
            $tender_result = BorrowTender::model()->findBySql($tender_sql, array(":borrow_id"=>$borrow_info->id));
            //根据tender_id, 获取上一个付息日
            $repaytime_sql = "select max(repay_time) as repay_time from dw_borrow_collection where borrow_id=:borrow_id and status=1";
            $repaytime_result = BorrowCollection::model()->findBySql($repaytime_sql, array(":borrow_id"=>$borrow_info->id));
            //从未付息过, 取上线时间
            $value_date = !empty($repaytime_result['repay_time'])?$repaytime_result['repay_time']:$borrow_info->formal_time;
            //持有天数
            $hold_days = ($today_time-strtotime("midnight", $value_date))/86400;
            //正常利息
            $return_result["repay_interest"] = round($collection_result['capital']*$hold_days*$borrow_info->apr/36000, 2);

            //补偿天数, 补偿利息
            if($advance_time > $today_time){
                $return_result["extra_day"] = (int)($advance_time - $today_time)/86400;
                $return_result["interest_extra"] = round($collection_result['capital']*$return_result["extra_day"]*$borrow_info->apr/36000, 2);
            }
            //总利息金额
            $return_result["total_interest"] = round($return_result["repay_interest"]+$return_result["interest_extra"], 2);//总利息金额
            return $return_result;
        }

        //非等额本息项目计算利息
        $last_tender_time = strtotime(date("Y-m-d",$borrow_info->last_tender_time));//项目投完的时间零点
        $formal_time = strtotime(date("Y-m-d",$borrow_info->formal_time));//项目上线时间零点
        $days = (int)($last_tender_time - $formal_time)/86400;
        $repay_interest = 0;//正常还款的付息总额
        for ($i=0; $i<=$days ; $i++) { 
            $params = array(); 
            $start_time = $last_tender_time - $i * 86400;//每一天投资开始时间
            $end_time = $start_time + 86400;//每一天投资结束时间
            
            //获取单日投资总额和某笔的tender_id
            $tender_result = $this->getSumAccount($borrow_id,$start_time,$end_time);
            $daliy_account = $tender_result['account'];

            //获取投资时间对应的上一个付息日
            //例如项目3183 2016/05/22发售, 2016/05/23投满, 2016/11/22提前还款, 
            //2016/05/22投资用户获取的上一付息日2016/11/22, 2016/05/23投资用户获取的上一付息日2016/10/23
            $last_interest_day = $this->getLastInterestDay($borrow_id, $tender_result['tender_id']);//获取上一个付息日
            if (empty($last_interest_day)) { //如果没有付过息，就取值项目发布当天
                $last_interest_day = $last_tender_time;
                //没有付息过, 起息时间取投资时间, 根据投满时间往前移
                $params['borrow_time'] = $last_interest_day - $i * 86400;  
            }else{
                //对应的投资时间付过息, 起息时间取上一个付息日
                $params['borrow_time'] = $last_interest_day;
            } 
            
            $params['account'] = FunctionUtil::float_bigger($daliy_account, 0, 3) ? $daliy_account : 0;
            $params['year_apr'] = $borrow_info->apr;
            $params['borrow_style'] = $borrow_info->style;
            $params['repayment_time'] = $today_time;
            $interest_data = InterestPayUtil::EqualInterest($params);
            if (!empty($interest_data)) {
                $interest = end($interest_data);
                $repay_interest += round($interest['all_interest'], 2);
            }   
        } 
        
        // 省心优选, 融满次日计息, 试算提前还款当天投资人应还的正常利息, 计算正常利息的方式和投资日计息的不一样
        if($borrow_info->delay_value_days == 2 and $borrow_info->type == 402){
            // 省心优选, 根据投资总额试算投资人的应还利息
            $params['account'] = FunctionUtil::float_bigger($borrow_info->account_yes, 0, 3) ? $borrow_info->account_yes : 0;
            $params['year_apr'] = $borrow_info->apr;
            $params['borrow_style'] = $borrow_info->style;
            $params['repayment_time'] = $today_time;
            // 判断小标的是否付息过
            // 查询某一笔在存的tender_id, 用于查询collection取更少的数据量
            $tender_result = BorrowTender::model()->findBySql("select * from dw_borrow_tender where borrow_id=:borrow_id and status=1 and account>0 and debt_type%2=1", array(":borrow_id"=>$borrow_info->id));
            if(empty($tender_result)){
                Yii::log("calculateInterest: tender_result is empty, tender_id: {$tender_result->id}", "error", "DwAccountService::calculateInterest");
                return false;
            }
            $repay_sql = "select count(1) as count, max(repay_time) as max_repay_time from dw_borrow_collection where borrow_id=:borrow_id and status=1 and tender_id=:tender_id";
            $borrow_collection = Yii::app()->dwdb->createCommand($repay_sql)->bindValues(array(":borrow_id"=>$borrow_info->id, ":tender_id"=>$tender_result['id']))->queryRow();
            if($borrow_collection['count'] == 0){
                //从未付息过, 计息时间=融满次日
                $params['borrow_time'] = $borrow_info->last_tender_time+86400;
            }else{
                //已经支付过利息, 计息时间=上一付息日
                $params['borrow_time'] = $borrow_collection['max_repay_time'];
            }
            $interest_data = InterestPayUtil::EqualInterest($params);
            if (!empty($interest_data)) {
                $interest = end($interest_data);
                $repay_interest = round($interest['all_interest'], 2);
            }
        }

        $interest_extra = 0;
        $extra_day = 0;
        if ($advance_time > $today_time) {//如果提前还款的时间大于今天则有补偿利息
            $extra_day = (int)($advance_time - $today_time)/86400;//补偿天数
            $params['account'] = FunctionUtil::float_bigger($borrow_info->account_yes, 0, 3) ? $borrow_info->account_yes : 0;
            $params['year_apr'] = $borrow_info->apr;
            $params['borrow_style'] = $borrow_info->style;
            $params['repayment_time'] = $advance_time;
            $params['borrow_time'] = $today_time;
            $interest_data = InterestPayUtil::EqualInterest($params);
            if (!empty($interest_data)) {
                $interest = end($interest_data);
                $interest_extra += round($interest['all_interest'], 2);
            }   
        }
        $return_result["total_interest"] = $interest_extra + $repay_interest;//总利息金额
        $return_result["repay_interest"] = $repay_interest;//正常还息
        $return_result["interest_extra"] = $interest_extra;//补偿利息
        $return_result["extra_day"] = $extra_day;//补偿天数
        return $return_result;
    }

    /**
    * 获取单日的购买的项目总额和单日某一笔的tender_id
    */
    private function getSumAccount($borrow_id,$start_time,$end_time){
        $data = array();
        $condition = new CDbCriteria;
        $condition->select = " sum(account_init) as account_init ";
        $condition->condition = " borrow_id = :borrow_id and addtime >= :start_time and addtime <:end_time and debt_type % 2 = 1";
        $condition->params[':borrow_id']  = $borrow_id;
        $condition->params[':start_time']  = $start_time;
        $condition->params[':end_time']  = $end_time;
        $tender_result = BorrowTender::model()->findAll($condition);
        $account = 0;
        if (isset($tender_result[0]['account_init']) && FunctionUtil::float_bigger($tender_result[0]['account_init'], 0, 3)) {
            $account = $tender_result[0]['account_init'];
        } 
	
		$condition->select = " id ";        
		$condition->condition = " borrow_id = :borrow_id and addtime >= :start_time and addtime <:end_time and debt_type % 2 = 1 and status=1";        
		$tender_result = BorrowTender::model()->find($condition);
        $data['account'] = $account;    //单日投资总额
        $data['tender_id'] = $tender_result['id'];   //单日的某笔投资id

        return $data;
    }
    
    //获取上一个付息日
    private function getLastInterestDay($borrow_id, $tender_id){
        $condition = new CDbCriteria;
        $condition->select = "max(repay_time) as repay_time";
        $condition->condition = " borrow_id = :borrow_id and status =1 and tender_id=:tender_id";
        $condition->params[':borrow_id']  = $borrow_id;
        $condition->params[':tender_id']  = $tender_id;
        $collection_result = BorrowCollection::model()->findAll($condition);
        $repay_yestime = "";
        if (isset($collection_result[0]['repay_time']) && $collection_result[0]['repay_time'] >0) {
            $repay_yestime = strtotime(date("Y-m-d",$collection_result[0]['repay_time']));
        } 
        return $repay_yestime;
    }

    /*
    * 计算加息收益
    * money: 投资金额
    * coupon_apr: 加息年化
    * experience_days: 加息天数
    * interest_max_money: 最高加息本金, 默认是0
    */ 
    public function calculateInterestReward($money,$coupon_apr,$experience_days,$interest_max_money=0) {
        if (FunctionUtil::float_bigger_equal(0, $money, 3)
            || FunctionUtil::float_bigger_equal(0, $coupon_apr, 3)
            || $experience_days < 1 
            || $experience_days != (int)$experience_days
            || FunctionUtil::float_bigger(0, $interest_max_money, 3)) {
            return 0;
        }

        //设置了最高加息本金, 加息的本金=min(投资金额, 最高加息本金)
        //未设置最高加息本金, 加息的本金=投资金额
        if(FunctionUtil::float_bigger($interest_max_money, 0))
        {
            $money = min($money, $interest_max_money);
        }
        return round(($money * $coupon_apr/100 * $experience_days/365), 2);
    }

    /**
    * 获取提前还款项目当天到利息指定付息日之间的还息时间和还息金额
    * borrow_id: 项目ID
    * advance_time: 项目指定的利息支付到的日期, Y-m-d格式
    */
    public function getNormalRepayInterest($borrow_id, $advance_time){
        //参数验证
        if($borrow_id<=0 || !is_numeric($borrow_id)){
            Yii::log("error params, borrow_id:$borrow_id", "error", "DwAccountService::getNormalRepayInterest");
            return false;
        }

        if($advance_time != date("Y-m-d",strtotime($advance_time))) {
            Yii::log("error params, advance_time:$advance_time", "error", "DwAccountService::getNormalRepayInterest"); 
            return false;
        }

        $result = array();  //返回数据

        $min_time = strtotime("midnight");      //当天
        $max_time = strtotime($advance_time);   //利息指定支付到的日期

        //获取当天到利息指定付息日之间的还款数据
        $collection_sql = "select repay_time,sum(interest) as repay_interest 
                           from dw_borrow_collection where borrow_id=:borrow_id and repay_time>=:min_time and repay_time<=:max_time
                           and status in(0,5) and type=1 group by repay_time";
        $result = Yii::app()->dwdb->createCommand($collection_sql)->bindValues(array(":borrow_id"=>(int)$borrow_id,":min_time"=>(int)$min_time,":max_time"=>(int)$max_time))->queryAll();
        return $result;
    } 

    /**
    * 获取提前还款项目当天到利息指定付息日之间的还息时间和还息金额
    * borrow_id: 项目ID
    * advance_time: 项目指定的利息支付到的日期, Y-m-d格式
    */
    public function getWiseNormalRepayInterest($wise_borrow_id, $advance_time){
        //参数验证
        if ($wise_borrow_id == '' || !isset($wise_borrow_id)) {
            Yii::log("error params, borrow_id:$borrow_id", "error", "DwAccountService::getNormalRepayInterest");
            return false;
        }

        if($advance_time != date("Y-m-d",strtotime($advance_time))) {
            Yii::log("error params, advance_time:$advance_time", "error", "DwAccountService::getNormalRepayInterest"); 
            return false;
        }

        $result = array();  //返回数据

        $min_time = strtotime("midnight");      //当天
        $max_time = strtotime($advance_time);   //利息指定支付到的日期

        //获取当天到利息指定付息日之间的还款数据
        $statRepay_sql = "SELECT repay_time,sum(interest) as repay_interest 
                           from itz_stat_repay where wise_borrow_id=:wise_borrow_id and repay_time>=:min_time and repay_time<=:max_time
                           and repay_status=0 and repay_type=2 group by repay_time";
        $result = Yii::app()->dwdb->createCommand($statRepay_sql)->bindValues(array(":wise_borrow_id"=>$wise_borrow_id,":min_time"=>(int)$min_time,":max_time"=>(int)$max_time))->queryAll();
        return $result;
    } 


    /**
     * [getAccountBalance 获取账户可用余额余额(企业、保障机构)]
     * @param  array  $data [description]
     * @return [type]       [description]
     * @author chengguilu@xxx.com
     */
    public function getAccountBalance($data=array())
    {
        $returnResult = array(
            'code' => 0, 'info' => '', 'data' => array()
        );
        $user_id = $data['user_id'];
        //参数校验
        if(empty($user_id)){
            $returnResult['code'] = 1;
            $returnResult['info'] = '账户id不能为空！';
            return $returnResult;
        }

        //拼接数据
        $requiredData = array(
            'serviceName' => 'QUERY_USER_INFORMATION', 
            'userDevice' => 'PC', 
            'reqData' => array(
                'platformUserNo' =>  "$user_id",
                'requestNo' => FunctionUtil::getRequestNo(),
            ), 
        );
        //调取java接口
        $xw_result = CurlService::getInstance()->service($requiredData);
        if($xw_result['code'] != 0){
            Yii::log("xw_result: ".print_r($xw_result, true), "error", "DwAccountService::getAccountBalance"); 
            $returnResult['code'] = $xw_result['data']['errorCode'];
            $returnResult['info'] = $xw_result['data']['errorMessage'];
            return $returnResult;
        }

        /*
        //审核状态, 审核通过
        if($xw_result['data']['auditStatus'] != "PASSED"){
            Yii::log("borrower auditStatus error, auditStatus: {$xw_result['data']['auditStatus']}, user_id: $user_id", "error", "DwAccountService::getAccountBalance"); 
            $returnResult['code'] = 2;
            $returnResult['info'] = "borrower auditStatus error, auditStatus: {$xw_result['data']['auditStatus']}";
            return $returnResult;
        }

        //用户状态, 可用
        if($xw_result['data']['activeStatus'] != "ACTIVATED"){
            Yii::log("borrower activeStatus error, activeStatus: {$xw_result['data']['activeStatus']}, user_id: $user_id", "error", "DwAccountService::getAccountBalance"); 
            $returnResult['code'] = 3;
            $returnResult['info'] = "borrower activeStatus error, activeStatus: {$xw_result['data']['activeStatus']}";
            return $returnResult;
        }

        //迁移导入会员状态，true表示已激活，false表示未激活，正常注册成功会员则默认状态为true
        if($xw_result['data']['isImportUserActivate'] == false){
            Yii::log("borrower isImportUserActivate error, isImportUserActivate: {$xw_result['data']['activeStatus']}, user_id: $user_id", "error", "DwAccountService::getAccountBalance"); 
            $returnResult['code'] = 4;
            $returnResult['info'] = "borrower isImportUserActivate error, isImportUserActivate: {$xw_result['data']['activeStatus']}";
            return $returnResult;
        }

        //绑定了卡号
        if(!isset($xw_result['data']['bankcardNo']) || empty($xw_result['data']['bankcardNo'])){
            Yii::log("borrower bankcardNo is empty, user_id: $user_id", "error", "DwAccountService::getAccountBalance"); 
            $returnResult['code'] = 5;
            $returnResult['info'] = "borrower bankcardNo is empty";
            return $returnResult;
        }*/

        $returnResult['info'] = "Success";
        $returnResult['data']['availableAmount'] = $xw_result['data']['availableAmount'];
        return $returnResult;
    }

    /**
     * 智选计划提前还款计算利息
     * @access public
     * @param int $borrow_id
     * @param int $advance_time (Y-m-d)
     * @return double
     */
    public function  wiseCalculateInterest($wise_borrow_id, $advance_time){
        Yii::log(" func_get_args :".json_encode(func_get_args()), 'info', __FUNCTION__);
        $return_result = ['code'=>0, 'info'=>'', 'data'=>[
                                                        'total_interest'=>0,//利息总金额
                                                        'repay_interest'=>0,//正常待还利息
                                                        'interest_extra'=>0,//补偿利息
                                                        'extra_day'=>0//补偿天数
                                                    ]];

        //参数非空校验
        if (empty($wise_borrow_id) || empty($advance_time)){
            Yii::log(" step01 params error :".json_encode(func_get_args()), 'error', __FUNCTION__);
            $return_result['code'] = 2000;
            return $return_result;
        }

        //校验还款时间：必须大于当前时间
        $advance_time = strtotime($advance_time);//提前还款的时间
        $today_time = strtotime('midnight');//今日零点
        if($advance_time<$today_time){
            Yii::log(" step02 advance_time[{$advance_time}] < today_time[{$today_time}] ", 'error', __FUNCTION__);
            $return_result['code'] = 3000;
            return $return_result;
        }

        //校验小标的信息[省心小贷]
        $wise_borrow = ItzWiseBorrow::model()->find("wise_borrow_id=:wise_borrow_id", array(':wise_borrow_id'=>$wise_borrow_id));
        if(empty($wise_borrow) || $wise_borrow->status != 0 || $wise_borrow->borrow_type != 2 || !$wise_borrow->finish_time){
            Yii::log(" step03 wise_borrow data exception, wise_borrow_id[{$wise_borrow_id}]", "error", __FUNCTION__);
            $return_result['code'] = 3001;
            return $return_result;
        }

        //查询第一笔wise_tender
        $first_time_info = ItzWiseTender::model()->find(array('select'=>'min(addtime) as addtime','condition'=>" wise_borrow_id='$wise_borrow_id' "));
        if(!$first_time_info || !$first_time_info->addtime || $first_time_info->addtime > $wise_borrow->finish_time){
            Yii::log(" step04 wise_tender data exception, wise_borrow_id[{$wise_borrow_id}]", "error", __FUNCTION__);
            $return_result['code'] = 3002;
            return $return_result;
        }

        //计算正常待还利息
        $last_tender_time = strtotime('midnight', $wise_borrow->finish_time);//融满时间
        $formal_time = strtotime('midnight', $first_time_info->addtime);//第一笔投资时间
        $days = (int)($last_tender_time - $formal_time)/86400;
        $repay_interest = 0;//正常还款的付息总额
        for ($i=0; $i<=$days ; $i++) {
            $params = array();
            $start_time = $last_tender_time - $i * 86400;//每一天投资开始时间
            $end_time = $start_time + 86400;//每一天投资结束时间

            //获取单日投资总额和某笔的tender_id
            $tender_result = $this->getWiseSumAccount($wise_borrow_id, $start_time, $end_time);
            if($tender_result['code'] != 0){
                return $tender_result;
            }
            //单日总投资额
            $daily_account = $tender_result['data']['account'];
            //获取投资时间对应的上一个付息日
            $last_interest_day = $this->getWiseLastInterestDay($wise_borrow_id, $start_time);//获取上一个付息日
            //如果没有付过息，就取值项目发布当天
            if(empty($last_interest_day)){
                $last_interest_day = $last_tender_time;
                //没有付息过, 起息时间取投资时间, 根据投满时间往前移
                $params['borrow_time'] = $last_interest_day - $i * 86400;
            }else{
                //对应的投资时间付过息, 起息时间取上一个付息日
                $params['borrow_time'] = $last_interest_day;
            }

            $params['account'] = FunctionUtil::float_bigger($daily_account, 0, 3) ? $daily_account : 0;
            $params['year_apr'] = $wise_borrow->apr;
            $params['borrow_style'] = $wise_borrow->style;
            $params['repayment_time'] = $today_time;
            $interest_data = InterestPayUtil::EqualInterest($params);
            if (!empty($interest_data)) {
                $interest = end($interest_data);
                $repay_interest += round($interest['all_interest'], 2);
            }
        }
        $interest_extra = 0;
        $extra_day = 0;
        if($advance_time > $today_time){//如果提前还款的时间大于今天则有补偿利息
            $extra_day = (int)($advance_time - $today_time)/86400;//补偿天数
            $params['account'] = FunctionUtil::float_bigger($wise_borrow->account_yes, 0, 3) ? $wise_borrow->account_yes : 0;
            $params['year_apr'] = $wise_borrow->apr;
            $params['borrow_style'] = $wise_borrow->style;
            $params['repayment_time'] = $advance_time;
            $params['borrow_time'] = $today_time;
            $interest_data = InterestPayUtil::EqualInterest($params);
            if (!empty($interest_data)) {
                $interest = end($interest_data);
                $interest_extra += round($interest['all_interest'], 2);
            }
        }
        $return_result['data']["total_interest"] = bcadd($interest_extra, $repay_interest, 2);//总利息金额
        $return_result['data']["repay_interest"] = $repay_interest;//正常还息
        $return_result['data']["interest_extra"] = $interest_extra;//补偿利息
        $return_result['data']["extra_day"] = $extra_day;//补偿天数
        return $return_result;
    }

    /**
     * 查询智选计划小标的单日投资总额
     * @param $wise_borrow_id
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private function getWiseSumAccount($wise_borrow_id, $start_time, $end_time){
        Yii::log(" func_get_args :".json_encode(func_get_args()), 'info', __FUNCTION__);
        $return_result = ['code'=>0, 'info'=>'', 'data'=>[
                                                        'account'=>0,//单日投资总额
                                                        'tender_id'=>0,//单日的某笔投资id
                                                    ]];
        //参数校验
        if(empty($wise_borrow_id) || empty($start_time) || empty($end_time)){
            $return_result['code'] = 2000;
            return $return_result;
        }

        //查询单日融资总额
        $condition = new CDbCriteria;
        $condition->select = " sum(account_init) as account_init, addtime  ";
        $condition->condition = " wise_borrow_id = :wise_borrow_id and addtime >= :start_time and addtime <:end_time and debt_type=3";
        $condition->params[':wise_borrow_id'] = $wise_borrow_id;
        $condition->params[':start_time']  = $start_time;
        $condition->params[':end_time']  = $end_time;
        $wise_tender = ItzWiseTender::model()->find($condition);
        if(!$wise_tender || empty($wise_tender->account_init) || FunctionUtil::float_bigger_equal(0.00, $wise_tender->account_init, 2)){
            Yii::log(" getWiseSumAccount wise_tender data exception, wise_borrow_id[{$wise_borrow_id}]", "error", __FUNCTION__);
            $return_result['code'] = 3002;
            return $return_result;
        }
        $return_result['data']['account'] = $wise_tender->account_init;
        $return_result['data']['investor_value_time'] = $start_time;
        return $return_result;
    }

    //获取上一个付息日
    private function getWiseLastInterestDay($wise_borrow_id, $investor_value_time){
        //参数校验
        if(empty($wise_borrow_id) || empty($investor_value_time)){
            return '';
        }
        $condition = new CDbCriteria;
        $condition->select = "max(repay_yestime) as repay_yestime";
        $condition->condition = " wise_borrow_id = :wise_borrow_id and repay_status =1 and investor_value_time=:investor_value_time";
        $condition->params[':wise_borrow_id']  = $wise_borrow_id;
        $condition->params[':investor_value_time']  = $investor_value_time;
        $collection_result = ItzStatRepay::model()->find($condition);
        $repay_yestime = "";
        if (isset($collection_result['repay_yestime']) && $collection_result['repay_yestime'] >0) {
            $repay_yestime = strtotime('midnight', $collection_result['repay_yestime']);
        }
        return $repay_yestime;
    }


}
